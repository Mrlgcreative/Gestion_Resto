<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function stats(Request $request): JsonResponse
    {
        $today = now()->startOfDay();

        $user = auth()->user();

        return response()->json([
            'today_orders' => Order::forService($user->service_id)->whereDate('created_at', $today)->count(),
            'today_revenue' => Order::forService($user->service_id)->whereDate('created_at', $today)
                ->where('status', 'paid')
                ->sum('total_amount'),
            'active_products' => Product::forService($user->service_id)->where('status', 'available')->count(),
            'active_users' => User::where('status', 'active')->count(),
            'pending_orders' => Order::forService($user->service_id)->where('status', 'pending')->count(),
        ]);
    }
}
