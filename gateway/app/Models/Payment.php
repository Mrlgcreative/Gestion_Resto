<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'session_id',
        'user_id',
        'method',
        'amount_paid',
        'currency',
        'exchange_rate',
    ];

    protected function casts(): array
    {
        return [
            'amount_paid' => 'decimal:2',
            'exchange_rate' => 'decimal:4',
            'created_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(CashierSession::class, 'session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isCash(): bool
    {
        return $this->method === 'cash';
    }

    // Créer un paiement pour une commande
    public static function createForOrder(Order $order, CashierSession $session, User $cashier): Payment
    {
        $payment = static::create([
            'order_id' => $order->id,
            'session_id' => $session->id,
            'user_id' => $cashier->id,
            'method' => 'cash',
            'amount_paid' => $order->total_amount,
            'currency' => $order->currency,
            'exchange_rate' => $order->exchange_rate,
        ]);

        $order->markAsPaid();

        return $payment;
    }
}
