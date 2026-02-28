<?php

namespace App\Services;

use App\Models\Plugin;
use Illuminate\Support\Facades\Log;

/**
 * Manages plugin lifecycle: install, uninstall, enable, disable.
 */
class PluginManager
{
    /**
     * Required keys in a plugin manifest.
     *
     * @var array<int, string>
     */
    private const REQUIRED_MANIFEST_KEYS = ['name', 'slug', 'version', 'hooks'];

    /**
     * Install a plugin from a manifest array.
     *
     * @param  array<string, mixed>  $manifest
     */
    public function install(int $companyId, array $manifest): Plugin
    {
        $this->validateManifest($manifest);

        $plugin = Plugin::create([
            'company_id' => $companyId,
            'name' => $manifest['name'],
            'slug' => $manifest['slug'],
            'version' => $manifest['version'] ?? '1.0.0',
            'author' => $manifest['author'] ?? null,
            'description' => $manifest['description'] ?? null,
            'manifest' => $manifest,
            'status' => Plugin::STATUS_DISABLED,
            'source' => $manifest['source'] ?? 'marketplace',
            'installed_at' => now(),
        ]);

        Log::info('Plugin installed', [
            'plugin' => $plugin->slug,
            'company_id' => $companyId,
        ]);

        return $plugin;
    }

    /**
     * Uninstall a plugin (removes it entirely).
     */
    public function uninstall(Plugin $plugin): void
    {
        $slug = $plugin->slug;
        $companyId = $plugin->company_id;

        // Disable first
        if ($plugin->isEnabled()) {
            $this->disable($plugin);
        }

        $plugin->delete();

        Log::info('Plugin uninstalled', [
            'plugin' => $slug,
            'company_id' => $companyId,
        ]);
    }

    /**
     * Enable a plugin.
     */
    public function enable(Plugin $plugin): Plugin
    {
        $plugin->update([
            'status' => Plugin::STATUS_ENABLED,
            'enabled_at' => now(),
        ]);

        // Register hooks from manifest
        $this->registerHooks($plugin);

        Log::info('Plugin enabled', ['plugin' => $plugin->slug]);

        return $plugin;
    }

    /**
     * Disable a plugin.
     */
    public function disable(Plugin $plugin): Plugin
    {
        // Deregister hooks
        $this->deregisterHooks($plugin);

        $plugin->update([
            'status' => Plugin::STATUS_DISABLED,
            'enabled_at' => null,
        ]);

        Log::info('Plugin disabled', ['plugin' => $plugin->slug]);

        return $plugin;
    }

    /**
     * Get all enabled plugins for a company.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Plugin>
     */
    public function getEnabledPlugins(int $companyId): \Illuminate\Database\Eloquent\Collection
    {
        return Plugin::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('status', Plugin::STATUS_ENABLED)
            ->get();
    }

    /**
     * Validate a plugin manifest.
     *
     * @param  array<string, mixed>  $manifest
     *
     * @throws \InvalidArgumentException
     */
    private function validateManifest(array $manifest): void
    {
        foreach (self::REQUIRED_MANIFEST_KEYS as $key) {
            if (! isset($manifest[$key])) {
                throw new \InvalidArgumentException("Plugin manifest missing required key: {$key}");
            }
        }

        if (! preg_match('/^[a-z0-9\-]+$/', $manifest['slug'])) {
            throw new \InvalidArgumentException('Plugin slug must only contain lowercase letters, numbers, and hyphens.');
        }

        // Security: no DB::statement or raw SQL allowed in hooks
        $hooksJson = json_encode($manifest['hooks'] ?? []);
        if (str_contains($hooksJson, 'DB::statement') || str_contains($hooksJson, 'DB::raw')) {
            throw new \InvalidArgumentException('Plugin hooks must not contain raw SQL operations.');
        }
    }

    /**
     * Register plugin hooks with the HookDispatcher.
     */
    private function registerHooks(Plugin $plugin): void
    {
        // TODO: wire into HookDispatcher when integration platform is active
        Log::debug('Plugin hooks registered', [
            'plugin' => $plugin->slug,
            'hooks' => array_keys($plugin->manifest['hooks'] ?? []),
        ]);
    }

    /**
     * Deregister plugin hooks.
     */
    private function deregisterHooks(Plugin $plugin): void
    {
        Log::debug('Plugin hooks deregistered', ['plugin' => $plugin->slug]);
    }
}
