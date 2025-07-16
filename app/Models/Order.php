<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Order extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'order_code',
        'invoice_number',
        'source_branch_id',
        'destination_branch_id',
        'customer_name',
        'customer_address',
        'customer_phone',
        'customer_coordinates',
        'delivery_type',
        'notes',
        'total_amount',
        'status',
        'pharmacist_id',
        'driver_id',
        'created_by',
        'prepared_at',
        'picked_up_at',
        'delivered_at'
    ];

    protected $casts = [
        'customer_coordinates' => 'array',
        'total_amount' => 'decimal:2',
        'prepared_at' => 'datetime',
        'picked_up_at' => 'datetime',
        'delivered_at' => 'datetime'
    ];

    // Boot method for generating order code
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            $order->order_code = 'ORD-' . strtoupper(uniqid());
        });
    }

    // Relationships
    public function sourceBranch()
    {
        return $this->belongsTo(Branch::class, 'source_branch_id');
    }

    public function destinationBranch()
    {
        return $this->belongsTo(Branch::class, 'destination_branch_id');
    }

    public function pharmacist()
    {
        return $this->belongsTo(User::class, 'pharmacist_id');
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistories()
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('changed_at', 'desc');
    }

    // Media Collections
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('invoices')
            ->singleFile()
            ->acceptsMimeTypes(['application/pdf', 'image/jpeg', 'image/png']);

        $this->addMediaCollection('delivery_proof')
            ->acceptsMimeTypes(['image/jpeg', 'image/png']);
    }

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeForPharmacist($query, $pharmacistId)
    {
        return $query->where('pharmacist_id', $pharmacistId);
    }

    public function scopeForDriver($query, $driverId)
    {
        return $query->where('driver_id', $driverId);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeReadyForPickup($query)
    {
        return $query->where('status', 'ready_for_pickup');
    }

    // Helper methods
    public function isHomeDelivery()
    {
        return $this->delivery_type === 'branch_to_customer';
    }

    public function canBeAssignedToPharmacist()
    {
        return in_array($this->status, ['pending', 'assigned_pharmacist']);
    }

    public function canBeAssignedToDriver()
    {
        return $this->status === 'ready_for_pickup';
    }

    public function getDestinationAddressAttribute()
    {
        if ($this->isHomeDelivery()) {
            return $this->customer_address;
        }
        return $this->destinationBranch?->address;
    }
}
