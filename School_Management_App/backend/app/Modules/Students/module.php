<?php

use App\Core\Modules\F;
use App\Models\User;

use App\Modules\Students\Models\Student;
use App\Modules\Students\Models\StudentDocument;

$family = function ($q, $user) { if (! $user->isAdmin() && ! $user->isStaff()) $q->whereIn('students.id', $user->ownStudentIds() ?: [0]); };

return [
[
    'key' => 'students', 'title' => 'Students', 'title_singular' => 'Student saved', 'group' => 'Students', 'icon' => 'school', 'order' => 10,
    'model' => Student::class, 'with' => ['user', 'schoolClass', 'section.schoolClass'],
    'scope' => $family,
    'title_fn' => fn ($m) => $m->user?->name, 'subtitle' => ['admission_no', 'section_id', 'roll_no'],
    // Picker text: name + class-section + admission no., so two "Rahul Kumar"s can be told apart.
    'option_fn' => fn ($m) => implode(' · ', array_filter([$m->user?->name, $m->section?->label ?: \App\Modules\Academics\Models\SchoolClass::displayName($m->schoolClass?->name), $m->admission_no])),
    'search' => ['admission_no', 'father_name', 'guardian_phone', 'aadhaar_no', 'user.name', 'user.phone'], 'sort' => 'class_id',
    'roles' => ['view' => ['all'], 'create' => ['office'], 'update' => ['office'], 'delete' => []],
    'fields' => [
        F::text('name', 'Student name', ['on' => 'user', 'required' => true, 'list' => true]),
        F::text('admission_no', 'Admission no.', ['list' => true, 'immutable' => true, 'unique' => true, 'help' => 'Permanent. Auto-generated if left blank.']),
        F::ref('class_id', 'Class', 'classes', ['required' => true, 'list' => true, 'filter' => true]),
        F::ref('section_id', 'Section', 'sections', ['required' => true, 'list' => true, 'filter' => true, 'help' => 'Choose the class first — only that class’s sections are listed.']),
        F::number('roll_no', 'Roll no.', ['list' => true]),
        F::image('photo', 'Photo', ['on' => 'user']),
        F::select('gender', 'Gender', ['male', 'female', 'other'], ['on' => 'user', 'filter' => true]),
        F::date('dob', 'Date of birth', ['on' => 'user']),
        F::date('admission_date', 'Admission date'),
        F::text('father_name', 'Father’s name', ['list' => true]),
        F::text('mother_name', 'Mother’s name'),
        F::phone('guardian_phone', 'Guardian mobile', ['list' => true]),
        F::ref('parent_id', 'Parent login', 'parents', ['help' => 'Link an existing parent account (or create one under Parents).']),
        F::email('email', 'Student email (login)', ['on' => 'user', 'unique' => true]),
        F::phone('phone', 'Student mobile (login)', ['on' => 'user', 'unique' => true]),
        F::textarea('address', 'Address', ['on' => 'user']),
        F::select('category', 'Category', ['General', 'OBC', 'SC', 'ST', 'EWS'], ['filter' => true]),
        F::text('religion', 'Religion'),
        F::select('blood_group', 'Blood group', ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']),
        F::text('aadhaar_no', 'Aadhaar no.'),
        F::text('nationality', 'Nationality'),
        F::text('mother_tongue', 'Mother tongue'),
        F::text('previous_school', 'Previous school'),
        F::select('house', 'House', ['Red', 'Blue', 'Green', 'Yellow']),
        F::ref('transport_route_id', 'Bus route', 'transport_routes'),
        F::ref('academic_year_id', 'Session', 'academic_years', ['readonly' => true]),
        F::select('status', 'Status', ['active' => 'Studying', 'left' => 'Left (TC issued)', 'graduated' => 'Passed out'], ['list' => true, 'filter' => true, 'default' => 'active']),
    ],
    'validate' => function (array $data, $m) {
        $class = $data['class_id'] ?? $m?->class_id; $sec = $data['section_id'] ?? $m?->section_id;
        if ($class && $sec && ! \App\Modules\Academics\Models\Section::whereKey($sec)->where('class_id', $class)->exists()) {
            throw \Illuminate\Validation\ValidationException::withMessages(['section_id' => 'That section does not belong to the chosen class.']);
        }
    },
    'create' => function (array $data, $request, $user) {
        $payload = $data;
        $photo = $payload['photo'] ?? null; unset($payload['photo'], $payload['status']);
        $resp = app(\App\Modules\Students\Http\StudentController::class)->store(new \Illuminate\Http\Request(array_filter($payload, fn ($v) => $v !== null)));
        $body = $resp->getData(true);
        if ($resp->getStatusCode() >= 400) abort($resp->getStatusCode(), $body['message'] ?? 'Could not admit the student.');
        $s = Student::findOrFail($body['data']['id']);
        $extra = array_intersect_key($data, array_flip(['nationality', 'mother_tongue', 'previous_school']));
        if ($extra) $s->update($extra);
        if ($photo) $s->user->update(['photo' => $photo]);
        return $s;
    },
    'import' => true, 'import_key' => 'admission_no',
    'actions' => [
        'id_card' => ['label' => 'ID card (PDF)', 'kind' => 'pdf', 'doc' => 'id-card', 'roles' => ['office', 'teacher']],
        'fee_statement' => ['label' => 'Fee statement (PDF)', 'kind' => 'pdf', 'doc' => 'fee-statement', 'roles' => ['office', 'family']],
        'report_card' => ['label' => 'Report card (PDF)', 'kind' => 'pdf', 'doc' => 'report-card', 'roles' => ['employees', 'family']],
        'admit_card' => ['label' => 'Admit card (PDF)', 'kind' => 'pdf', 'doc' => 'admit-card', 'roles' => ['employees', 'family']],
    ],
],
[
    'key' => 'student_documents', 'import' => false, 'title' => 'Student Documents', 'group' => 'Students', 'icon' => 'folder_shared', 'order' => 11,
    'model' => StudentDocument::class, 'with' => ['student.user'],
    'scope' => function ($q, $user) { if (! $user->isAdmin() && ! $user->isStaff()) $q->whereIn('student_id', $user->ownStudentIds() ?: [0]); },
    'label' => 'title', 'subtitle' => ['student_id', 'type'], 'search' => ['title', 'student.admission_no'],
    'roles' => ['view' => ['employees', 'family'], 'create' => ['office'], 'update' => ['office'], 'delete' => ['office']],
    'fields' => [
        F::ref('student_id', 'Student', 'students', ['required' => true, 'list' => true, 'filter' => true]),
        F::text('title', 'Document', ['required' => true, 'list' => true]),
        F::select('type', 'Type', ['birth_certificate' => 'Birth certificate', 'aadhaar' => 'Aadhaar', 'tc' => 'Previous TC', 'marksheet' => 'Marksheet', 'caste' => 'Caste certificate', 'photo' => 'Photo', 'other' => 'Other'], ['list' => true, 'filter' => true]),
        F::file('file', 'File (PDF / photo)', ['required' => true]),
        F::text('remarks', 'Remarks'),
    ],
],
];
