<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\CashierSession;
use App\Models\ExchangeRate;
use App\Models\KitchenNotification;
use App\Models\Order;
use App\Models\Product;
use App\Models\Server;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

class OrderController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            // Vue des commandes : view_all OU view_own
            new Middleware('permission.ownership:orders', only: ['index']),
            // Actions sur les commandes
            new Middleware('permission:orders.create', only: ['create', 'store']),
            new Middleware('permission:orders.edit', only: ['edit', 'update', 'addItem', 'removeItem']),
            new Middleware('permission:orders.cancel', only: ['cancel']),
            new Middleware('permission:payments.create', only: ['pay']),
        ];
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Order::forService(auth()->user()->service_id)->with(['server', 'session.user', 'items.product.currency', 'currencyRelation']);

        // Filtrage selon les permissions
        if ($request->get('view_own_only', false)) {
            // Si view_own_only, ne montrer que les commandes de ses sessions
            $query->whereHas('session', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        // Search by order number
        if ($request->filled('search')) {
            $query->where('id', 'like', "%{$request->search}%");
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by server
        if ($request->filled('server')) {
            $query->where('server_id', $request->server);
        }

        // Filter by date
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $orders = $query->orderByDesc('created_at')->paginate(15)->withQueryString();
        $servers = Server::where('status', 'active')->orderBy('name')->get();
        
        // Get active exchange rates for currency equivalents
        $exchangeRates = ExchangeRate::with('currency')
            ->where('is_active', true)
            ->get();

        return Inertia::render('Orders/Index', [
            'orders' => $orders,
            'servers' => $servers,
            'filters' => $request->only(['search', 'status', 'server', 'date']),
            'canViewAll' => $request->get('can_view_all', false),
            'settings' => Setting::instance(),
            'exchangeRates' => $exchangeRates,
        ]);
    }

    public function create()
    {
        // Check for open session
        $session = CashierSession::where('user_id', auth()->id())
            ->whereNull('closed_at')
            ->first();

        if (!$session) {
            return redirect()->route('sessions.create')
                ->with('warning', 'Veuillez ouvrir une session de caisse avant de créer une commande.');
        }

        $categories = \App\Models\Category::forService(auth()->user()->service_id)->orderBy('name')->get();
        $products = Product::forService(auth()->user()->service_id)->with(['category', 'currency'])
            ->where('status', 'available')
            ->orderBy('name')
            ->get();

        $servers = Server::where('status', 'active')->orderBy('name')->get();

        // Get active exchange rates for currency equivalents
        $exchangeRates = ExchangeRate::with('currency')
            ->where('is_active', true)
            ->get();
        
        $settings = Setting::instance();

        return Inertia::render('Orders/Create', [
            'currentSession' => $session,
            'categories' => $categories,
            'products' => $products,
            'servers' => $servers,
            'exchangeRates' => $exchangeRates,
            'settings' => $settings,
        ]);
    }

    public function store(Request $request)
    {
        // Check for open session
        $session = CashierSession::where('user_id', auth()->id())
            ->whereNull('closed_at')
            ->first();

        if (!$session) {
            return redirect()->route('sessions.create')
                ->with('error', 'Aucune session de caisse ouverte.');
        }

        $validated = $request->validate([
            'server_id' => ['required', 'exists:servers,id'],
            'table_number' => ['nullable', 'string', 'max:20'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.notes' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'cart_currency' => ['nullable', 'string', 'max:10'],
            'total_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        // Devise du panier (devise du premier produit ajouté)
        $cartCurrencyCode = $validated['cart_currency'] ?? $session->currency ?? 'USD';
        
        // Trouver l'ID de la devise
        $currency = \App\Models\Currency::where('code', $cartCurrencyCode)->first();
        
        // Create order
        $order = Order::create([
            'session_id' => $session->id,
            'user_id' => auth()->id(),
            'server_id' => $validated['server_id'],
            'table_number' => $validated['table_number'] ?? null,
            'status' => 'pending',
            'total_amount' => $validated['total_amount'] ?? 0,
            'currency_id' => $currency?->id,
            'currency' => $cartCurrencyCode, // Garder aussi le code pour compatibilité
            'exchange_rate' => $session->exchange_rate ?? 1,
        ]);

        // Add items
        foreach ($validated['items'] as $item) {
            $product = Product::find($item['product_id']);
            $orderItem = $order->addItem($product, $item['quantity'], $item['notes'] ?? null);
            
            // Ajouter la note cuisine si présente
            if (!empty($item['notes'])) {
                $orderItem->update(['kitchen_note' => $item['notes']]);
            }
        }

        // Notifier la cuisine
        KitchenNotification::notifyNewOrder($order);

        ActivityLog::logCreation('Order', $order->id);

        return redirect()->route('orders.show', $order)
            ->with('success', 'Commande créée avec succès.');
    }

    public function show(Order $order)
    {
        $user = auth()->user();
        $order->load(['server', 'session.user', 'items.product.currency', 'payments', 'currencyRelation']);
        
        // Vérifier l'accès : view_all OU commande de sa propre session
        $isOwnOrder = $order->session && $order->session->user_id === $user->id;
        if (!$user->hasPermission('orders.view_all') && !$isOwnOrder) {
            abort(403, 'Vous n\'avez pas accès à cette commande.');
        }
        
        // Get active exchange rates for currency equivalents
        $exchangeRates = ExchangeRate::with('currency')
            ->where('is_active', true)
            ->get();

        return Inertia::render('Orders/Show', [
            'order' => $order,
            'settings' => Setting::instance(),
            'exchangeRates' => $exchangeRates,
            'canEdit' => $user->hasPermission('orders.edit') && $order->status === 'pending',
            'canCancel' => $user->hasPermission('orders.cancel') && $order->status !== 'paid',
            'canPay' => $user->hasPermission('payments.create') && $order->status === 'pending',
        ]);
    }

    public function edit(Order $order)
    {
        if ($order->status !== 'pending') {
            return back()->with('error', 'Seules les commandes en attente peuvent être modifiées.');
        }

        $order->load(['items.product', 'session']);

        $products = Product::forService(auth()->user()->service_id)->with('category')
            ->where('status', 'available')
            ->orderBy('name')
            ->get()
            ->groupBy('category.name');

        $servers = Server::where('status', 'active')->orderBy('name')->get();

        return Inertia::render('Orders/Edit', [
            'order' => $order,
            'productsByCategory' => $products,
            'servers' => $servers,
        ]);
    }

    public function update(Request $request, Order $order)
    {
        if ($order->status !== 'pending') {
            return back()->with('error', 'Seules les commandes en attente peuvent être modifiées.');
        }

        $validated = $request->validate([
            'server_id' => ['required', 'exists:servers,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.notes' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $order->update([
            'server_id' => $validated['server_id'],
            'notes' => $validated['notes'] ?? null,
        ]);

        // Remove existing items and add new ones
        $order->items()->delete();

        foreach ($validated['items'] as $item) {
            $product = Product::find($item['product_id']);
            $order->addItem($product, $item['quantity'], $item['notes'] ?? null);
        }

        ActivityLog::logUpdate('Order', $order->id);

        return redirect()->route('orders.show', $order)
            ->with('success', 'Commande mise à jour avec succès.');
    }

    public function cancel(Order $order)
    {
        if ($order->status === 'paid') {
            return back()->with('error', 'Les commandes payées ne peuvent pas être annulées.');
        }

        $order->cancel();

        // Notifier la cuisine de l'annulation
        KitchenNotification::notifyOrderCanceled($order);

        ActivityLog::logUpdate('Order', $order->id, ['action' => 'canceled']);

        return back()->with('success', 'Commande annulée.');
    }

    public function pay(Request $request, Order $order)
    {
        if ($order->status !== 'pending') {
            return back()->with('error', 'Cette commande ne peut pas être payée.');
        }

        // Charger les items avec leurs produits et devises pour le calcul
        $order->load(['items.product.currency']);
        
        // Calculer le vrai total en convertissant les articles
        $rates = [];
        $exchangeRates = \App\Models\ExchangeRate::with('currency')->get();
        foreach ($exchangeRates as $er) {
            if ($er->currency) {
                $rates[$er->currency->code] = floatval($er->rate);
            }
        }
        $rates['USD'] = 1;
        
        $orderCurrency = $order->currency ?? 'USD';
        $calculatedTotal = 0;
        
        foreach ($order->items as $item) {
            $itemTotal = floatval($item->unit_price) * $item->quantity;
            $productCurrency = $item->product?->currency?->code ?? 'USD';
            
            if ($productCurrency === $orderCurrency) {
                $calculatedTotal += $itemTotal;
            } else {
                $fromRate = $rates[$productCurrency] ?? 1;
                $toRate = $rates[$orderCurrency] ?? 1;
                $amountInUSD = $itemTotal / $fromRate;
                $calculatedTotal += $amountInUSD * $toRate;
            }
        }
        
        // Utiliser le total calculé pour la validation
        $minAmount = $calculatedTotal > 0 ? $calculatedTotal : $order->total_amount;

        $validated = $request->validate([
            'amount_received' => ['required', 'numeric', 'min:' . $minAmount],
        ]);

        $order->markAsPaid($validated['amount_received']);

        ActivityLog::logUpdate('Order', $order->id, ['action' => 'paid']);

        return back()->with('success', 'Paiement enregistré.');
    }

    public function addItem(Request $request, Order $order)
    {
        if ($order->status !== 'pending') {
            return back()->with('error', 'Seules les commandes en attente peuvent être modifiées.');
        }

        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string'],
        ]);

        $product = Product::find($validated['product_id']);
        $order->addItem($product, $validated['quantity'], $validated['notes'] ?? null);

        return back()->with('success', 'Article ajouté.');
    }

    public function removeItem(Order $order, $itemId)
    {
        if ($order->status !== 'pending') {
            return back()->with('error', 'Seules les commandes en attente peuvent être modifiées.');
        }

        $item = $order->items()->findOrFail($itemId);
        $item->delete();
        $order->recalculateTotal();

        return back()->with('success', 'Article supprimé.');
    }
}
