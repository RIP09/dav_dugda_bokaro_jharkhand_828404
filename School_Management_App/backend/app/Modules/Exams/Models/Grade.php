<?php

namespace App\Modules\Exams\Models;

use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    use \App\Core\Concerns\Auditable, \App\Core\Concerns\Lockable;

    protected $guarded = ['id'];
    protected $casts = ['min_percent' => 'float', 'max_percent' => 'float', 'point' => 'float'];

    public static function forPercent(float $p): ?self
    {
        return static::where('min_percent', '<=', $p)->orderByDesc('min_percent')->first();
    }
}
