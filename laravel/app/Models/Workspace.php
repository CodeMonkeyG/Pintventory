<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Workspace extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'created_by_user_id',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'workspace_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function inventoryItems()
    {
        return $this->hasMany(InventoryItem::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
