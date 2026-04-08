<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Scopes\WorkspaceScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;

use App\Traits\BelongsToWorkspace;

#[ScopedBy([WorkspaceScope::class])]
class Customer extends Model
{
    use HasFactory, SoftDeletes, BelongsToWorkspace;

    protected $fillable = [
        'name',
        'contact_name',
        'email',
        'phone',
        'address',
        'notes',
        'workspace_id',
        'created_by_user_id',
    ];

    public function sales()
    {
        return $this->hasMany(Sale::class);
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

    public function scopeWithRevenueMetrics($query)
    {
        return $query->selectSub(function ($q) {
            $q->from('sales')
              ->whereColumn('sales.customer_id', 'customers.id')
              ->selectRaw('COALESCE(SUM(quantity_sold * unit_price), 0)');
        }, 'total_revenue');
    }
}
