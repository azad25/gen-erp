<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('event_store', function (Blueprint $table) {
            $table->id();
            $table->uuid('event_id')->unique();
            $table->string('aggregate_id');
            $table->string('aggregate_type');
            $table->string('event_type');
            $table->json('event_data');
            $table->integer('version');
            $table->timestamp('occurred_at');
            $table->timestamps();

            // Indexes for performance
            $table->index(['aggregate_id', 'aggregate_type']);
            $table->index(['aggregate_id', 'aggregate_type', 'version']);
            $table->index('event_type');
            $table->index('occurred_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_store');
    }
};
