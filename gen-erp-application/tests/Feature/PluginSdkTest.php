<?php

use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\Plugin;
use App\Models\User;
use App\Services\CompanyContext;
use App\Services\PluginManager;

// ── Plugin Installation ─────────────────────────────────────

test('plugin can be installed from manifest', function (): void {
    $user = User::factory()->create();
    $company = Company::factory()->create();
    CompanyUser::factory()->owner()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
    ]);
    CompanyContext::setActive($company);

    $manager = new PluginManager();
    $plugin = $manager->install($company->id, [
        'name' => 'Test Plugin',
        'slug' => 'test-plugin',
        'version' => '1.0.0',
        'author' => 'GenERP Team',
        'description' => 'A test plugin',
        'hooks' => ['invoice.created' => 'handleInvoiceCreated'],
    ]);

    expect($plugin->exists)->toBeTrue();
    expect($plugin->name)->toBe('Test Plugin');
    expect($plugin->slug)->toBe('test-plugin');
    expect($plugin->status)->toBe(Plugin::STATUS_DISABLED);
    expect($plugin->installed_at)->not->toBeNull();
});

test('plugin manifest validation rejects missing keys', function (): void {
    $manager = new PluginManager();

    expect(fn () => $manager->install(1, [
        'name' => 'Bad Plugin',
        // missing slug, version, hooks
    ]))->toThrow(\InvalidArgumentException::class, 'missing required key');
});

test('plugin manifest validation rejects invalid slug', function (): void {
    $manager = new PluginManager();

    expect(fn () => $manager->install(1, [
        'name' => 'Bad Plugin',
        'slug' => 'Bad Plugin Slug!',
        'version' => '1.0.0',
        'hooks' => [],
    ]))->toThrow(\InvalidArgumentException::class, 'slug must only contain');
});

test('plugin manifest rejects raw SQL in hooks', function (): void {
    $manager = new PluginManager();

    expect(fn () => $manager->install(1, [
        'name' => 'Malicious Plugin',
        'slug' => 'malicious-plugin',
        'version' => '1.0.0',
        'hooks' => ['sql_injection' => 'DB::statement("DROP TABLE users")'],
    ]))->toThrow(\InvalidArgumentException::class, 'raw SQL');
});

// ── Plugin Enable / Disable ─────────────────────────────────

test('plugin can be enabled and disabled', function (): void {
    $user = User::factory()->create();
    $company = Company::factory()->create();
    CompanyUser::factory()->owner()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
    ]);
    CompanyContext::setActive($company);

    $manager = new PluginManager();
    $plugin = $manager->install($company->id, [
        'name' => 'Toggle Plugin',
        'slug' => 'toggle-plugin',
        'version' => '1.0.0',
        'hooks' => [],
    ]);

    expect($plugin->isEnabled())->toBeFalse();

    $manager->enable($plugin);
    $plugin->refresh();
    expect($plugin->isEnabled())->toBeTrue();
    expect($plugin->enabled_at)->not->toBeNull();

    $manager->disable($plugin);
    $plugin->refresh();
    expect($plugin->isEnabled())->toBeFalse();
    expect($plugin->enabled_at)->toBeNull();
});

// ── Plugin Uninstall ────────────────────────────────────────

test('plugin can be uninstalled', function (): void {
    $user = User::factory()->create();
    $company = Company::factory()->create();
    CompanyUser::factory()->owner()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
    ]);
    CompanyContext::setActive($company);

    $manager = new PluginManager();
    $plugin = $manager->install($company->id, [
        'name' => 'Removable Plugin',
        'slug' => 'removable-plugin',
        'version' => '1.0.0',
        'hooks' => [],
    ]);

    $id = $plugin->id;
    $manager->uninstall($plugin);

    expect(Plugin::find($id))->toBeNull();
});

// ── Plugin Tenant Isolation ─────────────────────────────────

test('plugins are scoped by company', function (): void {
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    $userA = User::factory()->create();
    CompanyUser::factory()->owner()->create([
        'company_id' => $companyA->id,
        'user_id' => $userA->id,
    ]);

    $userB = User::factory()->create();
    CompanyUser::factory()->owner()->create([
        'company_id' => $companyB->id,
        'user_id' => $userB->id,
    ]);

    CompanyContext::setActive($companyA);
    Plugin::create([
        'company_id' => $companyA->id,
        'name' => 'A Plugin',
        'slug' => 'a-plugin',
        'manifest' => [],
    ]);

    CompanyContext::setActive($companyB);
    $plugins = Plugin::all();
    expect($plugins)->toHaveCount(0);
});
