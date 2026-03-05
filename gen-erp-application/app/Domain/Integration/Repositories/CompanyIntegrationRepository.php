<?php

namespace App\Domain\Integration\Repositories;

use App\Domain\Integration\Models\CompanyIntegration;
use Illuminate\Database\Eloquent\Collection;

class CompanyIntegrationRepository
{
    public function findByCompany(int $companyId, array $filters = []): Collection
    {
        return CompanyIntegration::query()
            ->where('company_id', $companyId)
            ->with(['integration'])
            ->when($filters['status'] ?? null, fn($q, $status) => $q->where('status', $status))
            ->when($filters['search'] ?? null, fn($q, $search) => 
                $q->whereHas('integration', fn($query) => 
                    $query->where('name', 'like', "%{$search}%")
                )
            )
            ->orderByDesc('installed_at')
            ->get();
    }

    public function findByIdAndCompany(int $id, int $companyId): CompanyIntegration
    {
        return CompanyIntegration::query()
            ->where('id', $id)
            ->where('company_id', $companyId)
            ->with(['integration', 'hooks', 'credentials', 'devices', 'syncSchedules'])
            ->firstOrFail();
    }

    public function findByCompanyAndIntegration(int $companyId, int $integrationId): ?CompanyIntegration
    {
        return CompanyIntegration::query()
            ->where('company_id', $companyId)
            ->where('integration_id', $integrationId)
            ->first();
    }

    public function create(array $data): CompanyIntegration
    {
        return CompanyIntegration::create($data);
    }

    public function update(CompanyIntegration $companyIntegration, array $data): CompanyIntegration
    {
        $companyIntegration->update($data);
        return $companyIntegration->fresh();
    }

    public function delete(CompanyIntegration $companyIntegration): void
    {
        $companyIntegration->delete();
    }
}
