<?php

use App\Core\Modules\F;
use App\Models\User;

use App\Modules\Admissions\Models\Enquiry;
use App\Modules\Communication\Models\AppNotification;

return [[
    'key' => 'enquiries', 'title' => 'Admissions & Enquiries', 'title_singular' => 'Enquiry saved', 'group' => 'Admissions', 'icon' => 'person_add', 'order' => 5,
    'model' => Enquiry::class, 'label' => 'student_name', 'subtitle' => ['class_sought', 'phone', 'status'],
    'search' => ['student_name', 'parent_name', 'phone', 'email'],
    'roles' => ['view' => ['office', 'teacher'], 'create' => ['office'], 'update' => ['office'], 'delete' => []],
    'fields' => [
        F::text('student_name', 'Student name', ['required' => true, 'list' => true]),
        F::text('parent_name', 'Parent name', ['list' => true]),
        F::phone('phone', 'Mobile', ['required' => true, 'list' => true]),
        F::email('email', 'Email'),
        F::text('class_sought', 'Class wanted', ['list' => true]),
        F::ref('class_id', 'Admit into class', 'classes', ['filter' => true]),
        F::date('dob', 'Date of birth'),
        F::select('gender', 'Gender', ['male', 'female', 'other']),
        F::text('previous_school', 'Previous school'),
        F::textarea('address', 'Address'),
        F::select('source', 'How they came', ['walk_in' => 'Walk-in', 'phone' => 'Phone call', 'website' => 'Website', 'reference' => 'Reference', 'other' => 'Other'], ['filter' => true, 'default' => 'walk_in']),
        F::select('status', 'Stage', ['new' => 'New', 'contacted' => 'Contacted', 'visit' => 'Visited school', 'test' => 'Admission test', 'approved' => 'Approved', 'admitted' => 'Admitted', 'rejected' => 'Not selected', 'closed' => 'Closed'], ['list' => true, 'filter' => true, 'default' => 'new']),
        F::date('follow_up_date', 'Next follow-up', ['list' => true]),
        F::date('test_date', 'Test / interaction date'),
        F::number('test_score', 'Test score'),
        F::ref('assigned_to', 'Handled by', 'staff'),
        F::textarea('message', 'Enquiry message'),
        F::textarea('notes', 'Office notes'),
        F::ref('student_id', 'Admitted student', 'students', ['readonly' => true]),
    ],
    'actions' => [
        'admit' => ['label' => 'Admit as student', 'kind' => 'post', 'roles' => ['office'], 'confirm' => 'Create the student record and logins from this enquiry?',
            'when' => fn ($m) => ! $m->student_id && ! in_array($m->status, ['rejected', 'closed'], true),
            'run' => function ($m, $request, $user) {
                $request->validate(['class_id' => 'nullable|exists:school_classes,id', 'section_id' => 'nullable|exists:sections,id']);
                $classId = $request->input('class_id', $m->class_id) ?: abort(422, 'Choose “Admit into class” first (edit the enquiry), then admit.');
                $sectionId = $request->input('section_id') ?: \App\Modules\Academics\Models\Section::where('class_id', $classId)->orderBy('name')->value('id');
                $payload = array_filter(['name' => $m->student_name, 'class_id' => $classId, 'section_id' => $sectionId, 'dob' => $m->dob?->format('Y-m-d'),
                    'gender' => $m->gender, 'address' => $m->address, 'father_name' => $m->parent_name, 'guardian_phone' => $m->phone,
                    'parent_name' => $m->parent_name, 'parent_phone' => $m->phone, 'parent_email' => $m->email, 'admission_date' => now()->toDateString()]);
                $resp = app(\App\Modules\Students\Http\StudentController::class)->store(new \Illuminate\Http\Request($payload));
                $body = $resp->getData(true);
                if ($resp->getStatusCode() >= 400) abort($resp->getStatusCode(), $body['message'] ?? 'Could not admit.');
                $m->update(['student_id' => $body['data']['id'], 'status' => 'admitted', 'class_id' => $classId]);
                if ($m->previous_school) \App\Modules\Students\Models\Student::whereKey($body['data']['id'])->update(['previous_school' => $m->previous_school]);
                return ['message' => 'Admitted. Admission no. '.$body['data']['admission_no'].' — student and parent logins created.'];
            }],
    ],
]];
