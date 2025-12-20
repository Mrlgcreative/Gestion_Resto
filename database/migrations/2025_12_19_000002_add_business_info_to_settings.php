<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('city')->nullable()->after('address');
            $table->string('province')->nullable()->after('city');
            $table->string('country')->nullable()->after('province');
            $table->text('description')->nullable()->after('country');
            $table->string('rccm')->nullable()->after('description');
            $table->string('id_nat')->nullable()->after('rccm');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'city',
                'province',
                'country',
                'description',
                'rccm',
                'id_nat',
            ]);
        });
    }
};
