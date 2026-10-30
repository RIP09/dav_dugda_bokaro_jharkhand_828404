@extends('website::layouts.cms')
@section('title', 'Admin accounts')
@section('content')
<div class="page-head">
    <div><h1>Website admin accounts</h1><p class="muted">People who can sign in to this website admin panel. These logins are separate from the school-management apps.</p></div>
    @if($me->isOwner())<a class="btn primary" href="{{ route('webadmin.users.create') }}">@icon('plus','ic',18) Add a person</a>@endif
</div>
<div class="card flush"><div class="tbl-wrap">
<table class="tbl">
    <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Last sign-in</th><th></th></tr></thead>
    <tbody>
    @foreach($users as $u)
        <tr>
            <td data-label="Name"><strong>{{ $u->name }}</strong> @if($u->id === $me->id)<span class="pill">You</span>@endif</td>
            <td data-label="Email">{{ $u->email }}</td>
            <td data-label="Role">{{ $u->isOwner() ? 'Main admin' : 'Editor' }}</td>
            <td data-label="Status"><span class="pill {{ $u->is_active ? 'pill-ok' : 'pill-off' }}">{{ $u->is_active ? 'Active' : 'Blocked' }}</span></td>
            <td data-label="Last sign-in" class="muted">{{ $u->last_login_at?->diffForHumans() ?? 'Never' }}</td>
            <td class="actions">@if($me->isOwner())<a class="icon-btn" href="{{ route('webadmin.users.edit', $u) }}" title="Edit">@icon('pencil','ic',17)</a>@endif</td>
        </tr>
    @endforeach
    </tbody>
</table></div></div>
<p class="muted small mt-s">Main admins can add or block people. Editors can change all website content but not admin accounts. To change your own password use <a href="{{ route('webadmin.account') }}">My account</a>.</p>
@endsection
