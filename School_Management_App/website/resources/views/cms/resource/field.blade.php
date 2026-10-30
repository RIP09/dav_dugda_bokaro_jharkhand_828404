@php
    use Website\Support\Media; use Website\Support\Icons;
    $n = $f['name']; $type = $f['type'];
    $val = old($n, $item->{$n});
    if ($val instanceof \DateTimeInterface) $val = $type === 'datetime' ? $val->format('Y-m-d\TH:i') : $val->format('Y-m-d');
    $id = 'fld_'.$n;
@endphp
<div class="fld {{ $f['wide'] || in_array($type, ['rich', 'textarea']) ? 'wide' : '' }} {{ $errors->has($n) ? 'bad' : '' }}">
    <label for="{{ $id }}">{{ $f['label'] }} @if($f['required'])<em>*</em>@endif</label>
    @switch($type)
        @case('textarea')
            <textarea id="{{ $id }}" name="{{ $n }}" rows="4" @if($f['required']) required @endif>{{ $val }}</textarea>
            @break
        @case('rich')
            <div class="rich" data-rich="{{ $id }}"><div class="rich-editor">{!! $val !!}</div></div>
            <textarea id="{{ $id }}" name="{{ $n }}" hidden>{{ $val }}</textarea>
            @break
        @case('number')
            <input id="{{ $id }}" type="number" name="{{ $n }}" value="{{ $val }}" @if($f['required']) required @endif>
            @break
        @case('date')
            <input id="{{ $id }}" type="date" name="{{ $n }}" value="{{ $val }}" @if($f['required']) required @endif>
            @break
        @case('datetime')
            <input id="{{ $id }}" type="datetime-local" name="{{ $n }}" value="{{ $val }}" @if($f['required']) required @endif>
            @break
        @case('select')
            <select id="{{ $id }}" name="{{ $n }}" @if($f['required']) required @endif>
                @foreach($f['options'] as $k => $l)<option value="{{ $k }}" @selected((string) $val === (string) $k)>{{ $l }}</option>@endforeach
            </select>
            @break
        @case('icon')
            <div class="icon-pick">
                <select id="{{ $id }}" name="{{ $n }}" data-icon-select>
                    <option value="">— none —</option>
                    @foreach($f['options'] as $k => $l)<option value="{{ $k }}" @selected($val === $k)>{{ str_replace('-', ' ', $l) }}</option>@endforeach
                </select>
                <span class="icon-preview" data-icon-preview>{!! Icons::svg($val ?: 'sparkles', 'ic', 22) !!}</span>
            </div>
            <template id="icon-svgs">@foreach($f['options'] as $k => $l)<span data-i="{{ $k }}">{!! Icons::svg($k, 'ic', 22) !!}</span>@endforeach</template>
            @break
        @case('toggle')
            <label class="switch"><input id="{{ $id }}" type="checkbox" name="{{ $n }}" value="1" @checked($val)><span></span><b>{{ $val ? 'Yes' : 'No' }}</b></label>
            @break
        @case('image')
            <div class="upload" data-upload>
                <div class="upload-prev">
                    @if($item->{$n})<img src="{{ Media::url($item->{$n}) }}" alt="">@else<span class="ph">@icon('image','ic',28)</span>@endif
                </div>
                <div class="upload-ctl">
                    <input id="{{ $id }}" type="file" name="{{ $n }}" accept="image/*">
                    <small class="muted">JPG, PNG or WebP. Big photos are shrunk automatically.</small>
                    @if($item->{$n} && !$f['required'])<label class="check"><input type="checkbox" name="_remove_{{ $n }}" value="1"> Remove this photo</label>@endif
                </div>
            </div>
            @break
        @case('file')
            <div class="upload-ctl">
                @if($item->{$n})<a class="file-cur" href="{{ Media::url($item->{$n}) }}" target="_blank" rel="noopener">@icon('file-text','ic',16) Current file ({{ Media::extension($item->{$n}) }}{{ Media::sizeLabel($item->{$n}) ? ', '.Media::sizeLabel($item->{$n}) : '' }})</a>@endif
                <input id="{{ $id }}" type="file" name="{{ $n }}">
                <small class="muted">PDF, Word, Excel, PowerPoint or image, up to 20 MB.</small>
                @if($item->{$n})<label class="check"><input type="checkbox" name="_remove_{{ $n }}" value="1"> Remove this file</label>@endif
            </div>
            @break
        @default
            <input id="{{ $id }}" type="{{ in_array($type, ['email', 'url']) ? $type : 'text' }}" name="{{ $n }}" value="{{ $val }}" @if($f['required']) required @endif @if($type === 'slug') data-slug-from="fld_{{ $f['from'] }}" placeholder="made from the title" @endif>
    @endswitch
    @if($f['help'])<small class="help">{{ $f['help'] }}</small>@endif
    @error($n)<small class="err">{{ $message }}</small>@enderror
</div>
