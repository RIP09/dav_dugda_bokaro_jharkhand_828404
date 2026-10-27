<?php

use App\Core\Modules\F;
use App\Models\User;

use App\Modules\FrontOffice\Models\Visitor;
use App\Modules\FrontOffice\Models\PostalRecord;
use App\Modules\FrontOffice\Models\CallLog;

return [
[
    'key' => 'visitors', 'title' => 'Visitor Book', 'title_singular' => 'Visitor saved', 'group' => 'Front Office', 'icon' => 'badge', 'order' => 170,
    'model' => Visitor::class, 'label' => 'name', 'subtitle' => ['purpose', 'whom_to_meet', 'in_time'], 'search' => ['name', 'phone', 'purpose', 'pass_no'], 'sort' => '-in_time',
    'import' => true,
    'roles' => ['view' => ['office'], 'create' => ['office'], 'update' => ['office'], 'delete' => []],
    'fields' => [
        F::text('pass_no', 'Pass no.', ['list' => true, 'help' => 'Auto-generated if blank.']),
        F::text('name', 'Visitor name', ['required' => true, 'list' => true]),
        F::phone('phone', 'Mobile', ['list' => true]),
        F::text('purpose', 'Purpose', ['required' => true, 'list' => true]),
        F::text('whom_to_meet', 'To meet', ['list' => true]),
        F::number('persons', 'No. of persons', ['default' => 1]),
        F::text('id_proof', 'ID proof'),
        F::datetime('in_time', 'In time', ['list' => true]),
        F::datetime('out_time', 'Out time', ['list' => true]),
        F::text('note', 'Note'),
    ],
    'before_save' => function ($m, $d, $u, $creating) {
        if ($creating) { $m->in_time = $m->in_time ?: now(); $m->pass_no = $m->pass_no ?: 'VP'.now()->format('ymd').'-'.str_pad((string) (Visitor::whereDate('in_time', now())->count() + 1), 3, '0', STR_PAD_LEFT); }
    },
    'actions' => ['checkout' => ['label' => 'Mark out', 'kind' => 'post', 'roles' => ['office'], 'when' => fn ($m) => ! $m->out_time, 'run' => function ($m) { $m->update(['out_time' => now()]); return ['message' => 'Visitor marked out']; }]],
],
[
    'key' => 'postal', 'title' => 'Postal (Dispatch / Receive)', 'group' => 'Front Office', 'icon' => 'local_post_office', 'order' => 171,
    'model' => PostalRecord::class, 'label' => 'title', 'subtitle' => ['type', 'from_to', 'date'], 'search' => ['title', 'from_to', 'reference_no'], 'sort' => '-date',
    'roles' => ['view' => ['office'], 'create' => ['office'], 'update' => ['office'], 'delete' => []],
    'fields' => [
        F::select('type', 'Type', ['dispatch' => 'Dispatch (sent)', 'receive' => 'Received'], ['required' => true, 'list' => true, 'filter' => true]),
        F::text('title', 'Subject / item', ['required' => true, 'list' => true]),
        F::text('reference_no', 'Ref / tracking no.'),
        F::text('from_to', 'From / to', ['list' => true]),
        F::text('address', 'Address'),
        F::date('date', 'Date', ['required' => true, 'list' => true]),
        F::text('note', 'Note'),
    ],
],
[
    'key' => 'call_logs', 'title' => 'Phone Call Log', 'group' => 'Front Office', 'icon' => 'call', 'order' => 172,
    'model' => CallLog::class, 'title_fn' => fn ($m) => ($m->name ?: $m->phone).' · '.$m->purpose, 'subtitle' => ['type', 'date', 'follow_up_date'], 'search' => ['name', 'phone', 'purpose'], 'sort' => '-date',
    'roles' => ['view' => ['office'], 'create' => ['office'], 'update' => ['office'], 'delete' => []],
    'fields' => [
        F::text('name', 'Caller name', ['list' => true]),
        F::phone('phone', 'Phone', ['required' => true, 'list' => true]),
        F::select('type', 'Type', ['incoming', 'outgoing'], ['list' => true, 'filter' => true]),
        F::text('purpose', 'Purpose', ['list' => true]),
        F::date('date', 'Date', ['required' => true, 'list' => true]),
        F::date('follow_up_date', 'Follow-up'),
        F::text('note', 'Note'),
    ],
],
];
