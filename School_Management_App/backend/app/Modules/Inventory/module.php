<?php

use App\Core\Modules\F;
use App\Models\User;

use App\Modules\Inventory\Models\InventoryItem;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Inventory\Models\Asset;

return [
[
    'key' => 'inventory_items', 'title' => 'Stock Items', 'group' => 'Inventory', 'icon' => 'inventory_2', 'order' => 130,
    'model' => InventoryItem::class, 'label' => 'name', 'subtitle' => ['category', 'quantity', 'unit'], 'search' => ['name', 'category', 'supplier'], 'sort' => 'name',
    'import' => true, 'import_key' => 'name',
    'filters' => ['low' => fn ($q, $v) => $q->whereColumn('quantity', '<=', 'min_quantity')],
    'roles' => ['view' => ['office', 'librarian'], 'create' => ['office'], 'update' => ['office'], 'delete' => []],
    'fields' => [
        F::text('name', 'Item', ['required' => true, 'list' => true]),
        F::select('category', 'Category', ['Stationery', 'Lab', 'Sports', 'Cleaning', 'Uniform', 'Books', 'Electrical', 'Furniture', 'Other'], ['list' => true, 'filter' => true]),
        F::text('unit', 'Unit', ['default' => 'pcs']),
        F::number('quantity', 'In stock', ['readonly' => true, 'list' => true, 'help' => 'Changes only through stock in/out entries.']),
        F::number('min_quantity', 'Re-order level'),
        F::money('unit_price', 'Unit price (₹)'),
        F::text('location', 'Store / room'),
        F::text('supplier', 'Supplier'),
    ],
],
[
    'key' => 'stock_movements', 'title' => 'Stock In / Out', 'group' => 'Inventory', 'icon' => 'swap_vert', 'order' => 131, 'lockable' => true,
    'lock_note' => 'Stock entries are permanent; correct with a reverse entry.', 'no_update' => true, 'no_delete' => true,
    'model' => StockMovement::class, 'with' => ['item'], 'title_fn' => fn ($m) => strtoupper($m->type).' '.(float) $m->quantity.' × '.($m->item?->name ?? ''),
    'subtitle' => ['date', 'party'], 'search' => ['party', 'note'], 'sort' => '-date',
    'roles' => ['view' => ['office', 'librarian'], 'create' => ['office', 'librarian']],
    'fields' => [
        F::ref('inventory_item_id', 'Item', 'inventory_items', ['required' => true, 'list' => true, 'filter' => true]),
        F::select('type', 'In / Out', ['in' => 'Stock in (purchase)', 'out' => 'Stock out (issue)'], ['required' => true, 'list' => true, 'filter' => true]),
        F::number('quantity', 'Quantity', ['required' => true, 'list' => true]),
        F::date('date', 'Date', ['required' => true, 'list' => true]),
        F::text('party', 'Supplier / issued to', ['list' => true]),
        F::text('note', 'Note'),
    ],
    'validate' => function (array $d) {
        if (($d['type'] ?? '') === 'out' && ($item = InventoryItem::find($d['inventory_item_id'] ?? 0)) && $item->quantity < ($d['quantity'] ?? 0)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['quantity' => 'Only '.(float) $item->quantity.' in stock.']);
        }
    },
    'before_save' => function ($m, $d, $user) { $m->created_by = $user->id; },
],
[
    'key' => 'assets', 'title' => 'Assets Register', 'group' => 'Inventory', 'icon' => 'chair', 'order' => 132,
    'model' => Asset::class, 'label' => 'name', 'subtitle' => ['asset_tag', 'location', 'condition'], 'search' => ['name', 'asset_tag', 'location', 'assigned_to'],
    'roles' => ['view' => ['office'], 'create' => ['office'], 'update' => ['office'], 'delete' => []],
    'fields' => [
        F::text('asset_tag', 'Asset tag', ['list' => true, 'immutable' => true, 'help' => 'Auto-generated if blank.']),
        F::text('name', 'Asset', ['required' => true, 'list' => true]),
        F::select('category', 'Category', ['Furniture', 'Computer', 'Lab equipment', 'Electrical', 'Vehicle', 'Sports', 'Musical', 'Other'], ['list' => true, 'filter' => true]),
        F::text('location', 'Location', ['list' => true]),
        F::date('purchase_date', 'Purchased on'),
        F::money('cost', 'Cost (₹)'),
        F::text('vendor', 'Vendor'),
        F::date('warranty_till', 'Warranty till'),
        F::select('condition', 'Condition', ['new', 'good', 'repair', 'scrapped'], ['list' => true, 'filter' => true]),
        F::text('assigned_to', 'Assigned to'),
    ],
    'before_save' => function ($m, $d, $u, $creating) { if ($creating) $m->asset_tag = $m->asset_tag ?: sprintf('DAV-A%04d', (Asset::max('id') ?? 0) + 1); },
],
];
