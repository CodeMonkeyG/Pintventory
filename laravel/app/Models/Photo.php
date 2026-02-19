<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Photo extends Model
{
    /** @use HasFactory<\Database\Factories\PhotoFactory> */
    use HasFactory;

    protected $fillable = [
        'inventory_item_id',
        'storage_key',
        'mime_type',
        'caption',
        'sort_order',
    ];

    protected $appends = ['url'];

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function getUrlAttribute()
    {
        return Storage::url($this->storage_key);
    }
}
