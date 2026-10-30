@extends('website::layouts.site')
@php use Website\Support\Media; use Website\Support\Html; @endphp
@section('title', $notice->title)
@section('description', Html::excerpt($notice->body, 160))
@if($notice->image)@section('og_image', Media::url($notice->image))@endif
@section('content')
@include('website::site.partials.pagehead', ['title' => $notice->title, 'subtitle' => ucfirst($notice->category).' · '.optional($notice->published_at)->format('d F Y'), 'crumbs' => ['Notices & News' => route('website.notices'), \Illuminate\Support\Str::limit($notice->title, 40) => null]])
<section class="section">
    <div class="wrap two-col">
        <article class="prose" data-reveal>
            @if($notice->image)<img class="rounded" src="{{ Media::url($notice->image) }}" alt="">@endif
            {!! $notice->body !!}
            @if($notice->attachment)
                <a class="dl dl-big" href="{{ Media::url($notice->attachment) }}" target="_blank" rel="noopener">
                    <span class="dl-ext">{{ Media::extension($notice->attachment) }}</span>
                    <span><strong>Download attachment</strong><small>{{ Media::sizeLabel($notice->attachment) }}</small></span>
                    @icon('download','ic',20)
                </a>
            @endif
            <div class="share">
                <span>Share:</span>
                <a href="https://wa.me/?text={{ rawurlencode($notice->title.' '.url()->current()) }}" target="_blank" rel="noopener" aria-label="Share on WhatsApp">@icon('whatsapp','ic',18)</a>
                <a href="https://www.facebook.com/sharer/sharer.php?u={{ rawurlencode(url()->current()) }}" target="_blank" rel="noopener" aria-label="Share on Facebook">@icon('facebook','ic',18)</a>
                <button type="button" data-copy="{{ url()->current() }}" aria-label="Copy link">@icon('external-link','ic',18) Copy link</button>
            </div>
        </article>
        <aside class="aside" data-reveal="right">
            <h3 class="aside-title">Recent notices</h3>
            <ul class="notice-list compact">
                @foreach($recent as $n)
                    <li><a href="{{ route('website.notices.show', $n->slug) }}"><span class="date-chip"><b>{{ optional($n->published_at)->format('d') }}</b>{{ optional($n->published_at)->format('M') }}</span><span class="nl-body"><strong>{{ $n->title }}</strong></span></a></li>
                @endforeach
            </ul>
        </aside>
    </div>
</section>
@endsection
