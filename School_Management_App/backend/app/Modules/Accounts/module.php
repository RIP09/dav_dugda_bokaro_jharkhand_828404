<?php

use App\Core\Modules\F;
use App\Models\User;

use App\Modules\Accounts\Models\AccountHead;
use App\Modules\Accounts\Models\Transaction;

return [
[
    'key' => 'account_heads', 'title' => 'Account Heads', 'group' => 'Accounts', 'icon' => 'account_tree', 'order' => 110,
    'model' => AccountHead::class, 'label' => 'name', 'subtitle' => ['type'], 'sort' => 'name',
    'roles' => ['view' => ['accountant'], 'create' => ['accountant'], 'update' => ['accountant'], 'delete' => []],
    'fields' => [
        F::text('name', 'Head', ['required' => true, 'list' => true, 'help' => 'Electricity, Salaries, Stationery, Donation, Building fund …']),
        F::select('type', 'Type', ['income' => 'Income', 'expense' => 'Expense'], ['required' => true, 'list' => true, 'filter' => true]),
        F::text('description', 'Description'),
    ],
],
[
    'key' => 'transactions', 'title' => 'Income & Expenses', 'title_singular' => 'Entry saved', 'group' => 'Accounts', 'icon' => 'account_balance_wallet', 'order' => 111,
    'model' => Transaction::class, 'with' => ['head'], 'title_fn' => fn ($m) => ($m->head?->name ?? ucfirst($m->type)).' · ₹'.number_format((float) $m->amount, 2),
    'subtitle' => ['voucher_no', 'date', 'party'], 'search' => ['voucher_no', 'party', 'description', 'reference'], 'sort' => '-date', 'import' => true, 'import_key' => 'voucher_no',
    'roles' => ['view' => ['accountant'], 'create' => ['accountant'], 'update' => ['accountant'], 'delete' => ['accountant']],
    'fields' => [
        F::text('voucher_no', 'Voucher no.', ['list' => true, 'immutable' => true, 'help' => 'Auto-generated if blank.']),
        F::date('date', 'Date', ['required' => true, 'list' => true, 'filter' => true]),
        F::select('type', 'Type', ['income' => 'Income', 'expense' => 'Expense'], ['required' => true, 'list' => true, 'filter' => true]),
        F::ref('account_head_id', 'Head', 'account_heads', ['list' => true, 'filter' => true]),
        F::money('amount', 'Amount (₹)', ['required' => true, 'list' => true]),
        F::select('mode', 'Mode', ['cash', 'upi', 'bank', 'cheque', 'card'], ['default' => 'cash']),
        F::text('party', 'Paid to / received from', ['list' => true]),
        F::text('reference', 'Bill / UTR no.'),
        F::text('description', 'Details'),
        F::ref('created_by', 'Entered by', 'staff', ['readonly' => true]),
    ],
    'before_save' => function ($m, $d, $user, $creating) {
        if ($creating) { $m->created_by = $user->id; $m->voucher_no = $m->voucher_no ?: sprintf('V%s%05d', now()->format('y'), (Transaction::max('id') ?? 0) + 1); }
    },
],
];
