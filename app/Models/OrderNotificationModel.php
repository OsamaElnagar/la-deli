<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderNotificationModel extends Model
{
    use HasFactory;
    protected $table = 'order_notifications';

    protected $fillable = [
        'order_id',
        'recipient_type',
        'recipient_id',
        'title',
        'message',
        'type',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    /**
     * Get the order this notification belongs to
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }


    /**
     * Scope for unread notifications
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope for notifications by recipient
     */
    public function scopeForRecipient($query, $type, $id)
    {
        return $query->where('recipient_type', $type)
            ->where('recipient_id', $id);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead()
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Create notification for multiple recipients
     */
    public static function createForMultiple($orderData, $recipients)
    {
        foreach ($recipients as $recipient) {
            self::create([
                'order_id' => $orderData['order_id'],
                'recipient_type' => $recipient['type'],
                'recipient_id' => $recipient['id'],
                'title' => $orderData['title'],
                'message' => $orderData['message'],
                'type' => $orderData['type'],
            ]);
        }
    }
}
