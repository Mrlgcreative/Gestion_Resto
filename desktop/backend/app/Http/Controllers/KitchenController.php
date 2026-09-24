<?php

namespace App\Http\Controllers;

use App\Models\KitchenNotification;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

class KitchenController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:kitchen.view'),
            new Middleware('permission:kitchen.update', only: ['updateItemStatus', 'markOrderReady']),
        ];
    }

    /**
     * Affiche l'écran cuisine avec les commandes en cours
     * Filtre uniquement les items de type "nourriture" (pas les boissons)
     */
    public function index(Request $request)
    {
        $orders = Order::forService(auth()->user()->service_id)->with(['items.product.category', 'server', 'user'])
            ->whereHas('items', function ($query) {
                $query->whereIn('kitchen_status', ['waiting', 'preparing', 'ready'])
                    ->whereHas('product.category', function ($q) {
                        $q->where('type', 'food');
                    });
            })
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($order) {
                // Filtrer les items pour ne garder que la nourriture
                $foodItems = $order->items->filter(function ($item) {
                    return $item->product->category && $item->product->category->type === 'food';
                });

                return [
                    'id' => $order->id,
                    'table_number' => $order->table_number,
                    'server' => $order->server ? $order->server->name : null,
                    'created_at' => $order->created_at->toIso8601String(),
                    'elapsed_minutes' => $order->created_at->diffInMinutes(now()),
                    'items' => $foodItems->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'product_name' => $item->product->name,
                            'product_image' => $item->product->image ? '/storage/' . $item->product->image : null,
                            'category' => $item->product->category->name ?? 'Sans catégorie',
                            'quantity' => $item->quantity,
                            'kitchen_status' => $item->kitchen_status,
                            'kitchen_note' => $item->kitchen_note,
                            'ready_at' => $item->ready_at?->toIso8601String(),
                        ];
                    })->values(),
                ];
            })
            ->filter(fn($order) => count($order['items']) > 0) // Exclure les commandes sans items de nourriture
            ->values();

        // Grouper les items par statut pour la vue Kanban
        $waitingItems = [];
        $preparingItems = [];
        $readyItems = [];

        foreach ($orders as $order) {
            foreach ($order['items'] as $item) {
                $itemWithOrder = array_merge($item, [
                    'order_id' => $order['id'],
                    'table_number' => $order['table_number'],
                    'elapsed_minutes' => $order['elapsed_minutes'],
                ]);

                match ($item['kitchen_status']) {
                    'waiting' => $waitingItems[] = $itemWithOrder,
                    'preparing' => $preparingItems[] = $itemWithOrder,
                    'ready' => $readyItems[] = $itemWithOrder,
                    default => null,
                };
            }
        }

        return Inertia::render('Kitchen/Index', [
            'orders' => $orders,
            'waitingItems' => $waitingItems,
            'preparingItems' => $preparingItems,
            'readyItems' => $readyItems,
            'notifications' => $this->getUnreadNotifications('kitchen'),
        ]);
    }

    /**
     * Met à jour le statut d'un item
     */
    public function updateItemStatus(Request $request, OrderItem $item)
    {
        $request->validate([
            'status' => 'required|in:waiting,preparing,ready,served',
        ]);

        $oldStatus = $item->kitchen_status;
        $newStatus = $request->status;

        $item->update([
            'kitchen_status' => $newStatus,
            'ready_at' => $newStatus === 'ready' ? now() : $item->ready_at,
        ]);

        // Notification quand un plat est prêt
        if ($newStatus === 'ready' && $oldStatus !== 'ready') {
            KitchenNotification::notifyItemReady($item);

            // Vérifier si toute la commande est prête
            $order = $item->order->fresh(['items']);
            $allReady = $order->items->every(fn($i) => $i->kitchen_status === 'ready' || $i->kitchen_status === 'served');
            
            if ($allReady) {
                KitchenNotification::notifyOrderReady($order);
            }
        }

        // Retourner JSON pour les requêtes AJAX
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Statut mis à jour',
                'item' => $item->fresh(),
            ]);
        }

        return back()->with('success', 'Statut mis à jour');
    }

    /**
     * Marque tous les items d'une commande comme prêts
     */
    public function markOrderReady(Order $order)
    {
        $order->items()
            ->whereIn('kitchen_status', ['waiting', 'preparing'])
            ->update([
                'kitchen_status' => 'ready',
                'ready_at' => now(),
            ]);

        KitchenNotification::notifyOrderReady($order);

        return back()->with('success', 'Commande marquée comme prête');
    }

    /**
     * API pour récupérer les notifications (polling)
     */
    public function getNotifications(Request $request)
    {
        $target = $request->get('target', 'kitchen');
        
        $notifications = KitchenNotification::where('target', $target)
            ->unread()
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return response()->json([
            'notifications' => $notifications,
            'count' => $notifications->count(),
        ]);
    }

    /**
     * Marquer une notification comme lue
     */
    public function markNotificationRead(KitchenNotification $notification)
    {
        $notification->markAsRead();
        return response()->json(['success' => true]);
    }

    /**
     * Marquer toutes les notifications comme lues
     */
    public function markAllNotificationsRead(Request $request)
    {
        $target = $request->get('target', 'kitchen');
        
        KitchenNotification::where('target', $target)
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json(['success' => true]);
    }

    /**
     * API pour rafraîchir les données cuisine (polling)
     * Filtre uniquement les items de type "nourriture"
     */
    public function refresh()
    {
        $orders = Order::forService(auth()->user()->service_id)->with(['items.product.category', 'server'])
            ->whereHas('items', function ($query) {
                $query->whereIn('kitchen_status', ['waiting', 'preparing', 'ready'])
                    ->whereHas('product.category', function ($q) {
                        $q->where('type', 'food');
                    });
            })
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($order) {
                // Filtrer les items pour ne garder que la nourriture
                $foodItems = $order->items->filter(function ($item) {
                    return $item->product->category && $item->product->category->type === 'food';
                });

                return [
                    'id' => $order->id,
                    'table_number' => $order->table_number,
                    'server' => $order->server ? $order->server->name : null,
                    'created_at' => $order->created_at->toIso8601String(),
                    'elapsed_minutes' => $order->created_at->diffInMinutes(now()),
                    'items' => $foodItems->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'product_name' => $item->product->name,
                            'product_image' => $item->product->image ? '/storage/' . $item->product->image : null,
                            'category' => $item->product->category->name ?? 'Sans catégorie',
                            'quantity' => $item->quantity,
                            'kitchen_status' => $item->kitchen_status,
                            'kitchen_note' => $item->kitchen_note,
                        ];
                    })->values(),
                ];
            })
            ->filter(fn($order) => count($order['items']) > 0)
            ->values();

        return response()->json([
            'orders' => $orders,
            'notifications' => $this->getUnreadNotifications('kitchen'),
        ]);
    }

    private function getUnreadNotifications(string $target): array
    {
        return KitchenNotification::where('target', $target)
            ->unread()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }
}
