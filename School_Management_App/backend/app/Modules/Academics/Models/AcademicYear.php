<?php

namespace App\Modules\Academics\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicYear extends Model
{
    protected $fillable = ['title', 'start_date', 'end_date', 'is_current'];
    protected $casts = ['start_date' => 'date:Y-m-d', 'end_date' => 'date:Y-m-d', 'is_current' => 'boolean'];

    public static function current(): ?self { return static::where('is_current', true)->first() ?? static::latest('start_date')->first(); }
}
