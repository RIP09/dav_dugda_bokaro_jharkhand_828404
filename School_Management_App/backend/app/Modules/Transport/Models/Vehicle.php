<?php

namespace App\Modules\Transport\Models;

use App\Core\Concerns\Auditable;
use App\Core\Concerns\Lockable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vehicle extends Model
{
    use Auditable, Lockable;

    protected $table = 'vehicles';

    protected $guarded = ['id'];

    protected $casts = [
        'insurance_expiry' => 'date:Y-m-d',
        'fitness_expiry' => 'date:Y-m-d',
        'pollution_expiry' => 'date:Y-m-d',
    ];

    public function route(): BelongsTo { return $this->belongsTo(TransportRoute::class, 'transport_route_id'); }
}
