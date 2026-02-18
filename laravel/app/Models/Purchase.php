<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Scopes\UserScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;

#[ScopedBy([UserScope::class])]
class Purchase extends Model
{
    /** @use HasFactory<\Database\Factories\PurchaseFactory> */
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
        'created_by_user_id',
    ];

    protected $casts = [
        'purchased_at' => 'datetime',
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
}
