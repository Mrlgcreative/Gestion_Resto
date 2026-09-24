<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Currency extends Model
{
    protected $fillable = [
        'code',
        'name',
        'symbol',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function exchangeRates(): HasMany
    {
        return $this->hasMany(ExchangeRate::class);
    }

    public function activeRate(): ?ExchangeRate
    {
        return $this->exchangeRates()->where('is_active', true)->first();
    }

    public static function default(): ?Currency
    {
        return static::where('is_default', true)->first();
    }

    public static function setDefault(Currency $currency): void
    {
        static::where('is_default', true)->update(['is_default' => false]);
        $currency->update(['is_default' => true]);
    }
}
