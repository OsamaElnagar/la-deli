<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessOrderAssignmentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    public function __construct(protected Order $order) {}

    public function handle(OrderService $orderService)
    {
        // Try to auto-assign driver for ready orders
        if ($this->order->status === 'ready_for_pickup' && !$this->order->driver_id) {
            $orderService->assignDriver($this->order);
        }
    }
}
