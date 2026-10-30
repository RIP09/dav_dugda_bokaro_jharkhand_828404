<?php

namespace Website\Modules\Banners;

use Illuminate\Database\Eloquent\Model;
use Website\Support\F;
use Website\Support\Resource;

class BannerResource extends Resource
{

    public static string $key = 'banners';
    public static string $label = 'Home Slider';
    public static string $singular = 'slide';
    public static string $model = Banner::class;
    public static string $icon = 'images';
    public static string $group = 'Home page';
    public static ?string $help = 'The big sliding photos at the top of the home page. Wide photos (landscape) look best.';

    public static function fields(): array
    {
        return [
            F::text('eyebrow', 'Small line above the heading'),
            F::text('title', 'Heading', ['required' => true]),
            F::textarea('subtitle', 'Text under the heading'),
            F::image('image', 'Photo', ['required' => true]),
            F::text('button_text', 'Button text'),
            F::text('button_url', 'Button link (e.g. /admissions)'),
            F::sort(),
            F::toggle('is_active', 'Show this slide', ['default' => true]),
        ];
    }

    public static function columns(): array
    {
        return ['image' => 'Photo', 'title' => 'Heading', 'sort' => 'Order', 'is_active' => 'Live'];
    }

}
