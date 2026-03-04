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
        Schema::create('cms_sites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('domain')->unique()->nullable(); // Custom domain
            $table->string('subdomain')->unique(); // tenant.yourplatform.com
            $table->string('logo')->nullable();
            $table->string('favicon')->nullable();
            $table->string('primary_color', 7)->default('#3B82F6');
            $table->string('accent_color', 7)->default('#10B981');
            $table->string('font_family', 100)->default('Inter');
            $table->enum('status', ['draft', 'published', 'maintenance'])->default('draft');
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->string('seo_image')->nullable();
            $table->string('google_analytics_id', 50)->nullable();
            $table->string('facebook_pixel_id', 50)->nullable();
            $table->json('settings')->nullable(); // Additional settings
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cms_sites');
    }
};
