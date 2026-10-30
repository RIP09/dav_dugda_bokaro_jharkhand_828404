@extends('website::layouts.site')
@section('title', $page->title ?? 'Faculty')
@section('content')
@include('website::site.partials.pagehead', ['title' => $page->title ?? 'Our Faculty', 'subtitle' => $page->subtitle ?? null, 'crumbs' => ['About' => route('website.about'), 'Faculty' => null]])
<section class="section">
    <div class="wrap">
        @if($page && $page->body)<div class="prose narrow center-block" data-reveal>{!! $page->body !!}</div>@endif
        @if($departments->count() > 1)
            <div class="filters" data-filter-group="faculty" data-reveal>
                <button class="chip is-active" data-filter="*">All</button>
                @foreach($departments as $d)<button class="chip" data-filter="{{ \Illuminate\Support\Str::slug($d) }}">{{ $d }}</button>@endforeach
            </div>
        @endif
        <div class="people-grid" data-filter-target="faculty">
            @foreach($teachers as $i => $p)
                <div data-tags="{{ \Illuminate\Support\Str::slug($p->department ?? '') }}">@include('website::site.partials.person', ['p' => $p, 'i' => $i])</div>
            @endforeach
        </div>
        @if(!$teachers->count() && !$office->count())
            <div class="empty-state" data-reveal>@icon('users','ic',40)<p>The staff list will be published here by the school.</p></div>
        @endif
        @if($office->count())
            <h2 class="h3 mt">Office & support staff</h2>
            <div class="people-grid">
                @foreach($office as $i => $p)@include('website::site.partials.person', ['p' => $p, 'i' => $i])@endforeach
            </div>
        @endif
    </div>
</section>
@endsection
