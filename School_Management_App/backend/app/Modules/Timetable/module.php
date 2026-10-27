<?php

use App\Core\Modules\F;
use App\Models\User;

use App\Modules\Timetable\Models\TimetableEntry;

return [[
    'key' => 'timetable', 'title' => 'Timetable (periods)', 'group' => 'Timetable', 'icon' => 'schedule', 'order' => 70,
    'model' => TimetableEntry::class, 'with' => ['subject', 'schoolClass', 'section', 'teacher'],
    'title_fn' => fn ($m) => ($m->subject?->name ?? 'Period').' · '.$m->day_name.' '.$m->start_time,
    'subtitle' => ['section_id', 'teacher_id', 'room'], 'sort' => 'day',
    'scope' => function ($q, $user) {
        if ($user->isAdmin() || $user->isStaff()) return;
        $secs = \App\Modules\Students\Models\Student::whereIn('id', $user->ownStudentIds() ?: [0])->pluck('section_id');
        $q->whereIn('section_id', $secs);
    },
    'roles' => ['view' => ['all'], 'create' => [], 'update' => [], 'delete' => []],
    'fields' => [
        F::ref('class_id', 'Class', 'classes', ['required' => true, 'list' => true, 'filter' => true]),
        F::ref('section_id', 'Section', 'sections', ['required' => true, 'list' => true, 'filter' => true]),
        F::select('day', 'Day', [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday'], ['required' => true, 'list' => true, 'filter' => true]),
        F::time('start_time', 'From', ['required' => true, 'list' => true]),
        F::time('end_time', 'To', ['required' => true, 'list' => true]),
        F::ref('subject_id', 'Subject', 'subjects', ['required' => true, 'list' => true]),
        F::ref('teacher_id', 'Teacher', 'teachers', ['list' => true, 'filter' => true]),
        F::text('room', 'Room'),
    ],
    'validate' => function (array $d, $m) {
        $sec = $d['section_id'] ?? $m?->section_id; $day = $d['day'] ?? $m?->day; $st = $d['start_time'] ?? $m?->start_time; $en = $d['end_time'] ?? $m?->end_time;
        $teacher = array_key_exists('teacher_id', $d) ? $d['teacher_id'] : $m?->teacher_id;
        $clash = TimetableEntry::where('day', $day)->where('start_time', '<', $en)->where('end_time', '>', $st)
            ->when($m, fn ($q) => $q->where('id', '!=', $m->id))
            ->where(fn ($q) => $q->where('section_id', $sec)->when($teacher, fn ($w) => $w->orWhere('teacher_id', $teacher)))->exists();
        if ($clash) throw \Illuminate\Validation\ValidationException::withMessages(['start_time' => 'Clash: this section or teacher already has a period at that time.']);
    },
]];
