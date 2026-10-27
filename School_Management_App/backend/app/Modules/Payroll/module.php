<?php

use App\Core\Modules\F;
use App\Models\User;

use App\Modules\Payroll\Models\SalaryStructure;
use App\Modules\Payroll\Models\Payslip;
use App\Modules\Audit\Models\AuditLog;

return [
[
    'key' => 'salary_structures', 'title' => 'Salary Structure', 'group' => 'Payroll', 'icon' => 'request_quote', 'order' => 120,
    'model' => SalaryStructure::class, 'with' => ['user'], 'title_fn' => fn ($m) => $m->user?->name ?? 'Staff',
    'subtitle' => ['basic', 'net_salary'], 'search' => ['user.name'],
    'scope' => function ($q, $user) { if (! in_array($user->role, ['admin', 'accountant'], true)) $q->where('user_id', $user->id); },
    'roles' => ['view' => ['employees'], 'create' => ['accountant'], 'update' => ['accountant'], 'delete' => ['accountant']],
    'fields' => [
        F::ref('user_id', 'Staff', 'staff', ['required' => true, 'list' => true, 'unique' => true]),
        F::money('basic', 'Basic', ['required' => true, 'list' => true]),
        F::money('hra', 'HRA'), F::money('da', 'DA'), F::money('ta', 'TA'), F::money('other_allowance', 'Other allowance'),
        F::money('pf', 'PF (deduction)'), F::money('esi', 'ESI (deduction)'), F::money('tds', 'TDS (deduction)'), F::money('other_deduction', 'Other deduction'),
        F::money('net_salary', 'Net salary', ['readonly' => true, 'virtual' => true, 'list' => true, 'get' => fn ($m) => round($m->grossAmount() - $m->deductionAmount(), 2)]),
        F::text('bank_name', 'Bank'), F::text('account_no', 'Account no.'), F::text('ifsc', 'IFSC'), F::text('pan', 'PAN'),
    ],
],
[
    'key' => 'payslips', 'title' => 'Salary Slips', 'group' => 'Payroll', 'icon' => 'receipt', 'order' => 121, 'lockable' => true,
    'lock_note' => 'A salary slip becomes permanent once marked paid.', 'no_create' => true,
    'model' => Payslip::class, 'with' => ['user'], 'title_fn' => fn ($m) => ($m->user?->name ?? 'Staff').' · '.$m->month,
    'subtitle' => ['payslip_no', 'net', 'status'], 'search' => ['payslip_no', 'user.name'], 'sort' => '-month',
    'scope' => function ($q, $user) { if (! in_array($user->role, ['admin', 'accountant'], true)) $q->where('user_id', $user->id); },
    'roles' => ['view' => ['employees'], 'update' => ['accountant'], 'delete' => ['accountant']],
    'fields' => [
        F::text('payslip_no', 'Slip no.', ['readonly' => true, 'list' => true]),
        F::ref('user_id', 'Staff', 'staff', ['readonly' => true, 'list' => true, 'filter' => true]),
        F::text('month', 'Month', ['readonly' => true, 'list' => true, 'filter' => true]),
        F::number('working_days', 'Working days'),
        F::number('present_days', 'Days present'),
        F::money('gross', 'Gross (₹)', ['readonly' => true, 'list' => true]),
        F::money('deductions', 'Deductions (₹)', ['readonly' => true]),
        F::money('net', 'Net pay (₹)', ['readonly' => true, 'list' => true]),
        F::json('breakdown', 'Breakdown'),
        F::select('status', 'Status', ['draft' => 'Draft', 'paid' => 'Paid'], ['readonly' => true, 'list' => true, 'filter' => true]),
        F::date('paid_on', 'Paid on', ['readonly' => true]),
        F::text('remarks', 'Remarks'),
    ],
    'after_save' => function ($m) { app(\App\Modules\Payroll\Http\PayrollController::class)->recalc($m); },
    'actions' => [
        'pay' => ['label' => 'Mark as paid', 'kind' => 'post', 'roles' => ['accountant'], 'when' => fn ($m) => $m->status === 'draft',
            'inputs' => [F::select('mode', 'Paid by', ['bank' => 'Bank transfer', 'cash' => 'Cash', 'cheque' => 'Cheque', 'upi' => 'UPI'], ['default' => 'bank'])],
            'confirm' => 'After marking paid, the slip becomes permanent. Continue?',
            'run' => function ($m, $r) { $m->update(['status' => 'paid', 'paid_on' => now()->toDateString(), 'mode' => $r->input('mode', 'bank')]); return ['message' => 'Marked paid. Slip is now locked.']; }],
        'slip' => ['label' => 'Salary slip (PDF)', 'kind' => 'pdf', 'doc' => 'salary-slip', 'roles' => ['employees']],
    ],
],
];
