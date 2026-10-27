<?php

namespace App\Modules\Hostel\Models;

use App\Core\Concerns\Auditable;
use App\Core\Concerns\Lockable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hostel extends Model
{
    use Auditable, Lockable;

    protected $table = 'hostels';

    protected $guarded = ['id'];

    protected $casts = [
    ];

    public function rooms(): HasMany { return $this->hasMany(HostelRoom::class, 'hostel_id'); }
}
