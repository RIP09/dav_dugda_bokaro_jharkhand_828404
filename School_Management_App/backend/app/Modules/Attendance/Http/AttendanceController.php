<?php

namespace App\Modules\Attendance\Http;

use App\Http\Controllers\Controller;
use App\Modules\Academics\Models\Section;
use App\Modules\Attendance\Models\StaffAttendance;
use App\Modules\Students\Models\Student;
use App\Modules\Attendance\Models\StudentAttendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    private const STATUSES = 'present,absent,late,half_day,leave';

    /** GET /attendance/students?section_id=&date=  — the sheet a teacher fills in */
    public function studentSheet(Request $request)
    {
        $request->validate(['section_id' => 'required|exists:sections,id', 'date' => 'nullable|date']);
        $date = $request->query('date', now()->toDateString());
        $this->ensureTeaches($request->user(), (int) $request->section_id);
        $students = Student::with('user')->where('section_id', $request->section_id)->where('status', 'active')
            ->orderBy('roll_no')->get();
        $existing = StudentAttendance::where('section_id', $request->section_id)->whereDate('date', $date)
            ->get()->keyBy('student_id');
        $rows = $students->map(fn ($s) => [
            'student_id' => $s->id,
            'name' => $s->name,
            'roll_no' => $s->roll_no,
            'admission_no' => $s->admission_no,
            'status' => $existing[$s->id]->status ?? null,
            'remark' => $existing[$s->id]->remark ?? null,
        ]);
        return $this->ok(['date' => $date, 'section_id' => (int) $request->section_id, 'already_marked' => $existing->isNotEmpty(), 'rows' => $rows]);
    }

    /** POST /attendance/students { section_id, date, records: [{student_id, status, remark}] } */
    public function storeStudents(Request $request)
    {
        $data = $request->validate([
            'section_id' => 'required|exists:sections,id',
            'date' => 'required|date|before_or_equal:today',
            'records' => 'required|array|min:1',
            'records.*.student_id' => 'required|exists:students,id',
            'records.*.status' => 'required|in:'.self::STATUSES,
            'records.*.remark' => 'nullable|string|max:200',
        ]);
        $this->ensureTeaches($request->user(), (int) $data['section_id']);
        $section = Section::findOrFail($data['section_id']);
        DB::transaction(function () use ($data, $section, $request) {
            foreach ($data['records'] as $r) {
                StudentAttendance::updateOrCreate(
                    ['student_id' => $r['student_id'], 'date' => $data['date']],
                    ['class_id' => $section->class_id, 'section_id' => $section->id, 'status' => $r['status'],
                        'remark' => $r['remark'] ?? null, 'marked_by' => $request->user()->id]
                );
            }
        });
        $c = collect($data['records'])->countBy('status');
        return $this->ok($c, sprintf('Attendance saved: %d present, %d absent', ($c['present'] ?? 0) + ($c['late'] ?? 0), $c['absent'] ?? 0));
    }

    /** GET /attendance/students/{student}?month=YYYY-MM — calendar + summary for one student */
    public function studentReport(Request $request, Student $student)
    {
        $this->ensureStudent($request->user(), $student);
        return $this->ok($this->report(StudentAttendance::where('student_id', $student->id), $request->query('month')));
    }

    /** GET /attendance/my?month=&student_id= — parent/student shortcut, staff = own staff attendance */
    public function my(Request $request)
    {
        $user = $request->user();
        if ($user->isStaff() || $user->isAdmin()) {
            return $this->ok($this->report(StaffAttendance::where('user_id', $user->id), $request->query('month')));
        }
        $student = $this->resolveOwnStudent($user, $request->query('student_id'));
        if (! $student) return $this->fail('No student is linked to this account.', 404);
        $r = $this->report(StudentAttendance::where('student_id', $student->id), $request->query('month'));
        $r['student'] = ['id' => $student->id, 'name' => $student->name, 'class_label' => $student->class_label];
        return $this->ok($r);
    }

    /** GET /attendance/class-summary?date= (admin) — today's attendance per section */
    public function classSummary(Request $request)
    {
        $date = $request->query('date', now()->toDateString());
        $sections = Section::with('schoolClass')->withCount(['students' => fn ($q) => $q->where('status', 'active')])->get();
        $marks = StudentAttendance::whereDate('date', $date)->selectRaw('section_id, status, count(*) c')
            ->groupBy('section_id', 'status')->get()->groupBy('section_id');
        return $this->ok($sections->map(function ($s) use ($marks) {
            $m = ($marks[$s->id] ?? collect())->pluck('c', 'status');
            return ['section_id' => $s->id, 'class_label' => $s->schoolClass->name.' - '.$s->name,
                'strength' => $s->students_count, 'present' => (int) (($m['present'] ?? 0) + ($m['late'] ?? 0)),
                'absent' => (int) ($m['absent'] ?? 0), 'marked' => $m->isNotEmpty()];
        })->values());
    }

    // ---- Staff attendance (admin) --------------------------------------------------------------
    public function staffSheet(Request $request)
    {
        $date = $request->query('date', now()->toDateString());
        $staff = User::whereIn('role', User::STAFF_ROLES)->where('is_active', true)->with('staffProfile')->orderBy('name')->get();
        $existing = StaffAttendance::whereDate('date', $date)->get()->keyBy('user_id');
        return $this->ok(['date' => $date, 'already_marked' => $existing->isNotEmpty(), 'rows' => $staff->map(fn ($u) => [
            'user_id' => $u->id, 'name' => $u->name, 'designation' => $u->staffProfile?->designation ?? ucfirst($u->role),
            'status' => $existing[$u->id]->status ?? null, 'in_time' => $existing[$u->id]->in_time ?? null,
            'out_time' => $existing[$u->id]->out_time ?? null,
        ])]);
    }

    public function storeStaff(Request $request)
    {
        $data = $request->validate([
            'date' => 'required|date|before_or_equal:today',
            'records' => 'required|array|min:1',
            'records.*.user_id' => 'required|exists:users,id',
            'records.*.status' => 'required|in:'.self::STATUSES,
            'records.*.in_time' => 'nullable|string|max:8',
            'records.*.out_time' => 'nullable|string|max:8',
        ]);
        foreach ($data['records'] as $r) {
            StaffAttendance::updateOrCreate(['user_id' => $r['user_id'], 'date' => $data['date']], [
                'status' => $r['status'], 'in_time' => $r['in_time'] ?? null, 'out_time' => $r['out_time'] ?? null,
                'marked_by' => $request->user()->id,
            ]);
        }
        return $this->ok(null, 'Staff attendance saved');
    }

    private function report($query, ?string $month): array
    {
        $start = $month ? Carbon::createFromFormat('Y-m', $month)->startOfMonth() : now()->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $days = (clone $query)->whereBetween('date', [$start->toDateString(), $end->toDateString()])->orderBy('date')->get(['date', 'status', 'remark']);
        $all = (clone $query)->selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status');
        $total = (int) $all->sum();
        $present = (int) (($all['present'] ?? 0) + ($all['late'] ?? 0));
        return [
            'month' => $start->format('Y-m'),
            'present' => $present,
            'absent' => (int) ($all['absent'] ?? 0),
            'late' => (int) ($all['late'] ?? 0),
            'half_day' => (int) ($all['half_day'] ?? 0),
            'leave' => (int) ($all['leave'] ?? 0),
            'total' => $total,
            'percentage' => $total ? round(($present + 0.5 * ($all['half_day'] ?? 0)) * 100 / $total, 1) : 0,
            'days' => $days,
        ];
    }

    private function ensureTeaches(User $user, int $sectionId): void
    {
        if ($user->isAdmin()) return;
        abort_unless($user->role === User::TEACHER && in_array($sectionId, $user->teachingSectionIds(), true), 403,
            'You can only take attendance for your own classes.');
    }
}
