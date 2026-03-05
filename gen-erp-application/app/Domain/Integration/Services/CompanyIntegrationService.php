<?php

namespace App\Domain\Integration\Services;

use App\Domain\Integration\Events\IntegrationInstalled;
use App\Domain\Integration\Events\IntegrationUninstalled;
use App\Domain\Integration\Exceptions\IntegrationNotEligibleException;
use App\Domain\Integration\Models\CompanyIntegration;
use App\Domain\Integration\Repositories\CompanyIntegrationRepository;
use App\Domain\Integration\Repositories\IntegrationRepository;
use Illuminate\Database\Eloquent\Collection;

class CompanyIntegrationService
{
    public function __construct(
        private readonly CompanyIntegrationRepository $companyIntegrationRepository,
        private readonly IntegrationRepository $integrationRepository,
        private readonly SyncEngine $syncEngine
    ) {}

    public function getCompanyIntegrations(int $companyId, array $filters = []): Collection
    {
        return $this->companyIntegrationRepository->findByCompany($companyId, $filters);
    }

    public function findById(int $id, int $companyId): CompanyIntegration
    {
        return $this->companyIntegrationRepository->findByIdAndCompany($id, $companyId);
    }

    public function install(int $companyId, int $integrationId, array $config = []): CompanyIntegration
    {
        $integration = $this->integrationRepository->findById($integrationId);

        // Check if already installed
        $existing = $this->companyIntegrationRepository->findByCompanyAndIntegration($companyId, $integrationId);
        if ($existing) {
            throw new \RuntimeException('Integration already installed.');
        }

        // Check plan eligibility (would need company plan from context)
        // For now, we'll skip this check or implement it based on your auth setup

        $companyIntegration = $this->companyIntegrationRepository->create([
            'company_id' => $companyId,
            'integration_id' => $integrationId,
            'config' => $config,
            'status' => 'active',
            'installed_at' => now(),
        ]);

        IntegrationInstalled::dispatch($companyIntegration);

        return $companyIntegration;
    }

    public function updateConfig(int $id, int $companyId, array $config, array $fieldMaps = []): CompanyIntegration
    {
        $companyIntegration = $this->findById($id, $companyId);

        return $this->companyIntegrationRepository->update($companyIntegration, [
            'config' => $config,
            'field_maps' => $fieldMaps,
        ]);
    }

    public function uninstall(int $id, int $companyId): void
    {
        $companyIntegration = $this->findById($id, $companyId);

        IntegrationUninstalled::dispatch($companyIntegration);

        $this->companyIntegrationRepository->delete($companyIntegration);
    }

    public function activate(int $id, int $companyId): CompanyIntegration
    {
        $companyIntegration = $this->findById($id, $companyId);

        return $this->companyIntegrationRepository->update($companyIntegration, [
            'status' => 'active',
        ]);
    }

    public function deactivate(int $id, int $companyId): CompanyIntegration
    {
        $companyIntegration = $this->findById($id, $companyId);

        // Pause all sync schedules
        $this->syncEngine->pauseAll($companyIntegration->id);

        return $this->companyIntegrationRepository->update($companyIntegration, [
            'status' => 'paused',
        ]);
    }

    public function triggerSync(int $id, int $companyId): void
    {
        $companyIntegration = $this->findById($id, $companyId);

        if (!$companyIntegration->isActive()) {
            throw new \RuntimeException('Cannot sync inactive integration.');
        }

        // Trigger sync for all schedules
        foreach ($companyIntegration->syncSchedules as $schedule) {
            $this->syncEngine->runNow($schedule);
        }
    }
}
