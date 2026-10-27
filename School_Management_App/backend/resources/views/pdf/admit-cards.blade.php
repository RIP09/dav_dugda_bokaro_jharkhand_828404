@extends('pdf.layout', ['title' => 'Admit Card'])
@section('content')
@foreach($cards as $c)
<div class="doc-title">Admit Card · {{ $exam->name }}</div>
<table style="width:100%"><tr><td><table class="kv">
<tr><td class="k">Name</td><td><b>{{ $c['student']->name }}</b></td></tr><tr><td class="k">Class</td><td>{{ $c['student']->class_label }}</td></tr>
<tr><td class="k">Roll No.</td><td><b>{{ $c['student']->roll_no }}</b></td></tr><tr><td class="k">Admission No.</td><td>{{ $c['student']->admission_no }}</td></tr>
<tr><td class="k">Father's name</td><td>{{ $c['student']->father_name }}</td></tr></table></td>
<td class="right" style="vertical-align:top">@if($c['photo'])<img src="{{ $c['photo'] }}" style="height:85px;border:1px solid #ccc">@else<div style="display:inline-block;width:70px;height:85px;border:1px dashed #999;text-align:center;line-height:85px;color:#999">Photo</div>@endif</td></tr></table>
<table class="grid" style="margin-top:6px"><tr><th>Date</th><th>Day</th><th>Subject</th><th>Time</th><th>Invigilator sign</th></tr>
@foreach($c['schedule'] as $sc)<tr><td>{{ $sc->exam_date?->format('d M Y') }}</td><td>{{ $sc->exam_date?->format('l') }}</td><td>{{ $sc->subject?->name }}</td><td>{{ $sc->start_time }}–{{ $sc->end_time }}</td><td></td></tr>@endforeach</table>
<p class="muted" style="font-size:9px">Bring this admit card to every exam. Reach 15 minutes early. Mobile phones are not allowed.</p>
@include('pdf.partials.sign', ['left' => 'Class Teacher', 'middle' => 'Exam In-charge'])
@if(!$loop->last)<div class="pagebreak"></div>@endif
@endforeach
@endsection
