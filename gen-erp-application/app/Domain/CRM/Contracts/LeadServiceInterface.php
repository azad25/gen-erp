<?php

namespace App\Domain\CRM\Contracts;

use App\Domain\CRM\DTOs\LeadData;
use App\Domain\CRM\Models\Lead;
use App\Domain\Customer\Models\Customer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface LeadServiceInterface
{
    public function create(LeadData $data, int $companyId, int $createdBy): Lead;
    
    public function update(Lead $lead, LeadData $data): Lead;
    
    public function delete(Lead $lead): bool;
    
    public function findById(int $id, int $companyId): ?Lead;
    
    public function findByUuid(string $uuid, int $companyId): ?Lead;
    
    public function getForCompany(int $companyId, array $filters = [], int $perPage = 15): LengthAwarePaginator;
    
    public function getAssignedTo(int $userId, int $companyId, array $filters = []): Collection;
    
    public function assignTo(Lead $lead, int $userId): Lead;
    
    public function updateScore(Lead $lead, int $score): Lead;
    
    public function qualify(Lead $lead): Lead;
    
    public function convertToCustomer(Lead $lead, Customer $customer): Lead;
    
    public function addNote(Lead $lead, string $content, int $userId, array $options = []): void;
    
    public function addTag(Lead $lead, int $tagId, int $userId): void;
    
    public function removeTag(Lead $lead, int $tagId): void;
    
    public function bulkAssign(array $leadIds, int $userId, int $companyId): int;
    
    public function bulkUpdateStatus(array $leadIds, string $status, int $companyId): int;
    
    public function getLeadStatistics(int $companyId, array $filters = []): array;
    
    public function getScoringStatistics(int $companyId): array;
    
    public function bulkScore(array $leadIds, int $companyId): int;
    
    public function bulkQualify(array $leadIds, int $companyId): int;
    
    public function scoreLead(Lead $lead): Lead;
}