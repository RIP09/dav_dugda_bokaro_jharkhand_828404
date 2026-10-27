<?php

namespace App\Modules\FrontOffice\Models;

use App\Core\Concerns\Auditable;
use App\Core\Concerns\Lockable;
use Illuminate\Database\Eloquent\Model;

class CallLog extends Model
{
    use Auditable, Lockable;

    protected $table = 'call_logs';

    protected $guarded = ['id'];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'follow_up_date' => 'date:Y-m-d',
    ];

}
