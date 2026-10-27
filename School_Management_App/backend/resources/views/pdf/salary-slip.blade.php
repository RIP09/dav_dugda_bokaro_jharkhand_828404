@extends('pdf.layout', ['title' => 'Salary Slip'])
@section('content')
<div class="doc-title">Salary Slip · {{ \Carbon\Carbon::parse($p->month.'-01')->format('F Y') }}</div>
<table class="kv" style="width:100%"><tr><td class="k">Name</td><td><b>{{ $p->user?->name }}</b></td><td class="k">Slip No.</td><td>{{ $p->payslip_no }}</td></tr>
<tr><td class="k">Employee ID</td><td>{{ $p->user?->staffProfile?->employee_id }}</td><td class="k">Designation</td><td>{{ $p->user?->staffProfile?->designation }}</td></tr>
<tr><td class="k">Working days</td><td>{{ $p->working_days }}</td><td class="k">Days paid</td><td>{{ $p->present_days }}</td></tr>
<tr><td class="k">Bank A/c</td><td>{{ $ss?->bank_name }} {{ $ss?->account_no }}</td><td class="k">PAN</td><td>{{ $ss?->pan }}</td></tr></table>
<table style="width:100%;margin-top:8px"><tr><td style="width:50%;vertical-align:top"><table class="grid"><tr><th>Earnings</th><th class="right">₹</th></tr>
@foreach(($p->breakdown['earnings'] ?? []) as $k => $v)<tr><td>{{ $k }}</td><td class="right">{{ number_format($v, 2) }}</td></tr>@endforeach
<tr><th>Gross</th><th class="right">{{ number_format($p->gross, 2) }}</th></tr></table></td>
<td style="vertical-align:top"><table class="grid"><tr><th>Deductions</th><th class="right">₹</th></tr>
@foreach(($p->breakdown['deductions'] ?? []) as $k => $v)<tr><td>{{ $k }}</td><td class="right">{{ number_format($v, 2) }}</td></tr>@endforeach
<tr><th>Total</th><th class="right">{{ number_format($p->deductions, 2) }}</th></tr></table></td></tr></table>
<p class="big" style="margin-top:8px">Net pay: ₹ {{ number_format($p->net, 2) }} <span class="muted" style="font-size:10px">({{ $words }})</span></p>
<p>Status: <b>{{ strtoupper($p->status) }}</b>{{ $p->paid_on ? ' on '.$p->paid_on->format('d M Y').' by '.strtoupper((string) $p->mode) : '' }}</p>
@include('pdf.partials.sign', ['left' => 'Employee', 'middle' => 'Accountant'])
@endsection
