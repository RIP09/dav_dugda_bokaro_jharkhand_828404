@extends('pdf.layout', ['title' => 'Attendance Register'])
@section('content')
<style>@page { size: A4 landscape; } table.grid th, table.grid td { padding: 2px 3px; font-size: 8px; }</style>
<div class="doc-title">Attendance Register · {{ $r['section']['label'] }} · {{ $r['month_label'] }}</div>
<table class="grid"><tr><th>Roll</th><th>Name</th>@foreach($r['days'] as $d)<th class="center" style="{{ $d['sunday'] ? 'background:#fde7e7' : '' }}">{{ $d['day'] }}</th>@endforeach<th>P</th><th>A</th><th>%</th></tr>
@foreach($r['students'] as $s)<tr><td>{{ $s['roll_no'] }}</td><td>{{ $s['name'] }}</td>@foreach($r['days'] as $d)<td class="center" style="{{ ($s['days'][$d['date']] ?? '') === 'A' ? 'color:#c62828;font-weight:bold' : '' }}">{{ $s['days'][$d['date']] ?? '' }}</td>@endforeach<td>{{ $s['totals']['present'] + $s['totals']['late'] }}</td><td>{{ $s['totals']['absent'] }}</td><td>{{ $s['percentage'] }}</td></tr>@endforeach
</table>
<p class="muted">P present · A absent · L late · H half day · LV leave</p>
@include('pdf.partials.sign', ['left' => 'Class Teacher', 'middle' => null])
@endsection
