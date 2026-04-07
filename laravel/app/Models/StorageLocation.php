<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\WorkspaceScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;

#[ScopedBy([WorkspaceScope::class])]
class StorageLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'workspace_id',
        'user_id',
    ];

    public function inventoryItems()
    {
        return $this->hasMany(InventoryItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }
}
