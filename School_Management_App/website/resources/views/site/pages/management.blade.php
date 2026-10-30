@extends('website::layouts.site')
@section('title', $page->title ?? 'Management')
@section('content')
@include('website::site.partials.pagehead', ['title' => $page->title ?? 'School Management', 'subtitle' => $page->subtitle ?? null, 'crumbs' => ['About' => route('website.about'), 'Management' => null]])
<section class="section">
    <div class="wrap">
        @if($page && $page->body)<div class="prose narrow center-block" data-reveal>{!! $page->body !!}</div>@endif
        <div class="people-grid">
            @foreach($people as $i => $p)@include('website::site.partials.person', ['p' => $p, 'i' => $i])@endforeach
        </div>
    </div>
</section>
@endsection
