<?php

use App\Core\Modules\F;
use App\Models\User;

use App\Modules\Leave\Models\LeaveRequest;


$review = function ($m, $r, $u, string $status) {
    $req = new \Illuminate\Http\Request(['status' => $status, 'note' => $r->input('reason')]);
    $req->setUserResolver(fn () => $u);
    $resp = app(\App\Modules\Leave\Http\LeaveController::class)->review($req, $m);
    if ($resp->getStatusCode() >= 400) abort($resp->getStatusCode(), $resp->getData(true)['message'] ?? 'Not allowed');
    return ['message' => 'Leave '.$status];
};

return [[
    'key' => 'leaves', 'title' => 'Leave Requests', 'group' => 'Attendance', 'icon' => 'beach_access', 'order' => 42,
    'model' => LeaveRequest::class, 'with' => ['applicant', 'student.user'], 'title_fn' => fn ($m) => ($m->student?->name ?? $m->applicant?->name ?? 'Leave').' · '.$m->from_date?->format('d M').'–'.$m->to_date?->format('d M'),
    'subtitle' => ['type', 'status'], 'search' => ['reason'], 'sort' => '-from_date',
    'scope' => function ($q, $user) {
        if ($user->isAdmin()) return;
        $q->where(function ($w) use ($user) {
            $w->where('user_id', $user->id);
            if ($user->role === 'student') $w->orWhereIn('student_id', $user->ownStudentIds() ?: [0]);
            if ($user->role === 'teacher') $w->orWhereHas('student', fn ($s) => $s->whereIn('section_id', $user->teachingSectionIds() ?: [0]));
        });
    },
    'roles' => ['view' => ['all'], 'create' => ['all'], 'update' => ['all'], 'delete' => ['all']],
    'can_edit' => fn ($m, $u) => $u->isAdmin() || ($m->user_id === $u->id && $m->status === 'pending'),
    'can_delete' => fn ($m, $u) => $u->isAdmin() || ($m->user_id === $u->id && $m->status === 'pending'),
    'fields' => [
        F::ref('user_id', 'Applied by', 'staff', ['readonly' => true, 'get' => fn ($m) => $m->user_id]),
        F::ref('student_id', 'For student (parents)', 'students', ['list' => true]),
        F::select('type', 'Type', ['casual' => 'Casual', 'sick' => 'Sick', 'other' => 'Other'], ['required' => true, 'list' => true]),
        F::date('from_date', 'From', ['required' => true, 'list' => true]),
        F::date('to_date', 'To', ['required' => true, 'list' => true]),
        F::textarea('reason', 'Reason', ['required' => true]),
        F::select('status', 'Status', ['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'], ['readonly' => true, 'list' => true, 'filter' => true]),
        F::text('review_note', 'Reviewer note', ['readonly' => true]),
    ],
    'before_save' => function ($m, $d, $user, $creating) { if ($creating) { $m->user_id = $user->id; $m->status = 'pending'; } },
    'actions' => [
        'approve' => ['label' => 'Approve', 'kind' => 'post', 'roles' => ['teacher'], 'when' => fn ($m) => $m->status === 'pending',
            'run' => fn ($m, $r, $u) => $review($m, $r, $u, 'approved')],
        'reject' => ['label' => 'Reject', 'kind' => 'post', 'roles' => ['teacher'], 'reason' => true, 'when' => fn ($m) => $m->status === 'pending',
            'run' => fn ($m, $r, $u) => $review($m, $r, $u, 'rejected')],
    ],
]];
