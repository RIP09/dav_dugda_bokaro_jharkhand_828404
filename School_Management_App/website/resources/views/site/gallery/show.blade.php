@extends('website::layouts.site')
@php use Website\Support\Media; @endphp
@section('title', $album->title.' — Gallery')
@section('og_image', $album->coverUrl())
@section('content')
@include('website::site.partials.pagehead', ['title' => $album->title, 'subtitle' => $album->description ?: ($album->photos->count().' photos'), 'crumbs' => ['Gallery' => route('website.gallery'), $album->title => null]])
<section class="section">
    <div class="wrap">
        <div class="masonry" data-lightbox-group>
            @foreach($album->photos as $i => $ph)
                <a class="masonry-item" href="{{ Media::url($ph->image) }}" data-lightbox data-caption="{{ $ph->caption }}" data-reveal="zoom" style="--d:{{ ($i % 6) * 50 }}ms">
                    <img src="{{ Media::url($ph->image) }}" alt="{{ $ph->caption ?: $album->title }}" loading="lazy">
                    @if($ph->caption)<span class="mosaic-cap">{{ $ph->caption }}</span>@endif
                </a>
            @endforeach
        </div>
        @if($others->count())
            <h2 class="h3 mt">More albums</h2>
            <div class="album-grid">
                @foreach($others as $a)
                    <a class="album" href="{{ route('website.gallery.show', $a->slug) }}">
                        <span class="album-stack"><img class="album-cover" src="{{ $a->coverUrl() }}" alt="" loading="lazy"></span>
                        <span class="album-body"><strong>{{ $a->title }}</strong><small>{{ $a->photos->count() }} photos</small></span>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</section>
@endsection
