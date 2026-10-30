@php
    use Website\Support\Media; use Website\Support\Registry; use Website\Support\Settings;
    $me = auth('webadmin')->user();
    $cur = request()->route('key');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Dashboard') · Website Admin</title>
    <link rel="icon" href="{{ Media::url(Settings::get('favicon'), 'img/favicon.png') }}">
    <link rel="stylesheet" href="{{ Media::asset('css/cms.css') }}">
    @stack('head')
</head>
<body>
<div class="cms">
    <aside class="side" id="side">
        <a class="side-brand" href="{{ route('webadmin.dashboard') }}">
            <img src="{{ Media::url(Settings::get('logo'), 'img/logo.webp') }}" alt="" width="38" height="38">
            <span><strong>Website Admin</strong><small>{{ Settings::get('site_short_name', 'DAV Dugda') }}</small></span>
        </a>
        <nav class="side-nav">
            <a class="{{ request()->routeIs('webadmin.dashboard') ? 'on' : '' }}" href="{{ route('webadmin.dashboard') }}">@icon('layout-dashboard','ic',18) Dashboard</a>
            <div class="side-label">Inbox</div>
            <a class="{{ request()->is('website-admin/inbox/enquiries*') ? 'on' : '' }}" href="{{ route('webadmin.inbox', 'enquiries') }}">@icon('inbox','ic',18) Admission enquiries @if($cmsCounts['enquiries'])<span class="badge">{{ $cmsCounts['enquiries'] }}</span>@endif</a>
            <a class="{{ request()->is('website-admin/inbox/messages*') ? 'on' : '' }}" href="{{ route('webadmin.inbox', 'messages') }}">@icon('mail','ic',18) Contact messages @if($cmsCounts['messages'])<span class="badge">{{ $cmsCounts['messages'] }}</span>@endif</a>
            @foreach(Registry::grouped() as $group => $resources)
                <div class="side-label">{{ $group }}</div>
                @foreach($resources as $r)
                    <a class="{{ $cur === $r::$key ? 'on' : '' }}" href="{{ route('webadmin.r.index', $r::$key) }}">@icon($r::$icon,'ic',18) {{ $r::$label }}</a>
                @endforeach
                @if($group === 'Website')
                    <a class="{{ request()->routeIs('webadmin.settings') ? 'on' : '' }}" href="{{ route('webadmin.settings') }}">@icon('settings','ic',18) Site settings</a>
                    <a class="{{ request()->routeIs('webadmin.users.*') ? 'on' : '' }}" href="{{ route('webadmin.users.index') }}">@icon('key','ic',18) Admin accounts</a>
                    <a class="{{ request()->routeIs('webadmin.log') ? 'on' : '' }}" href="{{ route('webadmin.log') }}">@icon('history','ic',18) Change log</a>
                @endif
            @endforeach
        </nav>
    </aside>
    <div class="side-scrim" id="sideScrim"></div>
    <div class="main">
        <header class="bar">
            <button class="bar-menu" id="sideToggle" aria-label="Menu">@icon('menu','ic',22)</button>
            <div class="bar-title">@yield('title', 'Dashboard')</div>
            <a class="bar-btn" href="{{ url('/') }}" target="_blank" rel="noopener">@icon('external-link','ic',16) <span>View website</span></a>
            <details class="bar-user">
                <summary><span class="av">{{ mb_substr($me->name, 0, 1) }}</span><span class="hide-sm">{{ $me->name }}</span>@icon('chevron-down','ic',16)</summary>
                <div class="menu">
                    <small>{{ $me->email }} · {{ $me->isOwner() ? 'Main admin' : 'Editor' }}</small>
                    <a href="{{ route('webadmin.account') }}">@icon('user','ic',16) My account & password</a>
                    <form method="post" action="{{ route('webadmin.logout') }}">@csrf<button type="submit">@icon('log-out','ic',16) Sign out</button></form>
                </div>
            </details>
        </header>
        <main class="content">
            @if(session('ok'))<div class="flash ok" role="status">@icon('circle-check','ic',18) {{ session('ok') }}</div>@endif
            @if(session('err'))<div class="flash err" role="alert">@icon('lock','ic',18) {{ session('err') }}</div>@endif
            @if($errors->any())<div class="flash err" role="alert">@icon('x','ic',18) Please fix the highlighted fields below.</div>@endif
            @yield('content')
            <footer class="cms-foot">© {{ date('Y') }} DAV Public School, Dugda. All rights reserved. · Affiliated to DAV CMC (DAV College Managing Committee, New Delhi) and DAV CAE (DAV Centre for Academic Excellence) · CBSE Affiliation No. 3430146</footer>
        </main>
    </div>
</div>
<script src="{{ Media::asset('js/cms.js') }}" defer></script>
@stack('scripts')
</body>
</html>
