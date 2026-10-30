<?php

namespace Website\Modules\Pages;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Route;
use Website\Modules\Careers\Vacancy;
use Website\Modules\Disclosure\Disclosure;
use Website\Modules\Facilities\Facility;
use Website\Modules\Faculty\Person;

class PageController extends Controller
{
    /** Built-in pages that have their own address and layout. */
    public const NAMED = [
        'about' => 'website.about',
        'principal-message' => 'website.principal',
        'management' => 'website.management',
        'academics' => 'website.academics',
        'admissions' => 'website.admissions',
        'facilities' => 'website.facilities',
        'faculty' => 'website.faculty',
        'mandatory-disclosure' => 'website.disclosure',
        'careers' => 'website.careers',
        'contact' => 'website.contact',
        'achievements' => 'website.achievements',
        'notices' => 'website.notices',
        'events' => 'website.events',
        'gallery' => 'website.gallery',
        'downloads' => 'website.downloads',
    ];

    public static function urlFor(string $slug): string
    {
        return isset(self::NAMED[$slug]) && Route::has(self::NAMED[$slug]) ? route(self::NAMED[$slug]) : route('website.page', $slug);
    }

    protected function page(string $slug): ?Page
    {
        return Page::where('slug', $slug)->where('is_published', true)->first();
    }

    public function about()
    {
        return view('website::site.pages.about', ['page' => $this->page('about')]);
    }

    public function principal()
    {
        return view('website::site.pages.principal', ['page' => $this->page('principal-message')]);
    }

    public function management()
    {
        return view('website::site.pages.management', [
            'page' => $this->page('management'),
            'people' => Person::published()->where('group', 'management')->orderBy('sort')->get(),
        ]);
    }

    public function academics()
    {
        return view('website::site.pages.academics', ['page' => $this->page('academics')]);
    }

    public function facilities()
    {
        return view('website::site.pages.facilities', [
            'page' => $this->page('facilities'),
            'facilities' => Facility::published()->orderBy('sort')->get(),
        ]);
    }

    public function facility(string $slug)
    {
        $f = Facility::published()->where('slug', $slug)->firstOrFail();

        return view('website::site.pages.facility', ['facility' => $f, 'others' => Facility::published()->where('id', '!=', $f->id)->orderBy('sort')->limit(4)->get()]);
    }

    public function faculty()
    {
        $people = Person::published()->whereIn('group', ['teaching', 'office'])->orderBy('sort')->orderBy('name')->get();

        return view('website::site.pages.faculty', [
            'page' => $this->page('faculty'),
            'teachers' => $people->where('group', 'teaching'),
            'office' => $people->where('group', 'office'),
            'departments' => $people->where('group', 'teaching')->pluck('department')->filter()->unique()->sort()->values(),
        ]);
    }

    public function disclosure()
    {
        return view('website::site.pages.disclosure', [
            'page' => $this->page('mandatory-disclosure'),
            'sections' => Disclosure::orderBy('sort')->orderBy('id')->get()->groupBy('section'),
        ]);
    }

    public function careers()
    {
        return view('website::site.pages.careers', [
            'page' => $this->page('careers'),
            'vacancies' => Vacancy::where('is_open', true)->where(fn ($q) => $q->whereNull('last_date')->orWhere('last_date', '>=', now()->toDateString()))->latest()->get(),
        ]);
    }

    public function show(string $slug)
    {
        if (isset(self::NAMED[$slug]) && Route::has(self::NAMED[$slug])) {
            return redirect()->route(self::NAMED[$slug], [], 301);
        }
        $page = Page::where('slug', $slug)->where('is_published', true)->firstOrFail();

        return view('website::site.pages.generic', ['page' => $page]);
    }
}
