<?php

namespace App\Modules\Dashboard\Http;

use App\Http\Controllers\Controller;
use App\Modules\Library\Models\BookIssue;
use App\Modules\Communication\Models\Event;
use App\Modules\Homework\Models\Homework;
use App\Modules\Homework\Models\HomeworkSubmission;
use App\Modules\Fees\Models\Invoice;
use App\Modules\Leave\Models\LeaveRequest;
use App\Modules\Communication\Models\Message;
use App\Modules\Communication\Models\Notice;
use App\Modules\Fees\Models\Payment;
use App\Modules\Settings\Models\Setting;
use App\Modules\Attendance\Models\StaffAttendance;
use App\Modules\Students\Models\Student;
use App\Modules\Attendance\Models\StudentAttendance;
use App\Modules\Timetable\Models\TimetableEntry;
use App\Models\User;
use Illuminate\Http\Request;

/** GET /dashboard — one call that gives each app its home screen. */
class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $u = $request->user();
        $today = now()->toDateString();
        $hour = (int) now()->format('G');
        $data = [
            'greeting' => ($hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening')).', '.explode(' ', $u->name)[0],
            'role' => $u->role,
            'school_name' => Setting::get('school_name', 'DAV Public School'),
            'date' => now()->format('l, d M Y'),
            'stats' => [],
            'notices' => Notice::visibleTo($u)->orderByDesc('is_pinned')->latest('publish_date')->limit(3)->get(),
            'events' => Event::where('start_date', '>=', $today)->orderBy('start_date')->limit(3)->get(),
            'today_classes' => [],
            'children' => [],
            'alerts' => [],
            'unread_messages' => Message::where('receiver_id', $u->id)->whereNull('read_at')->count(),
        ];
        $dow = (int) now()->format('N');

        if ($u->isAdmin()) {
            $present = StudentAttendance::where('date', $today)->whereIn('status', ['present', 'late'])->count();
            $marked = StudentAttendance::where('date', $today)->count();
            $data['stats'] = [
                ['label' => 'Students', 'value' => (string) Student::where('status', 'active')->count(), 'icon' => 'students'],
                ['label' => 'Staff', 'value' => (string) User::whereIn('role', User::STAFF_ROLES)->count(), 'icon' => 'staff'],
                ['label' => 'Parents', 'value' => (string) User::where('role', User::PARENT)->count(), 'icon' => 'parents'],
                ['label' => 'Present today', 'value' => $marked ? "$present / $marked" : 'Not marked', 'icon' => 'attendance'],
                ['label' => 'Fees collected (month)', 'value' => '₹'.number_format(Payment::where('paid_on', '>=', now()->startOfMonth()->toDateString())->sum('amount')), 'icon' => 'fees'],
                ['label' => 'Fees due', 'value' => '₹'.number_format(Invoice::where('status', '!=', 'paid')->get()->sum('balance')), 'icon' => 'due'],
            ];
            $pending = LeaveRequest::where('status', 'pending')->count();
            if ($pending) $data['alerts'][] = "$pending leave request(s) waiting for approval";
            $overdue = BookIssue::whereNull('returned_on')->where('due_on', '<', $today)->count();
            if ($overdue) $data['alerts'][] = "$overdue library book(s) overdue";
        } elseif ($u->isStaff()) {
            $classes = TimetableEntry::with(['subject', 'schoolClass', 'section'])->where('teacher_id', $u->id)->where('day', $dow)->orderBy('start_time')->get();
            $data['today_classes'] = $classes;
            $myAtt = StaffAttendance::where('user_id', $u->id)->where('date', $today)->value('status');
            $data['stats'] = [
                ['label' => 'Classes today', 'value' => (string) $classes->count(), 'icon' => 'timetable'],
                ['label' => 'My attendance today', 'value' => $myAtt ? ucfirst(str_replace('_', ' ', $myAtt)) : 'Not marked', 'icon' => 'attendance'],
            ];
            if ($u->role === User::TEACHER) {
                $data['stats'][] = ['label' => 'Homework posted', 'value' => (string) Homework::where('teacher_id', $u->id)->count(), 'icon' => 'homework'];
                $data['stats'][] = ['label' => 'Leave requests to review', 'value' => (string) LeaveRequest::where('status', 'pending')
                    ->whereHas('student', fn ($s) => $s->whereIn('section_id', $u->teachingSectionIds()))->count(), 'icon' => 'leave'];
                $unmarked = collect($u->teachingSectionIds())->filter(fn ($sid) => ! StudentAttendance::where('section_id', $sid)->where('date', $today)->exists())->count();
                if ($unmarked && $dow < 7) $data['alerts'][] = "Attendance not taken yet for $unmarked of your class(es) today";
            }
            if ($u->role === User::ACCOUNTANT) {
                $data['stats'][] = ['label' => 'Collected today', 'value' => '₹'.number_format(Payment::where('paid_on', '>=', $today)->sum('amount')), 'icon' => 'fees'];
                $data['stats'][] = ['label' => 'Unpaid invoices', 'value' => (string) Invoice::where('status', '!=', 'paid')->count(), 'icon' => 'due'];
            }
            if ($u->role === User::LIBRARIAN) {
                $data['stats'][] = ['label' => 'Books issued', 'value' => (string) BookIssue::whereNull('returned_on')->count(), 'icon' => 'library'];
                $data['stats'][] = ['label' => 'Overdue', 'value' => (string) BookIssue::whereNull('returned_on')->where('due_on', '<', $today)->count(), 'icon' => 'due'];
            }
        } else {
            $students = Student::with(['user', 'schoolClass', 'section'])->whereIn('id', $u->ownStudentIds())->get();
            $data['children'] = $students;
            $s = $this->resolveOwnStudent($u, $request->query('student_id'));
            if ($s) {
                $att = StudentAttendance::where('student_id', $s->id)->selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status');
                $tot = $att->sum();
                $pct = $tot ? round((($att['present'] ?? 0) + ($att['late'] ?? 0)) * 100 / $tot) : 0;
                $due = Invoice::where('student_id', $s->id)->where('status', '!=', 'paid')->get()->sum('balance');
                $pendingHw = Homework::where('class_id', $s->class_id)->where(fn ($w) => $w->whereNull('section_id')->orWhere('section_id', $s->section_id))
                    ->where('due_date', '>=', $today)
                    ->whereNotIn('id', HomeworkSubmission::where('student_id', $s->id)->pluck('homework_id'))->count();
                $data['stats'] = [
                    ['label' => 'Attendance', 'value' => "$pct%", 'icon' => 'attendance'],
                    ['label' => 'Fees due', 'value' => '₹'.number_format($due), 'icon' => 'fees'],
                    ['label' => 'Pending homework', 'value' => (string) $pendingHw, 'icon' => 'homework'],
                    ['label' => 'Today', 'value' => ucfirst(str_replace('_', ' ', StudentAttendance::where('student_id', $s->id)->where('date', $today)->value('status') ?? 'not marked')), 'icon' => 'today'],
                ];
                $data['today_classes'] = TimetableEntry::with(['subject', 'teacher', 'schoolClass', 'section'])->where('section_id', $s->section_id)->where('day', $dow)->orderBy('start_time')->get();
                if ($due > 0) $data['alerts'][] = 'Fee of ₹'.number_format($due).' is due for '.$s->name;
                if ($pendingHw) $data['alerts'][] = "$pendingHw homework pending for ".$s->name;
            }
        }
        return $this->ok($data);
    }
}
