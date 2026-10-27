<?php

use App\Core\Modules\F;
use App\Models\User;

use App\Modules\Homework\Models\Homework;
use App\Modules\Communication\Models\AppNotification;

return [[
    'key' => 'homework', 'title' => 'Homework', 'title_singular' => 'Homework saved', 'group' => 'Homework', 'icon' => 'assignment', 'order' => 75,
    'model' => Homework::class, 'with' => ['subject', 'schoolClass', 'section'], 'label' => 'title', 'subtitle' => ['class_id', 'section_id', 'subject_id', 'due_date'],
    'search' => ['title', 'description'], 'sort' => '-assigned_on',
    'scope' => function ($q, $user) {
        if ($user->isAdmin() || $user->isStaff()) return;
        $st = \App\Modules\Students\Models\Student::whereIn('id', $user->ownStudentIds() ?: [0])->get(['class_id', 'section_id']);
        $q->where(function ($w) use ($st) {
            foreach ($st as $s) $w->orWhere(fn ($x) => $x->where('class_id', $s->class_id)->where(fn ($y) => $y->whereNull('section_id')->orWhere('section_id', $s->section_id)));
        });
    },
    'roles' => ['view' => ['all'], 'create' => ['teacher'], 'update' => ['teacher'], 'delete' => ['teacher']],
    'can_edit' => fn ($m, $u) => $u->isAdmin() || $m->teacher_id === $u->id,
    'can_delete' => fn ($m, $u) => $u->isAdmin() || $m->teacher_id === $u->id,
    'fields' => [
        F::ref('class_id', 'Class', 'classes', ['required' => true, 'list' => true, 'filter' => true]),
        F::ref('section_id', 'Section (blank = all)', 'sections', ['list' => true, 'filter' => true]),
        F::ref('subject_id', 'Subject', 'subjects', ['list' => true]),
        F::text('title', 'Title', ['required' => true, 'list' => true]),
        F::textarea('description', 'Work to do'),
        F::date('assigned_on', 'Given on', ['list' => true]),
        F::date('due_date', 'Due date', ['list' => true]),
        F::ref('teacher_id', 'Teacher', 'teachers', ['readonly' => true]),
    ],
    'before_save' => function ($m, $d, $user, $creating) {
        if ($creating) { $m->teacher_id = $m->teacher_id ?: $user->id; $m->assigned_on = $m->assigned_on ?: now()->toDateString(); }
    },
    'after_create' => function ($m) {
        $students = \App\Modules\Students\Models\Student::where('class_id', $m->class_id)->when($m->section_id, fn ($q) => $q->where('section_id', $m->section_id))->get(['user_id', 'parent_id']);
        AppNotification::send($students->pluck('user_id')->merge($students->pluck('parent_id')), 'New homework: '.$m->title, 'Due '.($m->due_date?->format('d M') ?? 'soon'), 'homework', $m->id);
    },
]];
