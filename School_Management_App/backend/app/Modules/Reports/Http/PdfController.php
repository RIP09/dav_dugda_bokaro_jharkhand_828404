<?php

namespace App\Modules\Reports\Http;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Academics\Models\Section;
use App\Modules\Attendance\Http\AttendanceReportController;
use App\Modules\Attendance\Models\StudentAttendance;
use App\Modules\Certificates\Models\Certificate;
use App\Modules\Exams\Http\ExamController;
use App\Modules\Exams\Models\Exam;
use App\Modules\Exams\Models\ExamSchedule;
use App\Modules\Fees\Models\Invoice;
use App\Modules\Fees\Models\Payment;
use App\Modules\Payroll\Models\Payslip;
use App\Modules\Payroll\Models\SalaryStructure;
use App\Modules\Settings\Http\SettingsController;
use App\Modules\Students\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

/** Printable PDFs: receipts, invoices, report cards, ID cards, admit cards, certificates, salary slips, attendance. */
class PdfController extends Controller
{
    public function show(Request $request, string $doc, int $id = 0)
    {
        $u = $request->user();
        return match ($doc) {
            'receipt' => $this->receipt($u, $id),
            'invoice' => $this->invoice($u, $id),
            'fee-statement' => $this->feeStatement($u, $id),
            'report-card' => $this->reportCard($request, $u, $id),
            'id-card' => $this->idCards($u, Student::with('user', 'schoolClass', 'section')->whereKey($id)->get()),
            'id-cards' => $this->idCards($u, Student::with('user', 'schoolClass', 'section')->where('section_id', $request->query('section_id', $id))->where('status', 'active')->orderBy('roll_no')->get()),
            'staff-id-card' => $this->staffIdCard($u, $id),
            'admit-card' => $this->admitCards($request, $u, Student::whereKey($id)->get(), (int) $request->query('exam_id')),
            'admit-cards' => $this->admitCards($request, $u, Student::where('section_id', $request->query('section_id'))->where('status', 'active')->orderBy('roll_no')->get(), (int) ($request->query('exam_id') ?: $id)),
            'certificate' => $this->certificate($u, $id),
            'salary-slip' => $this->salarySlip($u, $id),
            'attendance' => $this->attendance($request, $u, $id),
            default => abort(404, 'Unknown document'),
        };
    }

    public static function school(): array
    {
        $s = SettingsController::profile();
        $s['logo_path'] = ! empty($s['logo']) && is_file(storage_path('app/public/'.$s['logo'])) ? storage_path('app/public/'.$s['logo']) : null;
        return $s;
    }

    public static function render(string $view, array $data, string $filename, string $paper = 'a4', string $orientation = 'portrait')
    {
        $pdf = Pdf::loadView('pdf.'.$view, $data + ['school' => self::school()])->setPaper($paper, $orientation)
            ->setOption(['isFontSubsettingEnabled' => true, 'isRemoteEnabled' => false, 'defaultFont' => 'DejaVu Sans', 'chroot' => [storage_path('app/public'), public_path()]]);
        return $pdf->stream($filename, ['Attachment' => false]);
    }

    private function canStudent(User $u, ?Student $s): void
    {
        abort_unless($s && ($u->isAdmin() || $u->isStaff() || in_array($s->id, $u->ownStudentIds(), true)), 403, 'Not allowed');
    }

    private function photo(?string $p): ?string
    {
        return $p && is_file(storage_path('app/public/'.$p)) ? storage_path('app/public/'.$p) : null;
    }

    public static function words(float $n): string
    {
        $n = round($n, 2); $r = (int) floor($n); $p = (int) round(($n - $r) * 100);
        $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
        $two = fn ($x) => $x < 20 ? $ones[$x] : trim($tens[intdiv($x, 10)].' '.$ones[$x % 10]);
        $three = fn ($x) => trim(($x >= 100 ? $ones[intdiv($x, 100)].' Hundred ' : '').$two($x % 100));
        if ($r === 0) $w = 'Zero';
        else {
            $parts = []; $cr = intdiv($r, 10000000); $r %= 10000000; $la = intdiv($r, 100000); $r %= 100000; $th = intdiv($r, 1000); $r %= 1000;
            if ($cr) $parts[] = $three($cr).' Crore'; if ($la) $parts[] = $two($la).' Lakh'; if ($th) $parts[] = $two($th).' Thousand'; if ($r) $parts[] = $three($r);
            $w = implode(' ', $parts);
        }
        return 'Rupees '.$w.($p ? ' and '.$two($p).' Paise' : '').' Only';
    }

    private function receipt(User $u, int $id)
    {
        $p = Payment::with('invoice.student.user', 'invoice.student.schoolClass', 'invoice.student.section', 'receiver')->findOrFail($id);
        $this->canStudent($u, $p->invoice?->student);
        return self::render('receipt', ['p' => $p, 'inv' => $p->invoice, 'st' => $p->invoice?->student, 'words' => self::words((float) $p->amount)], 'Receipt_'.$p->receipt_no.'.pdf');
    }

    private function invoice(User $u, int $id)
    {
        $inv = Invoice::with('student.user', 'student.schoolClass', 'student.section')->findOrFail($id);
        $this->canStudent($u, $inv->student);
        return self::render('invoice', ['inv' => $inv], 'Invoice_'.$inv->invoice_no.'.pdf');
    }

    private function feeStatement(User $u, int $id)
    {
        $st = Student::with('user', 'schoolClass', 'section')->findOrFail($id);
        $this->canStudent($u, $st);
        $invoices = Invoice::where('student_id', $id)->orderBy('due_date')->get();
        $payments = Payment::whereIn('invoice_id', $invoices->pluck('id'))->orderBy('paid_on')->get();
        return self::render('fee-statement', compact('st', 'invoices', 'payments'), 'Fee_statement_'.$st->admission_no.'.pdf');
    }

    private function reportCard(Request $request, User $u, int $id)
    {
        $st = Student::with('user')->findOrFail($id);
        $this->canStudent($u, $st);
        $examIds = $request->filled('exam_id') ? [(int) $request->query('exam_id')]
            : Exam::whereHas('marks', fn ($m) => $m->where('student_id', $id))->when(! $u->isAdmin() && ! $u->isStaff(), fn ($q) => $q->where('is_published', true))->orderBy('start_date')->pluck('id')->all();
        abort_if(! $examIds, 404, 'No results yet for this student.');
        $cards = [];
        foreach ($examIds as $eid) {
            $req = new Request(['exam_id' => $eid]); $req->setUserResolver(fn () => $u);
            $resp = app(ExamController::class)->reportCard($req, $st);
            if ($resp->getStatusCode() >= 400) { if (count($examIds) === 1) abort($resp->getStatusCode(), $resp->getData(true)['message'] ?? 'Not available'); continue; }
            $rc = $resp->getData(true)['data'];
            $att = StudentAttendance::where('student_id', $id)->selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status');
            $rc['attendance'] = $att->sum() ? (($att['present'] ?? 0) + ($att['late'] ?? 0)).' / '.$att->sum().' days' : null;
            $rc['photo'] = $this->photo($st->user?->photo);
            $cards[] = $rc;
        }
        abort_if(! $cards, 404, 'Results not published yet.');
        return self::render('report-card', ['cards' => $cards], 'Report_card_'.$st->admission_no.'.pdf');
    }

    private function idCards(User $u, $students)
    {
        abort_if($students->isEmpty(), 404, 'No students');
        foreach ($students as $s) $this->canStudent($u, $s);
        $people = $students->map(fn ($s) => ['name' => $s->name, 'kind' => 'Student', 'photo' => $this->photo($s->user?->photo), 'lines' => array_filter([
            'Class' => $s->class_label, 'Roll' => $s->roll_no, 'Adm. No.' => $s->admission_no, 'Father' => $s->father_name,
            'DOB' => $s->user?->dob?->format('d-m-Y'), 'Blood' => $s->blood_group, 'Phone' => $s->guardian_phone,
        ])]);
        return self::render('id-cards', ['people' => $people], 'ID_cards.pdf');
    }

    private function staffIdCard(User $u, int $id)
    {
        abort_unless($u->isAdmin() || $u->isStaff(), 403);
        $s = User::with('staffProfile')->findOrFail($id);
        $people = collect([['name' => $s->name, 'kind' => 'Staff', 'photo' => $this->photo($s->photo), 'lines' => array_filter([
            'Designation' => $s->staffProfile?->designation ?? ucfirst($s->role), 'Emp. ID' => $s->staffProfile?->employee_id, 'Dept.' => $s->staffProfile?->department, 'Phone' => $s->phone,
        ])]]);
        return self::render('id-cards', ['people' => $people], 'Staff_ID_'.$s->id.'.pdf');
    }

    private function admitCards(Request $request, User $u, $students, int $examId)
    {
        $exam = $examId ? Exam::findOrFail($examId) : Exam::where('start_date', '>=', now()->subMonths(1)->toDateString())->orderBy('start_date')->first() ?? Exam::latest('start_date')->firstOrFail();
        abort_if($students->isEmpty(), 404, 'No students');
        $cards = [];
        foreach ($students as $s) {
            $this->canStudent($u, $s);
            $cards[] = ['student' => $s->load('user', 'schoolClass', 'section'), 'photo' => $this->photo($s->user?->photo),
                'schedule' => ExamSchedule::with('subject')->where('exam_id', $exam->id)->where('class_id', $s->class_id)->orderBy('exam_date')->get()];
        }
        return self::render('admit-cards', ['cards' => $cards, 'exam' => $exam], 'Admit_cards.pdf');
    }

    private function certificate(User $u, int $id)
    {
        $c = Certificate::with('student.user', 'student.schoolClass', 'student.section')->findOrFail($id);
        $this->canStudent($u, $c->student);
        if (! $u->isAdmin() && ! $u->isStaff()) abort_unless($c->status === 'issued', 403);
        return self::render('certificate', ['c' => $c, 'title' => Certificate::TYPES[$c->type] ?? 'Certificate'], str_replace('/', '-', $c->certificate_no).'.pdf');
    }

    private function salarySlip(User $u, int $id)
    {
        $p = Payslip::with('user.staffProfile')->findOrFail($id);
        abort_unless(in_array($u->role, ['admin', 'accountant'], true) || $p->user_id === $u->id, 403);
        return self::render('salary-slip', ['p' => $p, 'ss' => SalaryStructure::where('user_id', $p->user_id)->first(), 'words' => self::words((float) $p->net)], 'Salary_slip_'.$p->payslip_no.'.pdf');
    }

    private function attendance(Request $request, User $u, int $id)
    {
        abort_unless($u->isAdmin() || $u->isStaff(), 403);
        $r = AttendanceReportController::monthly($id ?: (int) $request->query('section_id'), $request->query('month', now()->format('Y-m')));
        return self::render('attendance', ['r' => $r], 'Attendance_'.$r['section']['label'].'_'.$r['month'].'.pdf', 'a4', 'landscape');
    }
}
