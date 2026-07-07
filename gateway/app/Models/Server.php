<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Server extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'status',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    // Statistiques du serveur
    public function totalOrders(): int
    {
        return $this->orders()->count();
    }

    public function totalSales(): float
    {
        return $this->orders()->where('status', 'paid')->sum('total_amount');
    }

    public function todayOrders(): int
    {
        return $this->orders()->whereDate('created_at', today())->count();
    }

    public function todaySales(): float
    {
        return $this->orders()
            ->where('status', 'paid')
            ->whereDate('created_at', today())
            ->sum('total_amount');
    }
}
