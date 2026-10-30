@extends('website::layouts.site')
@php use Website\Support\Media; @endphp
@section('title', $page->title ?? 'Academics')
@section('content')
@include('website::site.partials.pagehead', ['title' => $page->title ?? 'Academics', 'subtitle' => $page->subtitle ?? null, 'image' => Media::url($page->image ?? null), 'crumbs' => ['Academics' => null]])
<section class="section">
    <div class="wrap">
        <div class="stages" data-reveal>
            @foreach([['Foundational', 'Nursery – II', 'baby'], ['Preparatory', 'III – V', 'palette'], ['Middle', 'VI – VIII', 'microscope'], ['Secondary', 'IX – X', 'book-open'], ['Senior Secondary', 'XI – XII', 'graduation-cap']] as $k => [$t, $c, $ic])
                <div class="stage" style="--d:{{ $k * 80 }}ms"><span class="stage-ic">@icon($ic,'ic',24)</span><strong>{{ $t }}</strong><small>Classes {{ $c }}</small></div>
            @endforeach
        </div>
        <div class="two-col mt">
            <article class="prose" data-reveal>{!! $page->body ?? '' !!}</article>
            <aside class="aside" data-reveal="right">
                <div class="aside-card streams">
                    <h3>Class XI – XII streams</h3>
                    <ul>
                        <li>@icon('flask-conical','ic',18) Science</li>
                        <li>@icon('calculator','ic',18) Commerce</li>
                    </ul>
                </div>
                <div class="aside-links">
                    <a href="{{ route('website.achievements') }}">@icon('trophy','ic',18) Results & achievements</a>
                    <a href="{{ route('website.downloads') }}">@icon('download','ic',18) Downloads</a>
                </div>
            </aside>
        </div>
    </div>
</section>
@include('website::site.partials.cta')
@endsection
