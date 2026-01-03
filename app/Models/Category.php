<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = [
        'name',
        'type',
    ];

    /**
     * Vérifie si c'est une catégorie de nourriture
     */
    public function isFood(): bool
    {
        return $this->type === 'food';
    }

    /**
     * Vérifie si c'est une catégorie de boissons
     */
    public function isDrink(): bool
    {
        return $this->type === 'drink';
    }

    /**
     * Scope pour les catégories de nourriture
     */
    public function scopeFood($query)
    {
        return $query->where('type', 'food');
    }

    /**
     * Scope pour les catégories de boissons
     */
    public function scopeDrink($query)
    {
        return $query->where('type', 'drink');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function availableProducts(): HasMany
    {
        return $this->hasMany(Product::class)->where('status', 'available');
    }
}
