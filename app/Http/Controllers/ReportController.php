<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Server;
use App\Models\CashierSession;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ReportController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            // Rapports : view_all OU view_own
            new Middleware('permission.ownership:reports'),
            new Middleware('permission:reports.export', only: ['export']),
        ];
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $viewOwnOnly = $request->get('view_own_only', false);
        
        $startDate = $request->filled('start_date') 
            ? Carbon::parse($request->start_date)->startOfDay()
            : Carbon::today()->startOfDay();
        
        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->end_date)->endOfDay()
            : Carbon::today()->endOfDay();

        // Pour les caissiers (view_own), on ne montre que leurs propres données
        $userId = $viewOwnOnly ? $user->id : null;

        // Sales Data
        $salesData = $this->getSalesData($startDate, $endDate, $userId);

        // Products Data
        $productsData = $this->getProductsData($startDate, $endDate, $userId);

        // Servers Data (masqué pour view_own)
        $serversData = $viewOwnOnly ? null : $this->getServersData($startDate, $endDate);

        // Sessions Data
        $sessionsData = $this->getSessionsData($startDate, $endDate, $userId);

        // Stocks Data (masqué pour view_own)
        $stocksData = $viewOwnOnly ? null : $this->getStocksData();

        // Activity Data (masqué pour view_own)
        $activityData = $viewOwnOnly ? null : $this->getActivityData($startDate, $endDate);

        return Inertia::render('Reports/Index', [
            'filters' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'salesData' => $salesData,
            'productsData' => $productsData,
            'serversData' => $serversData,
            'sessionsData' => $sessionsData,
            'stocksData' => $stocksData,
            'activityData' => $activityData,
            'canViewAll' => $request->get('can_view_all', false),
            'canExport' => $user->hasPermission('reports.export'),
        ]);
    }

    private function getSalesData($startDate, $endDate, $userId = null)
    {
        $query = Order::where('status', 'paid')
            ->whereBetween('created_at', [$startDate, $endDate]);
        
        if ($userId) {
            $query->whereHas('session', fn($q) => $q->where('user_id', $userId));
        }

        $totalRevenue = (clone $query)->sum('total_amount');
        $totalOrders = (clone $query)->count();

        $canceledQuery = Order::where('status', 'canceled')
            ->whereBetween('created_at', [$startDate, $endDate]);
        if ($userId) {
            $canceledQuery->whereHas('session', fn($q) => $q->where('user_id', $userId));
        }
        $canceledOrders = $canceledQuery->count();

        $dailySalesQuery = Order::where('status', 'paid')
            ->whereBetween('created_at', [$startDate, $endDate]);
        if ($userId) {
            $dailySalesQuery->whereHas('session', fn($q) => $q->where('user_id', $userId));
        }
        
        $dailySales = $dailySalesQuery
            ->selectRaw('DATE(created_at) as date, SUM(total_amount) as revenue, COUNT(*) as orders_count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => Carbon::parse($item->date)->format('d/m/Y'),
                    'revenue' => $item->revenue,
                    'orders_count' => $item->orders_count,
                    'average' => $item->orders_count > 0 ? $item->revenue / $item->orders_count : 0,
                ];
            });

        return [
            'total_revenue' => $totalRevenue,
            'total_orders' => $totalOrders,
            'average_order' => $totalOrders > 0 ? $totalRevenue / $totalOrders : 0,
            'canceled_orders' => $canceledOrders,
            'daily_sales' => $dailySales,
        ];
    }

    private function getProductsData($startDate, $endDate, $userId = null)
    {
        $baseQuery = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.status', 'paid')
            ->whereBetween('orders.created_at', [$startDate, $endDate]);
        
        if ($userId) {
            $baseQuery->whereExists(function ($query) use ($userId) {
                $query->select(DB::raw(1))
                    ->from('cashier_sessions')
                    ->whereColumn('cashier_sessions.id', 'orders.session_id')
                    ->where('cashier_sessions.user_id', $userId);
            });
        }

        $topProducts = (clone $baseQuery)
            ->selectRaw('products.id, products.name, products.image, SUM(order_items.quantity) as quantity_sold, SUM(order_items.total_price) as revenue')
            ->groupBy('products.id', 'products.name', 'products.image')
            ->orderByDesc('quantity_sold')
            ->limit(10)
            ->get();

        $totalItemsSold = (clone $baseQuery)->sum('order_items.quantity');
        $uniqueProducts = (clone $baseQuery)->distinct('product_id')->count('product_id');
        $totalRevenue = (clone $baseQuery)->sum('order_items.total_price');

        return [
            'top_products' => $topProducts,
            'total_items_sold' => $totalItemsSold,
            'unique_products' => $uniqueProducts,
            'total_revenue' => $totalRevenue,
            'top_product' => $topProducts->first(),
        ];
    }

    private function getServersData($startDate, $endDate)
    {
        $servers = Server::leftJoin('orders', function ($join) use ($startDate, $endDate) {
                $join->on('servers.id', '=', 'orders.server_id')
                    ->where('orders.status', 'paid')
                    ->whereBetween('orders.created_at', [$startDate, $endDate]);
            })
            ->selectRaw('servers.id, servers.name, COUNT(orders.id) as orders_count, COALESCE(SUM(orders.total_amount), 0) as revenue')
            ->groupBy('servers.id', 'servers.name')
            ->orderByDesc('revenue')
            ->get()
            ->map(function ($server) {
                return [
                    'id' => $server->id,
                    'name' => $server->name,
                    'orders_count' => $server->orders_count,
                    'revenue' => $server->revenue,
                    'average' => $server->orders_count > 0 ? $server->revenue / $server->orders_count : 0,
                ];
            });

        $activeServers = Server::where('status', 'active')->count();
        $totalOrders = $servers->sum('orders_count');
        $totalRevenue = $servers->sum('revenue');
        $averageRevenue = $activeServers > 0 ? $totalRevenue / $activeServers : 0;

        return [
            'servers' => $servers,
            'active_servers' => $activeServers,
            'total_orders' => $totalOrders,
            'top_server' => $servers->first(),
            'average_revenue' => $averageRevenue,
        ];
    }

    private function getSessionsData($startDate, $endDate, $userId = null)
    {
        $query = CashierSession::with('user')
            ->whereBetween('opened_at', [$startDate, $endDate]);
        
        if ($userId) {
            $query->where('user_id', $userId);
        }
        
        $sessions = $query
            ->withSum(['orders' => fn($q) => $q->where('status', 'paid')], 'total_amount')
            ->orderByDesc('opened_at')
            ->get()
            ->map(function ($session) {
                $expectedAmount = $session->opening_amount + ($session->orders_sum_total_amount ?? 0);
                return [
                    'id' => $session->id,
                    'user' => $session->user,
                    'currency' => $session->currency,
                    'opening_amount' => $session->opening_amount,
                    'closing_amount' => $session->closing_amount,
                    'expected_amount' => $expectedAmount,
                    'opened_at' => $session->opened_at,
                    'closed_at' => $session->closed_at,
                ];
            });

        $totalSessions = $sessions->count();
        $closedSessions = $sessions->whereNotNull('closed_at')->count();
        $totalCollected = $sessions->whereNotNull('closing_amount')->sum('closing_amount');
        $totalDifference = $sessions->whereNotNull('closing_amount')
            ->sum(fn($s) => $s['closing_amount'] - $s['expected_amount']);

        return [
            'sessions' => $sessions,
            'total_sessions' => $totalSessions,
            'closed_sessions' => $closedSessions,
            'total_collected' => $totalCollected,
            'total_difference' => $totalDifference,
        ];
    }

    private function getStocksData()
    {
        $ingredients = Ingredient::orderBy('name')->get();

        $lowStock = $ingredients->filter(fn($i) => $i->quantity <= $i->alert_level);

        $recentMovements = \App\Models\StockMovement::with(['ingredient', 'user'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return [
            'total_ingredients' => $ingredients->count(),
            'low_stock_count' => $lowStock->count(),
            'low_stock' => $lowStock->values(),
            'total_value' => $ingredients->sum(fn($i) => $i->quantity * ($i->unit_price ?? 0)),
            'movements_count' => \App\Models\StockMovement::count(),
            'recent_movements' => $recentMovements,
        ];
    }

    private function getActivityData($startDate, $endDate)
    {
        $logs = \App\Models\ActivityLog::with('user')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return [
            'logs' => $logs,
        ];
    }
}
