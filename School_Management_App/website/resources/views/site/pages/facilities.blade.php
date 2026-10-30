@extends('website::layouts.site')
@php use Website\Support\Media; @endphp
@section('title', $page->title ?? 'Facilities')
@section('content')
@include('website::site.partials.pagehead', ['title' => $page->title ?? 'Facilities', 'subtitle' => $page->subtitle ?? null, 'image' => Media::url($page->image ?? null), 'crumbs' => ['Facilities' => null]])
<section class="section">
    <div class="wrap">
        @if($page && $page->body)<div class="prose narrow center-block" data-reveal>{!! $page->body !!}</div>@endif
        <div class="fac-grid">
            @foreach($facilities as $i => $f)
                <a class="fac-card {{ $f->image ? 'has-img' : '' }}" href="{{ route('website.facility', $f->slug) }}" data-reveal style="--d:{{ ($i % 3) * 80 }}ms">
                    @if($f->image)<img src="{{ Media::url($f->image) }}" alt="" loading="lazy">@endif
                    <span class="fac-ic">@icon($f->icon ?: 'sparkles','ic',26)</span>
                    <span class="fac-txt"><strong>{{ $f->title }}</strong><small>{{ $f->summary }}</small></span>
                </a>
            @endforeach
        </div>
    </div>
</section>
@include('website::site.partials.cta')
@endsection
