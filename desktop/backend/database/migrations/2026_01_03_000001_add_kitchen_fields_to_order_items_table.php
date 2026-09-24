<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->enum('kitchen_status', ['waiting', 'preparing', 'ready', 'served'])
                ->default('waiting')
                ->after('total_price');
            $table->text('kitchen_note')->nullable()->after('kitchen_status');
            $table->timestamp('ready_at')->nullable()->after('kitchen_note');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['kitchen_status', 'kitchen_note', 'ready_at']);
        });
    }
};
