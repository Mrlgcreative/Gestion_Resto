<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('currency_id')->nullable()->after('total_amount')->constrained('currencies')->nullOnDelete();
        });

        // Migrer les données existantes : convertir le code de devise en currency_id
        DB::statement("
            UPDATE orders
            SET currency_id = (
                SELECT id FROM currencies WHERE code = orders.currency LIMIT 1
            )
            WHERE orders.currency IS NOT NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['currency_id']);
            $table->dropColumn('currency_id');
        });
    }
};
