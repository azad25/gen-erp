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
        Schema::create('cms_menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained('cms_menus')->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('cms_menu_items')->onDelete('cascade');
            $table->string('label', 100);
            $table->string('url')->nullable();
            $table->foreignId('page_id')->nullable()->constrained('cms_pages')->onDelete('set null');
            $table->string('target', 20)->default('_self');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cms_menu_items');
    }
};
