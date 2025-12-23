<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\CashierSession;
use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

class CashierSessionController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            // Vue des sessions : view_all OU view_own
            new Middleware('permission.ownership:sessions', only: ['index']),
            // Actions sur les sessions
            new Middleware('permission:sessions.open', only: ['create', 'store']),
            new Middleware('permission:sessions.close', only: ['close']),
            new Middleware('permission:sessions.force_close', only: ['forceClose']),
        ];
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $query = CashierSession::with('user');

        // Filtrage selon les permissions
        // Si view_own_only, ne montrer que ses propres sessions
        if ($request->get('view_own_only', false)) {
            $query->where('user_id', $user->id);
        } elseif ($request->filled('user')) {
            // Si view_all, permettre le filtre par utilisateur
            $query->where('user_id', $request->user);
        }

        // Filter by status
        if ($request->filled('status')) {
            if ($request->status === 'open') {
                $query->whereNull('closed_at');
            } else {
                $query->whereNotNull('closed_at');
            }
        }

        // Filter by date
        if ($request->filled('date')) {
            $query->whereDate('opened_at', $request->date);
        }

        $sessions = $query->orderByDesc('opened_at')->paginate(15)->withQueryString();
        
        // Liste des utilisateurs pour le filtre (seulement si can_view_all)
        $users = $request->get('can_view_all', false) 
            ? User::orderBy('name')->get(['id', 'name']) 
            : collect();

        return Inertia::render('Sessions/Index', [
            'sessions' => $sessions,
            'users' => $users,
            'filters' => $request->only(['user', 'status', 'date']),
            'canViewAll' => $request->get('can_view_all', false),
        ]);
    }

    public function create()
    {
        // Check if user already has an open session
        $openSession = CashierSession::where('user_id', auth()->id())
            ->whereNull('closed_at')
            ->first();

        if ($openSession) {
            return redirect()->route('sessions.show', $openSession)
                ->with('warning', 'Vous avez déjà une session ouverte.');
        }

        $currencies = Currency::orderBy('name')->get();

        return Inertia::render('Sessions/Create', [
            'currencies' => $currencies,
        ]);
    }

    public function store(Request $request)
    {
        // Check if user already has an open session
        $openSession = CashierSession::where('user_id', auth()->id())
            ->whereNull('closed_at')
            ->first();

        if ($openSession) {
            return redirect()->route('sessions.show', $openSession)
                ->with('warning', 'Vous avez déjà une session ouverte.');
        }

        $validated = $request->validate([
            'currency_id' => ['required', 'exists:currencies,id'],
            'opening_amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        // Récupérer le code de la devise
        $currency = Currency::find($validated['currency_id']);

        $session = CashierSession::create([
            'user_id' => auth()->id(),
            'currency_id' => $validated['currency_id'],
            'currency' => $currency->code, // Stocker le code de la devise
            'opening_amount' => $validated['opening_amount'],
            'opened_at' => now(),
            'notes' => $validated['notes'] ?? null,
        ]);

        ActivityLog::logCreation('CashierSession', $session->id);

        return redirect()->route('sessions.show', $session)
            ->with('success', 'Session ouverte avec succès.');
    }

    public function show(CashierSession $session)
    {
        $user = auth()->user();
        
        // Vérifier l'accès : view_all OU propriétaire de la session
        if (!$user->hasPermission('sessions.view_all') && $session->user_id !== $user->id) {
            abort(403, 'Vous n\'avez pas accès à cette session.');
        }

        $session->load(['user', 'orders.server', 'orders.items.product.currency', 'orders.currencyRelation', 'orders.payments']);

        // Récupérer les taux de change
        $exchangeRates = ExchangeRate::with('currency')->get();
        $rates = [];
        foreach ($exchangeRates as $er) {
            if ($er->currency) {
                $rates[$er->currency->code] = floatval($er->rate);
            }
        }
        $rates['USD'] = 1;

        $sessionCurrency = $session->currency ?? 'USD';

        // Calculer le total des ventes avec conversion
        $totalSales = 0;
        foreach ($session->orders->where('status', 'paid') as $order) {
            $orderCurrency = $order->currency ?? 'USD';
            $orderTotal = 0;
            
            foreach ($order->items as $item) {
                $itemTotal = floatval($item->unit_price) * $item->quantity;
                $productCurrency = $item->product?->currency?->code ?? 'USD';
                
                if ($productCurrency === $orderCurrency) {
                    $orderTotal += $itemTotal;
                } else {
                    $fromRate = $rates[$productCurrency] ?? 1;
                    $toRate = $rates[$orderCurrency] ?? 1;
                    $amountInUSD = $itemTotal / $fromRate;
                    $orderTotal += $amountInUSD * $toRate;
                }
            }
            
            // Convertir le total de la commande dans la devise de la session si nécessaire
            if ($orderCurrency === $sessionCurrency) {
                $totalSales += $orderTotal;
            } else {
                $fromRate = $rates[$orderCurrency] ?? 1;
                $toRate = $rates[$sessionCurrency] ?? 1;
                $amountInUSD = $orderTotal / $fromRate;
                $totalSales += $amountInUSD * $toRate;
            }
        }

        // Calculate totals
        $stats = [
            'total_orders' => $session->orders->count(),
            'total_sales' => round($totalSales, $sessionCurrency === 'CDF' ? 0 : 2),
            'total_cash' => round($totalSales, $sessionCurrency === 'CDF' ? 0 : 2),
            'expected_cash' => round($session->opening_amount + $totalSales, $sessionCurrency === 'CDF' ? 0 : 2),
        ];

        return Inertia::render('Sessions/Show', [
            'session' => $session,
            'stats' => $stats,
            'exchangeRates' => $exchangeRates,
            'canForceClose' => $user->hasPermission('sessions.force_close'),
        ]);
    }

    public function close(Request $request, CashierSession $session)
    {
        $user = auth()->user();
        
        // Vérifier l'accès : sessions.close ET (propriétaire OU force_close)
        if ($session->user_id !== $user->id && !$user->hasPermission('sessions.force_close')) {
            abort(403, 'Vous ne pouvez fermer que vos propres sessions.');
        }

        if ($session->closed_at) {
            return back()->with('error', 'Cette session est déjà fermée.');
        }

        // Check for pending orders
        $pendingOrders = $session->orders()->where('status', 'pending')->count();
        if ($pendingOrders > 0) {
            return back()->with('error', "Il reste {$pendingOrders} commande(s) en attente. Veuillez les finaliser avant de fermer la session.");
        }

        $validated = $request->validate([
            'closing_amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $session->close($validated['closing_amount'], $validated['notes']);

        ActivityLog::logUpdate('CashierSession', $session->id, ['action' => 'closed']);

        return redirect()->route('sessions.index')
            ->with('success', 'Session fermée avec succès.');
    }

    public function current()
    {
        $session = CashierSession::where('user_id', auth()->id())
            ->whereNull('closed_at')
            ->first();

        if (!$session) {
            return redirect()->route('sessions.create')
                ->with('info', 'Aucune session ouverte. Veuillez en créer une.');
        }

        return redirect()->route('sessions.show', $session);
    }
}
