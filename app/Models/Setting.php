<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Setting extends Model
{
    protected $fillable = [
        'restaurant_name',
        'address',
        'phone',
        'email',
        'logo',
        'default_currency_id',
        'orders_enabled',
        'tax_rate',
        'service_charge',
        'exchange_rate_enabled',
        'auto_update_prices',
    ];

    protected function casts(): array
    {
        return [
            'orders_enabled' => 'boolean',
            'tax_rate' => 'float',
            'service_charge' => 'float',
            'exchange_rate_enabled' => 'boolean',
            'auto_update_prices' => 'boolean',
        ];
    }

    public function defaultCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'default_currency_id');
    }

    public static function instance(): Setting
    {
        $setting = static::first();
        
        if (!$setting) {
            $setting = static::create([
                'restaurant_name' => 'Mon Restaurant',
                'orders_enabled' => true,
                'exchange_rate_enabled' => true,
            ]);
        }
        
        return $setting;
    }

    public static function getValue(string $key, mixed $default = null): mixed
    {
        return static::instance()->$key ?? $default;
    }

    public static function setValue(string $key, mixed $value): void
    {
        $setting = static::instance();
        $setting->$key = $value;
        $setting->save();
    }
}
