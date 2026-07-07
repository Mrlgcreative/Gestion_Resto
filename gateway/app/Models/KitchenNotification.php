<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KitchenNotification extends Model
{
    protected $fillable = [
        'order_id',
        'order_item_id',
        'type',
        'message',
        'target',
        'is_read',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'read_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    // Scopes
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeForKitchen($query)
    {
        return $query->where('target', 'kitchen');
    }

    public function scopeForServer($query)
    {
        return $query->where('target', 'server');
    }

    public function scopeForCashier($query)
    {
        return $query->where('target', 'cashier');
    }

    // Marquer comme lu
    public function markAsRead(): void
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    // Créer une notification de nouvelle commande
    public static function notifyNewOrder(Order $order): self
    {
        return self::create([
            'order_id' => $order->id,
            'type' => 'new_order',
            'message' => "Nouvelle commande #{$order->id}" . ($order->table_number ? " - Table {$order->table_number}" : ''),
            'target' => 'kitchen',
        ]);
    }

    // Créer une notification de plat prêt
    public static function notifyItemReady(OrderItem $item): self
    {
        $order = $item->order;
        return self::create([
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'type' => 'item_ready',
            'message' => "{$item->product->name} prêt - Commande #{$order->id}" . ($order->table_number ? " (Table {$order->table_number})" : ''),
            'target' => 'server',
        ]);
    }

    // Créer une notification de commande prête
    public static function notifyOrderReady(Order $order): self
    {
        return self::create([
            'order_id' => $order->id,
            'type' => 'order_ready',
            'message' => "Commande #{$order->id} entièrement prête" . ($order->table_number ? " - Table {$order->table_number}" : ''),
            'target' => 'server',
        ]);
    }

    // Créer une notification de commande annulée
    public static function notifyOrderCanceled(Order $order): self
    {
        return self::create([
            'order_id' => $order->id,
            'type' => 'order_canceled',
            'message' => "Commande #{$order->id} annulée" . ($order->table_number ? " - Table {$order->table_number}" : ''),
            'target' => 'kitchen',
        ]);
    }
}
