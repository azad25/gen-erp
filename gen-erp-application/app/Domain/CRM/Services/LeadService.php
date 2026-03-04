<?php

namespace App\Domain\CRM\Services;

use App\Domain\CRM\Contracts\LeadServiceInterface;
use App\Domain\CRM\DTOs\LeadData;
use App\Domain\CRM\Enums\LeadStatus;
use App\Domain\CRM\Enums\LeadSource;
use App\Domain\CRM\Models\Lead;
use App\Domain\CRM\Models\LeadNote;
use App\Domain\Customer\Models\Customer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class LeadService implements LeadServiceInterface
{
    public function create(LeadData $data, int $companyId, int $createdBy): Lead
    {
        return DB::transaction(function () use ($data, $companyId, $createdBy) {
            $leadData = array_merge($data->toArray(), [
                'company_id' => $companyId,
                'created_by' => $createdBy,
            ]);

            $lead = Lead::create($leadData);

            // Auto-assign if specified
            if ($data->assignedTo) {
                $lead->update(['assigned_to' => $data->assignedTo]);
            }

            // Create initial note if provided
            if ($data->notes) {
                $this->addNote($lead, $data->notes, $createdBy, ['type' => 'general']);
            }

            return $lead->fresh();
        });
    }

    public function update(Lead $lead, LeadData $data): Lead
    {
        $lead->update($data->toArray());
        return $lead->fresh();
    }

    public function delete(Lead $lead): bool
    {
        return $lead->delete();
    }

    public function findById(int $id, int $companyId): ?Lead
    {
        return Lead::forCompany($companyId)->find($id);
    }

    public function findByUuid(string $uuid, int $companyId): ?Lead
    {
        return Lead::forCompany($companyId)->where('uuid', $uuid)->first();
    }

    public function getForCompany(int $companyId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Lead::forCompany($companyId)
            ->with(['assignedTo', 'createdBy', 'tags', 'convertedToCustomer']);

        // Apply filters
        if (isset($filters['status'])) {
            $query->byStatus(LeadStatus::from($filters['status']));
        }

        if (isset($filters['assigned_to'])) {
            $query->assignedTo($filters['assigned_to']);
        }

        if (isset($filters['source'])) {
            $query->bySource(LeadSource::from($filters['source']));
        }

        if (isset($filters['min_score'])) {
            $query->highScore($filters['min_score']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%");
            });
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    public function getAssignedTo(int $userId, int $companyId, array $filters = []): Collection
    {
        $query = Lead::forCompany($companyId)
            ->assignedTo($userId)
            ->with(['tags', 'notes']);

        if (isset($filters['status'])) {
            $query->byStatus(LeadStatus::from($filters['status']));
        }

        return $query->get();
    }

    public function assignTo(Lead $lead, int $userId): Lead
    {
        $lead->update(['assigned_to' => $userId]);
        return $lead->fresh();
    }

    public function updateScore(Lead $lead, int $score): Lead
    {
        $lead->updateScore($score);
        return $lead->fresh();
    }

    public function qualify(Lead $lead): Lead
    {
        $lead->qualify();
        return $lead->fresh();
    }

    public function convertToCustomer(Lead $lead, Customer $customer): Lead
    {
        $lead->convertToCustomer($customer);
        return $lead->fresh();
    }

    public function addNote(Lead $lead, string $content, int $userId, array $options = []): void
    {
        LeadNote::create([
            'company_id' => $lead->company_id,
            'lead_id' => $lead->id,
            'user_id' => $userId,
            'title' => $options['title'] ?? null,
            'content' => $content,
            'type' => $options['type'] ?? 'general',
            'is_private' => $options['is_private'] ?? false,
            'is_pinned' => $options['is_pinned'] ?? false,
            'attachments' => $options['attachments'] ?? null,
            'tags' => $options['tags'] ?? null,
        ]);
    }

    public function addTag(Lead $lead, int $tagId, int $userId): void
    {
        if (!$lead->tags()->where('lead_tag_id', $tagId)->exists()) {
            $lead->tags()->attach($tagId, [
                'tagged_by' => $userId,
                'tagged_at' => now(),
                'is_auto_tagged' => false,
            ]);

            // Increment tag usage
            $lead->tags()->find($tagId)?->incrementUsage();
        }
    }

    public function removeTag(Lead $lead, int $tagId): void
    {
        if ($lead->tags()->where('lead_tag_id', $tagId)->exists()) {
            $lead->tags()->detach($tagId);

            // Decrement tag usage
            $lead->tags()->find($tagId)?->decrementUsage();
        }
    }

    public function bulkAssign(array $leadIds, int $userId, int $companyId): int
    {
        return Lead::forCompany($companyId)
            ->whereIn('id', $leadIds)
            ->update(['assigned_to' => $userId]);
    }

    public function bulkUpdateStatus(array $leadIds, string $status, int $companyId): int
    {
        return Lead::forCompany($companyId)
            ->whereIn('id', $leadIds)
            ->update(['status' => $status]);
    }

    public function getLeadStatistics(int $companyId, array $filters = []): array
    {
        $baseQuery = Lead::forCompany($companyId);

        // Apply date filters
        if (isset($filters['date_from'])) {
            $baseQuery->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $baseQuery->where('created_at', '<=', $filters['date_to']);
        }

        $total = $baseQuery->count();
        
        $byStatus = (clone $baseQuery)->groupBy('status')
            ->selectRaw('status, count(*) as count')
            ->pluck('count', 'status')
            ->toArray();

        $bySource = (clone $baseQuery)->groupBy('source')
            ->selectRaw('source, count(*) as count')
            ->pluck('count', 'source')
            ->toArray();

        $averageScore = (clone $baseQuery)->avg('score') ?? 0;
        $highScoreLeads = (clone $baseQuery)->where('score', '>=', 70)->count();

        $conversionRate = $total > 0 
            ? round((($byStatus[LeadStatus::CONVERTED->value] ?? 0) / $total) * 100, 2)
            : 0;

        return [
            'total_leads' => $total,
            'by_status' => $byStatus,
            'by_source' => $bySource,
            'average_score' => round($averageScore, 2),
            'high_score_leads' => $highScoreLeads,
            'conversion_rate' => $conversionRate,
        ];
    }

    public function getScoringStatistics(int $companyId): array
    {
        $baseQuery = Lead::forCompany($companyId);

        $hotLeads = (clone $baseQuery)->where('score', '>=', 80)->count();
        $warmLeads = (clone $baseQuery)->whereBetween('score', [50, 79])->count();
        $coldLeads = (clone $baseQuery)->whereBetween('score', [1, 49])->count();
        $unscoredLeads = (clone $baseQuery)->where('score', 0)->count();
        $averageScore = (clone $baseQuery)->avg('score') ?? 0;

        return [
            'hot_leads' => $hotLeads,
            'warm_leads' => $warmLeads,
            'cold_leads' => $coldLeads,
            'unscored_leads' => $unscoredLeads,
            'average_score' => round($averageScore, 1),
            'total_leads' => $hotLeads + $warmLeads + $coldLeads + $unscoredLeads,
        ];
    }

    public function bulkScore(array $leadIds, int $companyId): int
    {
        return DB::transaction(function () use ($leadIds, $companyId) {
            $leads = Lead::forCompany($companyId)->whereIn('id', $leadIds)->get();
            $updated = 0;

            foreach ($leads as $lead) {
                $score = $this->calculateLeadScore($lead);
                $lead->update(['score' => $score]);
                $updated++;
            }

            return $updated;
        });
    }

    public function bulkQualify(array $leadIds, int $companyId): int
    {
        return Lead::forCompany($companyId)
            ->whereIn('id', $leadIds)
            ->where('status', '!=', LeadStatus::QUALIFIED->value)
            ->update([
                'status' => LeadStatus::QUALIFIED->value,
                'qualified_at' => now(),
            ]);
    }

    public function scoreLead(Lead $lead): Lead
    {
        $score = $this->calculateLeadScore($lead);
        $lead->update(['score' => $score]);
        return $lead->fresh();
    }

    private function calculateLeadScore(Lead $lead): int
    {
        $score = 0;

        // Demographic scoring
        if ($lead->job_title && in_array(strtolower($lead->job_title), ['ceo', 'cto', 'manager', 'director', 'owner'])) {
            $score += 20;
        }

        if ($lead->company_name) {
            $score += 15;
        }

        if ($lead->phone) {
            $score += 10;
        }

        if ($lead->email && filter_var($lead->email, FILTER_VALIDATE_EMAIL)) {
            $score += 10;
        }

        // Source scoring
        $sourceScores = [
            'referral' => 25,
            'website' => 20,
            'social_media' => 15,
            'advertisement' => 10,
            'cold_call' => 5,
        ];
        $score += $sourceScores[$lead->source] ?? 0;

        // Expected value scoring
        if ($lead->expected_value) {
            if ($lead->expected_value >= 100000) {
                $score += 20;
            } elseif ($lead->expected_value >= 50000) {
                $score += 15;
            } elseif ($lead->expected_value >= 10000) {
                $score += 10;
            } else {
                $score += 5;
            }
        }

        // Activity scoring
        $activitiesCount = $lead->activities()->count();
        if ($activitiesCount > 0) {
            $score += min($activitiesCount * 2, 10); // Max 10 points for activities
        }

        // Engagement scoring
        if ($lead->last_activity_at && $lead->last_activity_at->diffInDays(now()) <= 7) {
            $score += 10; // Recent activity
        }

        return min($score, 100); // Cap at 100
    }
}