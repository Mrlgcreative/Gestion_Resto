<?php

namespace App\Http\Controllers;

use App\Models\CashierSession;
use App\Models\ExchangeRate;
use App\Models\Ingredient;
use App\Models\KitchenSession;
use App\Models\Order;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

class DashboardController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:dashboard.view'),
        ];
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $today = Carbon::today();
        
        // Récupérer les taux de change
        $exchangeRates = ExchangeRate::with('currency')->get();
        $rates = [];
        foreach ($exchangeRates as $er) {
            if ($er->currency) {
                $rates[$er->currency->code] = floatval($er->rate);
            }
        }
        $rates['USD'] = 1; // USD est la devise pivot
        
        // Base stats pour tous les rôles
        $stats = [
            'currency' => config('app.currency', 'USD'),
        ];
        
        // Fonction pour calculer les ventes avec conversion correcte
        $calculateSales = function ($orders) use ($rates) {
            $salesByUSD = 0;
            $salesByCDF = 0;
            
            foreach ($orders as $order) {
                $orderCurrency = $order->currency ?? 'USD';
                $orderTotal = 0;
                
                // Calculer le vrai total en convertissant chaque article
                foreach ($order->items as $item) {
                    $itemTotal = floatval($item->unit_price) * $item->quantity;
                    $productCurrency = $item->product?->currency?->code ?? 'USD';
                    
                    if ($productCurrency === $orderCurrency) {
                        $orderTotal += $itemTotal;
                    } else {
                        // Conversion via USD comme pivot
                        $fromRate = $rates[$productCurrency] ?? 1;
                        $toRate = $rates[$orderCurrency] ?? 1;
                        $amountInUSD = $itemTotal / $fromRate;
                        $orderTotal += $amountInUSD * $toRate;
                    }
                }
                
                if ($orderCurrency === 'CDF') {
                    $salesByCDF += $orderTotal;
                } else {
                    $salesByUSD += $orderTotal;
                }
            }
            
            return [
                'USD' => round($salesByUSD, 2),
                'CDF' => round($salesByCDF, 0),
            ];
        };
        
        // Stats selon les permissions
        if ($user->hasPermission('orders.view_all')) {
            // Admin/Gérant voit toutes les commandes
            $stats['todayOrders'] = Order::forService($user->service_id)->whereDate('created_at', $today)->count();
            
            $paidOrders = Order::forService($user->service_id)->with(['items.product.currency'])
                ->whereDate('created_at', $today)
                ->where('status', 'paid')
                ->get();
            $stats['todaySales'] = $calculateSales($paidOrders);
            
            $stats['pendingOrders'] = Order::forService($user->service_id)->where('status', 'pending')->count();
        } elseif ($user->hasPermission('orders.view_own')) {
            // Caissier voit seulement ses commandes (via ses sessions)
            $userSessionIds = CashierSession::where('user_id', $user->id)->pluck('id');
            $stats['todayOrders'] = Order::forService($user->service_id)->whereIn('session_id', $userSessionIds)
                ->whereDate('created_at', $today)->count();
            
            $paidOrders = Order::forService($user->service_id)->with(['items.product.currency'])
                ->whereIn('session_id', $userSessionIds)
                ->whereDate('created_at', $today)
                ->where('status', 'paid')
                ->get();
            $stats['todaySales'] = $calculateSales($paidOrders);
            
            $stats['pendingOrders'] = Order::forService($user->service_id)->whereIn('session_id', $userSessionIds)
                ->where('status', 'pending')->count();
        }
        
        // Session active du caissier
        $currentSession = null;
        if ($user->hasPermission('sessions.open')) {
            $currentSession = CashierSession::where('user_id', $user->id)
                ->whereNull('closed_at')
                ->first();
        }
        
        // Stats admin/gérant uniquement
        if ($user->hasPermission('products.view')) {
            $stats['activeProducts'] = Product::forService($user->service_id)->where('status', 'available')->count();
        }
        
        if ($user->hasPermission('stocks.view')) {
            $stats['lowStock'] = Ingredient::whereColumn('quantity', '<=', 'alert_level')->count();
        }
        
        // Recent orders selon permissions
        $recentOrders = collect();
        if ($user->hasPermission('orders.view_all')) {
            $recentOrders = Order::forService($user->service_id)->with(['server', 'currencyRelation', 'items.product.currency'])
                ->latest()
                ->take(5)
                ->get();
        } elseif ($user->hasPermission('orders.view_own')) {
            $userSessionIds = CashierSession::where('user_id', $user->id)->pluck('id');
            $recentOrders = Order::forService($user->service_id)->with(['server', 'currencyRelation', 'items.product.currency'])
                ->whereIn('session_id', $userSessionIds)
                ->latest()
                ->take(5)
                ->get();
        }
        
        // Top products (admin/gérant uniquement)
        $topProducts = collect();
        if ($user->hasPermission('products.view')) {
            $topProducts = Product::forService($user->service_id)->with('currency')
                ->withCount([
                'orderItems as orders_count' => function ($query) use ($today) {
                    $query->whereHas('order', function ($q) use ($today) {
                        $q->whereDate('created_at', $today)
                          ->where('status', 'paid');
                    });
                }
            ])
                ->where('status', 'available')
                ->orderByDesc('orders_count')
                ->take(5)
                ->get();
        }
        
        // Stats cuisine pour les cuisiniers
        $kitchenStats = null;
        $currentKitchenSession = null;
        $recentKitchenSessions = collect();
        
        if ($user->hasPermission('kitchen.view')) {
            // Session cuisine active
            $currentKitchenSession = KitchenSession::getOpenSession($user->id);
            
            // Stats du jour pour le cuisinier
            $todayKitchenSessions = KitchenSession::where('user_id', $user->id)
                ->whereDate('opened_at', $today)
                ->get();
            
            $totalItemsToday = 0;
            $totalOrdersToday = 0;
            $totalMinutesToday = 0;
            
            foreach ($todayKitchenSessions as $ks) {
                $sessionStats = $ks->calculateStats();
                $totalItemsToday += $sessionStats['total_items_prepared'];
                $totalOrdersToday += $sessionStats['total_orders_completed'];
                if ($ks->closed_at) {
                    $totalMinutesToday += $ks->opened_at->diffInMinutes($ks->closed_at);
                } elseif ($ks->id === $currentKitchenSession?->id) {
                    $totalMinutesToday += $ks->opened_at->diffInMinutes(now());
                }
            }
            
            $kitchenStats = [
                'totalItemsToday' => $totalItemsToday,
                'totalOrdersToday' => $totalOrdersToday,
                'totalMinutesToday' => $totalMinutesToday,
                'sessionsToday' => $todayKitchenSessions->count(),
                'currentSession' => $currentKitchenSession ? [
                    'id' => $currentKitchenSession->id,
                    'opened_at' => $currentKitchenSession->opened_at->toIso8601String(),
                    'duration_minutes' => $currentKitchenSession->opened_at->diffInMinutes(now()),
                    'stats' => $currentKitchenSession->calculateStats(),
                ] : null,
            ];
            
            // Sessions récentes
            $recentKitchenSessions = KitchenSession::where('user_id', $user->id)
                ->orderBy('opened_at', 'desc')
                ->take(5)
                ->get()
                ->map(function ($session) {
                    return [
                        'id' => $session->id,
                        'opened_at' => $session->opened_at->toIso8601String(),
                        'closed_at' => $session->closed_at?->toIso8601String(),
                        'duration_minutes' => $session->closed_at 
                            ? $session->opened_at->diffInMinutes($session->closed_at)
                            : $session->opened_at->diffInMinutes(now()),
                        'is_open' => $session->closed_at === null,
                        'stats' => $session->calculateStats(),
                    ];
                });
        }
        
        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'recentOrders' => $recentOrders,
            'topProducts' => $topProducts,
            'currentSession' => $currentSession,
            'exchangeRates' => ExchangeRate::with('currency')->get(),
            'kitchenStats' => $kitchenStats,
            'currentKitchenSession' => $currentKitchenSession ? [
                'id' => $currentKitchenSession->id,
                'opened_at' => $currentKitchenSession->opened_at->toIso8601String(),
            ] : null,
            'recentKitchenSessions' => $recentKitchenSessions,
        ]);
    }
}
