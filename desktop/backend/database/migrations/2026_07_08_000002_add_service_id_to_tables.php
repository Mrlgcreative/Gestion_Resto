<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('service_id')->nullable()->after('role_id')->constrained()->nullOnDelete();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('service_id')->nullable()->after('category_id')->constrained()->nullOnDelete();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('service_id')->nullable()->after('session_id')->constrained()->nullOnDelete();
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('service_id')->nullable()->after('type')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('categories', fn(Blueprint $t) => $t->dropConstrainedForeignId('service_id'));
        Schema::table('orders', fn(Blueprint $t) => $t->dropConstrainedForeignId('service_id'));
        Schema::table('products', fn(Blueprint $t) => $t->dropConstrainedForeignId('service_id'));
        Schema::table('users', fn(Blueprint $t) => $t->dropConstrainedForeignId('service_id'));
    }
};
