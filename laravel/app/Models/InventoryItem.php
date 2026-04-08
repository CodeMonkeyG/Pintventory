<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Scopes\WorkspaceScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;

use App\Traits\BelongsToWorkspace;

#[ScopedBy([WorkspaceScope::class])]
class InventoryItem extends Model
{
    /** @use HasFactory<\Database\Factories\InventoryItemFactory> */
    use HasFactory, HasUuids, BelongsToWorkspace;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'sku',
        'title',
        'description',
        'status',
        'item_type',
        'quantity_on_hand',
        'reorder_point',
        'unit',
        'tags',
        'location',
        'storage_location_id',
        'workspace_id',
        'evaluation',
        'market_analysis',
        'facebook_analysis',
        'etsy_analysis',
        'source_links',
        'ebay_listing_url',
        'facebook_listing_url',
        'etsy_listing_url',
        'created_by_user_id',
        'archived_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tags' => 'array',
        'market_analysis' => 'array',
        'facebook_analysis' => 'array',
        'etsy_analysis' => 'array',
        'source_links' => 'array',
        'archived_at' => 'datetime',
        'quantity_on_hand' => 'integer',
        'reorder_point' => 'integer',
    ];

    /**
     * Get the photos for the inventory item.
     */
    public function photos()
    {
        return $this->hasMany(Photo::class)->orderBy('sort_order');
    }

    /**
     * Get the purchases for the inventory item.
     */
    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    /**
     * Get the sales for the inventory item.
     */
    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * Get the user who created the inventory item.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Get the workspace for the inventory item.
     */
    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Get the storage location for the inventory item.
     */
    public function storageLocation()
    {
        return $this->belongsTo(StorageLocation::class);
    }

    /**
     * Scope: Search by title or SKU
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
     */
    public function scopeByStatus($query, $status)
    {
        if (!$status) {
            return $query;
        }

        return $query->where('status', $status);
    }

    /**
     * Scope: Filter by storage location
     */
    public function scopeByLocation($query, $locationId)
    {
        if (!$locationId) {
            return $query;
        }

        return $query->where('storage_location_id', $locationId);
    }

    /**
     * Scope: Filter by tag
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
     */
    public function scopeLowStock($query)
    {
        return $query->whereColumn('quantity_on_hand', '<=', 'reorder_point');
    }

    /**
     * Scope: Exclude archived items
     */
    public function scopeNotArchived($query)
    {
        return $query->whereNull('archived_at');
    }
}
