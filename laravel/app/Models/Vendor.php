<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Scopes\WorkspaceScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;

use App\Traits\BelongsToWorkspace;

#[ScopedBy([WorkspaceScope::class])]
class Vendor extends Model
{
    use HasFactory, SoftDeletes, BelongsToWorkspace;

    protected $fillable = [
        'name',
        'contact_name',
        'email',
        'phone',
        'address',
        'notes',
        'is_preferred',
        'workspace_id',
        'created_by_user_id',
    ];

    protected $casts = [
        'is_preferred' => 'boolean',
    ];

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    public function scopeSearch($query, $search)
    {
        if (!$search) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('contact_name', 'like', "%{$search}%");
        });
    }

    public function scopeWithSpendMetrics($query)
    {
        return $query->selectSub(function ($q) {
            $q->from('purchases')
              ->whereColumn('purchases.vendor_id', 'vendors.id')
              ->selectRaw('COALESCE(SUM(quantity_purchased * unit_cost), 0)');
        }, 'total_spend');
    }
}
