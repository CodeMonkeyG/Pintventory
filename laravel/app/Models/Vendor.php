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
}
