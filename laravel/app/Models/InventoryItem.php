<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Scopes\UserScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;

/**
 * App\Models\InventoryItem
 *
 * @property string $id
 * @property string $sku
 * @property string $title
 * @property string|null $description
 * @property string $status
 * @property string $item_type
 * @property int $quantity_on_hand
 * @property int $reorder_point
 * @property string|null $unit
 * @property array|null $tags
 * @property string|null $location
 * @property int|null $storage_location_id
 * @property string|null $evaluation
 * @property array|null $market_analysis
 * @property array|null $facebook_analysis
 * @property array|null $etsy_analysis
 * @property array|null $source_links
 * @property string|null $ebay_listing_url
 * @property string|null $facebook_listing_url
 * @property string|null $etsy_listing_url
 * @property int $created_by_user_id
 * @property \Illuminate\Support\Carbon|null $archived_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $creator
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Photo[] $photos
 * @property-read int|null $photos_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Purchase[] $purchases
 * @property-read int|null $purchases_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Sale[] $sales
 * @property-read int|null $sales_count
 * @property-read \App\Models\StorageLocation|null $storageLocation
 * @method static \Illuminate\Database\Eloquent\Builder|InventoryItem byStatus($status)
 * @method static \Illuminate\Database\Eloquent\Builder|InventoryItem byTag($tag)
 * @method static \Illuminate\Database\Eloquent\Builder|InventoryItem lowStock()
 * @method static \Illuminate\Database\Eloquent\Builder|InventoryItem notArchived()
 * @method static \Illuminate\Database\Eloquent\Builder|InventoryItem search($search)
 */
#[ScopedBy([UserScope::class])]
class InventoryItem extends Model
{
    /** @use HasFactory<\Database\Factories\InventoryItemFactory> */
    use HasFactory, HasUuids;

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
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function photos()
    {
        return $this->hasMany(Photo::class)->orderBy('sort_order');
    }

    /**
     * Get the purchases for the inventory item.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    /**
     * Get the sales for the inventory item.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * Get the user who created the inventory item.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Get the storage location for the inventory item.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
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
