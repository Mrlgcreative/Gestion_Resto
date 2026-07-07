<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->enum('type', ['food', 'drink'])->default('food')->after('name');
        });

        // Marquer automatiquement certaines catégories comme boissons
        $drinkKeywords = ['boisson', 'drink', 'jus', 'soda', 'bière', 'vin', 'cocktail', 'café', 'thé', 'eau'];
        
        foreach ($drinkKeywords as $keyword) {
            DB::table('categories')
                ->where('name', 'LIKE', '%' . $keyword . '%')
                ->update(['type' => 'drink']);
        }
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
