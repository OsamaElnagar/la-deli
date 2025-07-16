<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderStatusChangedNotification;

class NotificationService
{
    public function notifyAvailableDrivers(Order $order)
    {
        $availableDrivers = User::role('driver')
            ->whereHas('driverStatus', function ($query) {
                $query->where('status', 'online')
                    ->whereNull('current_order_id');
            })
            ->get();

        foreach ($availableDrivers as $driver) {
            $driver->notify(new OrderStatusChangedNotification($order, 'ready_for_pickup'));
        }
    }

    public function sendOrderUpdateNotification(Order $order, string $message)
    {
        $recipients = collect();

        // Add order creator
        if ($order->createdBy) {
            $recipients->push($order->createdBy);
        }

        // Add pharmacist if assigned
        if ($order->pharmacist) {
            $recipients->push($order->pharmacist);
        }

        // Add driver if assigned
        if ($order->driver) {
            $recipients->push($order->driver);
        }

        // Send notifications
        foreach ($recipients->unique('id') as $user) {
            $user->notify(new OrderStatusChangedNotification($order, $order->status));
        }
    }
}
