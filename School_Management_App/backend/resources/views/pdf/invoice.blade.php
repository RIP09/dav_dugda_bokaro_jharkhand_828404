@extends('pdf.layout', ['title' => 'Fee Invoice'])
@section('content')
<div class="doc-title">Fee Invoice / Demand Slip</div>
<table class="kv" style="width:100%"><tr><td class="k">Invoice No.</td><td><b>{{ $inv->invoice_no }}</b></td><td class="k">Due date</td><td>{{ $inv->due_date?->format('d M Y') }}</td></tr>
<tr><td class="k">Student</td><td><b>{{ $inv->student?->name }}</b> ({{ $inv->student?->admission_no }})</td><td class="k">Class</td><td>{{ $inv->student?->class_label }}</td></tr></table>
<table class="grid" style="margin-top:8px">
<tr><th>Particulars</th><th class="right">Amount (₹)</th></tr>
<tr><td>{{ $inv->title }}</td><td class="right">{{ number_format($inv->amount, 2) }}</td></tr>
@if($inv->fine > 0)<tr><td>Late fine</td><td class="right">{{ number_format($inv->fine, 2) }}</td></tr>@endif
@if($inv->discount > 0)<tr><td>Less: concession</td><td class="right">- {{ number_format($inv->discount, 2) }}</td></tr>@endif
<tr><td>Paid so far</td><td class="right">- {{ number_format($inv->paid, 2) }}</td></tr>
<tr><td class="right"><b>Balance due</b></td><td class="right big">₹ {{ number_format($inv->balance, 2) }}</td></tr></table>
@include('pdf.partials.sign', ['left' => 'Accounts', 'middle' => null])
@endsection
