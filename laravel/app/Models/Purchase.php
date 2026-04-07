<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\WorkspaceScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;

#[ScopedBy([WorkspaceScope::class])]
class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_item_id',
        'vendor_id',
        'purchased_at',
        'quantity_purchased',
        'unit_cost',
        'shipping_cost',
        'tax_cost',
        'reference_number',
        'notes',
        'workspace_id',
        'created_by_user_id',
    ];

    protected $casts = [
        'purchased_at' => 'date',
        'quantity_purchased' => 'integer',
        'unit_cost' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'tax_cost' => 'decimal:2',
    ];

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }
}
