<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends Model
{
    /** @use HasFactory<\Database\Factories\VendorFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'contact_name',
        'email',
        'phone',
        'address',
        'notes',
        'is_preferred',
    ];

    protected $casts = [
        'is_preferred' => 'boolean',
    ];

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    /**
     * Scope: Search by name, email, or contact name
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
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('contact_name', 'like', "%{$search}%");
        });
    }

    /**
     * Scope: Include purchases count and total spend
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithSpendMetrics($query)
    {
        return $query->withCount('purchases')
                     ->selectSub(function ($q) {
                         $q->from('purchases')
                           ->whereColumn('purchases.vendor_id', 'vendors.id')
                           ->selectRaw('COALESCE(SUM(quantity_purchased * unit_cost), 0)');
                     }, 'total_spend')
                     ->selectSub(function ($q) {
                         $q->from('purchases')
                           ->whereColumn('purchases.vendor_id', 'vendors.id')
                           ->selectRaw('COALESCE(SUM(quantity_purchased), 0)');
                     }, 'total_items_purchased');
    }
}
