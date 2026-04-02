<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Scopes\UserScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;

/**
 * App\Models\Customer
 *
 * @property int $id
 * @property string $name
 * @property string|null $contact_name
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $address
 * @property string|null $notes
 * @property int $created_by_user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\User $creator
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Sale[] $sales
 * @property-read int|null $sales_count
 * @method static \Illuminate\Database\Eloquent\Builder|Customer search($search)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer withRevenueMetrics()
 */
#[ScopedBy([UserScope::class])]
class Customer extends Model
{
    /** @use HasFactory<\Database\Factories\CustomerFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'contact_name',
        'email',
        'phone',
        'address',
        'notes',
        'created_by_user_id',
    ];

    /**
     * Get the sales associated with the customer.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * Get the user who created the customer record.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
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
