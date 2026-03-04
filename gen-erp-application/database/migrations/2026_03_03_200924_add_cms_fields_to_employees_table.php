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
        Schema::table('employees', function (Blueprint $table) {
            $table->boolean('show_on_website')->default(false)->after('photo_url');
            $table->text('bio')->nullable()->after('show_on_website');
            $table->string('position')->nullable()->after('bio'); // Display position for website
            $table->json('social_links')->nullable()->after('position'); // LinkedIn, Twitter, etc.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['show_on_website', 'bio', 'position', 'social_links']);
        });
    }
};