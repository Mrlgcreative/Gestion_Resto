<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashierSession extends Model
{
    protected $fillable = [
        'user_id',
        'opened_at',
        'closed_at',
        'total_cash',
        'total_orders',
        'currency',
        'exchange_rate',
        'status',
    ];

    protected $appends = ['currency_data'];

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'total_cash' => 'decimal:2',
            'exchange_rate' => 'decimal:4',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'session_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'session_id');
    }

    // Accesseur pour récupérer les données de la devise
    public function getCurrencyDataAttribute(): ?Currency
    {
        return Currency::where('code', $this->currency)->first();
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    // Fermer la session
    public function close(): void
    {
        $this->update([
            'closed_at' => now(),
            'status' => 'closed',
            'total_cash' => $this->calculateTotalCash(),
            'total_orders' => $this->orders()->count(),
        ]);
    }

    public function calculateTotalCash(): float
    {
        return $this->payments()->sum('amount_paid');
    }

    // Statistiques de la session
    public function paidOrdersCount(): int
    {
        return $this->orders()->where('status', 'paid')->count();
    }

    public function canceledOrdersCount(): int
    {
        return $this->orders()->where('status', 'canceled')->count();
    }

    public function pendingOrdersCount(): int
    {
        return $this->orders()->where('status', 'pending')->count();
    }
}
