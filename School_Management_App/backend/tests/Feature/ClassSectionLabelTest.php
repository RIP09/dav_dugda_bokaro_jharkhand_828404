<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Deepanshu's report (27 Oct 2026): a section picker showed only "A", "B" … without the class. */
class ClassSectionLabelTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function admin(): self
    {
        $this->actingAs(User::where('email', 'admin@davdugda.in')->firstOrFail(), 'sanctum');
        return $this;
    }

    public function test_section_pickers_name_the_class(): void
    {
        $meta = $this->admin()->getJson('/api/v1/meta/modules/students')->assertOk()->json('data');
        $sec = collect($meta['fields'])->firstWhere('name', 'section_id');
        $this->assertSame('_option', $sec['ref']['label_field']);
        $this->assertSame(['field' => 'class_id', 'key' => 'class_id'], $sec['ref']['depends_on']);

        $rows = $this->getJson('/api/v1/records/sections?per_page=500')->assertOk()->json('data');
        $c6a = collect($rows)->first(fn ($r) => $r['_option'] === 'Class 6 – A');
        $this->assertNotNull($c6a, 'Class 6 – A is offered');
        foreach ($rows as $r) $this->assertMatchesRegularExpression('/^(LKG|UKG|Class \d+) – [A-Z]( \((Science|Commerce)\))?$/u', $r['_option']);

        // v1 endpoints carry the label too
        $this->getJson('/api/v1/sections')->assertOk()->assertJsonPath('data.0.label', 'LKG – A');
        $classes = $this->getJson('/api/v1/classes')->assertOk()->json('data');
        $this->assertSame('Class 8 – A', collect($classes)->firstWhere('name', 'Class 8')['sections'][0]['label']);
    }

    public function test_subjects_students_and_exams_are_unambiguous_in_pickers(): void
    {
        $subj = $this->admin()->getJson('/api/v1/records/subjects?per_page=500')->json('data');
        $this->assertContains('Mathematics (Class 6)', array_column($subj, '_option'));
        $stud = $this->getJson('/api/v1/records/students?per_page=5')->json('data');
        $this->assertMatchesRegularExpression('/^.+ · (LKG|UKG|Class \d+) – [A-Z].* · DAV\d+$/u', $stud[0]['_option']);
        $marks = $this->getJson('/api/v1/meta/modules/marks')->json('data');
        $this->assertSame('section_id', collect($marks['fields'])->firstWhere('name', 'student_id')['ref']['depends_on']['field']);
    }

    public function test_mismatched_class_and_section_is_refused(): void
    {
        $c6 = SchoolClass::where('name', 'Class 6')->first();
        $c7a = Section::where('class_id', SchoolClass::where('name', 'Class 7')->value('id'))->first();
        $this->admin()->postJson('/api/v1/records/students', ['name' => 'Wrong Section', 'class_id' => $c6->id, 'section_id' => $c7a->id])
            ->assertStatus(422)->assertJsonValidationErrors('section_id');
        $this->postJson('/api/v1/records/homework', ['class_id' => $c6->id, 'section_id' => $c7a->id, 'title' => 'x', 'assigned_on' => now()->toDateString()])
            ->assertStatus(422)->assertJsonValidationErrors('section_id');
    }

    public function test_only_science_and_commerce_streams_and_only_for_xi_xii(): void
    {
        $meta = $this->admin()->getJson('/api/v1/meta/modules/sections')->json('data');
        $this->assertSame(['Science', 'Commerce'], array_column(collect($meta['fields'])->firstWhere('name', 'stream')['options'], 'value'));
        $streams = Section::whereNotNull('stream')->pluck('stream')->unique()->sort()->values()->all();
        $this->assertSame(['Commerce', 'Science'], $streams);
        $this->assertSame(0, \DB::table('subjects')->where('name', 'like', '%Humanit%')->orWhere('name', 'like', '%Arts%')->count());

        $c6 = SchoolClass::where('name', 'Class 6')->first();
        $c11 = SchoolClass::where('name', 'Class 11')->first();
        $this->postJson('/api/v1/sections', ['class_id' => $c11->id, 'name' => 'C', 'stream' => 'Humanities'])->assertStatus(422);
        $this->postJson('/api/v1/sections', ['class_id' => $c6->id, 'name' => 'C', 'stream' => 'Science'])->assertStatus(422);
        $this->postJson('/api/v1/sections', ['class_id' => $c11->id, 'name' => 'C', 'stream' => 'Commerce'])->assertCreated()
            ->assertJsonPath('data.label', 'Class 11 – C (Commerce)');
    }

    public function test_excel_import_accepts_old_and_new_section_names(): void
    {
        $this->admin();
        $csv = "Student name,Class,Section\nImp One,Class 8,Class 8 - A\nImp Two,Class 8,Class 8-A\nImp Three,Class 8,Class 8 – A\nImp Four,Class 11,Class 11 – B (Commerce)\nImp Five,Class 8,Class 7 – A\n";
        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('students.csv', $csv);
        $r = $this->post('/api/v1/import/students', ['file' => $file, 'dry_run' => 1], ['Accept' => 'application/json'])->assertOk();
        $this->assertSame(4, $r->json('data.created'), json_encode($r->json('data.errors')));
        $this->assertCount(1, $r->json('data.errors'));   // Class 8 with a Class 7 section is refused
    }

    public function test_class_names_typed_as_numbers_still_read_as_classes(): void
    {
        $this->assertSame('Class 6 – A', Section::labelFor('6', 'A'));
        $this->assertSame('Class VI – B', Section::labelFor('VI', 'B'));
        $this->assertSame('Nursery – A', Section::labelFor('Nursery', 'A'));
        $this->assertSame('Class 11 – A (Science)', Section::labelFor('Class 11', 'A', 'Science'));
    }
}
