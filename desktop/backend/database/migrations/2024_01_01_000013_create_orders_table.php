<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('cashier_sessions')->onDelete('restrict');
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict'); // caissier
            $table->foreignId('server_id')->constrained('servers')->onDelete('restrict'); // serveur terrain
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->string('currency', 10)->default('USD');
            $table->decimal('exchange_rate', 15, 4)->default(1);
            $table->enum('status', ['pending', 'paid', 'canceled'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
