@extends('website::layouts.site')
@php use Website\Support\Media; @endphp
@section('title', 'Downloads')
@section('content')
@include('website::site.partials.pagehead', ['title' => 'Downloads', 'subtitle' => 'Official files from the school', 'crumbs' => ['Downloads' => null]])
<section class="section">
    <div class="wrap narrow-wrap">
        @if($appsComingSoon ?? false)
            <div class="empty-state" data-reveal>@icon('download','ic',40)<p><strong>School apps — coming soon.</strong><br>The Admin, Staff, Parent and Student apps will be available here once they are ready for everyone to use.</p></div>
        @endif
        @forelse($groups as $cat => $items)
            <div class="dl-group" data-reveal>
                <h2 class="h3">{{ $sections[$cat] ?? ucfirst($cat) }}</h2>
                <div class="dl-list">
                    @foreach($items as $f)
                        <a class="dl" href="{{ Media::url($f->file) }}" target="_blank" rel="noopener">
                            <span class="dl-ext">{{ Media::extension($f->file) }}</span>
                            <span><strong>{{ $f->title }}</strong><small>{{ $f->description }} {{ Media::sizeLabel($f->file) ? '· '.Media::sizeLabel($f->file) : '' }}</small></span>
                            @icon('download','ic',20)
                        </a>
                    @endforeach
                </div>
            </div>
        @empty
            @if(! ($appsComingSoon ?? false))
            <div class="empty-state">@icon('download','ic',40)<p>Files will be available here soon.</p></div>
            @endif
        @endforelse
    </div>
</section>
@endsection
