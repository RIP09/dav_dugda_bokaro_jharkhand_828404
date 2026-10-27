<?php

namespace App\Modules\Accounts\Models;

use App\Core\Concerns\Auditable;
use App\Core\Concerns\Lockable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountHead extends Model
{
    use Auditable, Lockable;

    protected $table = 'account_heads';

    protected $guarded = ['id'];

    protected $casts = [
    ];

    public function transactions(): HasMany { return $this->hasMany(Transaction::class, 'account_head_id'); }
}
