<?php

namespace App\Modules\Attendance\Http;

use App\Http\Controllers\Controller;
use App\Modules\Academics\Models\Section;
use App\Modules\Attendance\Models\StudentAttendance;
use App\Modules\Students\Models\Student;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;

/** Monthly register (day-by-day per student) and daily summary per section. Also used by the PDF/Excel exports. */
class AttendanceReportController extends Controller
{
    public static function monthly(int $sectionId, string $month): array
    {
        $start = Carbon::parse($month.'-01'); $end = $start->copy()->endOfMonth();
        $section = Section::with('schoolClass')->findOrFail($sectionId);
        $students = Student::with('user')->where('section_id', $sectionId)->where('status', 'active')->orderBy('roll_no')->get();
        $att = StudentAttendance::where('section_id', $sectionId)->whereBetween('date', [$start->toDateString(), $end->toDateString()])->get()
            ->groupBy('student_id');
        $days = collect(CarbonPeriod::create($start, $end))->map(fn ($d) => ['date' => $d->toDateString(), 'day' => (int) $d->format('j'), 'dow' => $d->format('D'), 'sunday' => $d->isSunday()])->all();
        $code = ['present' => 'P', 'absent' => 'A', 'late' => 'L', 'half_day' => 'H', 'leave' => 'LV'];
        $rows = $students->map(function ($s) use ($att, $code, $days) {
            $mine = ($att[$s->id] ?? collect())->keyBy(fn ($a) => Carbon::parse($a->date)->toDateString());
            $marks = []; $c = ['present' => 0, 'absent' => 0, 'late' => 0, 'half_day' => 0, 'leave' => 0];
            foreach ($days as $d) { $a = $mine[$d['date']] ?? null; $marks[$d['date']] = $a ? $code[$a->status] ?? '?' : ''; if ($a) $c[$a->status] = ($c[$a->status] ?? 0) + 1; }
            $total = array_sum($c);
            return ['student_id' => $s->id, 'roll_no' => $s->roll_no, 'name' => $s->name, 'admission_no' => $s->admission_no, 'days' => $marks,
                'totals' => $c, 'working_days' => $total, 'percentage' => $total ? round(($c['present'] + $c['late'] + 0.5 * $c['half_day']) * 100 / $total, 1) : null];
        })->values()->all();
        return ['section' => ['id' => $section->id, 'label' => ($section->schoolClass?->name ?? '').'-'.$section->name], 'month' => $start->format('Y-m'),
            'month_label' => $start->format('F Y'), 'days' => $days, 'students' => $rows];
    }

    public function report(Request $request)
    {
        $data = $request->validate(['section_id' => 'required|exists:sections,id', 'month' => 'nullable|date_format:Y-m']);
        return $this->ok(self::monthly((int) $data['section_id'], $data['month'] ?? now()->format('Y-m')));
    }

    public function daily(Request $request)
    {
        $date = $request->query('date', now()->toDateString());
        $sections = Section::with('schoolClass')->orderBy('class_id')->orderBy('name')->get();
        $att = StudentAttendance::where('date', $date)->selectRaw('section_id, status, count(*) c')->groupBy('section_id', 'status')->get()->groupBy('section_id');
        $strength = Student::where('status', 'active')->selectRaw('section_id, count(*) c')->groupBy('section_id')->pluck('c', 'section_id');
        $rows = $sections->map(function ($s) use ($att, $strength) {
            $a = ($att[$s->id] ?? collect())->pluck('c', 'status');
            $present = ($a['present'] ?? 0) + ($a['late'] ?? 0);
            return ['section_id' => $s->id, 'label' => ($s->schoolClass?->name ?? '').'-'.$s->name, 'strength' => (int) ($strength[$s->id] ?? 0),
                'marked' => (int) $a->sum(), 'present' => (int) $present, 'absent' => (int) ($a['absent'] ?? 0), 'leave' => (int) ($a['leave'] ?? 0),
                'percentage' => $a->sum() ? round($present * 100 / $a->sum(), 1) : null];
        });
        return $this->ok(['date' => $date, 'sections' => $rows->values(), 'totals' => [
            'strength' => $rows->sum('strength'), 'marked' => $rows->sum('marked'), 'present' => $rows->sum('present'), 'absent' => $rows->sum('absent'),
        ]]);
    }
}
