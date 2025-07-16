<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriverStatus extends Model
{


    protected $fillable = [
        'driver_id',
        'status',
        'current_location',
        'current_order_id',
        'last_location_update'
    ];

    protected $casts = [
        'current_location' => 'array',
        'last_location_update' => 'datetime'
    ];

    // Relationships
    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function currentOrder()
    {
        return $this->belongsTo(Order::class, 'current_order_id');
    }

    // Scopes
    public function scopeOnline($query)
    {
        return $query->where('status', 'online');
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'online')->whereNull('current_order_id');
    }
}
