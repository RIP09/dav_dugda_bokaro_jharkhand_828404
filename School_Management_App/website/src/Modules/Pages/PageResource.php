<?php

namespace Website\Modules\Pages;

use Illuminate\Database\Eloquent\Model;
use Website\Support\F;
use Website\Support\Resource;

class PageResource extends Resource
{

    public static string $key = 'pages';
    public static string $label = 'Pages';
    public static string $singular = 'page';
    public static string $model = Page::class;
    public static string $icon = 'file-pen-line';
    public static string $orderBy = 'title';
    public static ?string $help = 'The text of every page on the website: About, Academics, Admissions, Principal\'s message and more. You can also add a new page and link it from Menus.';

    public static function fields(): array
    {
        return [
            F::text('title', 'Page title', ['required' => true]),
            F::slug('slug', 'Web address (slug)'),
            F::text('subtitle', 'Subtitle (short line under the title)', ['wide' => true]),
            F::rich('body', 'Page text'),
            F::image('image', 'Header photo'),
            F::textarea('meta_description', 'Description for Google (optional)'),
            F::toggle('is_published', 'Show on website', ['default' => true]),
        ];
    }

    public static function columns(): array
    {
        return ['title' => 'Title', 'slug' => 'Address', 'is_published' => 'Live'];
    }

    public static function publicUrl(Model $m): ?string
    {
        return \Website\Modules\Pages\PageController::urlFor($m->slug);
    }

    public static function deletable(Model $m): bool
    {
        return ! $m->is_system;
    }

}
