<?php

namespace Website\Modules\Events;

use Illuminate\Database\Eloquent\Model;
use Website\Support\F;
use Website\Support\Resource;

class EventResource extends Resource
{

    public static string $key = 'events';
    public static string $label = 'Events';
    public static string $singular = 'event';
    public static string $model = Event::class;
    public static string $icon = 'calendar-days';
    public static string $orderBy = 'starts_at';
    public static string $orderDir = 'desc';

    public static function fields(): array
    {
        return [
            F::text('title', 'Event name', ['required' => true, 'wide' => true]),
            F::datetime('starts_at', 'Starts', ['required' => true]),
            F::datetime('ends_at', 'Ends (optional)'),
            F::text('venue', 'Venue'),
            F::image('image', 'Photo'),
            F::rich('description', 'Details'),
            F::slug('slug', 'Web address (slug)'),
            F::toggle('is_published', 'Show on website', ['default' => true]),
        ];
    }

    public static function columns(): array
    {
        return ['title' => 'Event', 'starts_at' => 'Date', 'venue' => 'Venue', 'is_published' => 'Live'];
    }

    public static function publicUrl(Model $m): ?string
    {
        return route('website.events.show', $m->slug);
    }

}
