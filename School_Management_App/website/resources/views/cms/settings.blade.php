@extends('website::layouts.cms')
@php use Website\Support\Media; @endphp
@section('title', 'Site settings')
@section('content')
<div class="page-head"><div><h1>Site settings</h1><p class="muted">School name, logo, contact details, social links, colours and Google search text. Changes show on the website immediately.</p></div></div>
<form method="post" enctype="multipart/form-data" action="{{ route('webadmin.settings.update') }}" class="stack-lg">
    @csrf
    @foreach($schema as $group => $fields)
        <section class="card form-grid">
            <h2 class="wide">{{ $group }}</h2>
            @foreach($fields as [$key, $label, $type])
                @php $val = old($key, $values[$key] ?? ''); @endphp
                <div class="fld {{ $type === 'textarea' ? 'wide' : '' }} {{ $errors->has($key) ? 'bad' : '' }}">
                    <label for="s_{{ $key }}">{{ $label }}</label>
                    @if($type === 'textarea')
                        <textarea id="s_{{ $key }}" name="{{ $key }}" rows="3">{{ $val }}</textarea>
                    @elseif($type === 'image')
                        <div class="upload" data-upload>
                            <div class="upload-prev small">@if($val)<img src="{{ Media::url($val) }}" alt="">@else<span class="ph">@icon('image','ic',22)</span>@endif</div>
                            <div class="upload-ctl">
                                <input id="s_{{ $key }}" type="file" name="{{ $key }}" accept="image/*">
                                @if($val)<label class="check"><input type="checkbox" name="_remove_{{ $key }}" value="1"> Remove (use the default)</label>@endif
                            </div>
                        </div>
                    @elseif($type === 'color')
                        <div class="color-row"><input id="s_{{ $key }}" type="color" name="{{ $key }}" value="{{ $val ?: '#1B2A7A' }}"><code>{{ $val }}</code></div>
                    @elseif($type === 'toggle')
                        <input type="hidden" name="{{ $key }}" value="0">
                        <label class="switch"><input id="s_{{ $key }}" type="checkbox" name="{{ $key }}" value="1" @checked($val === '1')><span></span><b>{{ $val === '1' ? 'Yes' : 'No' }}</b></label>
                    @else
                        <input id="s_{{ $key }}" type="text" name="{{ $key }}" value="{{ $val }}">
                    @endif
                    @error($key)<small class="err">{{ $message }}</small>@enderror
                </div>
            @endforeach
        </section>
    @endforeach
    <div class="sticky-save"><button class="btn primary" type="submit">@icon('check','ic',18) Save settings</button></div>
</form>
@endsection
