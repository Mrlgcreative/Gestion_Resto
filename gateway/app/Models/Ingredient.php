<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ingredient extends Model
{
    protected $fillable = [
        'name',
        'quantity',
        'alert_level',
        'unit',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'alert_level' => 'decimal:2',
        ];
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_ingredients')
            ->withPivot('quantity_used')
            ->withTimestamps();
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function isLowStock(): bool
    {
        return $this->quantity <= $this->alert_level;
    }

    public function addStock(float $qty, string $reason, int $userId): StockMovement
    {
        $this->increment('quantity', $qty);

        return $this->stockMovements()->create([
            'type' => 'in',
            'quantity' => $qty,
            'reason' => $reason,
            'user_id' => $userId,
        ]);
    }

    public function removeStock(float $qty, string $reason, int $userId): StockMovement
    {
        $this->decrement('quantity', $qty);

        return $this->stockMovements()->create([
            'type' => 'out',
            'quantity' => $qty,
            'reason' => $reason,
            'user_id' => $userId,
        ]);
    }

    public static function lowStockIngredients()
    {
        return static::whereColumn('quantity', '<=', 'alert_level')->get();
    }
}
