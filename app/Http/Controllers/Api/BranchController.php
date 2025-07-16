<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BranchController extends Controller
{
    /**
     * Get all branches
     */
    public function index(Request $request): JsonResponse
    {
        $query = Branch::query();

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $branches = $query->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $branches
        ]);
    }

    /**
     * Create a new branch
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:branches',
            'address' => 'required|string',
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $branch = Branch::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Branch created successfully',
            'data' => $branch
        ], 201);
    }

    /**
     * Get specific branch
     */
    public function show($id): JsonResponse
    {
        $branch = Branch::with(['ordersFrom' => function ($query) {
            $query->latest()->limit(10);
        }, 'ordersTo' => function ($query) {
            $query->latest()->limit(10);
        }])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $branch
        ]);
    }

    /**
     * Update branch
     */
    public function update(Request $request, $id): JsonResponse
    {
        $branch = Branch::findOrFail($id);

        $request->validate([
            'name' => 'string|max:255',
            'code' => 'string|unique:branches,code,' . $id,
            'address' => 'string',
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'is_active' => 'boolean',
        ]);

        $branch->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Branch updated successfully',
            'data' => $branch
        ]);
    }

    /**
     * Get branch orders
     */
    public function orders($id, Request $request): JsonResponse
    {
        $branch = Branch::findOrFail($id);

        $query = $branch->allOrders()
            ->with(['fromBranch', 'toBranch', 'customer', 'driver', 'items']);

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by direction (from or to this branch)
        if ($request->has('direction')) {
            if ($request->direction === 'from') {
                $query->where('from_branch_id', $id);
            } elseif ($request->direction === 'to') {
                $query->where('to_branch_id', $id);
            }
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    /**
     * Get branch statistics
     */
    public function statistics($id): JsonResponse
    {
        $branch = Branch::findOrFail($id);

        $stats = [
            'total_orders' => $branch->allOrders()->count(),
            'orders_from' => $branch->ordersFrom()->count(),
            'orders_to' => $branch->ordersTo()->count(),
            'pending_orders' => $branch->allOrders()->where('status', 'pending')->count(),
            'completed_orders' => $branch->allOrders()->where('status', 'confirmed')->count(),
            'urgent_orders' => $branch->allOrders()->where('priority', 'urgent')->count(),
            'home_deliveries' => $branch->ordersFrom()->where('type', 'home_delivery')->count(),
            'internal_transfers' => $branch->allOrders()->where('type', 'internal_transfer')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Get active branches
     */
    public function active(): JsonResponse
    {
        $branches = Branch::active()->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $branches
        ]);
    }

    /**
     * Confirm order preparation
     */
    public function confirmPreparation(Request $request, $id): JsonResponse
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id'
        ]);

        $branch = Branch::findOrFail($id);
        $order = Order::findOrFail($request->order_id);

        // Check if this branch is the origin of the order
        if ($order->from_branch_id !== $branch->id) {
            return response()->json([
                'success' => false,
                'message' => 'This branch is not authorized to confirm preparation for this order'
            ], 403);
        }

        // Check if order can be prepared
        if ($order->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Order cannot be prepared in current status'
            ], 400);
        }

        $order->updateStatus('prepared');

        return response()->json([
            'success' => true,
            'message' => 'Order preparation confirmed successfully',
            'data' => $order->fresh(['fromBranch', 'toBranch', 'customer', 'driver'])
        ]);
    }

    /**
     * Confirm order receipt
     */
    public function confirmReceipt(Request $request, $id): JsonResponse
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id'
        ]);

        $branch = Branch::findOrFail($id);
        $order = Order::findOrFail($request->order_id);

        // Check if this branch is the destination of the order (for internal transfers)
        if ($order->type === 'internal_transfer' && $order->to_branch_id !== $branch->id) {
            return response()->json([
                'success' => false,
                'message' => 'This branch is not authorized to confirm receipt for this order'
            ], 403);
        }

        // Check if order can be confirmed
        if ($order->status !== 'delivered') {
            return response()->json([
                'success' => false,
                'message' => 'Order cannot be confirmed in current status'
            ], 400);
        }

        $order->updateStatus('confirmed');

        // Set driver back to available
        if ($order->driver) {
            $order->driver->setStatus('available');
        }

        return response()->json([
            'success' => true,
            'message' => 'Order receipt confirmed successfully',
            'data' => $order->fresh(['fromBranch', 'toBranch', 'customer', 'driver'])
        ]);
    }
}
