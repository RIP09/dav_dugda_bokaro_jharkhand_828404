<?php

use App\Core\Modules\F;
use App\Models\User;

use App\Modules\Attendance\Models\StudentAttendance;
use App\Modules\Attendance\Models\StaffAttendance;

$statuses = ['present' => 'Present', 'absent' => 'Absent', 'late' => 'Late', 'half_day' => 'Half day', 'leave' => 'On leave'];
return [
[
    'key' => 'student_attendance', 'title' => 'Student Attendance (records)', 'group' => 'Attendance', 'icon' => 'fact_check', 'order' => 40,
    'model' => StudentAttendance::class, 'with' => ['student.user'], 'title_fn' => fn ($m) => ($m->student?->name ?? 'Student').' · '.\Illuminate\Support\Carbon::parse($m->date)->format('d M Y'),
    'subtitle' => ['status', 'section_id'], 'search' => ['student.admission_no'], 'sort' => '-date',
    'scope' => function ($q, $user) { if (! $user->isAdmin() && ! $user->isStaff()) $q->whereIn('student_id', $user->ownStudentIds() ?: [0]); },
    'roles' => ['view' => ['all'], 'create' => ['teacher'], 'update' => ['teacher'], 'delete' => []],
    'fields' => [
        F::ref('student_id', 'Student', 'students', ['required' => true, 'list' => true, 'filter' => true]),
        F::ref('class_id', 'Class', 'classes', ['required' => true, 'filter' => true]),
        F::ref('section_id', 'Section', 'sections', ['required' => true, 'list' => true, 'filter' => true]),
        F::date('date', 'Date', ['required' => true, 'list' => true, 'filter' => true]),
        F::select('status', 'Status', $statuses, ['required' => true, 'list' => true, 'filter' => true]),
        F::text('remark', 'Remark'),
        F::ref('marked_by', 'Marked by', 'staff', ['readonly' => true]),
    ],
    'before_save' => function ($m, $d, $user) { $m->marked_by = $user->id; },
],
[
    'key' => 'staff_attendance', 'title' => 'Staff Attendance (records)', 'group' => 'Attendance', 'icon' => 'how_to_reg', 'order' => 41,
    'model' => StaffAttendance::class, 'with' => ['user'], 'title_fn' => fn ($m) => ($m->user?->name ?? 'Staff').' · '.\Illuminate\Support\Carbon::parse($m->date)->format('d M Y'),
    'subtitle' => ['status', 'in_time', 'out_time'], 'search' => ['user.name'], 'sort' => '-date',
    'scope' => function ($q, $user) { if (! $user->isAdmin()) $q->where('user_id', $user->id); },
    'roles' => ['view' => ['employees'], 'create' => [], 'update' => [], 'delete' => []],
    'fields' => [
        F::ref('user_id', 'Staff', 'staff', ['required' => true, 'list' => true, 'filter' => true]),
        F::date('date', 'Date', ['required' => true, 'list' => true, 'filter' => true]),
        F::select('status', 'Status', $statuses, ['required' => true, 'list' => true, 'filter' => true]),
        F::time('in_time', 'In time', ['list' => true]),
        F::time('out_time', 'Out time', ['list' => true]),
        F::text('remark', 'Remark'),
    ],
    'before_save' => function ($m, $d, $user) { $m->marked_by = $user->id; },
],
];
