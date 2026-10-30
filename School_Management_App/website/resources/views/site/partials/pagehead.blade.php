@php $img = $image ?? null; @endphp
<section class="pagehead {{ $img ? 'has-img' : '' }}">
    @if($img)<img class="pagehead-bg" src="{{ $img }}" alt="" fetchpriority="high">@endif
    <div class="pagehead-shapes" aria-hidden="true"><span></span><span></span></div>
    <div class="wrap pagehead-in">
        <nav class="crumbs" aria-label="Breadcrumb">
            <a href="{{ route('website.home') }}">@icon('home','ic',15) Home</a>
            @foreach(($crumbs ?? []) as $label => $url)
                @icon('chevron-right','ic',14)
                @if($url)<a href="{{ $url }}">{{ $label }}</a>@else<span>{{ $label }}</span>@endif
            @endforeach
        </nav>
        <h1 class="split" data-split>{{ $title }}</h1>
        @if(!empty($subtitle))<p class="lead">{{ $subtitle }}</p>@endif
    </div>
</section>
