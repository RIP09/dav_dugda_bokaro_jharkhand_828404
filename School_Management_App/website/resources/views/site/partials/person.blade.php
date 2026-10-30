@php use Website\Support\Media; @endphp
<article class="person" data-reveal style="--d:{{ ($i ?? 0) % 4 * 70 }}ms">
    <div class="person-photo">
        @if($p->photo)
            <img src="{{ Media::url($p->photo) }}" alt="{{ $p->name }}" loading="lazy">
        @else
            <span class="initials">{{ collect(preg_split('/\s+/', preg_replace('/[^\pL\s]/u', '', $p->name)))->filter()->map(fn($w) => mb_substr($w, 0, 1))->take(2)->implode('') ?: '•' }}</span>
        @endif
    </div>
    <h3>{{ $p->name }}</h3>
    <p class="person-role">{{ $p->designation }}</p>
    @if($p->qualification)<p class="person-meta">{{ $p->qualification }}</p>@endif
    @if($p->experience)<p class="person-meta">@icon('briefcase','ic',14) {{ $p->experience }}</p>@endif
    @if($p->email)<a class="person-meta" href="mailto:{{ $p->email }}">@icon('mail','ic',14) {{ $p->email }}</a>@endif
    @if($p->bio)<p class="person-bio">{{ $p->bio }}</p>@endif
</article>
