<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends BaseApiController
{
    protected OrderService $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    public function index(Request $request)
    {
        $query = Order::with(['sourceBranch', 'destinationBranch', 'pharmacist', 'driver', 'items']);

        // Filter by role
        $user = $request->user();

        if ($user->hasRole('pharmacist')) {
            $query->where('pharmacist_id', $user->id);
        } elseif ($user->hasRole('driver')) {
            $query->where('driver_id', $user->id);
        } elseif ($user->hasRole('feeder')) {
            $query->where('created_by', $user->id);
        }

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('branch_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('source_branch_id', $request->branch_id)
                    ->orWhere('destination_branch_id', $request->branch_id);
            });
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(20);

        return $this->successResponse([
            'orders' => OrderResource::collection($orders->items()),
            'pagination' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total()
            ]
        ]);
    }

    public function show(Request $request, Order $order)
    {
        $this->authorize('view', $order);

        $order->load(['sourceBranch', 'destinationBranch', 'pharmacist', 'driver', 'items', 'statusHistories.changedBy']);

        return $this->successResponse(new OrderResource($order));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Order::class);

        $request->validate([
            'invoice_number' => 'required|unique:orders',
            'source_branch_id' => 'required|exists:branches,id',
            'delivery_type' => 'required|in:branch_to_branch,branch_to_customer,warehouse_to_branch',
            'destination_branch_id' => 'required_unless:delivery_type,branch_to_customer|exists:branches,id',
            'customer_name' => 'required_if:delivery_type,branch_to_customer',
            'customer_address' => 'required_if:delivery_type,branch_to_customer',
            'customer_phone' => 'required_if:delivery_type,branch_to_customer',
            'items' => 'required|array|min:1',
            'items.*.product_name' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0'
        ]);

        try {
            $orderData = $request->all();
            $orderData['created_by'] = $request->user()->id;

            $order = $this->orderService->createOrder($orderData);

            return $this->successResponse(
                new OrderResource($order),
                'Order created successfully',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create order: ' . $e->getMessage());
        }
    }

    public function updateStatus(Request $request, Order $order)
    {
        $this->authorize('update', $order);

        $request->validate([
            'status' => 'required|in:preparing,ready_for_pickup,picked_up,in_transit,delivered,cancelled',
            'notes' => 'sometimes|string'
        ]);

        try {
            $updatedOrder = $this->orderService->updateOrderStatus(
                $order,
                $request->status,
                $request->notes
            );

            return $this->successResponse(
                new OrderResource($updatedOrder),
                'Order status updated successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update order status: ' . $e->getMessage());
        }
    }

    public function uploadDeliveryProof(Request $request, Order $order)
    {
        $this->authorize('update', $order);

        $request->validate([
            'proof_image' => 'required|image|max:5120', // 5MB max
            'delivery_notes' => 'sometimes|string'
        ]);

        try {
            $order->addMediaFromRequest('proof_image')
                ->toMediaCollection('delivery_proof');

            if ($request->has('delivery_notes')) {
                $order->statusHistories()->latest()->first()->update([
                    'notes' => $request->delivery_notes
                ]);
            }

            return $this->successResponse(null, 'Delivery proof uploaded successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to upload delivery proof: ' . $e->getMessage());
        }
    }
}
