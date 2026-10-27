<?php

namespace App\Modules\Fees\Models;

use Illuminate\Database\Eloquent\Model;

class FeeType extends Model
{
    use \App\Core\Concerns\Auditable, \App\Core\Concerns\Lockable;

    protected $guarded = ['id'];
    protected $casts = ['default_amount' => 'float'];
}
