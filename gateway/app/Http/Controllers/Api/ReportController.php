<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function sales(Request $request): JsonResponse
    {
        $dateFrom = $request->date_from ?? now()->startOfMonth();
        $dateTo = $request->date_to ?? now()->endOfDay();

        $orders = Order::whereBetween('created_at', [$dateFrom, $dateTo]);

        return response()->json([
            'period' => ['from' => $dateFrom, 'to' => $dateTo],
            'summary' => [
                'total_orders' => (clone $orders)->count(),
                'paid_orders' => (clone $orders)->where('status', 'paid')->count(),
                'canceled_orders' => (clone $orders)->where('status', 'canceled')->count(),
                'total_revenue' => (float) (clone $orders)->where('status', 'paid')->sum('total_amount'),
                'average_order' => (float) (clone $orders)->where('status', 'paid')->avg('total_amount') ?? 0,
            ],
            'top_products' => OrderItem::whereHas('order', fn($q) => $q->whereBetween('created_at', [$dateFrom, $dateTo]))
                ->selectRaw('product_id, SUM(quantity) as total_qty, SUM(total_price) as total_revenue')
                ->groupBy('product_id')
                ->orderByDesc('total_qty')
                ->limit(10)
                ->get()
                ->load('product'),
            'daily_sales' => (clone $orders)->where('status', 'paid')
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(total_amount) as revenue')
                ->groupBy('date')
                ->orderBy('date')
                ->get(),
        ]);
    }

    public function revenue(Request $request): JsonResponse
    {
        $year = $request->year ?? now()->year;

        $monthly = Order::where('status', 'paid')
            ->whereYear('created_at', $year)
            ->selectRaw("strftime('%m', created_at) as month, COUNT(*) as count, SUM(total_amount) as revenue")
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return response()->json(['year' => $year, 'monthly' => $monthly]);
    }
}
