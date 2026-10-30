<?php

namespace Website\Modules\Home;

use Illuminate\Database\Eloquent\Model;
use Website\Support\F;
use Website\Support\Resource;

class StatResource extends Resource
{

    public static string $key = 'stats';
    public static string $label = 'Counters';
    public static string $singular = 'counter';
    public static string $model = Stat::class;
    public static string $icon = 'calculator';
    public static string $group = 'Home page';
    public static array $searchable = ['label'];
    public static ?string $help = 'The animated numbers on the home page (students, teachers, years…).';

    public static function fields(): array
    {
        return [
            F::text('label', 'Label', ['required' => true]),
            F::number('value', 'Number', ['required' => true]),
            F::text('suffix', 'After the number (e.g. + or %)'),
            F::icon('icon', 'Icon'),
            F::sort(),
        ];
    }

    public static function columns(): array
    {
        return ['label' => 'Label', 'value' => 'Number', 'suffix' => 'Suffix', 'sort' => 'Order'];
    }

}
