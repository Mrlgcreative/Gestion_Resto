<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    protected $fillable = [
        'session_id',
        'user_id',
        'server_id',
        'table_number',
        'total_amount',
        'currency_id',
        'currency',
        'exchange_rate',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'exchange_rate' => 'decimal:4',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(CashierSession::class, 'session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * Relation vers la devise (Currency model)
     * Nommée currencyRelation pour éviter le conflit avec le champ 'currency' (string)
     */
    public function currencyRelation(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    // Statuts
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isCanceled(): bool
    {
        return $this->status === 'canceled';
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeCanceled($query)
    {
        return $query->where('status', 'canceled');
    }

    // Recalculer le total
    public function recalculateTotal(): void
    {
        $total = $this->items()->sum('total_price');
        $this->update(['total_amount' => $total]);
    }

    // Ajouter un item
    public function addItem(Product $product, int $quantity, ?string $notes = null): OrderItem
    {
        $item = $this->items()->create([
            'product_id' => $product->id,
            'quantity' => $quantity,
            'unit_price' => $product->selling_price,
            'total_price' => $product->selling_price * $quantity,
        ]);

        $this->recalculateTotal();

        return $item;
    }

    // Marquer comme payé
    public function markAsPaid(): void
    {
        $this->update(['status' => 'paid']);
    }

    // Annuler la commande
    public function cancel(): void
    {
        $this->update(['status' => 'canceled']);
    }
}
