{{-- Public form field. $name, $label, $type, $required, $options, $placeholder, $wide --}}
@php $type = $type ?? 'text'; $req = $required ?? false; $id = 'f_'.$name; @endphp
<label class="ff {{ !empty($wide) ? 'ff-wide' : '' }} {{ $errors->has($name) ? 'has-err' : '' }}" for="{{ $id }}">
    <span class="ff-label">{{ $label }} @if($req)<em aria-hidden="true">*</em>@endif</span>
    @if($type === 'textarea')
        <textarea id="{{ $id }}" name="{{ $name }}" rows="4" @if($req) required @endif placeholder="{{ $placeholder ?? '' }}">{{ old($name) }}</textarea>
    @elseif($type === 'select')
        <select id="{{ $id }}" name="{{ $name }}" @if($req) required @endif>
            <option value="">— Select —</option>
            @foreach($options as $o)<option value="{{ $o }}" @selected(old($name) === $o)>{{ $o }}</option>@endforeach
        </select>
    @else
        <input id="{{ $id }}" type="{{ $type }}" name="{{ $name }}" value="{{ old($name) }}" @if($req) required @endif placeholder="{{ $placeholder ?? '' }}" @if($type === 'tel') inputmode="tel" autocomplete="tel" @endif>
    @endif
    @error($name)<span class="ff-err">{{ $message }}</span>@enderror
</label>
