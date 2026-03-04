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
        Schema::create('board_columns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('color', 7)->default('#6b7280');
            $table->integer('position')->default(0);
            $table->integer('wip_limit')->nullable(); // Work In Progress limit
            $table->boolean('is_done_column')->default(false);
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->index(['board_id', 'position']);
            $table->index(['board_id', 'is_done_column']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('board_columns');
    }
};