@extends('website::layouts.site')
@php use Website\Support\Media; use Website\Support\Html; @endphp
@section('title', $facility->title)
@section('description', $facility->summary)
@section('content')
@include('website::site.partials.pagehead', ['title' => $facility->title, 'subtitle' => $facility->summary, 'image' => Media::url($facility->image), 'crumbs' => ['Facilities' => route('website.facilities'), $facility->title => null]])
<section class="section">
    <div class="wrap two-col">
        <article class="prose" data-reveal>
            @if($facility->image)<img class="rounded" src="{{ Media::url($facility->image) }}" alt="{{ $facility->title }}">@endif
            {!! $facility->body ?: '<p>'.e($facility->summary).'</p>' !!}
        </article>
        <aside class="aside" data-reveal="right">
            <h3 class="aside-title">More facilities</h3>
            <div class="aside-links">
                @foreach($others as $o)<a href="{{ route('website.facility', $o->slug) }}">@icon($o->icon ?: 'sparkles','ic',18) {{ $o->title }}</a>@endforeach
                <a href="{{ route('website.facilities') }}">@icon('layers','ic',18) All facilities</a>
            </div>
        </aside>
    </div>
</section>
@endsection
