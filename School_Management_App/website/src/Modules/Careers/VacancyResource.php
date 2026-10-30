<?php

namespace Website\Modules\Careers;

use Illuminate\Database\Eloquent\Model;
use Website\Support\F;
use Website\Support\Resource;

class VacancyResource extends Resource
{

    public static string $key = 'vacancies';
    public static string $label = 'Careers / Vacancies';
    public static string $singular = 'vacancy';
    public static string $model = Vacancy::class;
    public static string $icon = 'briefcase';
    public static string $orderBy = 'created_at';
    public static string $orderDir = 'desc';

    public static function fields(): array
    {
        return [
            F::text('title', 'Post', ['required' => true]),
            F::text('department', 'Department / subject'),
            F::text('qualification', 'Qualification', ['wide' => true]),
            F::text('experience', 'Experience'),
            F::number('positions', 'Number of posts', ['default' => 1]),
            F::date('last_date', 'Last date to apply'),
            F::rich('description', 'Details & how to apply'),
            F::toggle('is_open', 'Open (show on website)', ['default' => true]),
        ];
    }

    public static function columns(): array
    {
        return ['title' => 'Post', 'department' => 'Department', 'last_date' => 'Last date', 'is_open' => 'Open'];
    }

}
