<?php

namespace App\Modules\Academics\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicYear extends Model
{
    use \App\Core\Concerns\Auditable, \App\Core\Concerns\Lockable;

    protected $guarded = ['id'];
    protected $casts = ['start_date' => 'date:Y-m-d', 'end_date' => 'date:Y-m-d', 'is_current' => 'boolean'];

    public static function current(): ?self { return static::where('is_current', true)->first() ?? static::latest('start_date')->first(); }
}
