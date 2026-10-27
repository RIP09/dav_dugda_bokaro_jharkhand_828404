<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Exam;
use App\Models\ExamSchedule;
use App\Models\Grade;
use App\Models\Mark;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Exams, grade scale, exam schedules, marks entry and report cards (hrshadhin exams/grades/marks/results). */
class ExamController extends Controller
{
    public function index(Request $request)
    {
        $q = Exam::orderByDesc('start_date');
        if (in_array($request->user()->role, [User::PARENT, User::STUDENT], true)) $q->where('is_published', true);
        return $this->ok($q->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:80',
            'term' => 'nullable|string|max:30',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);
        $data['academic_year_id'] = AcademicYear::current()?->id;
        return $this->created(Exam::create($data), 'Exam created');
    }

    public function update(Request $request, Exam $exam)
    {
        $exam->update($request->validate([
            'name' => 'sometimes|string|max:80', 'term' => 'sometimes|nullable|string|max:30',
            'start_date' => 'sometimes|nullable|date', 'end_date' => 'sometimes|nullable|date',
            'is_published' => 'sometimes|boolean',
        ]));
        return $this->ok($exam, 'Exam updated');
    }

    /** POST /exams/{exam}/publish — toggles result publication for parents & students */
    public function publish(Exam $exam)
    {
        $exam->update(['is_published' => ! $exam->is_published]);
        return $this->ok($exam, $exam->is_published ? 'Results published' : 'Results hidden');
    }

    public function destroy(Exam $exam)
    {
        $exam->delete();
        return $this->ok(null, 'Exam deleted');
    }

    // ---- Schedules -----------------------------------------------------------------------------
    public function schedules(Request $request, Exam $exam)
    {
        $q = $exam->schedules()->with('subject')->orderBy('exam_date');
        if ($request->filled('class_id')) $q->where('class_id', $request->class_id);
        $user = $request->user();
        if (in_array($user->role, [User::PARENT, User::STUDENT], true)) {
            $s = $this->resolveOwnStudent($user, $request->query('student_id'));
            $q->where('class_id', $s?->class_id ?? 0);
        }
        return $this->ok($q->get());
    }

    public function storeSchedule(Request $request, Exam $exam)
    {
        $data = $request->validate([
            'class_id' => 'required|exists:school_classes,id',
            'subject_id' => 'required|exists:subjects,id',
            'exam_date' => 'required|date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'max_marks' => 'nullable|integer|min:1',
            'pass_marks' => 'nullable|integer|min:0',
        ]);
        return $this->created($exam->schedules()->create($data)->load('subject'), 'Exam paper scheduled');
    }

    // ---- Grades --------------------------------------------------------------------------------
    public function grades()
    {
        return $this->ok(Grade::orderByDesc('min_percent')->get());
    }

    public function storeGrade(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:5', 'min_percent' => 'required|numeric|between:0,100',
            'max_percent' => 'required|numeric|between:0,100', 'point' => 'nullable|numeric', 'remark' => 'nullable|string|max:50',
        ]);
        return $this->created(Grade::create($data), 'Grade added');
    }

    public function destroyGrade(Grade $grade)
    {
        $grade->delete();
        return $this->ok(null, 'Grade deleted');
    }

    // ---- Marks ---------------------------------------------------------------------------------
    /** GET /marks?exam_id=&section_id=&subject_id= — marks sheet for entry */
    public function marksSheet(Request $request)
    {
        $request->validate(['exam_id' => 'required|exists:exams,id', 'section_id' => 'required|exists:sections,id', 'subject_id' => 'required|exists:subjects,id']);
        $section = Section::findOrFail($request->section_id);
        $schedule = ExamSchedule::where('exam_id', $request->exam_id)->where('class_id', $section->class_id)
            ->where('subject_id', $request->subject_id)->first();
        $students = Student::with('user')->where('section_id', $section->id)->where('status', 'active')->orderBy('roll_no')->get();
        $marks = Mark::where('exam_id', $request->exam_id)->where('subject_id', $request->subject_id)
            ->whereIn('student_id', $students->pluck('id'))->get()->keyBy('student_id');
        return $this->ok([
            'max_marks' => $schedule?->max_marks ?? ($marks->first()->max_marks ?? 100),
            'pass_marks' => $schedule?->pass_marks ?? 33,
            'rows' => $students->map(fn ($s) => [
                'student_id' => $s->id, 'name' => $s->name, 'roll_no' => $s->roll_no,
                'marks_obtained' => $marks[$s->id]->marks_obtained ?? null,
                'is_absent' => (bool) ($marks[$s->id]->is_absent ?? false),
                'grade' => $marks[$s->id]->grade ?? null,
                'remark' => $marks[$s->id]->remark ?? null,
            ]),
        ]);
    }

    /** POST /marks { exam_id, section_id, subject_id, max_marks, records:[{student_id, marks_obtained, is_absent, remark}] } */
    public function storeMarks(Request $request)
    {
        $data = $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'section_id' => 'required|exists:sections,id',
            'subject_id' => 'required|exists:subjects,id',
            'max_marks' => 'required|integer|min:1',
            'records' => 'required|array|min:1',
            'records.*.student_id' => 'required|exists:students,id',
            'records.*.marks_obtained' => 'nullable|numeric|min:0',
            'records.*.is_absent' => 'nullable|boolean',
            'records.*.remark' => 'nullable|string|max:200',
        ]);
        $user = $request->user();
        $subject = Subject::findOrFail($data['subject_id']);
        if ($user->role === User::TEACHER && $subject->teacher_id !== $user->id
            && ! in_array((int) $data['section_id'], $user->teachingSectionIds(), true)) {
            return $this->fail('You can only enter marks for your own subjects or classes.', 403);
        }
        $section = Section::findOrFail($data['section_id']);
        $saved = 0;
        DB::transaction(function () use ($data, $section, $user, &$saved) {
            foreach ($data['records'] as $r) {
                $absent = (bool) ($r['is_absent'] ?? false);
                $m = $absent ? 0 : ($r['marks_obtained'] ?? null);
                if ($m === null && ! $absent) continue;
                if ($m > $data['max_marks']) abort(422, "Marks cannot exceed {$data['max_marks']}.");
                $g = Grade::forPercent($m * 100 / $data['max_marks']);
                Mark::updateOrCreate(
                    ['exam_id' => $data['exam_id'], 'student_id' => $r['student_id'], 'subject_id' => $data['subject_id']],
                    ['class_id' => $section->class_id, 'section_id' => $section->id, 'marks_obtained' => $m,
                        'max_marks' => $data['max_marks'], 'grade' => $g?->name, 'point' => $g?->point,
                        'is_absent' => $absent, 'remark' => $r['remark'] ?? null, 'entered_by' => $user->id]
                );
                $saved++;
            }
        });
        return $this->ok(['saved' => $saved], "$saved marks saved");
    }

    /** GET /students/{student}/report-card?exam_id= */
    public function reportCard(Request $request, Student $student)
    {
        $user = $request->user();
        $this->ensureStudent($user, $student);
        $exam = $request->filled('exam_id') ? Exam::findOrFail($request->exam_id)
            : Exam::whereHas('marks', fn ($q) => $q->where('student_id', $student->id))
                ->when(in_array($user->role, [User::PARENT, User::STUDENT], true), fn ($q) => $q->where('is_published', true))
                ->latest('start_date')->first();
        if (! $exam) return $this->fail('No results available yet.', 404);
        if (! $exam->is_published && in_array($user->role, [User::PARENT, User::STUDENT], true)) {
            return $this->fail('Results for this exam are not published yet.', 403);
        }
        return $this->ok($this->buildReport($student->load(['user', 'schoolClass', 'section']), $exam));
    }

    /** GET /report-card/my?exam_id=&student_id= — parent/student shortcut */
    public function myReportCard(Request $request)
    {
        $s = $this->resolveOwnStudent($request->user(), $request->query('student_id'));
        if (! $s) return $this->fail('No student is linked to this account.', 404);
        return $this->reportCard($request, $s);
    }

    private function buildReport(Student $student, Exam $exam): array
    {
        $marks = Mark::with('subject')->where('exam_id', $exam->id)->where('student_id', $student->id)->get();
        $total = $marks->sum('marks_obtained');
        $max = $marks->sum('max_marks');
        $pct = $max ? round($total * 100 / $max, 2) : 0;
        $g = Grade::forPercent($pct);
        $failed = $marks->contains(fn ($m) => $m->is_absent || ($m->max_marks && $m->marks_obtained * 100 / $m->max_marks < 33));

        // Rank within section by total marks
        $totals = Mark::where('exam_id', $exam->id)->where('section_id', $student->section_id)
            ->selectRaw('student_id, sum(marks_obtained) t')->groupBy('student_id')->orderByDesc('t')->pluck('t', 'student_id');
        $rank = $totals->keys()->search($student->id);

        return [
            'student' => $student,
            'exam' => $exam,
            'subjects' => $marks->map(fn ($m) => [
                'subject' => $m->subject?->name, 'marks_obtained' => $m->marks_obtained, 'max_marks' => $m->max_marks,
                'grade' => $m->grade, 'point' => $m->point, 'is_absent' => $m->is_absent, 'remark' => $m->remark,
            ])->values(),
            'total' => $total,
            'max_total' => $max,
            'percentage' => $pct,
            'grade' => $g?->name,
            'gpa' => $marks->count() ? round($marks->avg('point'), 2) : null,
            'rank' => $rank === false ? null : $rank + 1,
            'class_strength' => $totals->count(),
            'result' => $marks->isEmpty() ? 'Pending' : ($failed ? 'Needs improvement' : 'Pass'),
        ];
    }
}
