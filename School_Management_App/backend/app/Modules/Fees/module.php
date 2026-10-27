<?php

use App\Core\Modules\F;
use App\Models\User;

use App\Modules\Fees\Models\FeeType;
use App\Modules\Fees\Models\FeeStructure;
use App\Modules\Fees\Models\Concession;
use App\Modules\Fees\Models\Invoice;
use App\Modules\Fees\Models\Payment;
use App\Modules\Audit\Models\AuditLog;
use App\Core\Locks;

$familyStudent = function ($q, $user, $col = 'student_id') { if (! $user->isAdmin() && ! $user->isStaff()) $q->whereIn($col, $user->ownStudentIds() ?: [0]); };
$methods = ['cash' => 'Cash', 'upi' => 'UPI', 'card' => 'Card', 'bank' => 'Bank transfer', 'cheque' => 'Cheque', 'online' => 'Online'];

return [
[
    'key' => 'fee_types', 'title' => 'Fee Heads', 'group' => 'Fees', 'icon' => 'category', 'order' => 100,
    'model' => FeeType::class, 'label' => 'name', 'subtitle' => ['default_amount'], 'sort' => 'name',
    'roles' => ['view' => ['office'], 'create' => ['accountant'], 'update' => ['accountant'], 'delete' => []],
    'fields' => [
        F::text('name', 'Fee head', ['required' => true, 'list' => true, 'help' => 'Tuition, Admission, Exam, Transport, Hostel, Computer, Annual charges …']),
        F::text('description', 'Description'),
        F::money('default_amount', 'Default amount (₹)', ['list' => true]),
    ],
],
[
    'key' => 'fee_structures', 'title' => 'Fee Structure (class-wise)', 'group' => 'Fees', 'icon' => 'table_chart', 'order' => 101,
    'model' => FeeStructure::class, 'with' => ['schoolClass', 'feeType'], 'title_fn' => fn ($m) => ($m->schoolClass?->name ?? '').' · '.($m->feeType?->name ?? ''),
    'subtitle' => ['amount', 'frequency'], 'sort' => 'class_id', 'import' => true,
    'roles' => ['view' => ['office', 'family'], 'create' => ['accountant'], 'update' => ['accountant'], 'delete' => ['accountant']],
    'fields' => [
        F::ref('class_id', 'Class', 'classes', ['required' => true, 'list' => true, 'filter' => true]),
        F::ref('fee_type_id', 'Fee head', 'fee_types', ['required' => true, 'list' => true, 'filter' => true]),
        F::money('amount', 'Amount (₹)', ['required' => true, 'list' => true]),
        F::select('frequency', 'How often', ['monthly' => 'Monthly', 'quarterly' => 'Quarterly', 'half_yearly' => 'Half-yearly', 'yearly' => 'Yearly', 'one_time' => 'One time'], ['required' => true, 'list' => true]),
        F::number('due_day', 'Due day of month', ['default' => 10]),
        F::ref('academic_year_id', 'Session', 'academic_years'),
    ],
],
[
    'key' => 'concessions', 'title' => 'Concessions / Scholarships', 'group' => 'Fees', 'icon' => 'volunteer_activism', 'order' => 102,
    'model' => Concession::class, 'with' => ['student.user', 'feeType'], 'title_fn' => fn ($m) => ($m->student?->name ?? '').' · '.($m->type === 'percent' ? (float) $m->value.'%' : '₹'.(float) $m->value),
    'subtitle' => ['fee_type_id', 'reason'], 'search' => ['reason', 'student.admission_no'],
    'scope' => $familyStudent,
    'roles' => ['view' => ['office', 'family'], 'create' => ['accountant'], 'update' => ['accountant'], 'delete' => ['accountant']],
    'fields' => [
        F::ref('student_id', 'Student', 'students', ['required' => true, 'list' => true, 'filter' => true]),
        F::ref('fee_type_id', 'Fee head (blank = all)', 'fee_types', ['list' => true]),
        F::select('type', 'Type', ['percent' => 'Percent %', 'fixed' => 'Fixed ₹'], ['required' => true, 'list' => true]),
        F::number('value', 'Value', ['required' => true, 'list' => true]),
        F::select('reason', 'Reason', ['Sibling', 'Staff ward', 'Merit', 'RTE', 'Financial need', 'Sports', 'Other'], ['list' => true]),
        F::date('valid_till', 'Valid till'),
    ],
],
[
    'key' => 'invoices', 'title' => 'Fee Invoices / Dues', 'title_singular' => 'Invoice saved', 'group' => 'Fees', 'icon' => 'receipt_long', 'order' => 103,
    'model' => Invoice::class, 'with' => ['student.user'], 'title_fn' => fn ($m) => $m->title.' · '.($m->student?->name ?? ''),
    'subtitle' => ['invoice_no', 'balance', 'status', 'due_date'], 'search' => ['invoice_no', 'title', 'student.admission_no'], 'sort' => '-id',
    'scope' => $familyStudent,
    'roles' => ['view' => ['office', 'family'], 'create' => ['accountant'], 'update' => ['accountant'], 'delete' => ['accountant']],
    'can_delete' => fn ($m) => ! $m->payments()->where('status', '!=', 'cancelled')->exists(),
    'filters' => ['due' => fn ($q, $v) => $q->where('status', '!=', 'paid')],
    'fields' => [
        F::text('invoice_no', 'Invoice no.', ['list' => true, 'immutable' => true, 'help' => 'Auto-generated if blank. Permanent.']),
        F::ref('student_id', 'Student', 'students', ['required' => true, 'list' => true, 'filter' => true]),
        F::ref('fee_type_id', 'Fee head', 'fee_types', ['filter' => true]),
        F::text('title', 'Title', ['required' => true, 'list' => true, 'help' => 'e.g. Tuition fee – October 2026']),
        F::text('month', 'Month (YYYY-MM)', ['filter' => true]),
        F::money('amount', 'Amount (₹)', ['required' => true, 'list' => true]),
        F::money('discount', 'Concession (₹)'),
        F::money('fine', 'Late fine (₹)'),
        F::money('paid', 'Paid (₹)', ['readonly' => true, 'list' => true]),
        F::money('balance', 'Balance (₹)', ['readonly' => true, 'virtual' => true, 'list' => true, 'get' => fn ($m) => $m->balance]),
        F::date('due_date', 'Due date', ['list' => true]),
        F::select('status', 'Status', ['unpaid' => 'Unpaid', 'partial' => 'Part paid', 'paid' => 'Paid'], ['readonly' => true, 'list' => true, 'filter' => true]),
        F::ref('academic_year_id', 'Session', 'academic_years', ['readonly' => true]),
    ],
    'validate' => function (array $d, $m) {
        if ($m) {
            $net = ($d['amount'] ?? $m->amount) + ($d['fine'] ?? $m->fine) - ($d['discount'] ?? $m->discount);
            if ($net + 0.001 < $m->paid) throw \Illuminate\Validation\ValidationException::withMessages(['amount' => 'Amount after concession cannot be less than what is already paid (₹'.$m->paid.'). Cancel a receipt first.']);
        }
    },
    'before_save' => function ($m, $d, $user, $creating) {
        if ($creating) {
            $m->invoice_no = $m->invoice_no ?: sprintf('INV%s%05d', now()->format('y'), (Invoice::max('id') ?? 0) + 1);
            $m->academic_year_id = \App\Modules\Academics\Models\AcademicYear::current()?->id;
            $m->paid = 0; $m->status = 'unpaid';
        }
    },
    'after_save' => function ($m, $d, $user, $creating) { if (! $creating) $m->refreshStatus(); },
    'actions' => [
        'collect' => ['label' => 'Collect payment', 'kind' => 'post', 'roles' => ['accountant'], 'when' => fn ($m) => $m->balance > 0,
            'inputs' => [F::money('amount', 'Amount (₹)', ['required' => true]), F::select('method', 'Mode', $methods, ['default' => 'cash']), F::text('reference', 'UTR / cheque no.'), F::date('paid_on', 'Date')],
            'run' => function ($m, $r, $u) {
                $req = new \Illuminate\Http\Request($r->only(['amount', 'method', 'reference', 'paid_on']));
                $req->setUserResolver(fn () => $u);
                $resp = app(\App\Modules\Fees\Http\FeeController::class)->pay($req, $m);
                $body = $resp->getData(true);
                if ($resp->getStatusCode() >= 400) abort($resp->getStatusCode(), $body['message'] ?? 'Could not record payment');
                return ['message' => $body['message'], 'data' => ['payment_id' => $body['data']['payment']['id'], 'receipt_no' => $body['data']['payment']['receipt_no'], 'receipt_pdf' => 'pdf/receipt/'.$body['data']['payment']['id']]];
            }],
        'print' => ['label' => 'Invoice (PDF)', 'kind' => 'pdf', 'doc' => 'invoice', 'roles' => ['office', 'family']],
    ],
],
[
    'key' => 'payments', 'title' => 'Fee Receipts', 'group' => 'Fees', 'icon' => 'payments', 'order' => 104, 'lockable' => true,
    'lock_note' => 'Receipts are permanent. To correct a wrong receipt use “Cancel receipt” (reason needed) — the dues go back on the invoice — then collect again.',
    'no_create' => true, 'no_update' => true, 'no_delete' => true,
    'model' => Payment::class, 'with' => ['invoice.student.user'], 'title_fn' => fn ($m) => $m->receipt_no.' · ₹'.number_format((float) $m->amount, 2),
    'subtitle' => ['student_name', 'paid_on', 'method', 'status'], 'search' => ['receipt_no', 'reference', 'invoice.invoice_no'], 'sort' => '-id',
    'scope' => function ($q, $user) { if (! $user->isAdmin() && ! $user->isStaff()) $q->whereHas('invoice', fn ($i) => $i->whereIn('student_id', $user->ownStudentIds() ?: [0])); },
    'roles' => ['view' => ['office', 'family']],
    'fields' => [
        F::text('receipt_no', 'Receipt no.', ['list' => true, 'immutable' => true]),
        F::ref('invoice_id', 'Invoice', 'invoices', ['list' => true]),
        F::text('student_name', 'Student', ['readonly' => true, 'virtual' => true, 'list' => true, 'get' => fn ($m) => $m->invoice?->student?->name]),
        F::money('amount', 'Amount (₹)', ['list' => true]),
        F::select('method', 'Mode', $methods, ['list' => true, 'filter' => true]),
        F::text('reference', 'Reference'),
        F::date('paid_on', 'Date', ['list' => true, 'filter' => true]),
        F::ref('received_by', 'Received by', 'staff'),
        F::select('status', 'Status', ['valid' => 'Valid', 'cancelled' => 'Cancelled'], ['list' => true, 'filter' => true]),
        F::text('cancel_reason', 'Cancel reason'),
    ],
    'actions' => [
        'receipt' => ['label' => 'Print receipt', 'kind' => 'pdf', 'doc' => 'receipt', 'roles' => ['office', 'family']],
        'cancel' => ['label' => 'Cancel receipt', 'kind' => 'post', 'roles' => ['accountant'], 'reason' => true, 'when' => fn ($m) => $m->status !== 'cancelled',
            'confirm' => 'Cancel this receipt? It stays on record as CANCELLED and the amount goes back to dues.',
            'run' => function ($m, $r, $u) {
                Locks::bypass(fn () => $m->update(['status' => 'cancelled', 'cancelled_at' => now(), 'cancelled_by' => $u->id, 'cancel_reason' => $r->input('reason')]));
                $m->invoice?->refreshStatus();
                AuditLog::record('cancel', $m, ['status' => ['old' => 'valid', 'new' => 'cancelled'], 'reason' => ['old' => null, 'new' => $r->input('reason')]]);
                return ['message' => 'Receipt '.$m->receipt_no.' cancelled; ₹'.number_format((float) $m->amount, 2).' added back to dues'];
            }],
    ],
],
];
