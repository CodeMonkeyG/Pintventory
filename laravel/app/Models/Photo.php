<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use App\Models\Scopes\WorkspaceScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;

use App\Traits\BelongsToWorkspace;

#[ScopedBy([WorkspaceScope::class])]
class Photo extends Model
{
    use HasFactory, BelongsToWorkspace;

    protected $fillable = [
        'inventory_item_id',
        'storage_key',
        'mime_type',
        'caption',
        'sort_order',
        'workspace_id',
    ];

    protected $appends = ['url'];

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    public function getUrlAttribute()
    {
        return Storage::url($this->storage_key);
    }
}
