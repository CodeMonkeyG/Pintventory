<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * App\Models\Photo
 *
 * @property int $id
 * @property string $inventory_item_id
 * @property string $storage_key
 * @property string|null $mime_type
 * @property string|null $caption
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $url
 * @property-read \App\Models\InventoryItem $inventoryItem
 */
class Photo extends Model
{
    /** @use HasFactory<\Database\Factories\PhotoFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'inventory_item_id',
        'storage_key',
        'mime_type',
        'caption',
        'sort_order',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = ['url'];

    /**
     * Get the inventory item that owns the photo.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }

    /**
     * Get the full URL for the photo.
     *
     * @return string
     */
    public function getUrlAttribute()
    {
        return Storage::url($this->storage_key);
    }
}
