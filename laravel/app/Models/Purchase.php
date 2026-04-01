<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Scopes\UserScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;

/**
 * App\Models\Purchase
 *
 * @property int $id
 * @property string $inventory_item_id
 * @property int|null $vendor_id
 * @property \Illuminate\Support\Carbon $purchased_at
 * @property int $quantity_purchased
 * @property string $unit_cost
 * @property string $shipping_cost
 * @property string $tax_cost
 * @property string|null $reference_number
 * @property string|null $notes
 * @property int $created_by_user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $creator
 * @property-read \App\Models\InventoryItem $inventoryItem
 * @property-read \App\Models\Vendor|null $vendor
 */
#[ScopedBy([UserScope::class])]
class Purchase extends Model
{
    /** @use HasFactory<\Database\Factories\PurchaseFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
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

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'purchased_at' => 'datetime',
        'quantity_purchased' => 'integer',
        'unit_cost' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'tax_cost' => 'decimal:2',
    ];

    /**
     * Get the inventory item that was purchased.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }

    /**
     * Get the vendor from whom the item was purchased.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Get the user who recorded the purchase.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
