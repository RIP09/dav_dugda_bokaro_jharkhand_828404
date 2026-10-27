@extends('pdf.layout', ['title' => 'Report Card'])
@section('content')
@foreach($cards as $rc)
<div class="doc-title">Report Card · {{ $rc['exam']['name'] ?? '' }}</div>
<table style="width:100%"><tr><td>
<table class="kv"><tr><td class="k">Name</td><td><b>{{ $rc['student']['name'] }}</b></td></tr>
<tr><td class="k">Class</td><td>{{ $rc['student']['class_label'] }}</td></tr>
<tr><td class="k">Admission No.</td><td>{{ $rc['student']['admission_no'] }}</td></tr>
<tr><td class="k">Roll No.</td><td>{{ $rc['student']['roll_no'] }}</td></tr>
<tr><td class="k">Father / Mother</td><td>{{ $rc['student']['father_name'] }} / {{ $rc['student']['mother_name'] }}</td></tr></table></td>
<td class="right" style="vertical-align:top">@if(!empty($rc['photo']))<img src="{{ $rc['photo'] }}" style="height:85px;border:1px solid #ccc">@endif</td></tr></table>
<table class="grid" style="margin-top:8px"><tr><th>Subject</th><th class="right">Marks</th><th class="right">Max</th><th class="center">Grade</th><th class="center">Grade point</th><th>Remark</th></tr>
@foreach($rc['subjects'] as $sub)<tr><td>{{ $sub['subject'] }}</td><td class="right">{{ $sub['is_absent'] ? 'AB' : rtrim(rtrim(number_format((float) $sub['marks_obtained'], 2), '0'), '.') }}</td><td class="right">{{ $sub['max_marks'] }}</td><td class="center">{{ $sub['grade'] }}</td><td class="center">{{ $sub['point'] }}</td><td>{{ $sub['remark'] }}</td></tr>@endforeach
<tr><th>Total</th><th class="right">{{ $rc['total'] }}</th><th class="right">{{ $rc['max_total'] }}</th><th class="center">{{ $rc['grade'] }}</th><th class="center">{{ $rc['gpa'] }}</th><th>{{ $rc['percentage'] }}% · Rank {{ $rc['rank'] }} / {{ $rc['class_strength'] }}</th></tr></table>
<table style="width:100%;margin-top:8px"><tr>
 <td class="box" style="width:50%"><b>Result:</b> <span class="big" style="color:{{ $rc['result'] === 'Pass' ? '#2e7d32' : '#c62828' }}">{{ $rc['result'] }}</span><br><b>Attendance:</b> {{ $rc['attendance'] ?? '—' }}</td>
 <td class="box"><b>Grading scale (CBSE):</b> A1 91–100 · A2 81–90 · B1 71–80 · B2 61–70 · C1 51–60 · C2 41–50 · D 33–40 · E below 33</td></tr></table>
@if(empty($rc['exam']['results_locked']))<p class="muted center">Provisional — results are not yet locked.</p>@endif
@include('pdf.partials.sign', ['left' => 'Class Teacher', 'middle' => 'Parent'])
@if(!$loop->last)<div class="pagebreak"></div>@endif
@endforeach
@endsection
