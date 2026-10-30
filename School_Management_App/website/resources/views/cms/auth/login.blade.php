@php use Website\Support\Media; use Website\Support\Settings; @endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Sign in · Website Admin</title>
    <link rel="icon" href="{{ Media::url(Settings::get('favicon'), 'img/favicon.png') }}">
    <link rel="stylesheet" href="{{ Media::asset('css/cms.css') }}">
</head>
<body class="login-page">
<div class="login-art" aria-hidden="true"><img src="{{ Media::asset('img/photos/front.jpg') }}" alt=""></div>
<main class="login-box">
    <img class="login-logo" src="{{ Media::url(Settings::get('logo'), 'img/logo.webp') }}" alt="" width="72" height="72">
    <h1>Website Admin</h1>
    <p class="muted">{{ Settings::get('site_name', 'DAV Public School, Dugda') }} — edit everything on the public website.</p>
    @if(session('ok'))<div class="flash ok">{{ session('ok') }}</div>@endif
    <form method="post" action="{{ route('webadmin.login.submit') }}" class="stack">
        @csrf
        <label class="fld"><span>Email</span><input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"></label>
        <label class="fld"><span>Password</span><input type="password" name="password" required autocomplete="current-password"></label>
        @error('email')<div class="flash err">{{ $message }}</div>@enderror
        <label class="check"><input type="checkbox" name="remember" value="1"> Keep me signed in on this device</label>
        <button class="btn primary block" type="submit">Sign in</button>
    </form>
    <p class="note">This login is only for the website. It is separate from the school-management apps.</p>
    <a class="back" href="{{ url('/') }}">← Back to the website</a>
    <p class="note" style="margin-top:1.2rem;font-size:.75rem">© {{ date('Y') }} DAV Public School, Dugda. All rights reserved. · Affiliated to DAV CMC and DAV CAE · CBSE Aff. No. 3430146</p>
</main>
</body>
</html>
