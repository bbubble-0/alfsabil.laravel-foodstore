<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingZone extends Model
{
    protected $fillable = [
        'name',
        'shipping_cost',
        'is_active',
    ];

    protected $casts = [
        'shipping_cost' => 'integer',
        'is_active' => 'boolean',
    ];

    public function destinations(): HasMany
    {
        return $this->hasMany(ShippingDestination::class);
    }
}
