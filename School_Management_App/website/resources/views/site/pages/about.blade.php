@extends('website::layouts.site')
@php use Website\Support\Media; use Website\Support\Html; @endphp
@section('title', $page->title ?? 'About')
@section('description', $page->meta_description ?? Html::excerpt($page->body ?? '', 160))
@section('content')
@include('website::site.partials.pagehead', ['title' => $page->title ?? 'About Us', 'subtitle' => $page->subtitle ?? null, 'image' => Media::url($page->image ?? null), 'crumbs' => ['About' => null]])
<section class="section">
    <div class="wrap two-col">
        <article class="prose" data-reveal>{!! $page->body ?? '' !!}</article>
        <aside class="aside" data-reveal="right">
            <div class="aside-card affil">
                <img src="{{ Media::asset('img/dav-cmc.webp') }}" alt="DAV College Managing Committee logo" width="72" height="75" loading="lazy">
                <div><strong>DAV College Managing Committee</strong><span>New Delhi</span></div>
            </div>
            <dl class="facts">
                <div><dt>Board</dt><dd>CBSE, New Delhi</dd></div>
                <div><dt>Affiliation No.</dt><dd>{{ $s['affiliation_no'] ?? '—' }}</dd></div>
                <div><dt>School code</dt><dd>{{ $s['school_code'] ?? '—' }}</dd></div>
                <div><dt>Classes</dt><dd>Nursery – XII</dd></div>
                <div><dt>Streams (XI–XII)</dt><dd>Science, Commerce</dd></div>
                @if(!empty($s['established_year']))<div><dt>Founded</dt><dd>{{ $s['established_year'] }}</dd></div>@endif
                <div><dt>Sponsor</dt><dd>BCCL (Coal India Ltd)</dd></div>
                <div><dt>Principal</dt><dd>{{ $s['principal_name'] ?? '' }}</dd></div>
            </dl>
            <div class="aside-links">
                <a href="{{ route('website.principal') }}">@icon('user','ic',18) Our principal</a>
                <a href="{{ route('website.management') }}">@icon('users','ic',18) School management</a>
                <a href="{{ route('website.disclosure') }}">@icon('clipboard-list','ic',18) Mandatory disclosure</a>
            </div>
        </aside>
    </div>
</section>
@include('website::site.partials.cta')
@endsection
