<?php

namespace App\Modules\Hostel\Models;

use App\Core\Concerns\Auditable;
use App\Core\Concerns\Lockable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HostelRoom extends Model
{
    use Auditable, Lockable;

    protected $table = 'hostel_rooms';

    protected $guarded = ['id'];

    protected $casts = [
        'fee_per_month' => 'decimal:2',
    ];

    public function hostel(): BelongsTo { return $this->belongsTo(Hostel::class, 'hostel_id'); }
    public function allocations(): HasMany { return $this->hasMany(HostelAllocation::class, 'hostel_room_id'); }
}
