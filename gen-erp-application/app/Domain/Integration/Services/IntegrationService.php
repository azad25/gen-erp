<?php

namespace App\Domain\Integration\Services;

use App\Domain\Integration\Models\Integration;
use App\Domain\Integration\Repositories\IntegrationRepository;
use Illuminate\Database\Eloquent\Collection;

class IntegrationService
{
    public function __construct(
        private readonly IntegrationRepository $integrationRepository
    ) {}

    public function getAvailableIntegrations(array $filters = []): Collection
    {
        return $this->integrationRepository->findAvailable($filters);
    }

    public function findById(int $id): Integration
    {
        return $this->integrationRepository->findById($id);
    }

    public function create(array $data): Integration
    {
        return $this->integrationRepository->create($data);
    }

    public function update(int $id, array $data): Integration
    {
        $integration = $this->findById($id);
        
        return $this->integrationRepository->update($integration, $data);
    }

    public function delete(int $id): void
    {
        $integration = $this->findById($id);
        $this->integrationRepository->delete($integration);
    }

    public function checkPlanEligibility(Integration $integration, string $companyPlan): bool
    {
        return $integration->isPlanEligible($companyPlan);
    }
}
