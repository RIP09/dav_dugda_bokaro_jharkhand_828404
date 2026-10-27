<?php

namespace App\Modules\Inventory\Models;

use App\Core\Concerns\Auditable;
use App\Core\Concerns\Lockable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    use Auditable, Lockable;

    protected $table = 'inventory_items';

    protected $guarded = ['id'];

    protected $casts = [
        'quantity' => 'decimal:2',
        'min_quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
    ];

    public function movements(): HasMany { return $this->hasMany(StockMovement::class, 'inventory_item_id'); }
}
