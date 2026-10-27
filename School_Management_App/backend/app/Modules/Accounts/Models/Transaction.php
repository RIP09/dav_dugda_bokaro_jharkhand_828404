<?php

namespace App\Modules\Accounts\Models;

use App\Core\Concerns\Auditable;
use App\Core\Concerns\Lockable;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use Auditable, Lockable;

    protected $table = 'transactions';

    protected $guarded = ['id'];

    protected array $immutable = ['voucher_no'];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'amount' => 'decimal:2',
    ];

    public function head(): BelongsTo { return $this->belongsTo(AccountHead::class, 'account_head_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
