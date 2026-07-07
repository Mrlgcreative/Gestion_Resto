<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_changelog', function (Blueprint $table) {
            $table->id();
            $table->string('table');
            $table->integer('record_id');
            $table->string('action'); // created, updated, deleted
            $table->json('payload');
            $table->string('status')->default('pending'); // pending, synced, conflict, failed
            $table->text('conflict_details')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['table', 'record_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_changelog');
    }
};
