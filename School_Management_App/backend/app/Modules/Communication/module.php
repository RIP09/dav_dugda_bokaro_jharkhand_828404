<?php

use App\Core\Modules\F;
use App\Models\User;

use App\Modules\Communication\Models\Notice;
use App\Modules\Communication\Models\Event;
use App\Modules\Communication\Models\Complaint;
use App\Modules\Communication\Models\NoticeRead;
use App\Modules\Communication\Models\AppNotification;

$audiences = ['all' => 'Everyone', 'staff' => 'Staff', 'parents' => 'Parents', 'students' => 'Students'];
return [
[
    'key' => 'notices', 'title' => 'Notices', 'title_singular' => 'Notice saved', 'group' => 'Communication', 'icon' => 'campaign', 'order' => 180,
    'model' => Notice::class, 'label' => 'title', 'subtitle' => ['publish_date', 'audience'], 'search' => ['title', 'body'], 'sort' => '-publish_date',
    'scope' => fn ($q, $user) => $q->visibleTo($user),
    'roles' => ['view' => ['all'], 'create' => ['teacher'], 'update' => ['teacher'], 'delete' => ['teacher']],
    'can_edit' => fn ($m, $u) => $u->isAdmin() || $m->posted_by === $u->id,
    'can_delete' => fn ($m, $u) => $u->isAdmin() || $m->posted_by === $u->id,
    'fields' => [
        F::text('title', 'Title', ['required' => true, 'list' => true]),
        F::textarea('body', 'Notice', ['required' => true]),
        F::select('audience', 'For', $audiences, ['required' => true, 'list' => true, 'filter' => true, 'default' => 'all']),
        F::ref('class_id', 'Only class (optional)', 'classes'),
        F::date('publish_date', 'Date', ['list' => true]),
        F::bool('is_pinned', 'Pin on top'),
        F::bool('is_read', 'Read by me', ['readonly' => true, 'virtual' => true, 'get' => fn ($m) => NoticeRead::where('notice_id', $m->id)->where('user_id', request()->user()?->id)->exists()]),
        F::number('read_count', 'Read by', ['readonly' => true, 'virtual' => true, 'list' => true, 'get' => fn ($m) => NoticeRead::where('notice_id', $m->id)->count()]),
    ],
    'before_save' => function ($m, $d, $user, $creating) { if ($creating) { $m->posted_by = $user->id; $m->publish_date = $m->publish_date ?: now()->toDateString(); } },
    'after_create' => fn ($m) => app(\App\Modules\Communication\Http\NoticeController::class)->notifyAudience($m),
],
[
    'key' => 'events', 'title' => 'Events & Holidays', 'group' => 'Communication', 'icon' => 'celebration', 'order' => 181,
    'model' => Event::class, 'label' => 'title', 'subtitle' => ['type', 'start_date', 'location'], 'search' => ['title', 'location'], 'sort' => 'start_date',
    'roles' => ['view' => ['all'], 'create' => [], 'update' => [], 'delete' => []],
    'fields' => [
        F::text('title', 'Title', ['required' => true, 'list' => true]),
        F::select('type', 'Type', ['event' => 'Event', 'holiday' => 'Holiday', 'exam' => 'Exam', 'meeting' => 'PTM / Meeting'], ['required' => true, 'list' => true, 'filter' => true]),
        F::date('start_date', 'From', ['required' => true, 'list' => true, 'filter' => true]),
        F::date('end_date', 'To', ['list' => true]),
        F::text('location', 'Venue'),
        F::select('audience', 'For', $audiences, ['default' => 'all']),
        F::textarea('description', 'Details'),
    ],
],
[
    'key' => 'complaints', 'title' => 'Complaints & Feedback', 'title_singular' => 'Submitted', 'group' => 'Communication', 'icon' => 'support_agent', 'order' => 182,
    'model' => Complaint::class, 'with' => ['user'], 'label' => 'subject', 'subtitle' => ['type', 'status', 'raised_by'], 'search' => ['subject', 'description'],
    'scope' => function ($q, $user) { if (! $user->isAdmin()) $q->where(fn ($w) => $w->where('user_id', $user->id)->orWhere('assigned_to', $user->id)); },
    'roles' => ['view' => ['all'], 'create' => ['all'], 'update' => ['all'], 'delete' => []],
    'can_edit' => fn ($m, $u) => $u->isAdmin() || $m->assigned_to === $u->id || ($m->user_id === $u->id && $m->status === 'open'),
    'fields' => [
        F::select('type', 'Type', ['complaint' => 'Complaint', 'feedback' => 'Feedback', 'suggestion' => 'Suggestion'], ['required' => true, 'list' => true, 'filter' => true]),
        F::select('category', 'About', ['Academics', 'Fees', 'Transport', 'Hostel', 'Staff', 'Facilities', 'Safety', 'Other']),
        F::text('subject', 'Subject', ['required' => true, 'list' => true]),
        F::textarea('description', 'Details'),
        F::text('raised_by', 'Raised by', ['readonly' => true, 'virtual' => true, 'list' => true, 'get' => fn ($m) => $m->user?->name]),
        F::select('status', 'Status', ['open' => 'Open', 'in_progress' => 'In progress', 'resolved' => 'Resolved', 'closed' => 'Closed'], ['list' => true, 'filter' => true, 'default' => 'open']),
        F::ref('assigned_to', 'Assigned to', 'staff'),
        F::textarea('resolution', 'Resolution / reply'),
    ],
    'before_save' => function ($m, $d, $user, $creating) {
        if ($creating) { $m->user_id = $user->id; if (! $user->isAdmin()) $m->status = 'open'; }
        if ($m->isDirty('status') && in_array($m->status, ['resolved', 'closed'], true)) $m->resolved_at = now();
    },
    'after_create' => fn ($m) => AppNotification::send(\App\Models\User::where('role', 'admin')->pluck('id'), 'New '.$m->type.': '.$m->subject, $m->user?->name, 'complaints', $m->id),
],
];
