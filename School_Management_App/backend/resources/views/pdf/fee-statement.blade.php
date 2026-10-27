@extends('pdf.layout', ['title' => 'Fee Statement'])
@section('content')
<div class="doc-title">Fee Statement</div>
<p><b>{{ $st->name }}</b> · {{ $st->class_label }} · Adm. No. {{ $st->admission_no }} · Father: {{ $st->father_name }}</p>
<table class="grid"><tr><th>Invoice</th><th>Title</th><th>Due</th><th class="right">Amount</th><th class="right">Concession</th><th class="right">Paid</th><th class="right">Balance</th><th>Status</th></tr>
@foreach($invoices as $i)<tr><td>{{ $i->invoice_no }}</td><td>{{ $i->title }}</td><td>{{ $i->due_date?->format('d M Y') }}</td><td class="right">{{ number_format($i->amount + $i->fine, 2) }}</td><td class="right">{{ number_format($i->discount, 2) }}</td><td class="right">{{ number_format($i->paid, 2) }}</td><td class="right">{{ number_format($i->balance, 2) }}</td><td>{{ ucfirst($i->status) }}</td></tr>@endforeach
<tr><td colspan="6" class="right"><b>Total balance due</b></td><td class="right big">₹ {{ number_format($invoices->sum('balance'), 2) }}</td><td></td></tr></table>
<h4>Receipts</h4>
<table class="grid"><tr><th>Receipt</th><th>Date</th><th>Mode</th><th class="right">Amount</th><th>Status</th></tr>
@foreach($payments as $p)<tr><td>{{ $p->receipt_no }}</td><td>{{ \Carbon\Carbon::parse($p->paid_on)->format('d M Y') }}</td><td>{{ strtoupper($p->method) }}</td><td class="right">{{ number_format($p->amount, 2) }}</td><td>{{ $p->status === 'cancelled' ? 'CANCELLED' : 'Valid' }}</td></tr>@endforeach</table>
@include('pdf.partials.sign', ['left' => 'Accounts', 'middle' => null])
@endsection
