<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Scopes\UserScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;

#[ScopedBy([UserScope::class])]
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
        'storage_location_id',
        'evaluation',
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

    public function storageLocation()
    {
        return $this->belongsTo(StorageLocation::class);
    }

    /**
     * Scope: Search by title or SKU
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string|null  $search
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearch($query, $search)
    {
        if (!$search) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('sku', 'like', "%{$search}%");
        });
    }

    /**
     * Scope: Filter by status
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string|null  $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByStatus($query, $status)
    {
        if (!$status) {
            return $query;
        }

        return $query->where('status', $status);
    }

    /**
     * Scope: Filter by tag
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string|null  $tag
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByTag($query, $tag)
    {
        if (!$tag) {
            return $query;
        }

        return $query->whereJsonContains('tags', $tag);
    }

    /**
     * Scope: Filter by low stock
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLowStock($query)
    {
        return $query->whereColumn('quantity_on_hand', '<=', 'reorder_point');
    }

    /**
     * Scope: Exclude archived items
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNotArchived($query)
    {
        return $query->whereNull('archived_at');
    }
}
