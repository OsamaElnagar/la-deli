<?php

namespace App\Http\Controllers\Api;

use App\Events\DriverLocationUpdatedEvent;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use Illuminate\Http\Request;

class DriverStatusController extends BaseApiController
{
    public function updateStatus(Request $request)
    {
        $request->validate([
            'status' => 'required|in:online,offline',
            'location' => 'sometimes|array',
            'location.lat' => 'required_with:location|numeric',
            'location.lng' => 'required_with:location|numeric'
        ]);

        $user = $request->user();

        if (!$user->hasRole('driver')) {
            return $this->errorResponse('Only drivers can update status', 403);
        }

        $driverStatus = $user->driverStatus()->firstOrCreate(['driver_id' => $user->id]);

        $updateData = ['status' => $request->status];

        if ($request->has('location')) {
            $updateData['current_location'] = $request->location;
            $updateData['last_location_update'] = now();
        }

        // If going offline, remove current order assignment
        if ($request->status === 'offline') {
            $updateData['current_order_id'] = null;
        }

        $driverStatus->update($updateData);

        // Broadcast location update
        if ($request->has('location')) {
            event(new DriverLocationUpdatedEvent($user, $request->location, $driverStatus->currentOrder));
        }

        return $this->successResponse([
            'status' => $driverStatus->status,
            'current_order' => $driverStatus->currentOrder ? new OrderResource($driverStatus->currentOrder) : null
        ], 'Status updated successfully');
    }

    public function getCurrentOrder(Request $request)
    {
        $user = $request->user();

        if (!$user->hasRole('driver')) {
            return $this->errorResponse('Only drivers can access this endpoint', 403);
        }

        $currentOrder = $user->deliveryOrders()
            ->whereIn('status', ['assigned_driver', 'picked_up', 'in_transit'])
            ->with(['sourceBranch', 'destinationBranch', 'items'])
            ->first();

        if (!$currentOrder) {
            return $this->successResponse(null, 'No current order assigned');
        }

        return $this->successResponse(new OrderResource($currentOrder));
    }
}
