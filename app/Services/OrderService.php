<?php

namespace App\Services;

use App\Events\OrderStatusChangedEvent;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;


class OrderService
{
    public function createOrder(array $data): Order
    {
        DB::beginTransaction();

        try {
            $order = Order::create($data);

            // Add order items if provided
            if (isset($data['items'])) {
                foreach ($data['items'] as $item) {
                    $order->items()->create($item);
                }
            }

            // Attach invoice if provided
            if (isset($data['invoice_file'])) {
                $order->addMediaFromRequest('invoice_file')
                    ->toMediaCollection('invoices');
            }

            // Auto-assign pharmacist if source branch has available pharmacists
            $this->autoAssignPharmacist($order);

            DB::commit();

            return $order->load(['items', 'sourceBranch', 'destinationBranch']);
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function updateOrderStatus(Order $order, string $status, ?string $notes = null): Order
    {
        $previousStatus = $order->status;

        $order->update([
            'status' => $status,
            $this->getTimestampField($status) => now()
        ]);

        // Add notes to status history if provided
        if ($notes) {
            $order->statusHistories()->latest()->first()->update(['notes' => $notes]);
        }

        // Fire event
        event(new OrderStatusChangedEvent($order, $previousStatus, auth('web')->user()));

        return $order->fresh();
    }

    public function assignDriver(Order $order): ?User
    {
        $availableDriver = User::role('driver')
            ->whereHas('driverStatus', function ($query) use ($order) {
                $query->where('status', 'online')
                    ->whereNull('current_order_id');
            })
            ->first();

        if ($availableDriver) {
            $order->update([
                'driver_id' => $availableDriver->id,
                'status' => 'assigned_driver'
            ]);

            return $availableDriver;
        }

        return null;
    }

    private function autoAssignPharmacist(Order $order): void
    {
        $pharmacist = User::role('pharmacist')
            ->whereHas('branches', function ($query) use ($order) {
                $query->where('branch_id', $order->source_branch_id);
            })
            ->first();

        if ($pharmacist) {
            $order->update([
                'pharmacist_id' => $pharmacist->id,
                'status' => 'assigned_pharmacist'
            ]);
        }
    }

    private function getTimestampField(string $status): ?string
    {
        return match ($status) {
            'ready_for_pickup' => 'prepared_at',
            'picked_up' => 'picked_up_at',
            'delivered' => 'delivered_at',
            default => null
        };
    }
}
