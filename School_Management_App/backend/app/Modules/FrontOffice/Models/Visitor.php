<?php

namespace App\Modules\FrontOffice\Models;

use App\Core\Concerns\Auditable;
use App\Core\Concerns\Lockable;
use Illuminate\Database\Eloquent\Model;

class Visitor extends Model
{
    use Auditable, Lockable;

    protected $table = 'visitors';

    protected $guarded = ['id'];

    protected $casts = [
        'in_time' => 'datetime:Y-m-d H:i',
        'out_time' => 'datetime:Y-m-d H:i',
    ];

}
