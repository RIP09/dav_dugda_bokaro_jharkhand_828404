<?php

namespace Website\Modules\Testimonials;

use Illuminate\Database\Eloquent\Model;
use Website\Support\F;
use Website\Support\Resource;

class TestimonialResource extends Resource
{

    public static string $key = 'testimonials';
    public static string $label = 'Testimonials';
    public static string $singular = 'testimonial';
    public static string $model = Testimonial::class;
    public static string $icon = 'quote';
    public static string $group = 'Home page';
    public static array $searchable = ['name', 'quote'];

    public static function fields(): array
    {
        return [
            F::text('name', 'Name', ['required' => true]),
            F::text('role', 'Who they are (e.g. Parent of Class VII student)'),
            F::textarea('quote', 'What they said', ['required' => true]),
            F::image('photo', 'Photo (optional)'),
            F::sort(),
            F::toggle('is_published', 'Show on website', ['default' => true]),
        ];
    }

    public static function columns(): array
    {
        return ['name' => 'Name', 'role' => 'Role', 'sort' => 'Order', 'is_published' => 'Live'];
    }

}
