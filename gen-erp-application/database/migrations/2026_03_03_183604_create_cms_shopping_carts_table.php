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
        Schema::create('cms_shopping_carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('cms_sites')->onDelete('cascade');
            $table->string('session_id')->nullable(); // For guest users
            $table->unsignedBigInteger('customer_id')->nullable(); // Will add constraint later
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            
            $table->index(['session_id']);
            $table->index(['customer_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cms_shopping_carts');
    }
};
