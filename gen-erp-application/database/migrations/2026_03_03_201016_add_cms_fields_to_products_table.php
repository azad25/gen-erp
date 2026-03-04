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
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_featured')->default(false)->after('is_active');
            $table->json('tags')->nullable()->after('is_featured');
            $table->text('short_description')->nullable()->after('description');
            $table->json('gallery_images')->nullable()->after('image_url'); // Additional product images
            $table->json('specifications')->nullable()->after('gallery_images'); // Product specifications
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['is_featured', 'tags', 'short_description', 'gallery_images', 'specifications']);
        });
    }
};