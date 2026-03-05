<?php

namespace App\Domain\Integration\Repositories;

use App\Domain\Integration\Models\Integration;
use Illuminate\Database\Eloquent\Collection;

class IntegrationRepository
{
    public function findAvailable(array $filters = []): Collection
    {
        return Integration::query()
            ->where('is_active', true)
            ->when($filters['category'] ?? null, fn($q, $cat) => $q->where('category', $cat))
            ->when($filters['tier'] ?? null, fn($q, $tier) => $q->where('tier', $tier))
            ->when($filters['search'] ?? null, fn($q, $search) => 
                $q->where(fn($query) => 
                    $query->where('name', 'like', "%{$search}%")
                          ->orWhere('description', 'like', "%{$search}%")
                )
            )
            ->orderBy('is_official', 'desc')
            ->orderBy('name')
            ->get();
    }

    public function findById(int $id): Integration
    {
        return Integration::findOrFail($id);
    }

    public function create(array $data): Integration
    {
        return Integration::create($data);
    }

    public function update(Integration $integration, array $data): Integration
    {
        $integration->update($data);
        return $integration->fresh();
    }

    public function delete(Integration $integration): void
    {
        $integration->delete();
    }
}
