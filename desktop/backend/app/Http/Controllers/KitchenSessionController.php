<?php

namespace App\Http\Controllers;

use App\Models\KitchenSession;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;

class KitchenSessionController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:kitchen.view'),
        ];
    }

    /**
     * Ouvre une nouvelle session de cuisine
     */
    public function open(Request $request)
    {
        $session = KitchenSession::openSession($request->user()->id);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'session' => $session,
            ]);
        }

        return back()->with('success', 'Session de cuisine ouverte');
    }

    /**
     * Ferme la session actuelle
     */
    public function close(Request $request)
    {
        $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        $session = KitchenSession::getOpenSession($request->user()->id);

        if (!$session) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Aucune session ouverte'], 404);
            }
            return back()->with('error', 'Aucune session ouverte');
        }

        $session->close($request->notes);

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Session fermée avec succès');
    }

    /**
     * Affiche la session actuelle
     */
    public function current(Request $request)
    {
        $session = KitchenSession::getOpenSession($request->user()->id);

        if (!$session) {
            return response()->json([
                'session' => null,
                'has_open_session' => false,
            ]);
        }

        $stats = $session->calculateStats();

        return response()->json([
            'session' => [
                'id' => $session->id,
                'opened_at' => $session->opened_at->toIso8601String(),
                'duration_minutes' => $session->opened_at->diffInMinutes(now()),
                'stats' => $stats,
            ],
            'has_open_session' => true,
        ]);
    }

    /**
     * Liste les sessions (avec filtres)
     */
    public function index(Request $request)
    {
        $query = KitchenSession::with('user')
            ->orderBy('opened_at', 'desc');

        // Filtre par utilisateur (admin peut voir tout)
        if (!$request->user()->hasRole('admin')) {
            $query->where('user_id', $request->user()->id);
        }

        // Filtre par date
        if ($request->date_from) {
            $query->whereDate('opened_at', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('opened_at', '<=', $request->date_to);
        }

        $sessions = $query->paginate(20);

        return Inertia::render('Kitchen/Sessions/Index', [
            'sessions' => $sessions,
        ]);
    }

    /**
     * Affiche le détail d'une session
     */
    public function show(KitchenSession $session)
    {
        $session->load('user');
        
        $items = $session->preparedItems()
            ->with(['product', 'order'])
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_name' => $item->product->name,
                    'quantity' => $item->quantity,
                    'order_number' => $item->order->id,
                    'table_number' => $item->order->table_number,
                    'created_at' => $item->created_at->toIso8601String(),
                    'ready_at' => $item->ready_at?->toIso8601String(),
                    'preparation_time' => $item->ready_at 
                        ? $item->created_at->diffInMinutes($item->ready_at) 
                        : null,
                ];
            });

        $stats = $session->calculateStats();

        return Inertia::render('Kitchen/Sessions/Show', [
            'session' => [
                'id' => $session->id,
                'user_name' => $session->user->name,
                'opened_at' => $session->opened_at->toIso8601String(),
                'closed_at' => $session->closed_at?->toIso8601String(),
                'status' => $session->status,
                'notes' => $session->notes,
                'stats' => $stats,
            ],
            'items' => $items,
        ]);
    }

    /**
     * Génère un rapport PDF pour une session
     */
    public function report(KitchenSession $session)
    {
        $session->load('user');
        
        $items = $session->preparedItems()
            ->with(['product.category', 'order'])
            ->orderBy('ready_at', 'asc')
            ->get();

        $stats = $session->calculateStats();

        // Grouper les items par catégorie
        $itemsByCategory = $items->groupBy(function ($item) {
            return $item->product->category->name ?? 'Sans catégorie';
        })->map(function ($categoryItems) {
            return [
                'items' => $categoryItems->map(function ($item) {
                    return [
                        'product_name' => $item->product->name,
                        'quantity' => $item->quantity,
                        'order_number' => $item->order->id,
                        'table_number' => $item->order->table_number,
                        'created_at' => $item->created_at->format('H:i'),
                        'ready_at' => $item->ready_at?->format('H:i'),
                        'preparation_time' => $item->ready_at 
                            ? $item->created_at->diffInMinutes($item->ready_at) . ' min'
                            : '-',
                    ];
                }),
                'total_quantity' => $categoryItems->sum('quantity'),
            ];
        });

        $data = [
            'session' => $session,
            'stats' => $stats,
            'itemsByCategory' => $itemsByCategory,
            'total_duration' => $session->closed_at 
                ? $session->opened_at->diffForHumans($session->closed_at, true)
                : $session->opened_at->diffForHumans(now(), true),
        ];

        $pdf = Pdf::loadView('reports.kitchen-session', $data);
        
        $filename = 'rapport-cuisine-' . $session->id . '.pdf';
        
        return response($pdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }
}
