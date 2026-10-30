@extends('website::layouts.site')
@php use Website\Support\Media; use Website\Support\Html; @endphp
@section('title', $event->title)
@section('description', Html::excerpt($event->description, 160))
@section('content')
@include('website::site.partials.pagehead', ['title' => $event->title, 'subtitle' => $event->starts_at->format('l, d F Y'), 'image' => Media::url($event->image), 'crumbs' => ['Events' => route('website.events'), \Illuminate\Support\Str::limit($event->title, 40) => null]])
<section class="section">
    <div class="wrap two-col">
        <article class="prose" data-reveal>
            {!! $event->description !!}
        </article>
        <aside class="aside" data-reveal="right">
            <dl class="facts">
                <div><dt>Date</dt><dd>{{ $event->starts_at->format('D, d M Y') }}</dd></div>
                <div><dt>Time</dt><dd>{{ $event->starts_at->format('g:i A') }}@if($event->ends_at) – {{ $event->ends_at->format('g:i A') }}@endif</dd></div>
                @if($event->venue)<div><dt>Venue</dt><dd>{{ $event->venue }}</dd></div>@endif
            </dl>
            @php
                $gcal = 'https://calendar.google.com/calendar/render?action=TEMPLATE&text='.rawurlencode($event->title).'&dates='.$event->starts_at->copy()->utc()->format('Ymd\THis\Z').'/'.($event->ends_at ?? $event->starts_at->copy()->addHours(2))->copy()->utc()->format('Ymd\THis\Z').'&location='.rawurlencode($event->venue.', '.($s['site_name'] ?? ''));
            @endphp
            <a class="btn btn-primary btn-block" href="{{ $gcal }}" target="_blank" rel="noopener">@icon('calendar','ic',18) Add to Google Calendar</a>
            @if($more->count())
                <h3 class="aside-title mt-s">More upcoming</h3>
                <div class="aside-links">@foreach($more as $m)<a href="{{ route('website.events.show', $m->slug) }}">@icon('calendar-days','ic',18) {{ $m->title }} <small>{{ $m->starts_at->format('d M') }}</small></a>@endforeach</div>
            @endif
        </aside>
    </div>
</section>
@endsection
