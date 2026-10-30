@extends('website::layouts.site')
@php use Website\Support\Media; @endphp
@section('title', $page->title ?? 'Admissions')
@section('description', 'Admissions at DAV Public School, Dugda — process, eligibility, documents and online enquiry form.')
@section('content')
@include('website::site.partials.pagehead', ['title' => $page->title ?? 'Admissions', 'subtitle' => $page->subtitle ?? null, 'image' => Media::url($page->image ?? null), 'crumbs' => ['Admissions' => null]])
<section class="section">
    <div class="wrap">
        <div class="two-col">
            <article class="prose" data-reveal>{!! $page->body ?? '' !!}</article>
            <aside class="aside" data-reveal="right">
                @if($forms->count())
                    <h3 class="aside-title">Forms & prospectus</h3>
                    <div class="dl-list">
                        @foreach($forms as $f)
                            <a class="dl" href="{{ Media::url($f->file) }}" target="_blank" rel="noopener">
                                <span class="dl-ext">{{ Media::extension($f->file) }}</span>
                                <span><strong>{{ $f->title }}</strong><small>{{ Media::sizeLabel($f->file) }}</small></span>
                                @icon('download','ic',18)
                            </a>
                        @endforeach
                    </div>
                @endif
                @if(!empty($s['phone']))
                <div class="aside-card help">
                    <strong>Need help?</strong>
                    <p>Call the admission desk @if(!empty($s['office_hours']))({{ $s['office_hours'] }})@endif</p>
                    <a class="btn btn-primary btn-block" href="tel:{{ preg_replace('/[^0-9+]/', '', $s['phone']) }}">@icon('phone','ic',18) {{ $s['phone'] }}</a>
                </div>
                @endif
                <div class="aside-card">
                    <strong>Classes</strong>
                    <p>Nursery to Class XII · Science and Commerce in Classes XI–XII</p>
                </div>
            </aside>
        </div>
    </div>
</section>

<section class="section soft" id="enquiry">
    <div class="wrap form-wrap">
        <div class="form-intro" data-reveal>
            <span class="eyebrow">Online admission enquiry</span>
            <h2 class="h2">Tell us about your child</h2>
            <p>Fill this short form. It goes straight to the school office.</p>
            <ul class="ticks">
                <li>@icon('check','ic',18) Your details stay with the school only</li>
            </ul>
        </div>
        <div class="form-card" data-reveal="right">
            @if(session('enquiry_ok'))
                <div class="success" role="status">
                    <span class="success-ic">@icon('circle-check','ic',40)</span>
                    <h3>Thank you!</h3>
                    <p>We have received the enquiry @if(is_string(session('enquiry_ok')))for <strong>{{ session('enquiry_ok') }}</strong>@endif. The school office will contact you soon.</p>
                    <a class="btn btn-outline" href="{{ route('website.admissions') }}#enquiry">Send another enquiry</a>
                </div>
            @else
                <form method="post" action="{{ route('website.admissions.submit') }}" class="form" novalidate data-validate>
                    @csrf
                    <div class="hp" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
                    @include('website::site.partials.field', ['name' => 'student_name', 'label' => "Child's full name", 'required' => true])
                    @include('website::site.partials.field', ['name' => 'class_sought', 'label' => 'Admission sought for', 'type' => 'select', 'options' => $classes, 'required' => true])
                    @include('website::site.partials.field', ['name' => 'dob', 'label' => 'Date of birth', 'type' => 'date'])
                    @include('website::site.partials.field', ['name' => 'parent_name', 'label' => "Parent / guardian's name", 'required' => true])
                    @include('website::site.partials.field', ['name' => 'phone', 'label' => 'Mobile number', 'type' => 'tel', 'required' => true, 'placeholder' => '10-digit mobile'])
                    @include('website::site.partials.field', ['name' => 'email', 'label' => 'Email (optional)', 'type' => 'email'])
                    @include('website::site.partials.field', ['name' => 'previous_school', 'label' => 'Previous school (if any)', 'wide' => true])
                    @include('website::site.partials.field', ['name' => 'message', 'label' => 'Anything you would like to ask?', 'type' => 'textarea', 'wide' => true])
                    <button class="btn btn-accent btn-lg btn-block ff-wide" type="submit">Send enquiry @icon('send','ic',18)</button>
                </form>
            @endif
        </div>
    </div>
</section>
@endsection
