<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderStatusHistory extends Model
{


    protected $fillable = [
        'order_id',
        'from_status',
        'to_status',
        'changed_by',
        'notes',
        'metadata',
        'changed_at'
    ];

    protected $casts = [
        'metadata' => 'array',
        'changed_at' => 'datetime'
    ];

    // Relationships
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
