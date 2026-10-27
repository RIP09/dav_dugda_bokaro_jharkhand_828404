@extends('pdf.layout', ['title' => 'Fee Receipt'])
@section('content')
@foreach(['School copy', 'Parent copy'] as $copy)
<div class="doc-title">Fee Receipt <span class="muted" style="font-size:10px">({{ $copy }})</span></div>
@if($p->status === 'cancelled')<div class="center"><span class="stamp">CANCELLED</span><div class="muted">Reason: {{ $p->cancel_reason }}</div></div>@endif
<table class="kv" style="width:100%"><tr>
 <td class="k">Receipt No.</td><td><b>{{ $p->receipt_no }}</b></td><td class="k">Date</td><td>{{ \Carbon\Carbon::parse($p->paid_on)->format('d M Y') }}</td></tr>
 <tr><td class="k">Student</td><td><b>{{ $st?->name }}</b></td><td class="k">Admission No.</td><td>{{ $st?->admission_no }}</td></tr>
 <tr><td class="k">Class</td><td>{{ $st?->class_label }}</td><td class="k">Father's name</td><td>{{ $st?->father_name }}</td></tr>
</table>
<table class="grid" style="margin-top:8px">
 <tr><th>Particulars</th><th>Invoice</th><th class="right">Amount (₹)</th></tr>
 <tr><td>{{ $inv?->title }}</td><td>{{ $inv?->invoice_no }}</td><td class="right">{{ number_format($p->amount, 2) }}</td></tr>
 <tr><td colspan="2" class="right"><b>Total received</b></td><td class="right big">₹ {{ number_format($p->amount, 2) }}</td></tr>
</table>
<p>Amount in words: <b>{{ $words }}</b><br>Mode: {{ strtoupper($p->method) }}{{ $p->reference ? ' · Ref: '.$p->reference : '' }} · Balance on this invoice after payment: ₹ {{ number_format($inv?->balance ?? 0, 2) }}</p>
@include('pdf.partials.sign', ['left' => 'Received by: '.($p->receiver?->name ?? 'Accounts'), 'middle' => null])
@if($loop->first)<div style="border-top:1px dashed #999;margin:18px 0 8px"></div>@endif
@endforeach
@endsection
