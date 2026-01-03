<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'unit_price',
        'total_price',
        'kitchen_status',
        'kitchen_note',
        'ready_at',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'total_price' => 'decimal:2',
            'ready_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // Recalculer le total de l'item
    public function recalculate(): void
    {
        $this->total_price = $this->unit_price * $this->quantity;
        $this->save();
    }

    // Modifier la quantité
    public function updateQuantity(int $quantity): void
    {
        $this->update([
            'quantity' => $quantity,
            'total_price' => $this->unit_price * $quantity,
        ]);

        $this->order->recalculateTotal();
    }
}
