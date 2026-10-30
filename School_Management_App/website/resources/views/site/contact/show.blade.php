@extends('website::layouts.site')
@section('title', $page->title ?? 'Contact Us')
@section('content')
@include('website::site.partials.pagehead', ['title' => $page->title ?? 'Contact Us', 'subtitle' => $page->subtitle ?? null, 'crumbs' => ['Contact' => null]])
<section class="section">
    <div class="wrap">
        <div class="contact-cards">
            @if(!empty($s['address']))<div class="cc" data-reveal><span class="cc-ic">@icon('map-pin','ic',24)</span><strong>Address</strong><p>{!! nl2br(e($s['address'])) !!}</p></div>@endif
            @if(!empty($s['phone']))<div class="cc" data-reveal style="--d:80ms"><span class="cc-ic">@icon('phone','ic',24)</span><strong>Phone</strong><p><a href="tel:{{ preg_replace('/[^0-9+]/', '', $s['phone']) }}">{{ $s['phone'] }}</a>@if(!empty($s['phone_alt']))<br><a href="tel:{{ preg_replace('/[^0-9+]/', '', $s['phone_alt']) }}">{{ $s['phone_alt'] }}</a>@endif</p></div>@endif
            @if(!empty($s['email']))<div class="cc" data-reveal style="--d:160ms"><span class="cc-ic">@icon('mail','ic',24)</span><strong>Email</strong><p><a href="mailto:{{ $s['email'] }}">{{ $s['email'] }}</a></p></div>@endif
            @if(!empty($s['office_hours']))<div class="cc" data-reveal style="--d:240ms"><span class="cc-ic">@icon('clock','ic',24)</span><strong>Office hours</strong><p>{{ $s['office_hours'] }}</p></div>@endif
        </div>
    </div>
</section>
<section class="section soft" id="form">
    <div class="wrap form-wrap">
        <div data-reveal>
            <span class="eyebrow">Send us a message</span>
            <h2 class="h2">Questions, suggestions or feedback?</h2>
            @if($page && $page->body)<div class="prose">{!! $page->body !!}</div>@endif
            @if(!empty($s['map_embed_url']) && str_starts_with($s['map_embed_url'], 'https://'))
                <div class="map" data-map="{{ $s['map_embed_url'] }}">
                    <button class="map-load btn btn-outline" type="button">@icon('map-pin','ic',18) Show map</button>
                    <noscript><iframe src="{{ $s['map_embed_url'] }}" title="Map" loading="lazy"></iframe></noscript>
                </div>
            @endif
        </div>
        <div class="form-card" data-reveal="right">
            @if(session('contact_ok'))
                <div class="success" role="status">
                    <span class="success-ic">@icon('circle-check','ic',40)</span>
                    <h3>Message sent</h3>
                    <p>Thank you @if(is_string(session('contact_ok'))){{ session('contact_ok') }}@endif — the school office will reply soon.</p>
                    <a class="btn btn-outline" href="{{ route('website.contact') }}#form">Send another message</a>
                </div>
            @else
                <form method="post" action="{{ route('website.contact.submit') }}" class="form" novalidate data-validate>
                    @csrf
                    <div class="hp" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
                    @include('website::site.partials.field', ['name' => 'name', 'label' => 'Your name', 'required' => true])
                    @include('website::site.partials.field', ['name' => 'phone', 'label' => 'Mobile number', 'type' => 'tel'])
                    @include('website::site.partials.field', ['name' => 'email', 'label' => 'Email', 'type' => 'email'])
                    @include('website::site.partials.field', ['name' => 'subject', 'label' => 'Subject'])
                    @include('website::site.partials.field', ['name' => 'message', 'label' => 'Message', 'type' => 'textarea', 'required' => true, 'wide' => true])
                    <p class="ff-note ff-wide">Please give a phone number or an email so we can reply.</p>
                    <button class="btn btn-accent btn-lg btn-block ff-wide" type="submit">Send message @icon('send','ic',18)</button>
                </form>
            @endif
        </div>
    </div>
</section>
@endsection
