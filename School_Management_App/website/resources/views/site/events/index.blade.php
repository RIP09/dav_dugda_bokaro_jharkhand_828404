@extends('website::layouts.site')
@php use Website\Support\Media; use Website\Support\Html; @endphp
@section('title', 'Events')
@section('content')
@include('website::site.partials.pagehead', ['title' => 'Events & Calendar', 'subtitle' => 'What is coming up at DAV Dugda', 'crumbs' => ['Events' => null]])
<section class="section">
    <div class="wrap">
        <h2 class="h3">Upcoming</h2>
        <div class="timeline">
            @forelse($upcoming as $i => $e)
                <a class="tl-item" href="{{ route('website.events.show', $e->slug) }}" data-reveal style="--d:{{ $i * 70 }}ms">
                    <span class="tl-date"><b>{{ $e->starts_at->format('d') }}</b><span>{{ $e->starts_at->format('M') }}</span><small>{{ $e->starts_at->format('D') }}</small></span>
                    <span class="tl-card">
                        @if($e->image)<img src="{{ Media::url($e->image) }}" alt="" loading="lazy">@endif
                        <span class="tl-body">
                            <strong>{{ $e->title }}</strong>
                            <small>@icon('clock','ic',14) {{ $e->starts_at->format('g:i A') }}@if($e->ends_at) – {{ $e->ends_at->format('g:i A') }}@endif @if($e->venue) · @icon('map-pin','ic',14) {{ $e->venue }}@endif</small>
                            <span>{{ Html::excerpt($e->description, 140) }}</span>
                        </span>
                    </span>
                </a>
            @empty
                <div class="empty-state">@icon('calendar','ic',40)<p>New events will be announced soon.</p></div>
            @endforelse
        </div>
        @if($past->count())
            <h2 class="h3 mt">Recent events</h2>
            <div class="card-grid">
                @foreach($past as $i => $e)
                    <a class="ncard" href="{{ route('website.events.show', $e->slug) }}" data-reveal style="--d:{{ ($i % 3) * 70 }}ms">
                        <div class="ncard-top"><span class="date-chip"><b>{{ $e->starts_at->format('d') }}</b>{{ $e->starts_at->format('M Y') }}</span></div>
                        <h2>{{ $e->title }}</h2>
                        <p>{{ Html::excerpt($e->description, 110) }}</p>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</section>
@endsection
