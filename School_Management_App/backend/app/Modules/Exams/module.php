<?php

use App\Core\Modules\F;
use App\Models\User;

use App\Modules\Exams\Models\Exam;
use App\Modules\Exams\Models\ExamSchedule;
use App\Modules\Exams\Models\Mark;
use App\Modules\Exams\Models\Grade;
use App\Modules\Communication\Models\AppNotification;
use App\Modules\Audit\Models\AuditLog;

return [
[
    'key' => 'exams', 'title' => 'Exams', 'group' => 'Exams', 'icon' => 'quiz', 'order' => 80, 'lockable' => true,
    'lock_note' => 'After “Lock results (final)” the exam and all its marks become permanent.',
    'model' => Exam::class, 'label' => 'name', 'subtitle' => ['term', 'start_date', 'is_published'], 'sort' => '-start_date',
    'option_fn' => fn ($m) => $m->name.(($y = \App\Modules\Academics\Models\AcademicYear::find($m->academic_year_id)?->title) ? ' ('.$y.')' : ''),
    'roles' => ['view' => ['all'], 'create' => [], 'update' => [], 'delete' => []],
    'fields' => [
        F::text('name', 'Exam', ['required' => true, 'list' => true]),
        F::select('term', 'Term', ['Term 1', 'Term 2', 'Periodic Test', 'Half Yearly', 'Annual', 'Pre-Board'], ['list' => true]),
        F::ref('academic_year_id', 'Session', 'academic_years'),
        F::date('start_date', 'Starts', ['list' => true]),
        F::date('end_date', 'Ends'),
        F::bool('is_published', 'Results published to parents/students', ['list' => true]),
        F::bool('results_locked', 'Results locked (final)', ['readonly' => true, 'list' => true]),
    ],
    'actions' => [
        'publish' => ['label' => 'Publish results', 'kind' => 'post', 'roles' => [], 'when' => fn ($m) => ! $m->is_published,
            'run' => function ($m) {
                $m->update(['is_published' => true]);
                $ids = \App\Modules\Students\Models\Student::get(['user_id', 'parent_id']);
                AppNotification::send($ids->pluck('user_id')->merge($ids->pluck('parent_id')), 'Results published: '.$m->name, 'Open Results in the app to see the report card.', 'exams', $m->id);
                return ['message' => 'Results published and parents/students notified'];
            }],
        'lock_results' => ['label' => 'Lock results (final)', 'kind' => 'post', 'roles' => [], 'when' => fn ($m) => ! $m->results_locked,
            'confirm' => 'Locking is permanent: marks of this exam can never be edited again. Continue?',
            'run' => function ($m) {
                \App\Core\Locks::bypass(fn () => $m->update(['results_locked' => true, 'locked_at' => now(), 'is_published' => true]));
                AuditLog::record('lock', $m, ['results_locked' => ['old' => 0, 'new' => 1]]);
                return ['message' => 'Results locked. They are now permanent.'];
            }],
        'admit_cards' => ['label' => 'Admit cards (PDF)', 'kind' => 'pdf', 'doc' => 'admit-cards', 'roles' => ['employees']],
    ],
],
[
    'key' => 'exam_schedules', 'title' => 'Date Sheet', 'group' => 'Exams', 'icon' => 'event_note', 'order' => 81,
    'model' => ExamSchedule::class, 'with' => ['subject'], 'title_fn' => fn ($m) => ($m->subject?->name ?? 'Paper').' · '.$m->exam_date?->format('d M Y'),
    'subtitle' => ['exam_id', 'class_id', 'start_time'], 'sort' => 'exam_date',
    'roles' => ['view' => ['all'], 'create' => [], 'update' => [], 'delete' => []],
    'fields' => [
        F::ref('exam_id', 'Exam', 'exams', ['required' => true, 'list' => true, 'filter' => true]),
        F::ref('class_id', 'Class', 'classes', ['required' => true, 'list' => true, 'filter' => true]),
        F::ref('subject_id', 'Subject', 'subjects', ['required' => true, 'list' => true]),
        F::date('exam_date', 'Date', ['required' => true, 'list' => true]),
        F::time('start_time', 'From', ['list' => true]),
        F::time('end_time', 'To'),
        F::number('max_marks', 'Max marks', ['default' => 100]),
        F::number('pass_marks', 'Pass marks', ['default' => 33]),
    ],
],
[
    'key' => 'marks', 'title' => 'Marks', 'group' => 'Exams', 'icon' => 'edit_note', 'order' => 82, 'lockable' => true,
    'lock_note' => 'Marks are locked once the exam’s results are locked (final).',
    'model' => Mark::class, 'with' => ['student.user', 'subject', 'exam'], 'title_fn' => fn ($m) => ($m->student?->name ?? 'Student').' · '.($m->subject?->name ?? ''),
    'subtitle' => ['exam_id', 'marks_obtained', 'grade'], 'search' => ['student.admission_no'], 'sort' => 'student_id',
    'scope' => function ($q, $user) {
        if ($user->isAdmin() || $user->isStaff()) return;
        $q->whereIn('student_id', $user->ownStudentIds() ?: [0])->whereHas('exam', fn ($e) => $e->where('is_published', true));
    },
    'roles' => ['view' => ['all'], 'create' => ['teacher'], 'update' => ['teacher'], 'delete' => []],
    'fields' => [
        F::ref('exam_id', 'Exam', 'exams', ['required' => true, 'list' => true, 'filter' => true]),
        F::ref('student_id', 'Student', 'students', ['required' => true, 'list' => true, 'filter' => true]),
        F::ref('subject_id', 'Subject', 'subjects', ['required' => true, 'list' => true, 'filter' => true]),
        F::ref('class_id', 'Class', 'classes', ['required' => true, 'filter' => true]),
        F::ref('section_id', 'Section', 'sections', ['required' => true, 'filter' => true]),
        F::number('marks_obtained', 'Marks', ['list' => true]),
        F::number('max_marks', 'Out of', ['default' => 100, 'list' => true]),
        F::bool('is_absent', 'Absent'),
        F::text('grade', 'Grade', ['readonly' => true, 'list' => true]),
        F::text('remark', 'Remark'),
    ],
    'validate' => function (array $d, $m) {
        $max = $d['max_marks'] ?? $m?->max_marks ?? 100; $got = $d['marks_obtained'] ?? null;
        if ($got !== null && ($got < 0 || $got > $max)) throw \Illuminate\Validation\ValidationException::withMessages(['marks_obtained' => "Marks must be between 0 and {$max}."]);
    },
    'before_save' => function ($m, $d, $user) {
        $m->entered_by = $user->id;
        if ($m->is_absent) { $m->grade = 'AB'; $m->point = 0; return; }
        if ($m->marks_obtained !== null && $m->max_marks) {
            $pct = $m->marks_obtained * 100 / $m->max_marks;
            $g = Grade::where('min_percent', '<=', $pct)->orderByDesc('min_percent')->first();
            $m->grade = $g?->name; $m->point = $g?->point;
        }
    },
    'can_edit' => fn ($m, $u) => $u->isAdmin() || ($u->role === 'teacher' && (in_array($m->section_id, $u->teachingSectionIds(), true))),
],
[
    'key' => 'grades', 'title' => 'Grade Scale (CBSE)', 'group' => 'Exams', 'icon' => 'grade', 'order' => 83,
    'model' => Grade::class, 'label' => 'name', 'subtitle' => ['min_percent', 'max_percent', 'remark'], 'sort' => '-min_percent',
    'roles' => ['view' => ['all'], 'create' => [], 'update' => [], 'delete' => []],
    'fields' => [
        F::text('name', 'Grade', ['required' => true, 'list' => true]),
        F::number('min_percent', 'From %', ['required' => true, 'list' => true]),
        F::number('max_percent', 'To %', ['required' => true, 'list' => true]),
        F::number('point', 'Grade point', ['list' => true]),
        F::text('remark', 'Remark', ['list' => true]),
    ],
],
];
