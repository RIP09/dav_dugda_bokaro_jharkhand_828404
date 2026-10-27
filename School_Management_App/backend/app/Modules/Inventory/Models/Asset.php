<?php

namespace App\Modules\Inventory\Models;

use App\Core\Concerns\Auditable;
use App\Core\Concerns\Lockable;
use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    use Auditable, Lockable;

    protected $table = 'assets';

    protected $guarded = ['id'];

    protected array $immutable = ['asset_tag'];

    protected $casts = [
        'purchase_date' => 'date:Y-m-d',
        'warranty_till' => 'date:Y-m-d',
        'cost' => 'decimal:2',
    ];

}
