@extends('pdf.layout', ['title' => $title])
@section('content')
<style>@if(count($columns) > 7) @page { size: A4 landscape; } @endif table.grid th, table.grid td { font-size: 8.5px; padding: 3px 4px; }</style>
<div class="doc-title">{{ $title }}</div>
<table class="grid"><tr>@foreach($columns as $c)<th>{{ $c['label'] }}</th>@endforeach</tr>
@foreach($rows as $r)<tr>@foreach($columns as $c)<td>{{ $r[$c['name']] ?? '' }}</td>@endforeach</tr>@endforeach</table>
<p class="muted">{{ count($rows) }} records</p>
@endsection
