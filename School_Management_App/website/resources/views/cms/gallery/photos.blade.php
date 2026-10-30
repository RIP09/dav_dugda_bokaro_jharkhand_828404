@php use Website\Support\Media; @endphp
<section class="card mt" id="photos">
    <div class="card-head"><h2>Photos in this album ({{ $album->photos->count() }})</h2></div>
    <form method="post" enctype="multipart/form-data" action="{{ route('webadmin.gallery.upload', $album) }}" class="dropzone" data-dropzone>
        @csrf
        @icon('upload','ic',30)
        <strong>Add photos</strong>
        <span class="muted">Choose many photos at once (up to 40). Tap to pick, or drag & drop here.</span>
        <input type="file" name="photos[]" accept="image/*" multiple required>
        <button class="btn primary" type="submit">Upload photos</button>
    </form>
    @error('photos')<p class="err">{{ $message }}</p>@enderror
    @error('photos.*')<p class="err">{{ $message }}</p>@enderror
    @if($album->photos->count())
        <form method="post" action="{{ route('webadmin.gallery.photos.update', $album) }}" id="captions">@csrf @method('PUT')</form>
        <div class="photo-grid">
            @foreach($album->photos as $p)
                <div class="photo {{ $album->cover === $p->image ? 'is-cover' : '' }}">
                    <img src="{{ Media::url($p->image) }}" alt="" loading="lazy">
                    @if($album->cover === $p->image)<span class="cover-tag">Cover</span>@endif
                    <input form="captions" type="text" name="caption[{{ $p->id }}]" value="{{ $p->caption }}" placeholder="Caption (optional)">
                    <div class="photo-row">
                        <input form="captions" type="number" name="sort[{{ $p->id }}]" value="{{ $p->sort }}" title="Order" aria-label="Order">
                        <form method="post" action="{{ route('webadmin.gallery.photos.cover', [$album, $p]) }}">@csrf<button class="icon-btn" title="Use as cover">@icon('star','ic',16)</button></form>
                        <form method="post" action="{{ route('webadmin.gallery.photos.destroy', [$album, $p]) }}" data-confirm="Remove this photo?">@csrf @method('DELETE')<button class="icon-btn danger" title="Remove">@icon('trash-2','ic',16)</button></form>
                    </div>
                </div>
            @endforeach
        </div>
        <button class="btn" form="captions" type="submit">@icon('check','ic',18) Save captions & order</button>
    @endif
</section>
