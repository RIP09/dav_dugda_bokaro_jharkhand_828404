<?php

namespace App\Modules\Communication\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use \App\Core\Concerns\Auditable, \App\Core\Concerns\Lockable;

    protected $guarded = ['id'];
    protected $casts = ['start_date' => 'date:Y-m-d', 'end_date' => 'date:Y-m-d'];
}
