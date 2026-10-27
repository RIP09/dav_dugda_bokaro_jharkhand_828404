@extends('pdf.layout', ['title' => 'ID Cards'])
@section('header')@endsection
@section('content')
<table style="width:100%;border-collapse:separate;border-spacing:10px">
@foreach($people->chunk(2) as $row)<tr>
@foreach($row as $p)
<td style="width:50%;border:2px solid #0b2a6f;border-radius:10px;padding:0;vertical-align:top">
  <div style="background:#0b2a6f;color:#fff;padding:6px 8px;border-radius:8px 8px 0 0"><b style="font-size:12px">{{ $school['name'] }}</b><br><span style="font-size:8px">{{ $school['address'] }}</span></div>
  <table style="width:100%;padding:6px"><tr>
   <td style="width:72px;vertical-align:top">@if($p['photo'])<img src="{{ $p['photo'] }}" style="width:64px;height:76px;border:1px solid #ccc">@else<div style="width:64px;height:76px;background:#e8ecf6;text-align:center;line-height:76px;color:#0b2a6f;font-size:22px;font-weight:bold">{{ mb_substr($p['name'], 0, 1) }}</div>@endif</td>
   <td style="font-size:9.5px;vertical-align:top"><b style="font-size:12px;color:#8b1a1a">{{ $p['name'] }}</b><br>
     @foreach($p['lines'] as $k => $v)<span class="muted">{{ $k }}:</span> {{ $v }}<br>@endforeach</td></tr></table>
  <div style="font-size:8px;padding:0 8px 6px;text-align:right">{{ $school['principal_name'] }}, Principal &nbsp; · &nbsp; {{ $p['kind'] }} · {{ $school['current_session'] }}</div>
</td>
@endforeach
@if($row->count() < 2)<td></td>@endif
</tr>@endforeach
</table>
@endsection
