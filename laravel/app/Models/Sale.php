<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\WorkspaceScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;

#[ScopedBy([WorkspaceScope::class])]
class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_item_id',
        'customer_id',
        'sold_at',
        'quantity_sold',
        'unit_price',
        'discount',
        'tax',
        'payment_status',
        'notes',
        'workspace_id',
        'created_by_user_id',
    ];

    protected $casts = [
        'sold_at' => 'date',
        'quantity_sold' => 'integer',
        'unit_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
    ];

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
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
