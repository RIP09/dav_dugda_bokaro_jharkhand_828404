@extends('website::layouts.site')
@php use Website\Support\Media; @endphp
@section('title', 'Photo Gallery')
@section('content')
@include('website::site.partials.pagehead', ['title' => 'Photo Gallery', 'subtitle' => 'Moments from life at DAV Dugda', 'crumbs' => ['Gallery' => null]])
<section class="section">
    <div class="wrap album-grid">
        @forelse($albums as $i => $a)
            <a class="album" href="{{ route('website.gallery.show', $a->slug) }}" data-reveal style="--d:{{ ($i % 3) * 80 }}ms">
                <span class="album-stack">
                    @foreach($a->photos->take(3)->reverse() as $ph)<img src="{{ Media::url($ph->image) }}" alt="" loading="lazy">@endforeach
                    <img class="album-cover" src="{{ $a->coverUrl() }}" alt="" loading="lazy">
                </span>
                <span class="album-body">
                    <strong>{{ $a->title }}</strong>
                    <small>@icon('images','ic',14) {{ $a->photos_count }} photos @if($a->event_date)· {{ $a->event_date->format('M Y') }}@endif</small>
                </span>
            </a>
        @empty
            <div class="empty-state">@icon('image','ic',40)<p>Albums will appear here soon.</p></div>
        @endforelse
    </div>
</section>
@endsection
