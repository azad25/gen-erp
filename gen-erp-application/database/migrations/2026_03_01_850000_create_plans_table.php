<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100);         // "Free", "Pro", "Enterprise"
            $table->string('slug', 50)->unique(); // "free", "pro", "enterprise"
            $table->integer('monthly_price');     // in paise (BDT × 100), 0 for free
            $table->integer('annual_price');      // yearly discount price in paise
            $table->json('limits');               // {"products":5,"users":2,"branches":1,"storage_bytes":52428800}
            $table->json('feature_flags');        // {"api_access":false,"integrations":0,"plugin_sdk":false}
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['slug', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
