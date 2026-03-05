<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['personal', 'team', 'company', 'resource'])->default('personal');
            $table->string('color', 7)->default('#3B82F6');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_public')->default(false);
            $table->string('timezone')->default('UTC');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'user_id']);
            $table->index(['company_id', 'type']);
            $table->index(['company_id', 'team_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendars');
    }
};
