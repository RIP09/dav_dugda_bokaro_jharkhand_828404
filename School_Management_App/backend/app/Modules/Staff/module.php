<?php

use App\Core\Modules\F;
use App\Models\User;

$personFields = fn (string $loginHelp) => [
    F::text('name', 'Full name', ['required' => true, 'list' => true]),
    F::email('email', 'Email (login)', ['list' => true, 'unique' => true, 'help' => $loginHelp]),
    F::phone('phone', 'Mobile (login)', ['list' => true, 'unique' => true]),
    F::text('password', 'Password', ['write_only' => true, 'virtual' => true, 'help' => 'Leave blank to keep the current password.']),
    F::select('gender', 'Gender', ['male', 'female', 'other']),
    F::date('dob', 'Date of birth'),
    F::textarea('address', 'Address'),
    F::image('photo', 'Photo'),
    F::bool('is_active', 'Login enabled', ['list' => true, 'filter' => true, 'default' => true]),
    F::datetime('last_login_at', 'Last sign-in', ['readonly' => true]),
];
$userCreate = fn (string $role) => function (array $data, $request, $user) use ($role) {
    $payload = array_merge($data, ['role' => $data['role'] ?? $role]);
    unset($payload['photo'], $payload['is_active']);
    $resp = app(\App\Modules\Staff\Http\UserController::class)->store(new \Illuminate\Http\Request($payload));
    $body = $resp->getData(true);
    if ($resp->getStatusCode() >= 400) abort($resp->getStatusCode(), $body['message'] ?? 'Could not create the account.');
    $u = User::findOrFail($body['data']['id']);
    $u->update(array_filter(['photo' => $data['photo'] ?? null, 'is_active' => $data['is_active'] ?? true], fn ($v) => $v !== null));
    return $u;
};
$passwordAfterSave = function ($m, $data) {
    if (! empty($data['password'])) $m->forceFill(['password' => $data['password']])->save();
};
$noSelfDelete = fn ($m, $user) => $m->id !== $user->id;

$staffProfile = [
    F::text('employee_id', 'Employee ID', ['on' => 'staffProfile', 'list' => true, 'immutable' => true, 'help' => 'Auto-generated if left blank.']),
    F::text('designation', 'Designation', ['on' => 'staffProfile', 'list' => true]),
    F::text('department', 'Department', ['on' => 'staffProfile', 'filter' => true]),
    F::date('joining_date', 'Joining date', ['on' => 'staffProfile']),
    F::text('qualification', 'Qualification', ['on' => 'staffProfile']),
    F::money('salary', 'Monthly salary (₹)', ['on' => 'staffProfile']),
];
$roles = ['teacher' => 'Teacher', 'accountant' => 'Accountant', 'librarian' => 'Librarian', 'staff' => 'Other staff (clerk, peon, driver…)', 'admin' => 'Admin (office / principal)'];

return [[
    'key' => 'staff', 'title' => 'All Staff', 'title_singular' => 'Staff saved', 'group' => 'Staff', 'icon' => 'badge', 'order' => 31,
    'model' => User::class, 'with' => ['staffProfile'],
    'base' => fn ($q) => $q->whereIn('role', [User::ADMIN, ...User::STAFF_ROLES]),
    'label' => 'name', 'subtitle' => ['role', 'designation', 'phone'],
    'option_fn' => fn ($m) => implode(' · ', array_filter([$m->name, $m->staffProfile?->designation ?: ucfirst((string) $m->role)])), 'search' => ['name', 'email', 'phone', 'staffProfile.employee_id'], 'sort' => 'name',
    'roles' => ['view' => ['employees'], 'create' => [], 'update' => [], 'delete' => []],
    'fields' => array_merge([F::select('role', 'Job / role', $roles, ['required' => true, 'list' => true, 'filter' => true])],
        $personFields('Signs in to the Staff app (admins: School app) with this email or mobile.'), $staffProfile),
    'create' => $userCreate(User::STAFF), 'after_save' => $passwordAfterSave, 'can_delete' => $noSelfDelete,
    'import' => true, 'import_key' => 'email',
    'actions' => [
        'id_card' => ['label' => 'ID card (PDF)', 'kind' => 'pdf', 'doc' => 'staff-id-card', 'roles' => ['office']],
    ],
]];
