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
        Schema::create('cms_customer_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('cms_sites')->onDelete('cascade');
            $table->string('email');
            $table->string('password')->nullable(); // NULL for guest checkout
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('phone', 20)->nullable();
            $table->boolean('is_guest')->default(false);
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();
            
            $table->unique(['site_id', 'email'], 'unique_site_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cms_customer_accounts');
    }
};
