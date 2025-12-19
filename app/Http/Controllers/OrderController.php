<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\CashierSession;
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
        $query = Order::with(['server', 'session.user', 'items.product']);

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

        return Inertia::render('Orders/Index', [
            'orders' => $orders,
            'servers' => $servers,
            'filters' => $request->only(['search', 'status', 'server', 'date']),
            'canViewAll' => $request->get('can_view_all', false),
            'settings' => Setting::instance(),
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

        $categories = \App\Models\Category::orderBy('name')->get();
        $products = Product::with('category')
            ->where('status', 'available')
            ->orderBy('name')
            ->get();

        $servers = Server::where('status', 'active')->orderBy('name')->get();

        return Inertia::render('Orders/Create', [
            'currentSession' => $session,
            'categories' => $categories,
            'products' => $products,
            'servers' => $servers,
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
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.notes' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        // Create order
        $order = Order::create([
            'session_id' => $session->id,
            'user_id' => auth()->id(),
            'server_id' => $validated['server_id'],
            'status' => 'pending',
            'total_amount' => 0,
            'currency' => $session->currency,
            'exchange_rate' => $session->exchange_rate ?? 1,
        ]);

        // Add items
        foreach ($validated['items'] as $item) {
            $product = Product::find($item['product_id']);
            $order->addItem($product, $item['quantity'], $item['notes'] ?? null);
        }

        ActivityLog::logCreation('Order', $order->id);

        return redirect()->route('orders.show', $order)
            ->with('success', 'Commande créée avec succès.');
    }

    public function show(Order $order)
    {
        $user = auth()->user();
        $order->load(['server', 'session.user', 'items.product', 'payments']);
        
        // Vérifier l'accès : view_all OU commande de sa propre session
        $isOwnOrder = $order->session && $order->session->user_id === $user->id;
        if (!$user->hasPermission('orders.view_all') && !$isOwnOrder) {
            abort(403, 'Vous n\'avez pas accès à cette commande.');
        }

        return Inertia::render('Orders/Show', [
            'order' => $order,
            'settings' => Setting::instance(),
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

        $products = Product::with('category')
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

        ActivityLog::logUpdate('Order', $order->id, ['action' => 'canceled']);

        return back()->with('success', 'Commande annulée.');
    }

    public function pay(Request $request, Order $order)
    {
        if ($order->status !== 'pending') {
            return back()->with('error', 'Cette commande ne peut pas être payée.');
        }

        $validated = $request->validate([
            'amount_received' => ['required', 'numeric', 'min:' . $order->total_amount],
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
