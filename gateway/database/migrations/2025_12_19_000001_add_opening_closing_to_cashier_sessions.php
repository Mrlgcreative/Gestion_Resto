<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cashier_sessions', function (Blueprint $table) {
            $table->foreignId('currency_id')->nullable()->after('user_id')->constrained('currencies')->nullOnDelete();
            $table->decimal('opening_amount', 15, 2)->default(0)->after('opened_at');
            $table->decimal('closing_amount', 15, 2)->nullable()->after('opening_amount');
            $table->text('notes')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('cashier_sessions', function (Blueprint $table) {
            $table->dropForeign(['currency_id']);
            $table->dropColumn(['currency_id', 'opening_amount', 'closing_amount', 'notes']);
        });
    }
};
