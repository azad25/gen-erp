<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name', 255);
            $table->string('slug', 255)->unique();
            $table->string('logo_url', 500)->nullable();
            $table->string('business_type', 50);
            $table->string('country', 10)->default('BD');
            $table->string('currency', 10)->default('BDT');
            $table->string('timezone', 50)->default('Asia/Dhaka');
            $table->string('locale', 10)->default('en');
            $table->boolean('vat_registered')->default(false);
            $table->string('vat_bin', 20)->nullable();
            $table->string('address_line1', 255)->nullable();
            $table->string('address_line2', 255)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('district', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('website', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('plan', 20)->default('free');
            $table->timestamp('plan_expires_at')->nullable();
            $table->json('settings')->nullable();
            $table->timestamp('onboarding_completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('business_type');
            $table->index('is_active');
            $table->index('plan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
