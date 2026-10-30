<?php

namespace Website\Modules\Notices;

use Illuminate\Database\Eloquent\Model;
use Website\Support\F;
use Website\Support\Resource;

class NoticeResource extends Resource
{

    public static string $key = 'notices';
    public static string $label = 'Notices & News';
    public static string $singular = 'notice';
    public static string $model = Notice::class;
    public static string $icon = 'megaphone';
    public static string $orderBy = 'published_at';
    public static string $orderDir = 'desc';
    public static ?string $help = 'Notices, circulars and school news. Pinned notices stay on top; "Show in scrolling bar" puts the title in the moving strip on the home page.';

    public static function fields(): array
    {
        return [
            F::text('title', 'Title', ['required' => true, 'wide' => true]),
            F::select('category', 'Type', ['notice' => 'Notice', 'circular' => 'Circular', 'news' => 'News']),
            F::date('published_at', 'Date', ['default' => 'today']),
            F::rich('body', 'Details'),
            F::image('image', 'Photo (optional)'),
            F::file('attachment', 'Attach a PDF / file (optional)'),
            F::slug('slug', 'Web address (slug)'),
            F::toggle('is_pinned', 'Pin to top'),
            F::toggle('in_ticker', 'Show in scrolling bar', ['default' => true]),
            F::toggle('is_published', 'Show on website', ['default' => true]),
        ];
    }

    public static function columns(): array
    {
        return ['title' => 'Title', 'category' => 'Type', 'published_at' => 'Date', 'is_pinned' => 'Pinned', 'is_published' => 'Live'];
    }

    public static function filters(): array
    {
        return ['category' => ['notice' => 'Notices', 'circular' => 'Circulars', 'news' => 'News']];
    }

    public static function publicUrl(Model $m): ?string
    {
        return route('website.notices.show', $m->slug);
    }

}
