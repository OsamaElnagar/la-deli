<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DriverStatusController;
use App\Http\Resources\BranchResource;
use App\Models\Branch;


Route::prefix('v1')->group(function () {
    // Authentication
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        // Auth endpoints
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/profile', [AuthController::class, 'profile']);

        // Orders
        Route::apiResource('orders', OrderController::class);
        Route::post('/orders/{order}/update-status', [OrderController::class, 'updateStatus']);
        Route::post('/orders/{order}/upload-proof', [OrderController::class, 'uploadDeliveryProof']);

        // Driver specific
        Route::post('/driver/status', [DriverStatusController::class, 'updateStatus']);
        Route::get('/driver/current-order', [DriverStatusController::class, 'getCurrentOrder']);

        // Branches
        Route::get('/branches', function (Request $request) {
            $branches = Branch::active()->get();
            return response()->json([
                'success' => true,
                'data' => BranchResource::collection($branches)
            ]);
        });

        // Notifications
        Route::get('/notifications', function (Request $request) {
            $notifications = $request->user()->notifications()->paginate(20);
            return response()->json([
                'success' => true,
                'data' => $notifications->items(),
                'pagination' => [
                    'current_page' => $notifications->currentPage(),
                    'last_page' => $notifications->lastPage(),
                    'total' => $notifications->total()
                ]
            ]);
        });

        Route::post('/notifications/{id}/read', function (Request $request, $id) {
            $request->user()->notifications()->where('id', $id)->first()?->markAsRead();
            return response()->json(['success' => true]);
        });
    });
});
