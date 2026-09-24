<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $orders = Order::forService(auth()->user()->service_id)->with(['items.product', 'user', 'server', 'payment'])
            ->when($request->status, fn($q, $v) => $q->where('status', $v))
            ->when($request->date_from, fn($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($request->date_to, fn($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);

        return response()->json($orders);
    }

    public function show(Order $order): JsonResponse
    {
        return response()->json($order->load(['items.product.category', 'user', 'server', 'payment']));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => 'required|exists:cashier_sessions,id',
            'server_id' => 'nullable|exists:servers,id',
            'table_number' => 'nullable|string|max:10',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'currency' => 'nullable|string|size:3',
        ]);

        $order = Order::create([
            'session_id' => $validated['session_id'],
            'user_id' => $request->user()->id,
            'server_id' => $validated['server_id'] ?? null,
            'table_number' => $validated['table_number'] ?? null,
            'total_amount' => 0,
            'status' => 'pending',
            'currency' => $validated['currency'] ?? 'USD',
        ]);

        $total = 0;
        foreach ($validated['items'] as $item) {
            $itemTotal = $item['quantity'] * $item['unit_price'];
            $order->items()->create([
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total_price' => $itemTotal,
                'kitchen_status' => 'waiting',
            ]);
            $total += $itemTotal;
        }

        $order->update(['total_amount' => $total]);

        return response()->json($order->load('items.product'), 201);
    }

    public function destroy(Order $order): JsonResponse
    {
        if ($order->status !== 'pending') {
            return response()->json(['message' => 'Seules les commandes en attente peuvent être supprimées.'], 422);
        }
        $order->items()->delete();
        $order->delete();

        return response()->json(['message' => 'Commande supprimée.']);
    }
}
