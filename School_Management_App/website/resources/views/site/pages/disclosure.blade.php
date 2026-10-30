@extends('website::layouts.site')
@php use Website\Support\Media; @endphp
@section('title', $page->title ?? 'Mandatory Public Disclosure')
@section('content')
@include('website::site.partials.pagehead', ['title' => $page->title ?? 'Mandatory Public Disclosure', 'subtitle' => $page->subtitle ?? null, 'crumbs' => ['About' => route('website.about'), 'Mandatory Disclosure' => null]])
<section class="section">
    <div class="wrap">
        @if($page && $page->body)<div class="prose narrow" data-reveal>{!! $page->body !!}</div>@endif
        @foreach($sections as $section => $rows)
            <div class="disc" data-reveal>
                <h2 class="h3"><span class="disc-letter">{{ chr(64 + $loop->iteration) }}</span>{{ $section }}</h2>
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th style="width:3.5rem">S.No.</th><th>Information</th><th>Details</th></tr></thead>
                        <tbody>
                            @foreach($rows as $k => $r)
                                <tr>
                                    <td>{{ $k + 1 }}</td>
                                    <td>{{ $r->label }}</td>
                                    <td>
                                        @if($r->value){{ $r->value }}@endif
                                        @if($r->document)<a class="doc-link" href="{{ Media::url($r->document) }}" target="_blank" rel="noopener">@icon('file-text','ic',16) View document</a>@endif
                                        @if(!$r->value && !$r->document)—@endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    </div>
</section>
@endsection
