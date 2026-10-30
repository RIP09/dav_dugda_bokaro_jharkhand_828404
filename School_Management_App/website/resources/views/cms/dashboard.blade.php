@extends('website::layouts.cms')
@section('title', 'Dashboard')
@section('content')
<div class="hello">
    <div>
        <h1>Hello, {{ auth('webadmin')->user()->name }} 👋</h1>
        <p class="muted">Everything on the public website can be changed from here. Pick a section on the left, or start with a common task:</p>
    </div>
</div>
<div class="quick">
    <a href="{{ route('webadmin.r.create', 'notices') }}">@icon('megaphone','ic',22)<span>Post a notice</span></a>
    <a href="{{ route('webadmin.r.create', 'events') }}">@icon('calendar-days','ic',22)<span>Add an event</span></a>
    <a href="{{ route('webadmin.r.index', 'gallery') }}">@icon('image','ic',22)<span>Add photos</span></a>
    <a href="{{ route('webadmin.r.index', 'banners') }}">@icon('images','ic',22)<span>Change home slider</span></a>
    <a href="{{ route('webadmin.r.index', 'pages') }}">@icon('file-pen-line','ic',22)<span>Edit page text</span></a>
    <a href="{{ route('webadmin.settings') }}">@icon('settings','ic',22)<span>Phone, address, logo</span></a>
</div>
@php $todo = collect($checklist)->where('empty', true); @endphp
<section class="card checklist">
    <div class="card-head"><h2>Still to fill in ({{ $todo->count() }} of {{ count($checklist) }})</h2></div>
    <p class="muted">These sections are empty, so they are hidden on the public website. Add the real details and they appear automatically.</p>
    <ul class="check-list">
        @foreach($checklist as $c)
            <li class="{{ $c['empty'] ? 'todo' : 'done' }}"><span class="ck">{{ $c['empty'] ? '○' : '✓' }}</span><a href="{{ $c['url'] }}">{{ $c['label'] }}</a><small class="muted">{{ $c['empty'] ? 'empty — hidden on site' : 'filled' }}</small></li>
        @endforeach
    </ul>
</section>
<div class="tiles">
    @foreach($counts as $label => [$n, $ic, $url])
        <a class="tile" href="{{ $url }}"><span class="tile-ic">@icon($ic,'ic',20)</span><strong>{{ $n }}</strong><small>{{ $label }}</small></a>
    @endforeach
</div>
<div class="grid2">
    <section class="card">
        <div class="card-head"><h2>Latest admission enquiries</h2><a href="{{ route('webadmin.inbox', 'enquiries') }}">Open inbox →</a></div>
        <table class="tbl">
            <tbody>
            @forelse($enquiries as $e)
                <tr onclick="location.href='{{ route('webadmin.inbox.show', ['enquiries', $e->id]) }}'" class="click">
                    <td><strong>{{ $e->student_name }}</strong><br><small class="muted">{{ $e->class_sought }} · {{ $e->parent_name }}</small></td>
                    <td><a href="tel:{{ $e->phone }}">{{ $e->phone }}</a></td>
                    <td><span class="pill {{ $e->status === 'new' ? 'pill-new' : 'pill-ok' }}">{{ $e->status === 'new' ? 'New' : 'Handled' }}</span></td>
                    <td class="muted nowrap">{{ $e->created_at->diffForHumans() }}</td>
                </tr>
            @empty
                <tr><td class="muted">No enquiries yet. They arrive here when parents fill the form on the Admissions page.</td></tr>
            @endforelse
            </tbody>
        </table>
    </section>
    <section class="card">
        <div class="card-head"><h2>Recent changes</h2><a href="{{ route('webadmin.log') }}">Full change log →</a></div>
        <ul class="log">
            @forelse($log as $l)
                <li><span class="dot dot-{{ $l->action }}"></span><span><strong>{{ $l->admin_name ?? 'Website visitor' }}</strong> {{ $l->action }} <em>{{ $l->record_title }}</em> <small class="muted">({{ $l->module }}) · {{ \Carbon\Carbon::parse($l->created_at)->diffForHumans() }}</small></span></li>
            @empty
                <li class="muted">No changes yet.</li>
            @endforelse
        </ul>
    </section>
</div>
@endsection
