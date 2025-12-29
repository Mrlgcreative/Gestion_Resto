<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\ExchangeRate;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Server;
use App\Models\CashierSession;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ReportController extends Controller implements HasMiddleware
{
    // Cache des taux de change pour éviter les requêtes répétées
    private $exchangeRates = null;

    public static function middleware(): array
    {
        return [
            // Rapports : view_all OU view_own
            new Middleware('permission.ownership:reports'),
            new Middleware('permission:reports.export', only: ['export']),
        ];
    }

    /**
     * Récupérer les taux de change actifs
     */
    private function getExchangeRates(): array
    {
        if ($this->exchangeRates === null) {
            $this->exchangeRates = ExchangeRate::with('currency')
                ->where('is_active', true)
                ->get()
                ->mapWithKeys(function ($rate) {
                    return [$rate->currency->code => (float) $rate->rate];
                })
                ->toArray();
        }
        return $this->exchangeRates;
    }

    /**
     * Convertir un montant d'une devise vers une autre
     */
    private function convertAmount(float $amount, string $fromCurrency, string $toCurrency): float
    {
        if ($fromCurrency === $toCurrency) {
            return $amount;
        }

        $rates = $this->getExchangeRates();
        $fromRate = $rates[$fromCurrency] ?? 1;
        $toRate = $rates[$toCurrency] ?? 1;

        // Convertir en USD (base) puis en devise cible
        $amountInBase = $amount / $fromRate;
        return $amountInBase * $toRate;
    }

    /**
     * Déterminer la devise à utiliser pour les rapports
     */
    private function getReportCurrency($user)
    {
        // D'abord essayer la session ouverte de l'utilisateur
        $openSession = $user->openSession();
        if ($openSession && $openSession->currency) {
            return $openSession->currency;
        }

        // Sinon, utiliser la devise par défaut
        $defaultCurrency = Setting::instance()->defaultCurrency;
        return $defaultCurrency ? $defaultCurrency->code : 'USD';
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

        // Déterminer la devise pour les rapports
        $reportCurrency = $this->getReportCurrency($user);

        // Sales Data
        $salesData = $this->getSalesData($startDate, $endDate, $userId, $reportCurrency);

        // Products Data
        $productsData = $this->getProductsData($startDate, $endDate, $userId, $reportCurrency);

        // Servers Data (masqué pour view_own)
        $serversData = $viewOwnOnly ? null : $this->getServersData($startDate, $endDate, $reportCurrency);

        // Sessions Data
        $sessionsData = $this->getSessionsData($startDate, $endDate, $userId, $reportCurrency);

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
            'reportCurrency' => $reportCurrency,
            'canViewAll' => $request->get('can_view_all', false),
            'canExport' => $user->hasPermission('reports.export'),
        ]);
    }

    private function getSalesData($startDate, $endDate, $userId = null, $reportCurrency = 'USD')
    {
        $query = Order::where('status', 'paid')
            ->whereBetween('created_at', [$startDate, $endDate]);
        
        if ($userId) {
            $query->whereHas('session', fn($q) => $q->where('user_id', $userId));
        }

        // Récupérer toutes les commandes pour conversion
        $orders = (clone $query)->get();
        $totalRevenue = $orders->sum(function ($order) use ($reportCurrency) {
            return $this->convertAmount((float) $order->total_amount, $order->currency ?? 'USD', $reportCurrency);
        });
        $totalOrders = $orders->count();

        $canceledQuery = Order::where('status', 'canceled')
            ->whereBetween('created_at', [$startDate, $endDate]);
        if ($userId) {
            $canceledQuery->whereHas('session', fn($q) => $q->where('user_id', $userId));
        }
        $canceledOrders = $canceledQuery->count();

        // Ventes journalières avec conversion
        $dailySalesQuery = Order::where('status', 'paid')
            ->whereBetween('created_at', [$startDate, $endDate]);
        if ($userId) {
            $dailySalesQuery->whereHas('session', fn($q) => $q->where('user_id', $userId));
        }
        
        $dailySales = $dailySalesQuery
            ->get()
            ->groupBy(fn($order) => Carbon::parse($order->created_at)->format('Y-m-d'))
            ->map(function ($dayOrders, $date) use ($reportCurrency) {
                $revenue = $dayOrders->sum(function ($order) use ($reportCurrency) {
                    return $this->convertAmount((float) $order->total_amount, $order->currency ?? 'USD', $reportCurrency);
                });
                $ordersCount = $dayOrders->count();
                return [
                    'date' => Carbon::parse($date)->format('d/m/Y'),
                    'revenue' => round($revenue, $reportCurrency === 'CDF' ? 0 : 2),
                    'orders_count' => $ordersCount,
                    'average' => $ordersCount > 0 ? round($revenue / $ordersCount, $reportCurrency === 'CDF' ? 0 : 2) : 0,
                ];
            })
            ->sortKeys()
            ->values();

        return [
            'total_revenue' => round($totalRevenue, $reportCurrency === 'CDF' ? 0 : 2),
            'total_orders' => $totalOrders,
            'average_order' => $totalOrders > 0 ? round($totalRevenue / $totalOrders, $reportCurrency === 'CDF' ? 0 : 2) : 0,
            'canceled_orders' => $canceledOrders,
            'daily_sales' => $dailySales,
        ];
    }

    private function getProductsData($startDate, $endDate, $userId = null, $reportCurrency = 'USD')
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

        // Récupérer les items avec la devise de la commande pour conversion
        $items = (clone $baseQuery)
            ->select('order_items.*', 'products.id as product_id', 'products.name', 'products.image', 'orders.currency as order_currency')
            ->get();

        // Grouper par produit et calculer avec conversion
        $topProducts = $items->groupBy('product_id')
            ->map(function ($productItems) use ($reportCurrency) {
                $first = $productItems->first();
                $quantitySold = $productItems->sum('quantity');
                $revenue = $productItems->sum(function ($item) use ($reportCurrency) {
                    return $this->convertAmount((float) $item->total_price, $item->order_currency ?? 'USD', $reportCurrency);
                });
                return [
                    'id' => $first->product_id,
                    'name' => $first->name,
                    'image' => $first->image,
                    'quantity_sold' => $quantitySold,
                    'revenue' => round($revenue, $reportCurrency === 'CDF' ? 0 : 2),
                ];
            })
            ->sortByDesc('quantity_sold')
            ->take(10)
            ->values();

        $totalItemsSold = $items->sum('quantity');
        $uniqueProducts = $items->pluck('product_id')->unique()->count();
        $totalRevenue = $items->sum(function ($item) use ($reportCurrency) {
            return $this->convertAmount((float) $item->total_price, $item->order_currency ?? 'USD', $reportCurrency);
        });

        return [
            'top_products' => $topProducts,
            'total_items_sold' => $totalItemsSold,
            'unique_products' => $uniqueProducts,
            'total_revenue' => round($totalRevenue, $reportCurrency === 'CDF' ? 0 : 2),
            'top_product' => $topProducts->first(),
        ];
    }

    private function getServersData($startDate, $endDate, $reportCurrency = 'USD')
    {
        // Récupérer tous les serveurs
        $allServers = Server::all();
        
        // Récupérer toutes les commandes avec leur devise
        $orders = Order::where('status', 'paid')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('server_id')
            ->get();

        // Grouper les commandes par serveur
        $ordersByServer = $orders->groupBy('server_id');

        $servers = $allServers->map(function ($server) use ($ordersByServer, $reportCurrency) {
            $serverOrders = $ordersByServer->get($server->id, collect());
            $ordersCount = $serverOrders->count();
            $revenue = $serverOrders->sum(function ($order) use ($reportCurrency) {
                return $this->convertAmount((float) $order->total_amount, $order->currency ?? 'USD', $reportCurrency);
            });
            return [
                'id' => $server->id,
                'name' => $server->name,
                'orders_count' => $ordersCount,
                'revenue' => round($revenue, $reportCurrency === 'CDF' ? 0 : 2),
                'average' => $ordersCount > 0 ? round($revenue / $ordersCount, $reportCurrency === 'CDF' ? 0 : 2) : 0,
            ];
        })->sortByDesc('revenue')->values();

        $activeServers = Server::where('status', 'active')->count();
        $totalOrders = $servers->sum('orders_count');
        $totalRevenue = $servers->sum('revenue');
        $averageRevenue = $activeServers > 0 ? round($totalRevenue / $activeServers, $reportCurrency === 'CDF' ? 0 : 2) : 0;

        return [
            'servers' => $servers,
            'active_servers' => $activeServers,
            'total_orders' => $totalOrders,
            'top_server' => $servers->first(),
            'average_revenue' => $averageRevenue,
        ];
    }

    private function getSessionsData($startDate, $endDate, $userId = null, $reportCurrency = 'USD')
    {
        $query = CashierSession::with(['user', 'orders' => fn($q) => $q->where('status', 'paid')])
            ->whereBetween('opened_at', [$startDate, $endDate]);
        
        if ($userId) {
            $query->where('user_id', $userId);
        }
        
        $sessions = $query
            ->orderByDesc('opened_at')
            ->get()
            ->map(function ($session) use ($reportCurrency) {
                // Calculer le total des commandes avec conversion
                $ordersTotal = $session->orders->sum(function ($order) use ($reportCurrency) {
                    return $this->convertAmount((float) $order->total_amount, $order->currency ?? 'USD', $reportCurrency);
                });
                
                // Convertir les montants de la session
                $openingAmount = $this->convertAmount((float) $session->opening_amount, $session->currency ?? 'USD', $reportCurrency);
                $closingAmount = $session->closing_amount !== null 
                    ? $this->convertAmount((float) $session->closing_amount, $session->currency ?? 'USD', $reportCurrency)
                    : null;
                
                $expectedAmount = $openingAmount + $ordersTotal;
                
                return [
                    'id' => $session->id,
                    'user' => $session->user,
                    'currency' => $session->currency,
                    'opening_amount' => round($openingAmount, $reportCurrency === 'CDF' ? 0 : 2),
                    'closing_amount' => $closingAmount !== null ? round($closingAmount, $reportCurrency === 'CDF' ? 0 : 2) : null,
                    'expected_amount' => round($expectedAmount, $reportCurrency === 'CDF' ? 0 : 2),
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
            'total_collected' => round($totalCollected, $reportCurrency === 'CDF' ? 0 : 2),
            'total_difference' => round($totalDifference, $reportCurrency === 'CDF' ? 0 : 2),
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
