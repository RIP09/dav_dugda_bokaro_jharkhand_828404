<?php

use App\Core\Modules\F;
use App\Models\User;

use App\Modules\Certificates\Models\Certificate;
use App\Modules\Certificates\Models\Promotion;
use App\Modules\Audit\Models\AuditLog;
use App\Core\Locks;

return [
[
    'key' => 'certificates', 'import' => false, 'title' => 'Certificates (TC, Character, Bonafide)', 'title_singular' => 'Certificate saved', 'group' => 'Certificates', 'icon' => 'workspace_premium', 'order' => 90,
    'lockable' => true, 'lock_note' => 'Issued certificates are permanent. Cancel (with reason) and issue a new one to correct.',
    'model' => Certificate::class, 'with' => ['student.user'], 'title_fn' => fn ($m) => (Certificate::TYPES[$m->type] ?? $m->type).' · '.($m->student?->name ?? ''),
    'subtitle' => ['certificate_no', 'status', 'issue_date'], 'search' => ['certificate_no', 'student.admission_no'],
    'scope' => function ($q, $user) { if (! $user->isAdmin() && ! $user->isStaff()) $q->whereIn('student_id', $user->ownStudentIds() ?: [0])->where('status', 'issued'); },
    'roles' => ['view' => ['office', 'family'], 'create' => ['office'], 'update' => ['office'], 'delete' => ['office']],
    'fields' => [
        F::select('type', 'Certificate', Certificate::TYPES, ['required' => true, 'list' => true, 'filter' => true, 'immutable' => true]),
        F::ref('student_id', 'Student', 'students', ['required' => true, 'list' => true, 'immutable' => true]),
        F::text('certificate_no', 'Certificate no.', ['list' => true, 'immutable' => true, 'help' => 'Auto-generated if blank. Permanent.']),
        F::select('status', 'Status', ['draft' => 'Draft', 'issued' => 'Issued', 'cancelled' => 'Cancelled'], ['readonly' => true, 'list' => true, 'filter' => true]),
        F::date('issue_date', 'Issue date', ['list' => true]),
        F::text('purpose', 'Purpose (bonafide)', ['help' => 'e.g. bank account, scholarship, passport']),
        F::select('conduct', 'Conduct', ['Excellent', 'Very Good', 'Good', 'Satisfactory']),
        F::date('leaving_date', 'Date of leaving (TC)'),
        F::text('last_class', 'Class last studied (TC)'),
        F::text('reason_for_leaving', 'Reason for leaving (TC)'),
        F::text('remarks', 'Remarks'),
        F::text('cancel_reason', 'Cancel reason', ['readonly' => true]),
    ],
    'before_save' => function ($m, $d, $user, $creating) {
        if ($creating) {
            $m->status = 'draft';
            $prefix = ['tc' => 'TC', 'character' => 'CC', 'bonafide' => 'BC'][$m->type] ?? 'CERT';
            $m->certificate_no = $m->certificate_no ?: sprintf('%s/%s/%04d', $prefix, now()->format('Y'), (Certificate::where('type', $m->type)->count() + 1));
            if ($m->type === 'tc') { $s = \App\Modules\Students\Models\Student::find($m->student_id); $m->last_class = $m->last_class ?: $s?->class_label; }
        }
    },
    'actions' => [
        'issue' => ['label' => 'Issue (final)', 'kind' => 'post', 'roles' => [], 'when' => fn ($m) => $m->status === 'draft',
            'confirm' => 'Once issued the certificate is permanent and cannot be edited. Issue now?',
            'run' => function ($m, $r, $u) {
                $m->update(['status' => 'issued', 'issue_date' => $m->issue_date ?? now()->toDateString(), 'issued_by' => $u->id]);
                if ($m->type === 'tc') Locks::bypass(fn () => $m->student->update(['status' => 'left']));
                return ['message' => 'Certificate '.$m->certificate_no.' issued'];
            }],
        'cancel' => ['label' => 'Cancel certificate', 'kind' => 'post', 'roles' => [], 'reason' => true, 'when' => fn ($m) => $m->status === 'issued',
            'run' => function ($m, $r) {
                Locks::bypass(fn () => $m->update(['status' => 'cancelled', 'cancel_reason' => $r->input('reason')]));
                AuditLog::record('cancel', $m, ['status' => ['old' => 'issued', 'new' => 'cancelled'], 'reason' => ['old' => null, 'new' => $r->input('reason')]]);
                return ['message' => 'Certificate cancelled (kept on record)'];
            }],
        'print' => ['label' => 'Print / PDF', 'kind' => 'pdf', 'doc' => 'certificate', 'roles' => ['office', 'family']],
    ],
],
[
    'key' => 'promotions', 'title' => 'Promotion History', 'group' => 'Certificates', 'icon' => 'trending_up', 'order' => 91,
    'lockable' => true, 'lock_note' => 'Class/roll history is permanent.', 'no_create' => true, 'no_update' => true, 'no_delete' => true,
    'model' => Promotion::class, 'with' => ['student.user'], 'title_fn' => fn ($m) => $m->student?->name ?? 'Student',
    'subtitle' => ['from_class_id', 'to_class_id', 'result'], 'search' => ['student.admission_no'],
    'roles' => ['view' => ['office', 'teacher']],
    'fields' => [
        F::ref('student_id', 'Student', 'students', ['list' => true, 'filter' => true]),
        F::ref('from_academic_year_id', 'From session', 'academic_years', ['list' => true]),
        F::ref('to_academic_year_id', 'To session', 'academic_years'),
        F::ref('from_class_id', 'From class', 'classes', ['list' => true, 'filter' => true]),
        F::ref('from_section_id', 'From section', 'sections'),
        F::ref('to_class_id', 'To class', 'classes', ['list' => true]),
        F::ref('to_section_id', 'To section', 'sections'),
        F::number('old_roll_no', 'Old roll'),
        F::number('new_roll_no', 'New roll'),
        F::select('result', 'Result', ['promoted', 'detained', 'passed_out'], ['list' => true, 'filter' => true]),
    ],
],
];
