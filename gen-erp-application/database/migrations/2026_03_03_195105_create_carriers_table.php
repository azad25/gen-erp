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
        Schema::create('carriers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            
            $table->string('name', 100);
            $table->string('code', 50); // 'pathao', 'paperfly', 'steadfast'
            $table->string('api_endpoint')->nullable();
            $table->text('api_key')->nullable();
            $table->text('api_secret')->nullable();
            
            $table->boolean('is_active')->default(true);
            $table->boolean('supports_cod')->default(true);
            $table->boolean('supports_tracking')->default(true);
            
            $table->decimal('base_rate', 10, 2)->nullable();
            $table->decimal('per_kg_rate', 10, 2)->nullable();
            $table->decimal('cod_charge_percentage', 5, 2)->nullable();
            
            $table->json('settings')->nullable(); // carrier-specific settings
            
            $table->timestamps();
            
            $table->index(['company_id', 'code']);
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carriers');
    }
};
