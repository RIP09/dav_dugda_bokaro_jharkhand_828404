<?php

namespace Website\Modules\Gallery;

use Illuminate\Routing\Controller;

class GalleryController extends Controller
{
    public function index()
    {
        return view('website::site.gallery.index', [
            'albums' => Album::published()->with('photos')->withCount('photos')->orderBy('sort')->orderByDesc('event_date')->get(),
        ]);
    }

    public function show(string $slug)
    {
        $album = Album::published()->where('slug', $slug)->with('photos')->firstOrFail();

        return view('website::site.gallery.show', [
            'album' => $album,
            'others' => Album::published()->where('id', '!=', $album->id)->with('photos')->orderByDesc('event_date')->limit(3)->get(),
        ]);
    }
}
