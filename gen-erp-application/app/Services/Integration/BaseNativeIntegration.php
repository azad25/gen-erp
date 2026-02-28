<?php

namespace App\Services\Integration;

use App\Models\CompanyIntegration;
use App\Models\InboundWebhook;
use App\Models\Integration;
use App\Models\IntegrationHook;
use App\Models\SyncSchedule;
use RuntimeException;

/**
 * Abstract base class for all native (Tier 1) integrations.
 * Subclasses implement install(), uninstall(), and sync logic.
 */
abstract class BaseNativeIntegration
{
    /** The integration slug (must match integrations table). */
    abstract public function slug(): string;

    /**
     * Called when a company installs this integration.
     * Register hooks, create devices, set up sync schedules.
     */
    abstract public function install(CompanyIntegration $ci): void;

    /**
     * Called when a company uninstalls this integration.
     * Remove hooks and schedules. Never delete company data.
     */
    abstract public function uninstall(CompanyIntegration $ci): void;

    /** Get the Integration model for this native integration. */
    public function getIntegration(): Integration
    {
        return Integration::where('slug', $this->slug())->firstOrFail();
    }

    /** Register a hook handler for a company integration. */
    protected function registerHook(
        CompanyIntegration $ci,
        string $hookName,
        string $handlerClass,
        int $priority = 10,
    ): IntegrationHook {
        return IntegrationHook::create([
            'company_id' => $ci->company_id,
            'company_integration_id' => $ci->id,
            'hook_name' => $hookName,
            'handler_class' => $handlerClass,
            'priority' => $priority,
        ]);
    }

    /** Create a sync schedule for a company integration. */
    protected function createSyncSchedule(
        CompanyIntegration $ci,
        string $entityType,
        string $direction,
        string $frequency = 'hourly',
    ): SyncSchedule {
        return SyncSchedule::create([
            'company_id' => $ci->company_id,
            'company_integration_id' => $ci->id,
            'entity_type' => $entityType,
            'direction' => $direction,
            'frequency' => $frequency,
            'next_run_at' => now(),
        ]);
    }

    /** Create an inbound webhook for a company integration. */
    protected function createInboundWebhook(
        CompanyIntegration $ci,
        string $entityType,
        array $fieldMaps = [],
    ): InboundWebhook {
        return InboundWebhook::create([
            'company_id' => $ci->company_id,
            'company_integration_id' => $ci->id,
            'entity_type' => $entityType,
            'field_maps' => $fieldMaps,
        ]);
    }

    /** Verify the company's plan is eligible for this integration. */
    public function verifyPlanEligibility(string $companyPlan): void
    {
        $integration = $this->getIntegration();

        if (! $integration->isPlanEligible($companyPlan)) {
            throw new RuntimeException(
                "Integration '{$integration->name}' requires {$integration->min_plan} plan or higher."
            );
        }
    }
}
