@extends('website::layouts.cms')
@section('title', $title)
@section('content')
<div class="page-head">
    <div><h1>{{ $title }}</h1><p class="muted">{{ $box === 'enquiries' ? 'Sent by parents from the Admissions page of the website. Each one is also copied to the school-management system.' : 'Sent from the Contact page of the website.' }}</p></div>
    <a class="btn" href="{{ route('webadmin.inbox.export', $box) }}">@icon('download','ic',18) Download Excel (CSV)</a>
</div>
<form class="filters" method="get">
    <div class="searchbox">@icon('search','ic',18)<input type="search" name="q" value="{{ request('q') }}" placeholder="Search name, phone, email…"></div>
    <select name="status" onchange="this.form.submit()">
        <option value="">All</option>
        <option value="new" @selected(request('status') === 'new')>New</option>
        <option value="handled" @selected(request('status') === 'handled')>Handled</option>
    </select>
    <button class="btn">Search</button>
</form>
<div class="card flush">
    <div class="tbl-wrap">
    <table class="tbl">
        <thead><tr>
            @if($box === 'enquiries')<th>Child</th><th>Class</th><th>Parent</th>@else<th>Name</th><th>Subject</th>@endif
            <th>Phone</th><th>Status</th><th>Received</th><th></th>
        </tr></thead>
        <tbody>
        @forelse($items as $m)
            <tr class="{{ $m->status === 'new' ? 'unread' : '' }}">
                @if($box === 'enquiries')
                    <td data-label="Child"><a class="strong" href="{{ route('webadmin.inbox.show', [$box, $m->id]) }}">{{ $m->student_name }}</a></td>
                    <td data-label="Class">{{ $m->class_sought }}</td>
                    <td data-label="Parent">{{ $m->parent_name }}</td>
                @else
                    <td data-label="Name"><a class="strong" href="{{ route('webadmin.inbox.show', [$box, $m->id]) }}">{{ $m->name }}</a></td>
                    <td data-label="Subject">{{ \Illuminate\Support\Str::limit($m->subject ?: $m->message, 50) }}</td>
                @endif
                <td data-label="Phone">@if($m->phone)<a href="tel:{{ $m->phone }}">{{ $m->phone }}</a>@else<span class="muted">—</span>@endif</td>
                <td data-label="Status"><span class="pill {{ $m->status === 'new' ? 'pill-new' : 'pill-ok' }}">{{ $m->status === 'new' ? 'New' : 'Handled' }}</span></td>
                <td data-label="Received" class="muted nowrap">{{ $m->created_at->format('d M Y, g:i A') }}</td>
                <td class="actions"><a class="icon-btn" href="{{ route('webadmin.inbox.show', [$box, $m->id]) }}" title="Open">@icon('eye','ic',17)</a></td>
            </tr>
        @empty
            <tr><td colspan="7" class="empty">Nothing here yet.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
</div>
{{ $items->links('website::cms.partials.pagination') }}
@endsection
