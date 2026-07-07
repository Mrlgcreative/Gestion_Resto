<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            // Options des commandes
            $table->boolean('orders_enabled')->default(true);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('service_charge', 5, 2)->default(0);
            
            // Options du taux de change
            $table->boolean('exchange_rate_enabled')->default(true);
            $table->boolean('auto_update_prices')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'orders_enabled',
                'tax_rate',
                'service_charge',
                'exchange_rate_enabled',
                'auto_update_prices',
            ]);
        });
    }
};
