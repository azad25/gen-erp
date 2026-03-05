<?php

namespace App\Domain\Document\Services;

use App\Domain\Shared\Models\CustomFieldDefinition;
use App\Domain\Shared\Services\CustomFieldService;
use App\Services\CompanyContext;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Service for managing custom fields across all domains from the Document domain.
 * This provides a centralized view and management interface for custom fields.
 */
class CustomFieldManagementService
{
    public function __construct(
        private CustomFieldService $customFieldService
    ) {}

    /**
     * Get all custom fields grouped by domain and entity type.
     */
    public function getAllCustomFieldsGrouped(): array
    {
        $companyId = CompanyContext::activeId();
        
        return Cache::remember(
            "custom_fields_grouped:{$companyId}",
            3600,
            function () {
                $fields = CustomFieldDefinition::query()
                    ->with(['creator'])
                    ->orderBy('domain')
                    ->orderBy('entity_type')
                    ->orderBy('display_order')
                    ->get();

                $grouped = [];
                
                foreach ($fields as $field) {
                    $domain = $field->domain ?? 'general';
                    $entityType = $field->entity_type;
                    
                    if (!isset($grouped[$domain])) {
                        $grouped[$domain] = [];
                    }
                    
                    if (!isset($grouped[$domain][$entityType])) {
                        $grouped[$domain][$entityType] = [];
                    }
                    
                    $grouped[$domain][$entityType][] = $field;
                }
                
                return $grouped;
            }
        );
    }

    /**
     * Get paginated custom fields with filtering.
     */
    public function getCustomFields(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = CustomFieldDefinition::query()
            ->with(['creator']);

        // Apply filters
        if (!empty($filters['domain'])) {
            $query->where('domain', $filters['domain']);
        }

        if (!empty($filters['entity_type'])) {
            $query->where('entity_type', $filters['entity_type']);
        }

        if (!empty($filters['field_type'])) {
            $query->where('field_type', $filters['field_type']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('label', 'like', "%{$filters['search']}%")
                  ->orWhere('field_key', 'like', "%{$filters['search']}%");
            });
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['security_level'])) {
            $query->where('security_level', $filters['security_level']);
        }

        return $query->orderBy('domain')
            ->orderBy('entity_type')
            ->orderBy('display_order')
            ->paginate($perPage);
    }

    /**
     * Get custom field statistics.
     */
    public function getCustomFieldStats(): array
    {
        $companyId = CompanyContext::activeId();
        
        return Cache::remember(
            "custom_field_stats:{$companyId}",
            1800, // 30 minutes
            function () {
                $totalFields = CustomFieldDefinition::count();
                $activeFields = CustomFieldDefinition::where('is_active', true)->count();
                $domainCounts = CustomFieldDefinition::select('domain', DB::raw('count(*) as count'))
                    ->groupBy('domain')
                    ->pluck('count', 'domain')
                    ->toArray();
                
                $fieldTypeCounts = CustomFieldDefinition::select('field_type', DB::raw('count(*) as count'))
                    ->groupBy('field_type')
                    ->pluck('count', 'field_type')
                    ->toArray();

                return [
                    'total_fields' => $totalFields,
                    'active_fields' => $activeFields,
                    'inactive_fields' => $totalFields - $activeFields,
                    'domains' => $domainCounts,
                    'field_types' => $fieldTypeCounts,
                ];
            }
        );
    }

    /**
     * Get available domains that have custom fields.
     */
    public function getAvailableDomains(): Collection
    {
        return CustomFieldDefinition::select('domain')
            ->whereNotNull('domain')
            ->distinct()
            ->orderBy('domain')
            ->get()
            ->pluck('domain')
            ->map(function ($domain) {
                return [
                    'value' => $domain,
                    'label' => ucfirst($domain),
                    'count' => CustomFieldDefinition::where('domain', $domain)->count(),
                ];
            });
    }

    /**
     * Get available entity types for a domain.
     */
    public function getEntityTypesForDomain(string $domain): Collection
    {
        return CustomFieldDefinition::select('entity_type')
            ->where('domain', $domain)
            ->distinct()
            ->orderBy('entity_type')
            ->get()
            ->pluck('entity_type')
            ->map(function ($entityType) use ($domain) {
                return [
                    'value' => $entityType,
                    'label' => ucwords(str_replace('_', ' ', $entityType)),
                    'count' => CustomFieldDefinition::where('domain', $domain)
                        ->where('entity_type', $entityType)
                        ->count(),
                ];
            });
    }

    /**
     * Create a custom field definition (with domain validation).
     */
    public function createCustomField(array $data): CustomFieldDefinition
    {
        // Validate domain access
        $this->validateDomainAccess($data['domain'] ?? null);

        return DB::transaction(function () use ($data) {
            $definition = CustomFieldDefinition::create([
                'company_id' => CompanyContext::activeId(),
                'domain' => $data['domain'] ?? null,
                'entity_type' => $data['entity_type'],
                'field_key' => $data['field_key'],
                'label' => $data['label'],
                'field_type' => $data['field_type'],
                'help_text' => $data['help_text'] ?? null,
                'is_required' => $data['is_required'] ?? false,
                'is_filterable' => $data['is_filterable'] ?? false,
                'is_searchable' => $data['is_searchable'] ?? false,
                'show_in_list' => $data['show_in_list'] ?? false,
                'default_value' => $data['default_value'] ?? null,
                'options' => $data['options'] ?? null,
                'validation_rules' => $data['validation_rules'] ?? null,
                'conditional_logic' => $data['conditional_logic'] ?? null,
                'display_order' => $data['display_order'] ?? 0,
                'is_active' => $data['is_active'] ?? true,
                'created_by' => auth()->id(),
                'security_level' => $data['security_level'] ?? 'internal',
            ]);

            // Clear cache
            $this->clearCustomFieldCache();

            return $definition;
        });
    }

    /**
     * Update a custom field definition (with security checks).
     */
    public function updateCustomField(CustomFieldDefinition $definition, array $data): ?CustomFieldDefinition
    {
        // Validate domain access
        $this->validateDomainAccess($definition->domain);

        return DB::transaction(function () use ($definition, $data) {
            $definition->update([
                'label' => $data['label'] ?? $definition->label,
                'help_text' => $data['help_text'] ?? $definition->help_text,
                'is_required' => $data['is_required'] ?? $definition->is_required,
                'is_filterable' => $data['is_filterable'] ?? $definition->is_filterable,
                'is_searchable' => $data['is_searchable'] ?? $definition->is_searchable,
                'show_in_list' => $data['show_in_list'] ?? $definition->show_in_list,
                'default_value' => $data['default_value'] ?? $definition->default_value,
                'options' => $data['options'] ?? $definition->options,
                'validation_rules' => $data['validation_rules'] ?? $definition->validation_rules,
                'conditional_logic' => $data['conditional_logic'] ?? $definition->conditional_logic,
                'display_order' => $data['display_order'] ?? $definition->display_order,
                'is_active' => $data['is_active'] ?? $definition->is_active,
                'security_level' => $data['security_level'] ?? $definition->security_level,
            ]);

            // Clear cache
            $this->clearCustomFieldCache();

            return $definition->fresh();
        });
    }

    /**
     * Delete a custom field definition (with security checks).
     */
    public function deleteCustomField(CustomFieldDefinition $definition): bool
    {
        // Validate domain access
        $this->validateDomainAccess($definition->domain);

        return DB::transaction(function () use ($definition) {
            // Delete all associated values
            $definition->customFieldValues()->delete();
            
            // Delete the definition
            $result = $definition->delete();
            
            // Clear cache
            $this->clearCustomFieldCache();
            
            return (bool) $result;
        });
    }

    /**
     * Bulk update custom field display order.
     */
    public function updateFieldOrder(array $fieldOrders): bool
    {
        return DB::transaction(function () use ($fieldOrders) {
            foreach ($fieldOrders as $fieldId => $order) {
                CustomFieldDefinition::where('id', $fieldId)->update([
                    'display_order' => $order
                ]);
            }
            
            $this->clearCustomFieldCache();
            return true;
        });
    }

    /**
     * Get custom field usage statistics.
     */
    public function getFieldUsageStats(CustomFieldDefinition $definition): array
    {
        $totalValues = $definition->customFieldValues()->count();
        $filledValues = $definition->customFieldValues()
            ->whereNotNull('value_text')
            ->orWhereNotNull('value_number')
            ->orWhereNotNull('value_boolean')
            ->orWhereNotNull('value_date')
            ->orWhereNotNull('value_json')
            ->count();

        return [
            'total_records' => $totalValues,
            'filled_records' => $filledValues,
            'empty_records' => $totalValues - $filledValues,
            'usage_percentage' => $totalValues > 0 ? round(($filledValues / $totalValues) * 100, 2) : 0,
        ];
    }

    /**
     * Validate domain access for security.
     */
    private function validateDomainAccess(?string $domain): void
    {
        // Add domain-specific access control logic here
        // For now, allow all domains but this can be extended
        
        $allowedDomains = [
            'sales', 'purchase', 'inventory', 'accounting', 
            'hr', 'crm', 'projects', 'cms', 'documents'
        ];

        if ($domain && !in_array($domain, $allowedDomains)) {
            throw new \InvalidArgumentException("Invalid domain: {$domain}");
        }
    }

    /**
     * Clear custom field related caches.
     */
    private function clearCustomFieldCache(): void
    {
        $companyId = CompanyContext::activeId();
        
        Cache::forget("custom_fields_grouped:{$companyId}");
        Cache::forget("custom_field_stats:{$companyId}");
        
        // Clear domain-specific caches
        $domains = ['sales', 'purchase', 'inventory', 'accounting', 'hr', 'crm', 'projects', 'cms'];
        foreach ($domains as $domain) {
            Cache::forget("custom_fields:{$companyId}:{$domain}");
        }
    }
}