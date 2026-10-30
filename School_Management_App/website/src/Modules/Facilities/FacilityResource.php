<?php

namespace Website\Modules\Facilities;

use Illuminate\Database\Eloquent\Model;
use Website\Support\F;
use Website\Support\Resource;

class FacilityResource extends Resource
{

    public static string $key = 'facilities';
    public static string $label = 'Facilities';
    public static string $singular = 'facility';
    public static string $model = Facility::class;
    public static string $icon = 'building-2';

    public static function fields(): array
    {
        return [
            F::text('title', 'Facility name', ['required' => true]),
            F::icon('icon', 'Icon'),
            F::textarea('summary', 'Short description'),
            F::image('image', 'Photo'),
            F::rich('body', 'More details (optional)'),
            F::slug('slug', 'Web address (slug)'),
            F::sort(),
            F::toggle('is_published', 'Show on website', ['default' => true]),
        ];
    }

    public static function columns(): array
    {
        return ['image' => 'Photo', 'title' => 'Facility', 'sort' => 'Order', 'is_published' => 'Live'];
    }

}
