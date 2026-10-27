<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Audit\Models\AuditLog;
use App\Modules\Exams\Models\Exam;
use App\Modules\Exams\Models\Mark;
use App\Modules\Fees\Models\Invoice;
use App\Modules\Fees\Models\Payment;
use App\Modules\Students\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Editing system + permanent records + audit trail, end to end through the HTTP API. */
class EditLockTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function as(string $email): self
    {
        $this->actingAs(User::where('email', $email)->firstOrFail(), 'sanctum');
        return $this;
    }

    public function test_principal_is_seeded_and_shown_everywhere(): void
    {
        $this->getJson('/api/v1/school')->assertOk()->assertJsonPath('data.principal_name', 'Prasanjeet Kumar Paul');
        $this->postJson('/api/v1/auth/login', ['login' => 'principal@davdugda.in', 'password' => 'principal123', 'app' => 'school'])
            ->assertOk()->assertJsonPath('data.user.name', 'Prasanjeet Kumar Paul');
        $this->postJson('/api/v1/auth/login', ['login' => 'admin@davdugda.in', 'password' => 'admin123', 'app' => 'web'])->assertOk();
        $this->as('principal@davdugda.in')->getJson('/api/v1/dashboard/charts')->assertOk()->assertJsonPath('principal_name', 'Prasanjeet Kumar Paul');
    }

    public function test_edit_writes_audit_trail_with_old_and_new_values(): void
    {
        $s = Student::first();
        $old = $s->father_name;
        $this->as('principal@davdugda.in')->putJson("/api/v1/records/students/{$s->id}", ['father_name' => 'Corrected Name', 'name' => 'Fixed Student Name'])
            ->assertOk()->assertJsonPath('data.father_name', 'Corrected Name')->assertJsonPath('data.name', 'Fixed Student Name');
        $h = $this->getJson("/api/v1/records/students/{$s->id}/history")->assertOk()->json('data');
        $all = collect($h)->pluck('changes')->collapse();
        $this->assertSame($old, $all['father_name']['old']);
        $this->assertSame('Corrected Name', $all['father_name']['new']);
        $this->assertSame('Fixed Student Name', $all['name']['new']);
        $this->assertSame('Prasanjeet Kumar Paul', $h[0]['user_name']);
    }

    public function test_immutable_admission_number_cannot_change(): void
    {
        $s = Student::first();
        $this->as('principal@davdugda.in')->putJson("/api/v1/records/students/{$s->id}", ['admission_no' => 'HACKED1'])
            ->assertStatus(422)->assertJsonValidationErrors('admission_no');
        $this->assertNotSame('HACKED1', $s->fresh()->admission_no);
    }

    public function test_fee_receipt_is_locked_and_only_cancel_reverses_it(): void
    {
        $p = Payment::where('status', 'valid')->first();
        $inv = $p->invoice; $paidBefore = (float) $inv->paid;
        $this->as('accountant@davdugda.in');
        $this->putJson("/api/v1/records/payments/{$p->id}", ['amount' => 1])->assertStatus(423)->assertJsonStructure(['lock_reason']);
        $this->deleteJson("/api/v1/records/payments/{$p->id}")->assertStatus(423);
        $this->postJson("/api/v1/records/payments/{$p->id}/actions/cancel", [])->assertStatus(422); // reason required
        $this->postJson("/api/v1/records/payments/{$p->id}/actions/cancel", ['reason' => 'Entered twice by mistake'])->assertOk()
            ->assertJsonPath('data.status', 'cancelled')->assertJsonPath('data._locked', true);
        $this->assertEqualsWithDelta($paidBefore - (float) $p->amount, (float) $inv->fresh()->paid, 0.01);
        $this->assertTrue(AuditLog::where('action', 'cancel')->where('record_id', $p->id)->exists());
        // even model code cannot silently edit it
        $this->expectException(\App\Core\Exceptions\RecordLockedException::class);
        $p->fresh()->update(['amount' => 5]);
    }

    public function test_locked_results_freeze_marks(): void
    {
        $exam = Exam::where('results_locked', false)->whereHas('marks')->firstOrFail();
        $mark = Mark::where('exam_id', $exam->id)->first();
        $this->as('principal@davdugda.in');
        $this->putJson("/api/v1/records/marks/{$mark->id}", ['marks_obtained' => 10])->assertOk();
        $this->postJson("/api/v1/records/exams/{$exam->id}/actions/lock_results")->assertOk();
        $this->putJson("/api/v1/records/marks/{$mark->id}", ['marks_obtained' => 11])->assertStatus(423);
        $this->putJson("/api/v1/records/exams/{$exam->id}", ['name' => 'Renamed'])->assertStatus(423);
        $this->getJson("/api/v1/records/marks/{$mark->id}")->assertJsonPath('data._locked', true)->assertJsonPath('data._can_edit', false);
    }

    public function test_certificate_issue_locks_it_and_cancel_keeps_record(): void
    {
        $s = Student::first();
        $this->as('principal@davdugda.in');
        $id = $this->postJson('/api/v1/records/certificates', ['type' => 'bonafide', 'student_id' => $s->id, 'purpose' => 'Scholarship'])->assertCreated()->json('data.id');
        $this->putJson("/api/v1/records/certificates/{$id}", ['purpose' => 'Bank account'])->assertOk();
        $this->postJson("/api/v1/records/certificates/{$id}/actions/issue")->assertOk();
        $this->putJson("/api/v1/records/certificates/{$id}", ['purpose' => 'Changed'])->assertStatus(423);
        $this->get("/api/v1/pdf/certificate/{$id}")->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->postJson("/api/v1/records/certificates/{$id}/actions/cancel", ['reason' => 'Wrong purpose'])->assertOk()->assertJsonPath('data.status', 'cancelled');
        $this->deleteJson("/api/v1/records/certificates/{$id}")->assertStatus(423);
    }

    public function test_audit_log_itself_cannot_be_edited(): void
    {
        $this->as('principal@davdugda.in')->putJson('/api/v1/records/students/'.Student::first()->id, ['house' => 'Blue'])->assertOk();
        $a = AuditLog::latest('id')->first();
        $this->putJson("/api/v1/records/audit/{$a->id}", ['user_name' => 'x'])->assertStatus(423);
        $this->expectException(\App\Core\Exceptions\RecordLockedException::class);
        $a->delete();
    }

    public function test_role_rules_on_generic_records(): void
    {
        $other = Student::whereNotIn('parent_id', [User::where('email', 'parent@davdugda.in')->value('id')])->first();
        $this->as('parent@davdugda.in')->getJson("/api/v1/records/students/{$other->id}")->assertNotFound();
        $own = Invoice::whereIn('student_id', User::where('email', 'parent@davdugda.in')->first()->ownStudentIds())->first();
        $this->putJson('/api/v1/records/invoices/'.$own->id, ['amount' => 1])->assertForbidden();
        $this->as('teacher@davdugda.in')->postJson('/api/v1/records/fee_types', ['name' => 'X'])->assertForbidden();
        $this->as('student@davdugda.in')->getJson('/api/v1/records/audit')->assertForbidden();
        $this->as('accountant@davdugda.in')->getJson('/api/v1/records/transactions')->assertOk();
    }

    public function test_pdfs_exports_import_and_backup(): void
    {
        $this->as('principal@davdugda.in');
        foreach (['receipt/'.Payment::first()->id, 'report-card/'.Mark::first()->student_id, 'id-card/'.Student::first()->id, 'fee-statement/'.Student::first()->id] as $doc) {
            $this->get('/api/v1/pdf/'.$doc)->assertOk()->assertHeader('content-type', 'application/pdf');
        }
        $this->get('/api/v1/export/students?format=csv')->assertOk();
        $csv = "Visitor name,Mobile,Purpose,To meet\nRam Kumar,9431999999,Meeting,Principal\n,,,\nNo Purpose Person,1,,\n";
        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('visitors.csv', $csv);
        $this->post('/api/v1/import/visitors', ['file' => $file, 'dry_run' => 1], ['Accept' => 'application/json'])->assertOk()->assertJsonPath('data.created', 1);
        $this->assertSame(0, \App\Modules\FrontOffice\Models\Visitor::where('name', 'Ram Kumar')->count());
        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('visitors.csv', $csv);
        $r = $this->post('/api/v1/import/visitors', ['file' => $file], ['Accept' => 'application/json'])->assertOk();
        $this->assertSame(1, \App\Modules\FrontOffice\Models\Visitor::where('name', 'Ram Kumar')->count());
        $this->assertCount(1, $r->json('data.errors'));
        $this->get('/api/v1/backup/download')->assertOk();
    }

    public function test_excel_template_round_trip_import(): void
    {
        $this->as('principal@davdugda.in');
        $resp = $this->get('/api/v1/import/books/template')->assertOk();
        $tmp = tempnam(sys_get_temp_dir(), 'tpl').'.xlsx';
        copy($resp->baseResponse->getFile()->getPathname(), $tmp);
        $book = \PhpOffice\PhpSpreadsheet\IOFactory::load($tmp);
        $this->assertSame('How to fill', $book->getSheet(1)->getTitle());
        $sheet = $book->getSheet(0);
        $headers = $sheet->rangeToArray('A1:'.$sheet->getHighestColumn().'1')[0];
        $row = [];
        foreach ($headers as $h) $row[] = match (true) {
            stripos($h, 'title') !== false => 'Godan', stripos($h, 'author') !== false => 'Munshi Premchand',
            stripos($h, 'isbn') !== false => '9788126402417', stripos($h, 'cop') !== false => 3, default => null,
        };
        $sheet->fromArray($row, null, 'A2');
        \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($book, 'Xlsx')->save($tmp);
        $file = new \Illuminate\Http\UploadedFile($tmp, 'books.xlsx', null, null, true);
        $this->post('/api/v1/import/books', ['file' => $file], ['Accept' => 'application/json'])->assertOk()->assertJsonPath('data.created', 1);
        $this->assertDatabaseHas('books', ['title' => 'Godan', 'isbn' => '9788126402417']);
        // same ISBN again → update, not duplicate
        $file = new \Illuminate\Http\UploadedFile($tmp, 'books.xlsx', null, null, true);
        $this->post('/api/v1/import/books', ['file' => $file], ['Accept' => 'application/json'])->assertOk()->assertJsonPath('data.updated', 1);
        $this->assertSame(1, \App\Modules\Library\Models\Book::where('isbn', '9788126402417')->count());
    }
}
