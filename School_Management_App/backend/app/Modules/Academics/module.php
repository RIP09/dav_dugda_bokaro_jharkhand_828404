<?php

use App\Core\Modules\F;
use App\Models\User;

use App\Modules\Academics\Models\AcademicYear;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\Academics\Models\Subject;
use App\Modules\Academics\Models\Syllabus;
use App\Modules\Academics\Models\StudyMaterial;

$familySections = function ($q, $user, $col = 'class_id') {
    if ($user->isAdmin() || $user->isStaff()) return;
    $classIds = \App\Modules\Students\Models\Student::whereIn('id', $user->ownStudentIds() ?: [0])->pluck('class_id');
    $q->whereIn($col, $classIds);
};

return [
[
    'key' => 'academic_years', 'title' => 'Sessions', 'group' => 'Academics', 'icon' => 'date_range', 'order' => 60,
    'model' => AcademicYear::class, 'label' => 'title', 'subtitle' => ['start_date', 'end_date'], 'sort' => '-start_date',
    'roles' => ['view' => ['all'], 'create' => [], 'update' => [], 'delete' => []],
    'fields' => [
        F::text('title', 'Session', ['required' => true, 'list' => true, 'help' => 'e.g. 2027-28']),
        F::date('start_date', 'Starts', ['required' => true, 'list' => true]),
        F::date('end_date', 'Ends', ['required' => true, 'list' => true]),
        F::bool('is_current', 'Current session', ['list' => true]),
    ],
    'after_save' => function ($m) { if ($m->is_current) AcademicYear::where('id', '!=', $m->id)->update(['is_current' => false]); },
],
[
    'key' => 'classes', 'title' => 'Classes', 'group' => 'Academics', 'icon' => 'class', 'order' => 61,
    'model' => SchoolClass::class, 'label' => 'name', 'subtitle' => ['group'], 'sort' => 'numeric_order',
    'roles' => ['view' => ['all'], 'create' => [], 'update' => [], 'delete' => []],
    'fields' => [
        F::text('name', 'Class', ['required' => true, 'list' => true]),
        F::number('numeric_order', 'Order', ['list' => true, 'help' => 'Used for sorting and promotion (LKG=0, UKG=1, Class 1=2 …).']),
        F::select('group', 'Wing', ['Pre-primary', 'Primary', 'Middle', 'Secondary', 'Senior Secondary'], ['list' => true, 'filter' => true]),
        F::number('students_count', 'Students', ['readonly' => true, 'virtual' => true, 'list' => true, 'get' => fn ($m) => $m->students()->count()]),
    ],
],
[
    'key' => 'sections', 'title' => 'Sections', 'group' => 'Academics', 'icon' => 'view_module', 'order' => 62,
    'model' => Section::class, 'with' => ['schoolClass'], 'title_fn' => fn ($m) => $m->label,
    'subtitle' => ['teacher_id', 'room'], 'sort' => 'class_id', 'search' => ['name', 'schoolClass.name'],
    'roles' => ['view' => ['all'], 'create' => [], 'update' => [], 'delete' => []],
    'fields' => [
        F::ref('class_id', 'Class', 'classes', ['required' => true, 'list' => true, 'filter' => true]),
        F::text('name', 'Section', ['required' => true, 'list' => true, 'help' => 'Just the letter, e.g. A. It is always shown with its class: “Class 6 – A”.']),
        F::select('stream', 'Stream (Class XI–XII only)', Section::STREAMS, ['list' => true, 'filter' => true, 'help' => 'Science or Commerce. Leave blank for Nursery–X.']),
        F::ref('teacher_id', 'Class teacher', 'teachers', ['list' => true]),
        F::number('capacity', 'Capacity'),
        F::text('room', 'Room'),
    ],
    'validate' => function (array $data, $m) {
        $stream = array_key_exists('stream', $data) ? $data['stream'] : $m?->stream;
        $classId = $data['class_id'] ?? $m?->class_id;
        if ($stream && $classId && ! SchoolClass::isSeniorSecondary(SchoolClass::find($classId))) {
            throw \Illuminate\Validation\ValidationException::withMessages(['stream' => 'Stream (Science / Commerce) is only for Class XI and XII.']);
        }
        $name = $data['name'] ?? null;
        if ($name !== null && $classId && Section::where('class_id', $classId)->where('name', $name)->when($m, fn ($q) => $q->whereKeyNot($m->getKey()))->exists()) {
            throw \Illuminate\Validation\ValidationException::withMessages(['name' => 'That section already exists in this class.']);
        }
    },
],
[
    'key' => 'subjects', 'title' => 'Subjects', 'group' => 'Academics', 'icon' => 'menu_book', 'order' => 63,
    'model' => Subject::class, 'with' => ['schoolClass'], 'title_fn' => fn ($m) => $m->name.' ('.SchoolClass::displayName($m->schoolClass?->name).')',
    'subtitle' => ['code', 'teacher_id'], 'search' => ['name', 'code'], 'sort' => 'class_id',
    'roles' => ['view' => ['all'], 'create' => [], 'update' => [], 'delete' => []],
    'fields' => [
        F::ref('class_id', 'Class', 'classes', ['required' => true, 'list' => true, 'filter' => true]),
        F::text('name', 'Subject', ['required' => true, 'list' => true]),
        F::text('code', 'Code', ['list' => true]),
        F::select('type', 'Type', ['theory', 'practical', 'both']),
        F::ref('teacher_id', 'Subject teacher', 'teachers', ['list' => true, 'filter' => true]),
    ],
],
[
    'key' => 'syllabus', 'title' => 'Syllabus / Lesson Plan', 'group' => 'Academics', 'icon' => 'checklist', 'order' => 64,
    'model' => Syllabus::class, 'label' => 'title', 'subtitle' => ['class_id', 'subject_id', 'status'], 'search' => ['title'],
    'scope' => $familySections,
    'roles' => ['view' => ['all'], 'create' => ['teacher'], 'update' => ['teacher'], 'delete' => ['teacher']],
    'fields' => [
        F::ref('class_id', 'Class', 'classes', ['required' => true, 'list' => true, 'filter' => true]),
        F::ref('subject_id', 'Subject', 'subjects', ['list' => true, 'filter' => true]),
        F::text('title', 'Chapter / topic', ['required' => true, 'list' => true]),
        F::textarea('description', 'Details'),
        F::text('planned_month', 'Planned month (YYYY-MM)'),
        F::select('status', 'Status', ['pending' => 'Not started', 'in_progress' => 'In progress', 'completed' => 'Completed'], ['list' => true, 'filter' => true, 'default' => 'pending']),
        F::date('completed_on', 'Completed on'),
    ],
],
[
    'key' => 'study_materials', 'title' => 'Study Material', 'group' => 'Academics', 'icon' => 'cloud_download', 'order' => 65,
    'model' => StudyMaterial::class, 'label' => 'title', 'subtitle' => ['class_id', 'subject_id'], 'search' => ['title'],
    'scope' => $familySections,
    'roles' => ['view' => ['all'], 'create' => ['teacher'], 'update' => ['teacher'], 'delete' => ['teacher']],
    'can_edit' => fn ($m, $u) => $u->isAdmin() || $m->uploaded_by === $u->id,
    'fields' => [
        F::ref('class_id', 'Class', 'classes', ['required' => true, 'list' => true, 'filter' => true]),
        F::ref('subject_id', 'Subject', 'subjects', ['list' => true]),
        F::text('title', 'Title', ['required' => true, 'list' => true]),
        F::textarea('description', 'Description'),
        F::file('file', 'File (PDF, notes, worksheet)'),
        F::text('link', 'Video / web link'),
        F::ref('uploaded_by', 'Uploaded by', 'staff', ['readonly' => true]),
    ],
    'before_save' => function ($m, $d, $user, $creating) { if ($creating) $m->uploaded_by = $user->id; },
],
];
