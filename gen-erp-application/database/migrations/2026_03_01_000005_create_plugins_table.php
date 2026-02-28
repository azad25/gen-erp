<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plugins', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name', 255);
            $table->string('slug', 100)->index();
            $table->string('version', 20)->default('1.0.0');
            $table->string('author', 255)->nullable();
            $table->text('description')->nullable();
            $table->json('manifest')->nullable();
            $table->string('status', 20)->default('disabled');
            $table->string('source', 20)->default('marketplace');
            $table->timestamp('installed_at')->nullable();
            $table->timestamp('enabled_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'slug']);
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plugins');
    }
};
