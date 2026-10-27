<?php

namespace App\Modules\Inventory\Models;

use App\Core\Concerns\Auditable;
use App\Core\Concerns\Lockable;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use Auditable, Lockable;

    protected $table = 'stock_movements';

    protected $guarded = ['id'];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'quantity' => 'decimal:2',
    ];

    public function item(): BelongsTo { return $this->belongsTo(InventoryItem::class, 'inventory_item_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    protected static function booted(): void
    {
        static::created(function (self $m) {
            $item = $m->item;
            if ($item) \App\Core\Locks::bypass(fn () => $item->update(['quantity' => $item->quantity + ($m->type === 'in' ? $m->quantity : -$m->quantity)]));
        });
    }

    public function lockReason(): ?string { return 'Stock entries are permanent; add a reverse entry (in/out) to correct.'; }
}
