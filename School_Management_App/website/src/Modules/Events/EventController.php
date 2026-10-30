<?php

namespace Website\Modules\Events;

use Illuminate\Routing\Controller;

class EventController extends Controller
{
    public function index()
    {
        $today = now()->startOfDay();

        return view('website::site.events.index', [
            'upcoming' => Event::published()->where('starts_at', '>=', $today)->orderBy('starts_at')->get(),
            'past' => Event::published()->where('starts_at', '<', $today)->orderByDesc('starts_at')->limit(12)->get(),
        ]);
    }

    public function show(string $slug)
    {
        $event = Event::published()->where('slug', $slug)->firstOrFail();

        return view('website::site.events.show', [
            'event' => $event,
            'more' => Event::published()->where('id', '!=', $event->id)->where('starts_at', '>=', now()->startOfDay())->orderBy('starts_at')->limit(3)->get(),
        ]);
    }
}
