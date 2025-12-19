<?php

namespace App\Http\Controllers;

use App\Models\CashierSession;
use App\Models\Ingredient;
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
        
        // Base stats pour tous les rôles
        $stats = [
            'currency' => config('app.currency', 'USD'),
        ];
        
        // Stats selon les permissions
        if ($user->hasPermission('orders.view_all')) {
            // Admin/Gérant voit toutes les commandes
            $stats['todayOrders'] = Order::whereDate('created_at', $today)->count();
            $stats['todaySales'] = Order::whereDate('created_at', $today)
                ->where('status', 'paid')
                ->sum('total_amount');
            $stats['pendingOrders'] = Order::where('status', 'pending')->count();
        } elseif ($user->hasPermission('orders.view_own')) {
            // Caissier voit seulement ses commandes (via ses sessions)
            $userSessionIds = CashierSession::where('user_id', $user->id)->pluck('id');
            $stats['todayOrders'] = Order::whereIn('session_id', $userSessionIds)
                ->whereDate('created_at', $today)->count();
            $stats['todaySales'] = Order::whereIn('session_id', $userSessionIds)
                ->whereDate('created_at', $today)
                ->where('status', 'paid')
                ->sum('total_amount');
            $stats['pendingOrders'] = Order::whereIn('session_id', $userSessionIds)
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
            $stats['activeProducts'] = Product::where('status', 'available')->count();
        }
        
        if ($user->hasPermission('stocks.view')) {
            $stats['lowStock'] = Ingredient::whereColumn('quantity', '<=', 'alert_level')->count();
        }
        
        // Recent orders selon permissions
        $recentOrders = collect();
        if ($user->hasPermission('orders.view_all')) {
            $recentOrders = Order::with('server')
                ->latest()
                ->take(5)
                ->get();
        } elseif ($user->hasPermission('orders.view_own')) {
            $userSessionIds = CashierSession::where('user_id', $user->id)->pluck('id');
            $recentOrders = Order::with('server')
                ->whereIn('session_id', $userSessionIds)
                ->latest()
                ->take(5)
                ->get();
        }
        
        // Top products (admin/gérant uniquement)
        $topProducts = collect();
        if ($user->hasPermission('products.view')) {
            $topProducts = Product::withCount([
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
        
        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'recentOrders' => $recentOrders,
            'topProducts' => $topProducts,
            'currentSession' => $currentSession,
        ]);
    }
}
