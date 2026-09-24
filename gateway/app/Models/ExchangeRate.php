<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExchangeRate extends Model
{
    protected $fillable = [
        'currency_id',
        'rate',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public static function activate(ExchangeRate $rate): void
    {
        // Désactiver les autres taux pour cette devise
        static::where('currency_id', $rate->currency_id)
            ->where('id', '!=', $rate->id)
            ->update(['is_active' => false]);

        $rate->update(['is_active' => true]);
    }
}
