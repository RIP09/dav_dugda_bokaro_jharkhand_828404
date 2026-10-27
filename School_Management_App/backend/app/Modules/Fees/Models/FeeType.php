<?php

namespace App\Modules\Fees\Models;

use Illuminate\Database\Eloquent\Model;

class FeeType extends Model
{
    protected $fillable = ['name', 'description', 'default_amount'];
    protected $casts = ['default_amount' => 'float'];
}
