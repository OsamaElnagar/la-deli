<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class OrderPolicy
{
    public function viewAny(User $user)
    {
        return $user->hasAnyRole(['super_admin', 'feeder', 'pharmacist', 'driver']);
    }

    public function view(User $user, Order $order)
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        if ($user->hasRole('feeder') && $order->created_by === $user->id) {
            return true;
        }

        if ($user->hasRole('pharmacist') && $order->pharmacist_id === $user->id) {
            return true;
        }

        if ($user->hasRole('driver') && $order->driver_id === $user->id) {
            return true;
        }

        return false;
    }

    public function create(User $user)
    {
        return $user->hasRole(['feeder', 'super_admin', 'pharmacist']);
    }

    public function update(User $user, Order $order)
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        if ($user->hasRole('pharmacist') && $order->pharmacist_id === $user->id) {
            return $order->canBeAssignedToPharmacist();
        }

        if ($user->hasRole('driver') && $order->driver_id === $user->id) {
            return in_array($order->status, ['assigned_driver', 'picked_up', 'in_transit']);
        }

        return false;
    }
}
