<?php

namespace Website\Modules\Home;

use Illuminate\Routing\Controller;
use Website\Modules\Achievements\Achievement;
use Website\Modules\Banners\Banner;
use Website\Modules\Events\Event;
use Website\Modules\Facilities\Facility;
use Website\Modules\Gallery\Photo;
use Website\Modules\Notices\Notice;
use Website\Modules\Testimonials\Testimonial;

class HomeController extends Controller
{
    public function index()
    {
        return view('website::site.home.index', [
            'banners' => Banner::where('is_active', true)->orderBy('sort')->get(),
            'stats' => Stat::orderBy('sort')->get(),
            'highlights' => Highlight::published()->orderBy('sort')->limit(6)->get(),
            'notices' => Notice::published()->orderByDesc('is_pinned')->orderByDesc('published_at')->limit(5)->get(),
            'events' => Event::published()->where('starts_at', '>=', now()->startOfDay())->orderBy('starts_at')->limit(3)->get(),
            'facilities' => Facility::published()->orderBy('sort')->limit(8)->get(),
            'toppers' => Achievement::published()->orderBy('sort')->limit(4)->get(),
            'testimonials' => Testimonial::published()->orderBy('sort')->get(),
            'photos' => Photo::query()->whereHas('album', fn ($q) => $q->where('is_published', true))->latest('id')->limit(7)->get(),
        ]);
    }
}
