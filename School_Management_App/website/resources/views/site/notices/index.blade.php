@extends('website::layouts.site')
@php use Website\Support\Html; @endphp
@section('title', 'Notices & News')
@section('content')
@include('website::site.partials.pagehead', ['title' => 'Notices & News', 'subtitle' => 'Circulars, announcements and stories from school', 'crumbs' => ['Notices & News' => null]])
<section class="section">
    <div class="wrap">
        <div class="toolbar" data-reveal>
            <div class="filters">
                <a class="chip {{ !$type ? 'is-active' : '' }}" href="{{ route('website.notices') }}">All</a>
                <a class="chip {{ $type === 'notice' ? 'is-active' : '' }}" href="{{ route('website.notices', ['type' => 'notice']) }}">Notices</a>
                <a class="chip {{ $type === 'circular' ? 'is-active' : '' }}" href="{{ route('website.notices', ['type' => 'circular']) }}">Circulars</a>
                <a class="chip {{ $type === 'news' ? 'is-active' : '' }}" href="{{ route('website.notices', ['type' => 'news']) }}">News</a>
            </div>
            <form class="search" method="get">
                @if($type)<input type="hidden" name="type" value="{{ $type }}">@endif
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Search notices" aria-label="Search notices">
                <button aria-label="Search">@icon('search','ic',18)</button>
            </form>
        </div>
        <div class="card-grid">
            @forelse($notices as $i => $n)
                <a class="ncard" href="{{ route('website.notices.show', $n->slug) }}" data-reveal style="--d:{{ ($i % 3) * 70 }}ms">
                    <div class="ncard-top">
                        <span class="date-chip"><b>{{ optional($n->published_at)->format('d') }}</b>{{ optional($n->published_at)->format('M Y') }}</span>
                        <span><span class="tag tag-{{ $n->category }}">{{ ucfirst($n->category) }}</span>@if($n->is_pinned)<span class="tag tag-pin">Pinned</span>@endif</span>
                    </div>
                    <h2>{{ $n->title }}</h2>
                    <p>{{ Html::excerpt($n->body, 130) }}</p>
                    <span class="link-arrow">Read more @icon('arrow-right','ic',16)</span>
                    @if($n->attachment)<span class="att">@icon('file-text','ic',14) Attachment</span>@endif
                </a>
            @empty
                <div class="empty-state">@icon('search','ic',40)<p>No notices found.</p></div>
            @endforelse
        </div>
        {{ $notices->links('website::site.partials.pagination') }}
    </div>
</section>
@endsection
