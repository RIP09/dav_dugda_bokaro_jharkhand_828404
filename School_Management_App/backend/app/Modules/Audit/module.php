<?php

use App\Core\Modules\F;
use App\Models\User;

use App\Modules\Audit\Models\AuditLog;

return [[
    'key' => 'audit', 'title' => 'Audit Log (all changes)', 'group' => 'Settings', 'icon' => 'history', 'order' => 300, 'lockable' => true,
    'lock_note' => 'The audit log is permanent and can never be edited.', 'no_create' => true, 'no_update' => true, 'no_delete' => true,
    'model' => AuditLog::class, 'title_fn' => fn ($m) => ucfirst($m->action).' · '.$m->module.' · '.$m->record_title,
    'subtitle' => ['user_name', 'created_at'], 'search' => ['record_title', 'user_name', 'module'],
    'roles' => ['view' => [], 'export' => []],
    'fields' => [
        F::datetime('created_at', 'When', ['list' => true, 'filter' => true]),
        F::text('user_name', 'Who', ['list' => true]),
        F::select('action', 'Action', ['create', 'update', 'delete', 'cancel', 'lock', 'issue'], ['list' => true, 'filter' => true]),
        F::text('module', 'Module', ['list' => true, 'filter' => true]),
        F::number('record_id', 'Record id'),
        F::text('record_title', 'Record', ['list' => true]),
        F::json('changes', 'Changes (old → new)'),
        F::text('ip', 'IP'),
    ],
]];
