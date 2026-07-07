<?php

namespace App\Http\Middleware;

use App\Models\Service;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'app' => fn () => $this->getAppSettings(),
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'role' => $request->user()->role,
                    'service' => $request->user()->service,
                ] : null,
            ],
            'services' => fn () => Service::orderBy('sort_order')->get(),
            'permissions' => fn () => $this->getUserPermissions($request),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
            ],
        ];
    }

    /**
     * Get the application settings.
     */
    protected function getAppSettings(): array
    {
        $settings = Setting::instance();
        
        return [
            'name' => $settings->restaurant_name ?? 'RestoApp',
            'logo' => $settings->logo,
            'phone' => $settings->phone,
            'email' => $settings->email,
            'address' => $settings->address,
        ];
    }

    /**
     * Get the user's permissions for the sidebar.
     */
    protected function getUserPermissions(Request $request): array
    {
        $user = $request->user();
        
        if (!$user) {
            return [];
        }

        return [
            // Commandes
            'canViewOrders' => $user->hasPermission('orders.view_all') || $user->hasPermission('orders.view_own'),
            'canViewAllOrders' => $user->hasPermission('orders.view_all'),
            'canCreateOrders' => $user->hasPermission('orders.create'),
            
            // Sessions
            'canViewSessions' => $user->hasPermission('sessions.view_all') || $user->hasPermission('sessions.view_own'),
            'canOpenSession' => $user->hasPermission('sessions.open'),
            
            // Produits
            'canViewProducts' => $user->hasPermission('products.view'),
            'canCreateProducts' => $user->hasPermission('products.create'),
            
            // Catégories
            'canViewCategories' => $user->hasPermission('categories.view'),
            'canCreateCategories' => $user->hasPermission('categories.create'),
            
            // Stocks
            'canViewStocks' => $user->hasPermission('stocks.view'),
            'canManageStocks' => $user->hasPermission('stocks.manage'),
            
            // Serveurs
            'canViewServers' => $user->hasPermission('servers.view'),
            'canCreateServers' => $user->hasPermission('servers.create'),
            
            // Utilisateurs
            'canViewUsers' => $user->hasPermission('users.view'),
            'canCreateUsers' => $user->hasPermission('users.create'),
            
            // Paramètres
            'canViewSettings' => $user->hasPermission('settings.manage'),
            
            // Rapports
            'canViewReports' => $user->hasPermission('reports.view_all') || $user->hasPermission('reports.view_own'),
            'canViewAllReports' => $user->hasPermission('reports.view_all'),
            
            // Cuisine
            'canViewKitchen' => $user->hasPermission('kitchen.view'),
        ];
    }
}
