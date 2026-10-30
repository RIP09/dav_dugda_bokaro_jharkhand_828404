<?php

namespace Website\Modules\Gallery;

use Illuminate\Database\Eloquent\Model;
use Website\Support\F;
use Website\Support\Resource;

class AlbumResource extends Resource
{

    public static string $key = 'gallery';
    public static string $label = 'Photo Gallery';
    public static string $singular = 'album';
    public static string $model = Album::class;
    public static string $icon = 'image';
    public static string $orderBy = 'event_date';
    public static string $orderDir = 'desc';
    public static ?string $help = 'Make an album, save it, then add many photos at once on the album\'s page.';

    public static function fields(): array
    {
        return [
            F::text('title', 'Album name', ['required' => true]),
            F::date('event_date', 'Date'),
            F::textarea('description', 'Short description'),
            F::image('cover', 'Cover photo (optional — first photo is used otherwise)'),
            F::slug('slug', 'Web address (slug)'),
            F::sort(),
            F::toggle('is_published', 'Show on website', ['default' => true]),
        ];
    }

    public static function columns(): array
    {
        return ['cover' => 'Cover', 'title' => 'Album', 'event_date' => 'Date', 'photos_count' => 'Photos', 'is_published' => 'Live'];
    }

    public static function query()
    {
        return Album::query()->withCount('photos');
    }

    public static function publicUrl(Model $m): ?string
    {
        return route('website.gallery.show', $m->slug);
    }

}
