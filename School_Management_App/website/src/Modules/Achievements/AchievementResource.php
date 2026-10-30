<?php

namespace Website\Modules\Achievements;

use Illuminate\Database\Eloquent\Model;
use Website\Support\F;
use Website\Support\Resource;

class AchievementResource extends Resource
{

    public static string $key = 'achievements';
    public static string $label = 'Results & Achievements';
    public static string $singular = 'achievement';
    public static string $model = Achievement::class;
    public static string $icon = 'trophy';
    public static array $searchable = ['title', 'student_name'];
    public static ?string $help = 'Board toppers, olympiad winners, sports and cultural prizes.';

    public static function fields(): array
    {
        return [
            F::text('title', 'Title (e.g. Class XII Topper)', ['required' => true]),
            F::select('category', 'Type', ['board' => 'Board result / topper', 'olympiad' => 'Olympiad / competition', 'sports' => 'Sports', 'cultural' => 'Cultural', 'other' => 'Other']),
            F::text('student_name', 'Student name'),
            F::text('class_name', 'Class'),
            F::text('score', 'Score / position (e.g. 97.4% or Gold)'),
            F::text('year', 'Year / session'),
            F::image('photo', 'Photo'),
            F::textarea('description', 'Details'),
            F::sort(),
            F::toggle('is_published', 'Show on website', ['default' => true]),
        ];
    }

    public static function columns(): array
    {
        return ['photo' => 'Photo', 'title' => 'Title', 'student_name' => 'Student', 'score' => 'Score', 'year' => 'Year', 'is_published' => 'Live'];
    }

    public static function filters(): array
    {
        return ['category' => ['board' => 'Board', 'olympiad' => 'Olympiad', 'sports' => 'Sports', 'cultural' => 'Cultural', 'other' => 'Other']];
    }

}
