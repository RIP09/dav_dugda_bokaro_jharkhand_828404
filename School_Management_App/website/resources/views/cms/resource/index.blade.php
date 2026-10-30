@extends('website::layouts.cms')
@php use Website\Support\Media; use Illuminate\Support\Str; @endphp
@section('title', $r::$label)
@section('content')
<div class="page-head">
    <div>
        <h1>{{ $r::$label }}</h1>
        @if($r::$help)<p class="muted">{{ $r::$help }}</p>@endif
    </div>
    @if($r::$canCreate)<a class="btn primary" href="{{ route('webadmin.r.create', $r::$key) }}">@icon('plus','ic',18) Add {{ $r::$singular }}</a>@endif
</div>
<form class="filters" method="get">
    <div class="searchbox">@icon('search','ic',18)<input type="search" name="q" value="{{ request('q') }}" placeholder="Search {{ Str::lower($r::$label) }}…"></div>
    @foreach($r::filters() as $col => $opts)
        <select name="{{ $col }}" onchange="this.form.submit()">
            <option value="">All</option>
            @foreach($opts as $v => $l)<option value="{{ $v }}" @selected(request($col) === (string) $v)>{{ $l }}</option>@endforeach
        </select>
    @endforeach
    <button class="btn">Search</button>
    @if(request()->query())<a class="btn ghost" href="{{ route('webadmin.r.index', $r::$key) }}">Clear</a>@endif
</form>
<div class="card flush">
    <div class="tbl-wrap">
    <table class="tbl">
        <thead><tr>@foreach($r::columns() as $col => $label)<th>{{ $label }}</th>@endforeach<th>Last edited</th><th></th></tr></thead>
        <tbody>
        @forelse($items as $item)
            <tr>
                @foreach($r::columns() as $col => $label)
                    @php
                        $field = collect($r::fields())->firstWhere('name', $col);
                        $val = data_get($item, $col);
                        $type = $field['type'] ?? null;
                    @endphp
                    <td data-label="{{ $label }}">
                        @if($type === 'image')
                            @if($val)<img class="thumb" src="{{ Media::url($val) }}" alt="" loading="lazy">@else<span class="thumb ph">@icon('image','ic',16)</span>@endif
                        @elseif($type === 'toggle')
                            <span class="pill {{ $val ? 'pill-ok' : 'pill-off' }}">{{ $val ? 'Yes' : 'No' }}</span>
                        @elseif($type === 'file')
                            @if($val)<a href="{{ Media::url($val) }}" target="_blank" rel="noopener">@icon('file-text','ic',16) {{ Media::extension($val) }}</a>@else<span class="muted">—</span>@endif
                        @elseif($type === 'select' || $type === 'icon')
                            {{ $field['options'][$val] ?? $val }}
                        @elseif($val instanceof \DateTimeInterface)
                            {{ $type === 'datetime' ? $val->format('d M Y, g:i A') : $val->format('d M Y') }}
                        @elseif($loop->first)
                            <a class="strong" href="{{ route('webadmin.r.edit', [$r::$key, $item->id]) }}">{{ Str::limit((string) $val, 70) ?: '—' }}</a>
                        @else
                            {{ Str::limit(strip_tags((string) $val), 60) }}
                        @endif
                    </td>
                @endforeach
                <td class="muted small nowrap" data-label="Last edited">{{ $item->updated_at?->diffForHumans() }}@if($item->editor)<br>by {{ $item->editor->name }}@endif</td>
                <td class="actions">
                    @if($url = $r::publicUrl($item))<a class="icon-btn" href="{{ $url }}" target="_blank" rel="noopener" title="View on website">@icon('eye','ic',17)</a>@endif
                    <a class="icon-btn" href="{{ route('webadmin.r.edit', [$r::$key, $item->id]) }}" title="Edit">@icon('pencil','ic',17)</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="{{ count($r::columns()) + 2 }}" class="empty">Nothing here yet. @if($r::$canCreate)<a href="{{ route('webadmin.r.create', $r::$key) }}">Add the first {{ $r::$singular }}</a>.@endif</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
</div>
{{ $items->links('website::cms.partials.pagination') }}
@endsection
