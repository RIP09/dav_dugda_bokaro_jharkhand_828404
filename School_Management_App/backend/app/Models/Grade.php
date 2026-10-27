<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    protected $fillable = ['name', 'min_percent', 'max_percent', 'point', 'remark'];
    protected $casts = ['min_percent' => 'float', 'max_percent' => 'float', 'point' => 'float'];

    public static function forPercent(float $p): ?self
    {
        return static::where('min_percent', '<=', $p)->orderByDesc('min_percent')->first();
    }
}
