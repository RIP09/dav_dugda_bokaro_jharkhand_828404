@extends('website::layouts.site')
@php use Website\Support\Media; @endphp
@section('title', 'Results & Achievements')
@section('content')
@include('website::site.partials.pagehead', ['title' => 'Results & Achievements', 'subtitle' => 'Celebrating our students in class, on the field and on stage', 'crumbs' => ['Academics' => route('website.academics'), 'Achievements' => null]])
<section class="section">
    <div class="wrap">
        @if($toppers->count())
            <div class="sec-head" data-reveal><span class="eyebrow">Board examinations</span><h2 class="h2">Our toppers</h2></div>
            <div class="achiever-grid light">
                @foreach($toppers as $i => $a)
                    <article class="achiever" data-reveal style="--d:{{ ($i % 4) * 80 }}ms">
                        <div class="ach-photo">@if($a->photo)<img src="{{ Media::url($a->photo) }}" alt="{{ $a->student_name }}" loading="lazy">@else<span>{{ collect(explode(' ', $a->student_name ?? '★'))->map(fn($w) => mb_substr($w, 0, 1))->take(2)->implode('') }}</span>@endif</div>
                        <strong class="ach-score">{{ $a->score }}</strong>
                        <h3>{{ $a->student_name }}</h3>
                        <p>{{ $a->title }}@if($a->year) · {{ $a->year }}@endif</p>
                    </article>
                @endforeach
            </div>
        @endif
        @if($others->count())
            <div class="sec-head mt" data-reveal><span class="eyebrow">Beyond the classroom</span><h2 class="h2">Competitions, sports & culture</h2></div>
            <div class="award-list">
                @foreach($others as $i => $a)
                    <div class="award" data-reveal style="--d:{{ ($i % 4) * 60 }}ms">
                        <span class="award-ic">@icon(['olympiad' => 'award', 'sports' => 'trophy', 'cultural' => 'music'][$a->category] ?? 'star','ic',22)</span>
                        <div><strong>{{ $a->title }}</strong><small>{{ $a->student_name }}@if($a->class_name) · Class {{ $a->class_name }}@endif @if($a->year)· {{ $a->year }}@endif</small>@if($a->description)<p>{{ $a->description }}</p>@endif</div>
                        @if($a->score)<span class="award-score">{{ $a->score }}</span>@endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
@endsection
