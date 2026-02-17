<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    /** @use HasFactory<\Database\Factories\InventoryItemFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'sku',
        'title',
        'description',
        'status',
        'quantity_on_hand',
        'reorder_point',
        'unit',
        'tags',
        'location',
        'created_by_user_id',
        'archived_at',
    ];

    protected $casts = [
        'tags' => 'array',
        'archived_at' => 'datetime',
        'quantity_on_hand' => 'integer',
        'reorder_point' => 'integer',
    ];

    public function photos()
    {
        return $this->hasMany(Photo::class)->orderBy('sort_order');
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
