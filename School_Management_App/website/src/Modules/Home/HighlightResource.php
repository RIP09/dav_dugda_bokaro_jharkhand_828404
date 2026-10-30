<?php

namespace Website\Modules\Home;

use Illuminate\Database\Eloquent\Model;
use Website\Support\F;
use Website\Support\Resource;

class HighlightResource extends Resource
{

    public static string $key = 'highlights';
    public static string $label = 'Why Choose Us';
    public static string $singular = 'highlight';
    public static string $model = Highlight::class;
    public static string $icon = 'sparkles';
    public static string $group = 'Home page';
    public static ?string $help = 'Short highlight cards on the home page.';

    public static function fields(): array
    {
        return [
            F::text('title', 'Title', ['required' => true]),
            F::textarea('summary', 'Short text'),
            F::icon('icon', 'Icon'),
            F::sort(),
            F::toggle('is_published', 'Show on website', ['default' => true]),
        ];
    }

    public static function columns(): array
    {
        return ['title' => 'Title', 'icon' => 'Icon', 'sort' => 'Order', 'is_published' => 'Live'];
    }

}
