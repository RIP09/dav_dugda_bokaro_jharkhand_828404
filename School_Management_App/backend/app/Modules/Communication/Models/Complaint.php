<?php

namespace App\Modules\Communication\Models;

use App\Core\Concerns\Auditable;
use App\Core\Concerns\Lockable;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Complaint extends Model
{
    use Auditable, Lockable;

    protected $table = 'complaints';

    protected $guarded = ['id'];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
    public function assignee(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }
}
