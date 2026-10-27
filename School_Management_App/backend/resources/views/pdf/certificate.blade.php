@extends('pdf.layout', ['title' => $title])
@section('content')
<table style="width:100%"><tr><td class="muted">No.: <b>{{ $c->certificate_no }}</b></td><td class="right muted">Date: {{ ($c->issue_date ?? now())->format('d M Y') }}</td></tr></table>
<div class="doc-title" style="font-size:18px;margin-top:14px">{{ $title }}</div>
@if($c->status === 'cancelled')<div class="center"><span class="stamp">CANCELLED</span><div class="muted">{{ $c->cancel_reason }}</div></div>@elseif($c->status === 'draft')<div class="center muted">DRAFT — not issued yet</div>@endif
@php($st = $c->student)
@php($he = ($st->user?->gender === 'female') ? ['She', 'her', 'daughter'] : ['He', 'his', 'son'])
@if($c->type === 'tc')
<table class="grid" style="margin-top:10px">
@foreach([
 'Name of the student' => $st->name, 'Admission No.' => $st->admission_no, "Father's name" => $st->father_name, "Mother's name" => $st->mother_name,
 'Date of birth' => $st->user?->dob?->format('d M Y'), 'Nationality' => $st->nationality ?: 'Indian', 'Category' => $st->category,
 'Date of admission' => $st->admission_date?->format('d M Y'), 'Class in which last studied' => $c->last_class ?: $st->class_label,
 'Date of leaving' => $c->leaving_date?->format('d M Y'), 'Reason for leaving' => $c->reason_for_leaving, 'General conduct' => $c->conduct ?: 'Good',
 'Whether qualified for promotion' => 'As per school records', 'Fee dues' => 'All dues cleared' , 'Remarks' => $c->remarks,
] as $k => $v)<tr><td style="width:45%">{{ $loop->iteration }}. {{ $k }}</td><td><b>{{ $v }}</b></td></tr>@endforeach
</table>
@elseif($c->type === 'character')
<p style="font-size:13px;line-height:1.9;margin-top:18px;text-align:justify">This is to certify that <b>{{ $st->name }}</b>, {{ $he[2] }} of <b>{{ $st->father_name }}</b>, Admission No. <b>{{ $st->admission_no }}</b>, has been a student of this school{{ $st->admission_date ? ' since '.$st->admission_date->format('d M Y') : '' }} and is studying / has studied in <b>{{ $c->last_class ?: $st->class_label }}</b>. During this period {{ strtolower($he[0]) }} bore a <b>{{ $c->conduct ?: 'good' }}</b> moral character and conduct. To the best of my knowledge {{ strtolower($he[0]) }} has not taken part in any activity against the school or the nation.</p>
<p style="font-size:13px">We wish {{ $he[1] === 'her' ? 'her' : 'him' }} every success in life. {{ $c->remarks }}</p>
@else
<p style="font-size:13px;line-height:1.9;margin-top:18px;text-align:justify">This is to certify that <b>{{ $st->name }}</b>, {{ $he[2] }} of <b>{{ $st->father_name }}</b> and <b>{{ $st->mother_name }}</b>, Admission No. <b>{{ $st->admission_no }}</b>, is a bonafide student of this school, studying in <b>{{ $st->class_label }}</b> in the session {{ $school['current_session'] }}. {{ $he[0] }} was born on <b>{{ $st->user?->dob?->format('d M Y') ?? '—' }}</b> as per school records.</p>
<p style="font-size:13px">This certificate is issued on request for the purpose of <b>{{ $c->purpose ?: 'official use' }}</b>.</p>
@endif
@include('pdf.partials.sign', ['left' => 'Prepared by', 'middle' => 'School seal'])
@endsection
