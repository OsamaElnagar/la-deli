<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrderNotificationModel;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class OrderNotificationController extends Controller
{
    /**
     * Get notifications for a specific recipient
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'recipient_type' => 'required|in:branch,driver,customer',
            'recipient_id' => 'required|integer',
        ]);

        $query = OrderNotificationModel::forRecipient($request->recipient_type, $request->recipient_id)
            ->with('order');

        // Filter by read status
        if ($request->has('is_read')) {
            $query->where('is_read', $request->boolean('is_read'));
        }

        // Filter by notification type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $notifications = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $notifications
        ]);
    }

    /**
     * Get unread notifications count
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $request->validate([
            'recipient_type' => 'required|in:branch,driver,customer',
            'recipient_id' => 'required|integer',
        ]);

        $count = OrderNotificationModel::forRecipient($request->recipient_type, $request->recipient_id)
            ->unread()
            ->count();

        return response()->json([
            'success' => true,
            'data' => ['unread_count' => $count]
        ]);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($id): JsonResponse
    {
        $notification = OrderNotificationModel::findOrFail($id);
        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read',
            'data' => $notification
        ]);
    }

    /**
     * Mark all notifications as read for a recipient
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->validate([
            'recipient_type' => 'required|in:branch,driver,customer',
            'recipient_id' => 'required|integer',
        ]);

        $updated = OrderNotificationModel::forRecipient($request->recipient_type, $request->recipient_id)
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now()
            ]);

        return response()->json([
            'success' => true,
            'message' => "Marked {$updated} notifications as read"
        ]);
    }

    /**
     * Get specific notification
     */
    public function show($id): JsonResponse
    {
        $notification = OrderNotificationModel::with('order')->findOrFail($id);

        // Mark as read when viewed
        if (!$notification->is_read) {
            $notification->markAsRead();
        }

        return response()->json([
            'success' => true,
            'data' => $notification
        ]);
    }

    /**
     * Delete notification
     */
    public function destroy($id): JsonResponse
    {
        $notification = OrderNotificationModel::findOrFail($id);
        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted successfully'
        ]);
    }

    /**
     * Get notifications by order
     */
    public function byOrder($orderId): JsonResponse
    {
        $notifications = OrderNotificationModel::where('order_id', $orderId)
            ->with('order')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $notifications
        ]);
    }
}



// class OrderNotificationController extends Controller
// {
//     //
// }
