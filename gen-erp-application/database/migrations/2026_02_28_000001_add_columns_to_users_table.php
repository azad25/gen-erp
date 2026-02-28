<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->after('email');
            $table->string('avatar_url', 500)->nullable()->after('phone');
            $table->string('preferred_locale', 10)->default('en')->after('avatar_url');
            $table->foreignId('last_active_company_id')
                ->nullable()
                ->after('preferred_locale')
                ->constrained('companies')
                ->nullOnDelete();
            $table->boolean('is_superadmin')->default(false)->after('last_active_company_id');
            $table->text('two_factor_secret')->nullable()->after('is_superadmin');
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
            $table->unsignedSmallInteger('failed_login_count')->default(0)->after('two_factor_confirmed_at');
            $table->timestamp('locked_until')->nullable()->after('failed_login_count');
            $table->timestamp('password_changed_at')->nullable()->after('locked_until');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['last_active_company_id']);
            $table->dropColumn([
                'phone',
                'avatar_url',
                'preferred_locale',
                'last_active_company_id',
                'is_superadmin',
                'two_factor_secret',
                'two_factor_recovery_codes',
                'two_factor_confirmed_at',
                'failed_login_count',
                'locked_until',
                'password_changed_at',
            ]);
        });
    }
};
