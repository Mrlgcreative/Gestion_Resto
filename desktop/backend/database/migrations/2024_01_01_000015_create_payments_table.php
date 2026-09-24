<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('session_id')->constrained('cashier_sessions')->onDelete('restrict');
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict'); // caissier
            $table->enum('method', ['cash'])->default('cash');
            $table->decimal('amount_paid', 15, 2);
            $table->string('currency', 10)->default('USD');
            $table->decimal('exchange_rate', 15, 4)->default(1);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
