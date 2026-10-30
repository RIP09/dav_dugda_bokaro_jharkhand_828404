@extends('website::layouts.cms')
@php use Website\Support\Media; use Illuminate\Support\Str; $editing = $item->exists; @endphp
@section('title', ($editing ? 'Edit ' : 'Add ').$r::$singular)
@push('head')<link rel="stylesheet" href="{{ Media::asset('vendor/quill/quill.snow.css') }}">@endpush
@push('scripts')<script src="{{ Media::asset('vendor/quill/quill.js') }}" defer></script>@endpush
@section('content')
<div class="page-head">
    <div>
        <a class="crumb" href="{{ route('webadmin.r.index', $r::$key) }}">← {{ $r::$label }}</a>
        <h1>{{ $editing ? $r::title($item) : 'Add '.$r::$singular }}</h1>
        @if($editing)<p class="muted small">Last edited {{ $item->updated_at?->format('d M Y, g:i A') }} @if($item->editor) by <strong>{{ $item->editor->name }}</strong>@endif</p>@endif
    </div>
    @if($editing && ($url = $r::publicUrl($item)))<a class="btn ghost" href="{{ $url }}" target="_blank" rel="noopener">@icon('eye','ic',18) View on website</a>@endif
</div>

<form method="post" enctype="multipart/form-data" action="{{ $editing ? route('webadmin.r.update', [$r::$key, $item->id]) : route('webadmin.r.store', $r::$key) }}" class="card form-grid" data-cms-form>
    @csrf
    @if($editing) @method('PUT') @endif
    @foreach($r::fields() as $f)
        @include('website::cms.resource.field', ['f' => $f, 'item' => $item])
    @endforeach
    <div class="form-actions wide">
        <button class="btn primary" type="submit">@icon('check','ic',18) Save</button>
        @if(!$editing)<button class="btn" type="submit" name="_after" value="new">Save & add another</button>@endif
        <a class="btn ghost" href="{{ route('webadmin.r.index', $r::$key) }}">Cancel</a>
    </div>
</form>

@if($editing && $r::$key === 'gallery')
    @include('website::cms.gallery.photos', ['album' => $item])
@endif

@if($editing)
<div class="grid2 mt">
    <section class="card">
        <h2>Change history</h2>
        <ul class="log">
            @forelse($history as $h)
                <li><span class="dot dot-{{ $h->action }}"></span><span><strong>{{ $h->admin_name ?? '—' }}</strong> {{ $h->action }} · <small class="muted">{{ \Carbon\Carbon::parse($h->created_at)->format('d M Y, g:i A') }}</small>
                    @if($h->changes)
                        <ul class="diff">@foreach(json_decode($h->changes, true) as $field => [$old, $new])<li><b>{{ str_replace('_', ' ', $field) }}</b>: <del>{{ Str::limit($old ?? '—', 60) }}</del> → <ins>{{ Str::limit($new ?? '—', 60) }}</ins></li>@endforeach</ul>
                    @endif
                </span></li>
            @empty
                <li class="muted">No changes recorded yet.</li>
            @endforelse
        </ul>
    </section>
    <section class="card danger-zone">
        <h2>Delete</h2>
        @if($r::deletable($item))
            <p class="muted">Deleting removes this {{ $r::$singular }} from the website for good. If you only want to hide it, switch off "Show on website" instead.</p>
            <form method="post" action="{{ route('webadmin.r.destroy', [$r::$key, $item->id]) }}" data-confirm="Delete this {{ $r::$singular }}? This cannot be undone.">
                @csrf @method('DELETE')
                <button class="btn danger" type="submit">@icon('trash-2','ic',18) Delete {{ $r::$singular }}</button>
            </form>
        @else
            <p class="muted">@icon('lock','ic',16) This is a built-in page of the website, so it cannot be deleted. You can still edit it, or hide it with "Show on website".</p>
        @endif
    </section>
</div>
@endif
@endsection
