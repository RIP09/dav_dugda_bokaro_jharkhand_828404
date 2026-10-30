@extends('website::layouts.site')
@php use Website\Support\Media; @endphp
@section('title', $page->title ?? "Principal's Message")
@section('content')
@include('website::site.partials.pagehead', ['title' => $page->title ?? "Principal's Message", 'subtitle' => $page->subtitle ?? null, 'crumbs' => ['About' => route('website.about'), ($page->title ?? "Principal's Message") => null]])
<section class="section">
    <div class="wrap principal-page">
        <aside class="principal-card" data-reveal="left">
            @if(!empty($s['principal_photo']))
                <img src="{{ Media::url($s['principal_photo']) }}" alt="{{ $s['principal_name'] ?? '' }}">
            @else
                <div class="avatar-ph lg" role="img" aria-label="School logo"><img src="{{ Media::url($s['logo'] ?? null, 'img/logo.webp') }}" alt="" style="width:70%;height:auto;object-fit:contain;border-radius:0;box-shadow:none"></div>
            @endif
            <strong>{{ $s['principal_name'] ?? '' }}</strong>
            <span>{{ $s['principal_title'] ?? 'Principal' }}, {{ $s['site_name'] ?? '' }}</span>
            @if(!empty($s['principal_qualifications']))<span>{{ $s['principal_qualifications'] }}</span>@endif
            @if(!empty($s['principal_experience']))<span>{{ $s['principal_experience'] }}</span>@endif
            @if(!empty($s['email']))<a href="mailto:{{ $s['email'] }}">@icon('mail','ic',16) {{ $s['email'] }}</a>@endif
        </aside>
        @if(trim(strip_tags($page->body ?? '')) !== '')
        <article class="prose letter" data-reveal>
            @icon('quote','ic q-ic',48)
            {!! $page->body !!}
        </article>
        @else
        <article class="prose" data-reveal>
            <h2>{{ $s['principal_name'] ?? '' }}</h2>
            <p>{{ $s['principal_name'] ?? '' }} is the Principal of {{ $s['site_name'] ?? 'DAV Public School, Dugda' }}.@if(!empty($s['principal_qualifications'])) Qualifications: {{ rtrim($s['principal_qualifications'], '.') }}.@endif @if(!empty($s['principal_experience'])) Experience: {{ rtrim($s['principal_experience'], '.') }}.@endif</p>
        </article>
        @endif
    </div>
</section>
@endsection
