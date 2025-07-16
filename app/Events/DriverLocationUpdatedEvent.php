<?php

namespace App\Events;

use App\Models\Order;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DriverLocationUpdatedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;


    public function __construct(public User $driver, public array $location, public ?Order $currentOrder) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('driver-location-updated'),
        ];
    }
}
