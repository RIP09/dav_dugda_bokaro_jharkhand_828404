<?php

namespace App\Modules\FrontOffice\Models;

use App\Core\Concerns\Auditable;
use App\Core\Concerns\Lockable;
use Illuminate\Database\Eloquent\Model;

class PostalRecord extends Model
{
    use Auditable, Lockable;

    protected $table = 'postal_records';

    protected $guarded = ['id'];

    protected $casts = [
        'date' => 'date:Y-m-d',
    ];

}
