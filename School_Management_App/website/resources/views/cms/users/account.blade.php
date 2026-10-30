@extends('website::layouts.cms')
@section('title', 'My account')
@section('content')
<div class="page-head"><div><h1>My account & password</h1><p class="muted">Change your name, email or password. Please change the demo password the first time you sign in.</p></div></div>
<form method="post" action="{{ route('webadmin.account.update') }}" class="card form-grid narrow">
    @csrf
    <div class="fld {{ $errors->has('name') ? 'bad' : '' }}"><label>Name</label><input name="name" value="{{ old('name', $user->name) }}" required></div>
    <div class="fld {{ $errors->has('email') ? 'bad' : '' }}"><label>Email</label><input type="email" name="email" value="{{ old('email', $user->email) }}" required>@error('email')<small class="err">{{ $message }}</small>@enderror</div>
    <div class="fld {{ $errors->has('password') ? 'bad' : '' }}"><label>New password (leave empty to keep the current one)</label><input type="password" name="password" autocomplete="new-password" minlength="8">@error('password')<small class="err">{{ $message }}</small>@enderror</div>
    <div class="fld"><label>Type the new password again</label><input type="password" name="password_confirmation" autocomplete="new-password"></div>
    <div class="fld wide {{ $errors->has('current_password') ? 'bad' : '' }}"><label>Your current password (to confirm it's you) <em>*</em></label><input type="password" name="current_password" required autocomplete="current-password">@error('current_password')<small class="err">{{ $message }}</small>@enderror</div>
    <div class="form-actions wide"><button class="btn primary">@icon('check','ic',18) Save</button></div>
</form>
@endsection
