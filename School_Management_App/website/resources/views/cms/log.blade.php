@extends('website::layouts.cms')
@php use Illuminate\Support\Str; @endphp
@section('title', 'Change log')
@section('content')
<div class="page-head"><div><h1>Change log</h1><p class="muted">Every change made in the website admin: who, when, and what changed (old → new). This list cannot be edited.</p></div></div>
<div class="card flush"><div class="tbl-wrap">
<table class="tbl">
    <thead><tr><th>When</th><th>Who</th><th>What</th><th>Item</th><th>Changes</th></tr></thead>
    <tbody>
    @foreach($log as $l)
        <tr>
            <td class="nowrap muted" data-label="When">{{ \Carbon\Carbon::parse($l->created_at)->format('d M Y, g:i A') }}</td>
            <td data-label="Who">{{ $l->admin_name ?? '—' }}</td>
            <td data-label="What"><span class="pill pill-{{ $l->action }}">{{ ucfirst($l->action) }}</span> <small class="muted">{{ $l->module }}</small></td>
            <td data-label="Item">{{ Str::limit($l->record_title, 50) }}</td>
            <td data-label="Changes">
                @if($l->changes)
                    <ul class="diff">@foreach(array_slice(json_decode($l->changes, true), 0, 6) as $field => [$old, $new])<li><b>{{ str_replace('_', ' ', $field) }}</b>: <del>{{ Str::limit($old ?? '—', 40) }}</del> → <ins>{{ Str::limit($new ?? '—', 40) }}</ins></li>@endforeach</ul>
                @else <span class="muted">—</span> @endif
            </td>
        </tr>
    @endforeach
    </tbody>
</table></div></div>
{{ $log->links('website::cms.partials.pagination') }}
@endsection
