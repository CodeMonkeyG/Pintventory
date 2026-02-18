<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    /** @use HasFactory<\Database\Factories\CustomerFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'contact_name',
        'email',
        'phone',
        'address',
        'notes',
    ];

    public function sales()
    {
        return $this->hasMany(Sale::class);
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
     * Scope: Include sales count and total revenue
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithRevenueMetrics($query)
    {
        return $query->withCount('sales')
                     ->selectSub(function ($q) {
                         $q->from('sales')
                           ->whereColumn('sales.customer_id', 'customers.id')
                           ->selectRaw('COALESCE(SUM(quantity_sold * unit_price), 0)');
                     }, 'total_revenue')
                     ->selectSub(function ($q) {
                         $q->from('sales')
                           ->whereColumn('sales.customer_id', 'customers.id')
                           ->selectRaw('COALESCE(SUM(quantity_sold), 0)');
                     }, 'total_items_purchased');
    }
}
