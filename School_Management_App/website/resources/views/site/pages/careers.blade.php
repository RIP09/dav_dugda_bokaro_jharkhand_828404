@extends('website::layouts.site')
@section('title', $page->title ?? 'Careers')
@section('content')
@include('website::site.partials.pagehead', ['title' => $page->title ?? 'Careers', 'subtitle' => $page->subtitle ?? null, 'crumbs' => ['Careers' => null]])
<section class="section">
    <div class="wrap narrow-wrap">
        @if($page && $page->body)<div class="prose" data-reveal>{!! $page->body !!}</div>@endif
        <div class="vacancies">
            @forelse($vacancies as $i => $v)
                <details class="vacancy" data-reveal style="--d:{{ $i * 70 }}ms" @if($loop->first) open @endif>
                    <summary>
                        <span class="vac-ic">@icon('briefcase','ic',22)</span>
                        <span class="vac-head"><strong>{{ $v->title }}</strong>
                            <small>{{ $v->department }} · {{ $v->positions }} post{{ $v->positions > 1 ? 's' : '' }} @if($v->last_date)· Apply by {{ $v->last_date->format('d M Y') }}@endif</small></span>
                        @icon('chevron-down','ic vac-caret',20)
                    </summary>
                    <div class="vac-body prose">
                        <p><strong>Qualification:</strong> {{ $v->qualification ?: '—' }}<br><strong>Experience:</strong> {{ $v->experience ?: '—' }}</p>
                        {!! $v->description !!}
                        @if(!empty($s['email']))<a class="btn btn-primary" href="mailto:{{ $s['email'] }}?subject={{ rawurlencode('Application for '.$v->title) }}">@icon('send','ic',18) Apply by email</a>@endif
                    </div>
                </details>
            @empty
                <div class="empty-state" data-reveal>@icon('briefcase','ic',40)<p>There are no open vacancies right now.</p></div>
            @endforelse
        </div>
    </div>
</section>
@endsection
