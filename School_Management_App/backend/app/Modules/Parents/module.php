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

return [[
    'key' => 'parents', 'title' => 'Parents', 'title_singular' => 'Parent saved', 'group' => 'Parents', 'icon' => 'family_restroom', 'order' => 20,
    'model' => User::class, 'with' => ['children.user', 'children.schoolClass', 'children.section'],
    'base' => fn ($q) => $q->where('role', User::PARENT),
    'scope' => function ($q, $user) { if (! $user->isAdmin() && ! $user->isStaff()) $q->whereKey($user->id); },
    'label' => 'name', 'subtitle' => ['phone', 'children_names'], 'search' => ['name', 'email', 'phone'], 'sort' => 'name',
    'roles' => ['view' => ['employees'], 'create' => ['office'], 'update' => ['office'], 'delete' => []],
    'fields' => array_merge($personFields('Parents sign in to the Parent app with this mobile number or email.'), [
        F::text('children_names', 'Children', ['readonly' => true, 'virtual' => true, 'list' => true,
            'get' => fn ($m) => $m->children->map(fn ($c) => $c->name.' ('.$c->class_label.')')->implode(', ')]),
    ]),
    'create' => $userCreate(User::PARENT), 'after_save' => $passwordAfterSave,
    'import' => true, 'import_key' => 'phone',
]];
