<?php

// use App\Models\Order;
// use Illuminate\Support\Facades\DB;
// use App\Jobs\ProcessOrderAssignmentJob;
// use Illuminate\Support\Facades\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    // ->withSchedule(function (Schedule $schedule) {
    //     // Define your scheduled tasks here
    //     $schedule->call(function () {
    //         $readyOrders = Order::where('status', 'ready_for_pickup')
    //             ->whereNull('driver_id')
    //             ->limit(10)
    //             ->get();

    //         foreach ($readyOrders as $order) {
    //             ProcessOrderAssignmentJob::dispatch($order);
    //         }
    //     })->everyMinute();

    //     // Clean old notifications
    //     $schedule->call(function () {
    //         DB::table('notifications')
    //             ->where('created_at', '<', now()->subDays(30))
    //             ->delete();
    //     })->daily();
    // })
    ->create();
