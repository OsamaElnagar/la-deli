<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Carbon;

class ReportService
{
    public function getDashboardStats(?Carbon $from = null, ?Carbon $to = null)
    {
        $from = $from ?? now()->startOfMonth();
        $to = $to ?? now()->endOfMonth();

        return [
            'total_orders' => Order::whereBetween('created_at', [$from, $to])->count(),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'in_progress_orders' => Order::whereIn('status', [
                'assigned_pharmacist',
                'preparing',
                'ready_for_pickup',
                'assigned_driver',
                'picked_up',
                'in_transit'
            ])->count(),
            'delivered_orders' => Order::where('status', 'delivered')
                ->whereBetween('delivered_at', [$from, $to])
                ->count(),
            'active_drivers' => User::role('driver')
                ->whereHas('driverStatus', fn($q) => $q->where('status', 'online'))
                ->count(),
            'total_revenue' => Order::where('status', 'delivered')
                ->whereBetween('delivered_at', [$from, $to])
                ->sum('total_amount')
        ];
    }

    public function getOrdersByStatus()
    {
        return Order::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }
}
