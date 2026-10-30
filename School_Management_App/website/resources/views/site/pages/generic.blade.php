@extends('website::layouts.site')
@php use Website\Support\Media; use Website\Support\Html; @endphp
@section('title', $page->title)
@section('description', $page->meta_description ?: Html::excerpt($page->body, 160))
@section('content')
@include('website::site.partials.pagehead', ['title' => $page->title, 'subtitle' => $page->subtitle, 'image' => Media::url($page->image), 'crumbs' => [$page->title => null]])
<section class="section">
    <div class="wrap narrow-wrap">
        <article class="prose" data-reveal>{!! $page->body !!}</article>
    </div>
</section>
@endsection
