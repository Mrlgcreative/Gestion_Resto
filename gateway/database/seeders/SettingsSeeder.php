<?php

namespace Database\Seeders;

use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        // Créer les devises
        $usd = Currency::create([
            'code' => 'USD',
            'name' => 'Dollar américain',
            'symbol' => '$',
            'is_default' => true,
        ]);

        $cdf = Currency::create([
            'code' => 'CDF',
            'name' => 'Franc congolais',
            'symbol' => 'FC',
            'is_default' => false,
        ]);

        // Créer les taux de change
        ExchangeRate::create([
            'currency_id' => $usd->id,
            'rate' => 1.0000,
            'is_active' => true,
        ]);

        ExchangeRate::create([
            'currency_id' => $cdf->id,
            'rate' => 2800.0000, // 1 USD = 2800 CDF
            'is_active' => true,
        ]);

        // Paramètres du restaurant
        Setting::create([
            'restaurant_name' => 'Mon Restaurant',
            'address' => '123 Avenue Principale, Kinshasa',
            'phone' => '+243 000 000 000',
            'email' => 'contact@monresto.cd',
            'logo' => null,
            'default_currency_id' => $usd->id,
        ]);
    }
}
