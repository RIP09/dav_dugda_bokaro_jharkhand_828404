@extends('website::layouts.site')
@php use Website\Support\Media; use Website\Support\Html; @endphp
@section('body_class', 'home')

@section('content')
{{-- ============ HERO SLIDER ============ --}}
<section class="hero" id="hero" data-slider data-interval="6500" aria-roledescription="carousel" aria-label="Highlights">
    @foreach($banners as $i => $b)
        <div class="hero-slide {{ $i === 0 ? 'is-active' : '' }}" data-slide aria-roledescription="slide" aria-label="{{ $i + 1 }} of {{ $banners->count() }}">
            <img class="hero-bg" src="{{ Media::url($b->image) }}" alt="" @if($i === 0) fetchpriority="high" @else loading="lazy" @endif>
            <div class="wrap hero-grid">
                <div class="hero-copy">
                    @if($b->eyebrow)<span class="eyebrow eyebrow-light">{{ $b->eyebrow }}</span>@endif
                    <h1 class="hero-title">{{ $b->title }}</h1>
                    @if($b->subtitle)<p class="hero-sub">{{ $b->subtitle }}</p>@endif
                    <div class="hero-actions">
                        @if($b->button_text)<a class="btn btn-accent btn-lg" href="{{ $b->button_url ?: '#' }}">{{ $b->button_text }} @icon('arrow-right','ic',18)</a>@endif
                        <a class="btn btn-ghost-light btn-lg" href="{{ route('website.contact') }}">Visit the campus</a>
                    </div>
                </div>
                <figure class="hero-frame" aria-hidden="true">
                    <img src="{{ Media::url($b->image) }}" alt="" loading="{{ $i === 0 ? 'eager' : 'lazy' }}">
                    <figcaption class="hero-chip chip-a">@icon('award','ic',18) CBSE Affiliation No. {{ $s['affiliation_no'] ?? '' }}</figcaption>
                    <span class="hero-chip chip-b">@icon('sparkles','ic',18) A DAV institution</span>
                </figure>
            </div>
        </div>
    @endforeach
    @if($banners->count() > 1)
        <div class="hero-nav wrap">
            <div class="hero-dots" role="tablist">
                @foreach($banners as $i => $b)
                    <button class="hero-dot {{ $i === 0 ? 'is-active' : '' }}" data-dot="{{ $i }}" role="tab" aria-label="Show slide {{ $i + 1 }}: {{ $b->title }}"><span></span></button>
                @endforeach
            </div>
            <div class="hero-arrows">
                <button data-prev aria-label="Previous slide">@icon('chevron-left','ic',20)</button>
                <button data-next aria-label="Next slide">@icon('chevron-right','ic',20)</button>
            </div>
        </div>
    @endif
    <div class="hero-scroll" aria-hidden="true"><span></span></div>
</section>

{{-- ============ NOTICE TICKER ============ --}}
@php $ticker = $notices->where('in_ticker', true); @endphp
@if($ticker->count())
<section class="ticker" aria-label="Latest notices">
    <div class="wrap ticker-in">
        <a class="ticker-label" href="{{ route('website.notices') }}">@icon('bell','ic',16) Latest</a>
        <div class="ticker-track">
            <div class="ticker-move">
                @foreach([1, 2] as $copy)
                    @foreach($ticker as $n)
                        <a href="{{ route('website.notices.show', $n->slug) }}" @if($copy === 2) aria-hidden="true" tabindex="-1" @endif><span class="tdot"></span>{{ $n->title }}</a>
                    @endforeach
                @endforeach
            </div>
        </div>
    </div>
</section>
@endif

{{-- ============ INTRO + COUNTERS ============ --}}
<section class="section intro">
    <div class="wrap intro-grid">
        <div data-reveal>
            <span class="eyebrow">Welcome to {{ $s['site_short_name'] ?? 'DAV Dugda' }}</span>
            <h2 class="h2">{{ $s['home_intro_title'] ?? '' }}</h2>
            <p class="lead">{{ $s['home_intro_text'] ?? '' }}</p>
            <div class="intro-actions">
                <a class="btn btn-primary" href="{{ route('website.about') }}">Our story @icon('arrow-right','ic',18)</a>
                <a class="link-arrow" href="{{ route('website.academics') }}">Academics @icon('arrow-right','ic',16)</a>
            </div>
        </div>
        <div class="stats" data-reveal="right">
            @foreach($stats as $st)
                <div class="stat">
                    <span class="stat-ic">@icon($st->icon ?: 'sparkles','ic',22)</span>
                    <strong><span data-count="{{ $st->value }}">0</span>{{ $st->suffix }}</strong>
                    <small>{{ $st->label }}</small>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ============ PRINCIPAL ============ --}}
<section class="section principal-band">
    <div class="wrap principal-grid">
        <div class="principal-photo" data-reveal="left">
            <div class="pp-ring" aria-hidden="true"></div>
            @if(!empty($s['principal_photo']))
                <img src="{{ Media::url($s['principal_photo']) }}" alt="{{ $s['principal_name'] ?? 'Principal' }}" loading="lazy">
            @else
                <div class="avatar-ph" role="img" aria-label="{{ $s['site_name'] ?? 'School' }} logo"><img src="{{ Media::url($s['logo'] ?? null, 'img/logo.webp') }}" alt="" loading="lazy" style="width:70%;height:auto;object-fit:contain;border-radius:0;box-shadow:none"></div>
            @endif
            <div class="pp-badge"><strong>{{ $s['principal_name'] ?? '' }}</strong><span>{{ $s['principal_title'] ?? 'Principal' }}</span></div>
        </div>
        <div data-reveal>
            @if(!empty($s['principal_excerpt']))
            <span class="eyebrow">From the Principal's desk</span>
            <blockquote class="principal-quote">
                @icon('quote','ic q-ic',40)
                <p>{{ $s['principal_excerpt'] }}</p>
            </blockquote>
            <p class="sign"><strong>{{ $s['principal_name'] ?? '' }}</strong> · {{ $s['principal_title'] ?? 'Principal' }}</p>
            <a class="btn btn-outline" href="{{ route('website.principal') }}">Read the full message @icon('arrow-right','ic',18)</a>
            @else
            <span class="eyebrow">Our Principal</span>
            <h2 class="h2">{{ $s['principal_name'] ?? '' }}</h2>
            <ul class="principal-facts">
                @if(!empty($s['principal_qualifications']))<li>@icon('graduation-cap','ic',20) {{ $s['principal_qualifications'] }}</li>@endif
                @if(!empty($s['principal_experience']))<li>@icon('award','ic',20) {{ $s['principal_experience'] }}</li>@endif
            </ul>
            <a class="btn btn-outline" href="{{ route('website.principal') }}">About our principal @icon('arrow-right','ic',18)</a>
            @endif
        </div>
    </div>
</section>

{{-- ============ WHY US ============ --}}
@if($highlights->count())
<section class="section">
    <div class="wrap">
        <div class="sec-head" data-reveal>
            <span class="eyebrow">Why families choose us</span>
            <h2 class="h2">Education with a heart, and a plan</h2>
        </div>
        <div class="bento">
            @foreach($highlights as $i => $h)
                <article class="bento-card" data-reveal style="--d:{{ $i * 70 }}ms">
                    <span class="bento-ic">@icon($h->icon ?: 'sparkles','ic',26)</span>
                    <h3>{{ $h->title }}</h3>
                    <p>{{ $h->summary }}</p>
                </article>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ============ NOTICES + EVENTS ============ --}}
@if($notices->count() || $events->count())
<section class="section soft">
    <div class="wrap ne-grid">
        <div class="panel" data-reveal>
            <div class="panel-head">
                <h2 class="h3">@icon('megaphone','ic',22) Notices & News</h2>
                <a class="link-arrow" href="{{ route('website.notices') }}">All notices @icon('arrow-right','ic',16)</a>
            </div>
            <ul class="notice-list">
                @forelse($notices as $n)
                    <li>
                        <a href="{{ route('website.notices.show', $n->slug) }}">
                            <span class="date-chip"><b>{{ optional($n->published_at)->format('d') }}</b>{{ optional($n->published_at)->format('M') }}</span>
                            <span class="nl-body">
                                <span class="tag tag-{{ $n->category }}">{{ ucfirst($n->category) }}</span>@if($n->is_pinned)<span class="tag tag-pin">Pinned</span>@endif
                                <strong>{{ $n->title }}</strong>
                            </span>
                            @icon('chevron-right','ic nl-go',18)
                        </a>
                    </li>
                @empty
                    <li class="empty">No notices right now.</li>
                @endforelse
            </ul>
        </div>
        <div class="panel" data-reveal="right">
            <div class="panel-head">
                <h2 class="h3">@icon('calendar-days','ic',22) Upcoming Events</h2>
                <a class="link-arrow" href="{{ route('website.events') }}">Calendar @icon('arrow-right','ic',16)</a>
            </div>
            <div class="event-mini">
                @forelse($events as $e)
                    <a class="em-card" href="{{ route('website.events.show', $e->slug) }}">
                        <span class="em-date"><b>{{ $e->starts_at->format('d') }}</b><span>{{ $e->starts_at->format('M') }}</span></span>
                        <span class="em-body">
                            <strong>{{ $e->title }}</strong>
                            <small>@icon('clock','ic',14) {{ $e->starts_at->format('D, g:i A') }} @if($e->venue)· @icon('map-pin','ic',14) {{ $e->venue }}@endif</small>
                        </span>
                    </a>
                @empty
                    <p class="empty">New events will be announced soon.</p>
                @endforelse
            </div>
        </div>
    </div>
</section>

@endif

{{-- ============ FACILITIES ============ --}}
@if($facilities->count())
<section class="section">
    <div class="wrap">
        <div class="sec-head sec-head-row" data-reveal>
            <div>
                <span class="eyebrow">Campus & facilities</span>
                <h2 class="h2">Spaces made for curious minds</h2>
            </div>
            <a class="btn btn-outline" href="{{ route('website.facilities') }}">All facilities @icon('arrow-right','ic',18)</a>
        </div>
        <div class="fac-scroller">
            @foreach($facilities as $i => $f)
                <a class="fac-card {{ $f->image ? 'has-img' : '' }}" href="{{ route('website.facility', $f->slug) }}" data-reveal style="--d:{{ ($i % 4) * 80 }}ms">
                    @if($f->image)<img src="{{ Media::url($f->image) }}" alt="" loading="lazy">@endif
                    <span class="fac-ic">@icon($f->icon ?: 'sparkles','ic',26)</span>
                    <span class="fac-txt"><strong>{{ $f->title }}</strong><small>{{ $f->summary }}</small></span>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ============ TOPPERS ============ --}}
@if($toppers->count())
<section class="section dark achievers">
    <div class="wrap">
        <div class="sec-head" data-reveal>
            <span class="eyebrow eyebrow-light">Pride of DAV Dugda</span>
            <h2 class="h2">Our achievers</h2>
            @php $yrs = $toppers->pluck('year')->filter()->unique(); @endphp
            @if($yrs->count() === 1)<p class="lead" style="color:rgba(255,255,255,.75)">School toppers, CBSE board examinations {{ $yrs->first() }}</p>@endif
        </div>
        <div class="achiever-grid">
            @foreach($toppers as $i => $a)
                <article class="achiever" data-reveal style="--d:{{ $i * 90 }}ms">
                    <div class="ach-photo">
                        @if($a->photo)<img src="{{ Media::url($a->photo) }}" alt="{{ $a->student_name }}" loading="lazy">@else<span>{{ collect(explode(' ', $a->student_name ?? '★'))->map(fn($w) => mb_substr($w, 0, 1))->take(2)->implode('') }}</span>@endif
                    </div>
                    <strong class="ach-score">{{ $a->score }}</strong>
                    <h3>{{ $a->student_name }}</h3>
                    <p>{{ $a->title }}@if($a->year) · {{ $a->year }}@endif</p>
                </article>
            @endforeach
        </div>
        <div class="center"><a class="btn btn-ghost-light" href="{{ route('website.achievements') }}">All results & achievements @icon('arrow-right','ic',18)</a></div>
    </div>
</section>
@endif

{{-- ============ GALLERY PREVIEW ============ --}}
@if($photos->count())
<section class="section">
    <div class="wrap">
        <div class="sec-head sec-head-row" data-reveal>
            <div>
                <span class="eyebrow">Life at school</span>
                <h2 class="h2">Moments from our campus</h2>
            </div>
            <a class="btn btn-outline" href="{{ route('website.gallery') }}">Open gallery @icon('arrow-right','ic',18)</a>
        </div>
        <div class="mosaic" data-lightbox-group>
            @foreach($photos as $i => $ph)
                <a class="mosaic-item m{{ $i }}" href="{{ Media::url($ph->image) }}" data-lightbox data-caption="{{ $ph->caption }}" data-reveal="zoom" style="--d:{{ $i * 60 }}ms">
                    <img src="{{ Media::url($ph->image) }}" alt="{{ $ph->caption ?: 'School photo' }}" loading="lazy">
                    @if($ph->caption)<span class="mosaic-cap">{{ $ph->caption }}</span>@endif
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ============ TESTIMONIALS ============ --}}
@if($testimonials->count())
<section class="section soft">
    <div class="wrap">
        <div class="sec-head center" data-reveal>
            <span class="eyebrow">Voices of our community</span>
            <h2 class="h2">What parents & alumni say</h2>
        </div>
        <div class="quotes" data-quotes data-reveal>
            @foreach($testimonials as $i => $t)
                <figure class="quote {{ $i === 0 ? 'is-active' : '' }}" data-quote>
                    @icon('quote','ic q-ic',36)
                    <blockquote>{{ $t->quote }}</blockquote>
                    <figcaption>
                        <span class="q-av">@if($t->photo)<img src="{{ Media::url($t->photo) }}" alt="" loading="lazy">@else{{ mb_substr($t->name, 0, 1) }}@endif</span>
                        <span><strong>{{ $t->name }}</strong><small>{{ $t->role }}</small></span>
                    </figcaption>
                </figure>
            @endforeach
            <div class="quote-dots">
                @foreach($testimonials as $i => $t)<button class="{{ $i === 0 ? 'is-active' : '' }}" data-qdot="{{ $i }}" aria-label="Show testimonial {{ $i + 1 }}"></button>@endforeach
            </div>
        </div>
    </div>
</section>
@endif

@include('website::site.partials.cta')
@endsection
