@php
    use Website\Support\Media;
    use Website\Support\SiteData;
    $siteName = $s['site_name'] ?? 'DAV Public School, Dugda';
    $pageTitle = trim($__env->yieldContent('title'));
    $pageTitle = $pageTitle ? html_entity_decode($pageTitle, ENT_QUOTES) : $pageTitle;
    $fullTitle = $pageTitle ? $pageTitle.' · '.$siteName : $siteName.' — '.($s['tagline'] ?? 'CBSE School');
    $desc = trim($__env->yieldContent('description')) ?: ($s['meta_description'] ?? '');
    $ogImage = trim($__env->yieldContent('og_image')) ?: Media::url($s['og_image'] ?? null, 'img/photos/front.jpg');
    $logo = Media::url($s['logo'] ?? null, 'img/logo.webp');
    $primary = preg_match('/^#[0-9a-f]{6}$/i', $s['color_primary'] ?? '') ? $s['color_primary'] : '#7A1631';
    $accent = preg_match('/^#[0-9a-f]{6}$/i', $s['color_accent'] ?? '') ? $s['color_accent'] : '#F2A007';
    $socials = collect(['facebook' => 'Facebook', 'instagram' => 'Instagram', 'youtube' => 'YouTube', 'x' => 'X'])->filter(fn ($l, $k) => ! empty($s[$k]));
@endphp
<!DOCTYPE html>
<html lang="en" class="no-js">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ $fullTitle }}</title>
    <meta name="description" content="{{ \Illuminate\Support\Str::limit($desc, 300) }}">
    @if(!empty($s['meta_keywords']))<meta name="keywords" content="{{ $s['meta_keywords'] }}">@endif
    <link rel="canonical" href="{{ url()->current() }}">
    <meta name="theme-color" content="{{ $primary }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:title" content="{{ $pageTitle ?: $siteName }}">
    <meta property="og:description" content="{{ \Illuminate\Support\Str::limit($desc, 200) }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="icon" href="{{ Media::url($s['favicon'] ?? null, 'img/favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ Media::asset('img/apple-touch-icon.png') }}">
    <link rel="preload" href="{{ Media::asset('fonts/jakarta.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="{{ Media::asset('fonts/fraunces.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="{{ Media::asset('css/site.css') }}">
    <style>:root{--primary:{{ $primary }};--accent:{{ $accent }};}</style>
    <script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@type' => 'School', 'name' => $siteName, 'url' => url('/'), 'logo' => $logo, 'email' => $s['email'] ?? null, 'telephone' => $s['phone'] ?? null, 'address' => ['@type' => 'PostalAddress', 'streetAddress' => $s['address'] ?? '', 'addressLocality' => 'Dugda', 'addressRegion' => 'Jharkhand', 'postalCode' => '828404', 'addressCountry' => 'IN']], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    @stack('head')
</head>
<body class="@yield('body_class')">
<a class="skip" href="#main">Skip to content</a>

@if(($s['admissions_open'] ?? '1') === '1' && !empty($s['admission_banner']))
<div class="strip">
    <div class="wrap strip-in">
        <span class="strip-dot" aria-hidden="true"></span>
        <span>{{ $s['admission_banner'] }}</span>
        <a href="{{ route('website.admissions') }}#enquiry">Enquire now @icon('arrow-right','ic',16)</a>
    </div>
</div>
@endif

<div class="topbar">
    <div class="wrap topbar-in">
        <div class="topbar-contact">
            @if(!empty($s['phone']))<a href="tel:{{ preg_replace('/[^0-9+]/', '', $s['phone']) }}">@icon('phone','ic',15) {{ $s['phone'] }}</a>@endif
            @if(!empty($s['email']))<a href="mailto:{{ $s['email'] }}">@icon('mail','ic',15) {{ $s['email'] }}</a>@endif
            @if(!empty($s['office_hours']))<span class="hide-sm">@icon('clock','ic',15) {{ $s['office_hours'] }}</span>@endif
        </div>
        <div class="topbar-links">
            <a href="{{ route('website.downloads') }}">Downloads</a>
            @if(\Illuminate\Support\Facades\DB::table('site_vacancies')->where('is_open', true)->exists())<a href="{{ route('website.careers') }}">Careers</a>@endif
            <a href="{{ route('website.disclosure') }}">Disclosure</a>
            @foreach($socials as $k => $label)
                <a class="soc" href="{{ $s[$k] }}" target="_blank" rel="noopener" aria-label="{{ $label }}">@icon($k,'ic',15)</a>
            @endforeach
        </div>
    </div>
</div>

<header class="header" id="header">
    <div class="wrap header-in">
        <a class="brand" href="{{ route('website.home') }}" aria-label="{{ $siteName }} — home">
            <img src="{{ $logo }}" alt="" width="52" height="52">
            <span class="brand-text">
                <strong>{{ $siteName }}</strong>
                <small>CBSE Affiliation No. {{ $s['affiliation_no'] ?? '—' }} · Dugda, Bokaro</small>
            </span>
        </a>
        <nav class="nav" aria-label="Main">
            <ul>
                @foreach($menu as $item)
                    <li class="{{ $item->children->count() ? 'has-sub' : '' }} {{ SiteData::active($item->url) ? 'active' : '' }}">
                        <a href="{{ $item->url }}" @if($item->new_tab) target="_blank" rel="noopener" @endif>{{ $item->label }} @if($item->children->count())@icon('chevron-down','ic caret',14)@endif</a>
                        @if($item->children->count())
                            <ul class="sub">
                                @foreach($item->children as $c)
                                    <li><a href="{{ $c->url }}" @if($c->new_tab) target="_blank" rel="noopener" @endif>{{ $c->label }}</a></li>
                                @endforeach
                            </ul>
                        @endif
                    </li>
                @endforeach
            </ul>
        </nav>
        <a class="btn btn-accent header-cta" href="{{ route('website.admissions') }}#enquiry">Apply now</a>
        <button class="burger" id="burger" aria-label="Open menu" aria-expanded="false" aria-controls="drawer"><span></span><span></span><span></span></button>
    </div>
</header>

<div class="drawer" id="drawer" aria-hidden="true">
    <div class="drawer-panel">
        <div class="drawer-head">
            <img src="{{ $logo }}" alt="" width="40" height="40"><strong>{{ $s['site_short_name'] ?? 'DAV Dugda' }}</strong>
            <button class="drawer-close" id="drawerClose" aria-label="Close menu">@icon('x','ic',22)</button>
        </div>
        <ul class="drawer-nav">
            @foreach($menu as $item)
                <li>
                    @if($item->children->count())
                        <details>
                            <summary>{{ $item->label }} @icon('chevron-down','ic',18)</summary>
                            <ul>
                                @foreach($item->children as $c)<li><a href="{{ $c->url }}">{{ $c->label }}</a></li>@endforeach
                            </ul>
                        </details>
                    @else
                        <a href="{{ $item->url }}">{{ $item->label }}</a>
                    @endif
                </li>
            @endforeach
        </ul>
        <a class="btn btn-accent btn-block" href="{{ route('website.admissions') }}#enquiry">Admission enquiry</a>
        <div class="drawer-contact">
            @if(!empty($s['phone']))<a href="tel:{{ preg_replace('/[^0-9+]/', '', $s['phone']) }}">@icon('phone','ic',16) {{ $s['phone'] }}</a>@endif
            @if(!empty($s['email']))<a href="mailto:{{ $s['email'] }}">@icon('mail','ic',16) {{ $s['email'] }}</a>@endif
        </div>
    </div>
</div>

<main id="main">
    @yield('content')
</main>

<footer class="footer">
    <div class="footer-wave" aria-hidden="true"></div>
    <div class="wrap footer-grid">
        <div class="footer-about">
            <a class="brand brand-light" href="{{ route('website.home') }}">
                <img src="{{ $logo }}" alt="" width="52" height="52" loading="lazy">
                <span class="brand-text"><strong>{{ $siteName }}</strong><small>{{ $s['tagline'] ?? '' }}</small></span>
            </a>
            <p>{{ $s['footer_text'] ?? '' }}</p>
            <div class="footer-parent">
                <img src="{{ Media::asset('img/dav-cmc.webp') }}" alt="DAV College Managing Committee" width="48" height="50" loading="lazy">
                <span>Affiliated to <strong>DAV CMC</strong> (DAV College Managing Committee, New Delhi) and <strong>DAV CAE</strong> (DAV Centre for Academic Excellence) · CBSE Affiliation No. {{ $s['affiliation_no'] ?? '3430146' }}</span>
            </div>
            <div class="footer-soc">
                @foreach($socials as $k => $label)
                    <a href="{{ $s[$k] }}" target="_blank" rel="noopener" aria-label="{{ $label }}">@icon($k,'ic',18)</a>
                @endforeach
            </div>
        </div>
        <div>
            <h3>Quick links</h3>
            <ul class="footer-links">
                @foreach($footerMenu as $f)<li><a href="{{ $f->url }}">{{ $f->label }}</a></li>@endforeach
            </ul>
        </div>
        <div>
            <h3>Explore</h3>
            <ul class="footer-links">
                <li><a href="{{ route('website.about') }}">About us</a></li>
                <li><a href="{{ route('website.principal') }}">Our principal</a></li>
                <li><a href="{{ route('website.facilities') }}">Facilities</a></li>
                <li><a href="{{ route('website.achievements') }}">Results</a></li>
                <li><a href="{{ route('website.gallery') }}">Gallery</a></li>
            </ul>
        </div>
        <div>
            <h3>Reach us</h3>
            <ul class="footer-contact">
                @if(!empty($s['address']))<li>@icon('map-pin','ic',18)<span>{!! nl2br(e($s['address'])) !!}</span></li>@endif
                @if(!empty($s['phone']))<li>@icon('phone','ic',18)<a href="tel:{{ preg_replace('/[^0-9+]/', '', $s['phone']) }}">{{ $s['phone'] }}</a></li>@endif
                @if(!empty($s['email']))<li>@icon('mail','ic',18)<a href="mailto:{{ $s['email'] }}">{{ $s['email'] }}</a></li>@endif
                @if(!empty($s['office_hours']))<li>@icon('clock','ic',18)<span>{{ $s['office_hours'] }}</span></li>@endif
            </ul>
        </div>
    </div>
    <div class="wrap footer-bottom">
        <span>© {{ date('Y') }} {{ $siteName }}. All rights reserved.</span>
        <span>{{ $s['affiliation_text'] ?? '' }}</span>
    </div>
</footer>

@if(!empty($s['whatsapp']))
<a class="wa-float" href="https://wa.me/{{ preg_replace('/\D/', '', $s['whatsapp']) }}" target="_blank" rel="noopener" aria-label="Chat on WhatsApp">@icon('whatsapp','ic',26)</a>
@endif
<button class="to-top" id="toTop" aria-label="Back to top">
    <svg viewBox="0 0 44 44" aria-hidden="true"><circle cx="22" cy="22" r="20" class="ring-bg"/><circle cx="22" cy="22" r="20" class="ring" id="toTopRing"/></svg>
    @icon('arrow-up','ic',18)
</button>

<script src="{{ Media::asset('js/site.js') }}" defer></script>
@stack('scripts')
</body>
</html>
