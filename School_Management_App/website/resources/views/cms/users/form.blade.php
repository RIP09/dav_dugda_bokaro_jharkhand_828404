@extends('website::layouts.cms')
@section('title', $user->exists ? 'Edit account' : 'Add a person')
@section('content')
<div class="page-head"><div><a class="crumb" href="{{ route('webadmin.users.index') }}">← Admin accounts</a><h1>{{ $user->exists ? $user->name : 'Add a person' }}</h1></div></div>
<form method="post" action="{{ $user->exists ? route('webadmin.users.update', $user) : route('webadmin.users.store') }}" class="card form-grid">
    @csrf @if($user->exists) @method('PUT') @endif
    <div class="fld {{ $errors->has('name') ? 'bad' : '' }}"><label>Name <em>*</em></label><input name="name" value="{{ old('name', $user->name) }}" required>@error('name')<small class="err">{{ $message }}</small>@enderror</div>
    <div class="fld {{ $errors->has('email') ? 'bad' : '' }}"><label>Email (used to sign in) <em>*</em></label><input type="email" name="email" value="{{ old('email', $user->email) }}" required>@error('email')<small class="err">{{ $message }}</small>@enderror</div>
    <div class="fld"><label>Role</label><select name="role"><option value="editor" @selected(old('role', $user->role) === 'editor')>Editor — can change website content</option><option value="owner" @selected(old('role', $user->role) === 'owner')>Main admin — can also manage accounts</option></select></div>
    @if($user->exists)<div class="fld"><label>Status</label><label class="switch"><input type="checkbox" name="is_active" value="1" @checked($user->is_active)><span></span><b>{{ $user->is_active ? 'Active' : 'Blocked' }}</b></label></div>@endif
    <div class="fld {{ $errors->has('password') ? 'bad' : '' }}"><label>{{ $user->exists ? 'New password (leave empty to keep)' : 'Password' }} @if(!$user->exists)<em>*</em>@endif</label><input type="password" name="password" autocomplete="new-password" @if(!$user->exists) required @endif minlength="8">@error('password')<small class="err">{{ $message }}</small>@enderror<small class="help">At least 8 characters.</small></div>
    <div class="fld"><label>Type the password again</label><input type="password" name="password_confirmation" autocomplete="new-password"></div>
    <div class="form-actions wide"><button class="btn primary">@icon('check','ic',18) Save</button><a class="btn ghost" href="{{ route('webadmin.users.index') }}">Cancel</a></div>
</form>
@if($user->exists && $user->id !== auth('webadmin')->id())
<form method="post" action="{{ route('webadmin.users.destroy', $user) }}" data-confirm="Delete this account?" class="mt-s">@csrf @method('DELETE')<button class="btn danger ghost">@icon('trash-2','ic',16) Delete account</button></form>
@endif
@endsection
