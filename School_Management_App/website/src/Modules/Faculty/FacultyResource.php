<?php

namespace Website\Modules\Faculty;

use Illuminate\Database\Eloquent\Model;
use Website\Support\F;
use Website\Support\Resource;

class FacultyResource extends Resource
{

    public static string $key = 'people';
    public static string $label = 'Faculty & Management';
    public static string $singular = 'person';
    public static string $model = Person::class;
    public static string $icon = 'users';
    public static array $searchable = ['name', 'designation', 'department'];
    public static ?string $help = 'Teachers, office staff and the management committee shown on the Faculty and Management pages.';

    public static function fields(): array
    {
        return [
            F::text('name', 'Full name', ['required' => true]),
            F::select('group', 'Shown on', ['teaching' => 'Faculty page (teachers)', 'management' => 'Management page', 'office' => 'Faculty page (office staff)']),
            F::text('designation', 'Designation (e.g. PGT Physics)'),
            F::text('department', 'Department / subject'),
            F::text('qualification', 'Qualification'),
            F::text('experience', 'Experience (e.g. 12 years)'),
            F::email('email', 'Email (optional, shown publicly)'),
            F::image('photo', 'Photo'),
            F::textarea('bio', 'Short note (optional)'),
            F::sort(),
            F::toggle('is_published', 'Show on website', ['default' => true]),
        ];
    }

    public static function columns(): array
    {
        return ['photo' => 'Photo', 'name' => 'Name', 'designation' => 'Designation', 'group' => 'Page', 'is_published' => 'Live'];
    }

    public static function filters(): array
    {
        return ['group' => ['teaching' => 'Teachers', 'office' => 'Office staff', 'management' => 'Management']];
    }

}
