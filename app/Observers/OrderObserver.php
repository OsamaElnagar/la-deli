<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\User;
use App\Notifications\NewOrderAssignedNotification;
use App\Notifications\OrderStatusChangedNotification;

class OrderObserver
{
    public function created(Order $order)
    {
        // Create initial status history
        $order->statusHistories()->create([
            'from_status' => '',
            'to_status' => 'pending',
            'changed_by' => $order->created_by,
            'changed_at' => now(),
            'notes' => 'Order created'
        ]);
    }

    public function updating(Order $order)
    {
        // Track status changes
        if ($order->isDirty('status')) {
            $original = $order->getOriginal();

            // Create status history record
            $order->statusHistories()->create([
                'from_status' => $original['status'],
                'to_status' => $order->status,
                'changed_by' => auth('web')->id(),
                'changed_at' => now()
            ]);

            // Send notifications based on status change
            $this->handleStatusChangeNotifications($order, $original['status']);
        }

        // Track pharmacist assignment
        if ($order->isDirty('pharmacist_id') && $order->pharmacist_id) {
            $order->pharmacist->notify(new NewOrderAssignedNotification($order));
        }

        // Track driver assignment
        if ($order->isDirty('driver_id') && $order->driver_id) {
            $order->driver->notify(new NewOrderAssignedNotification($order));

            // Update driver status
            $order->driver->driverStatus()->update([
                'status' => 'busy',
                'current_order_id' => $order->id
            ]);
        }
    }

    private function handleStatusChangeNotifications(Order $order, string $fromStatus)
    {
        switch ($order->status) {
            case 'ready_for_pickup':
                // Notify available drivers
                $availableDrivers = User::role('driver')
                    ->whereHas('driverStatus', function ($query) {
                        $query->where('status', 'online')
                            ->whereNull('current_order_id');
                    })->get();

                foreach ($availableDrivers as $driver) {
                    $driver->notify(new OrderStatusChangedNotification($order, 'ready_for_pickup'));
                }
                break;

            case 'delivered':
                // Free up driver
                if ($order->driver) {
                    $order->driver->driverStatus()->update([
                        'status' => 'online',
                        'current_order_id' => null
                    ]);
                }

                // Notify relevant parties
                $order->createdBy->notify(new OrderStatusChangedNotification($order, 'delivered'));
                break;
        }
    }
}
