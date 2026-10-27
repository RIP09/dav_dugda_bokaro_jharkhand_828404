<?php

namespace App\Modules\Communication\Models;

use App\Core\Concerns\Auditable;
use App\Core\Concerns\Lockable;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NoticeRead extends Model
{
    use Auditable, Lockable;

    protected $table = 'notice_reads';

    protected $guarded = ['id'];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function notice(): BelongsTo { return $this->belongsTo(Notice::class, 'notice_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
    public $timestamps = false;
}
