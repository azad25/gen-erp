<?php

namespace App\Http\Controllers\Document;

use App\Domain\Document\Services\CustomFieldManagementService;
use App\Domain\Shared\Models\CustomFieldDefinition;
use App\Http\Controllers\Controller;
use App\Support\Enums\CustomFieldType;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controller for managing custom fields from the Document domain.
 * Provides a centralized view and management interface for custom fields across all domains.
 */
class CustomFieldController extends Controller
{
    public function __construct(
        private CustomFieldManagementService $customFieldService
    ) {}

    /**
     * Display a listing of custom fields.
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['domain', 'entity_type', 'field_type', 'search', 'is_active', 'security_level']);
        $customFields = $this->customFieldService->getCustomFields($filters, $request->integer('per_page', 15));
        
        return Inertia::render('Documents/CustomFields/Index', [
            'customFields' => $customFields,
            'filters' => $filters,
            'stats' => $this->customFieldService->getCustomFieldStats(),
            'domains' => $this->customFieldService->getAvailableDomains(),
            'fieldTypes' => CustomFieldType::options(),
        ]);
    }

    /**
     * Display custom fields grouped by domain and entity.
     */
    public function overview(): Response
    {
        $groupedFields = $this->customFieldService->getAllCustomFieldsGrouped();
        $stats = $this->customFieldService->getCustomFieldStats();

        return Inertia::render('Documents/CustomFields/Overview', [
            'groupedFields' => $groupedFields,
            'stats' => $stats,
        ]);
    }

    /**
     * Show the form for creating a new custom field.
     */
    public function create(Request $request): Response
    {
        $domain = $request->get('domain');
        $entityTypes = $domain ? $this->customFieldService->getEntityTypesForDomain($domain) : collect();

        return Inertia::render('Documents/CustomFields/Create', [
            'domains' => $this->customFieldService->getAvailableDomains(),
            'entityTypes' => $entityTypes,
            'fieldTypes' => CustomFieldType::groupedOptions(),
            'selectedDomain' => $domain,
        ]);
    }

    /**
     * Store a newly created custom field.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'domain' => ['nullable', 'string', 'max:50'],
            'entity_type' => ['required', 'string', 'max:100'],
            'field_key' => ['required', 'string', 'max:100'],
            'label' => ['required', 'string', 'max:255'],
            'field_type' => ['required', 'string'],
            'help_text' => ['nullable', 'string', 'max:1000'],
            'is_required' => ['boolean'],
            'is_filterable' => ['boolean'],
            'is_searchable' => ['boolean'],
            'show_in_list' => ['boolean'],
            'default_value' => ['nullable', 'string'],
            'options' => ['nullable', 'array'],
            'validation_rules' => ['nullable', 'array'],
            'conditional_logic' => ['nullable', 'array'],
            'display_order' => ['integer', 'min:0'],
            'is_active' => ['boolean'],
            'security_level' => ['required', 'string', 'in:public,internal,restricted'],
        ]);

        // Validate unique field key within domain and entity type
        $existingField = CustomFieldDefinition::where('company_id', auth()->user()->activeCompany->id)
            ->where('domain', $validated['domain'])
            ->where('entity_type', $validated['entity_type'])
            ->where('field_key', $validated['field_key'])
            ->first();

        if ($existingField) {
            return back()->withErrors([
                'field_key' => __('A field with this key already exists for this domain and entity type.')
            ]);
        }

        $customField = $this->customFieldService->createCustomField($validated);

        return redirect()->route('documents.custom-fields.show', $customField)
            ->with('success', __('Custom field created successfully.'));
    }

    /**
     * Display the specified custom field.
     */
    public function show(CustomFieldDefinition $customField): Response
    {
        $customField->load(['creator']);
        $usageStats = $this->customFieldService->getFieldUsageStats($customField);

        return Inertia::render('Documents/CustomFields/Show', [
            'customField' => $customField,
            'usageStats' => $usageStats,
        ]);
    }

    /**
     * Show the form for editing the specified custom field.
     */
    public function edit(CustomFieldDefinition $customField): Response
    {
        $customField->load(['creator']);
        $entityTypes = $customField->domain 
            ? $this->customFieldService->getEntityTypesForDomain($customField->domain) 
            : collect();

        return Inertia::render('Documents/CustomFields/Edit', [
            'customField' => $customField,
            'domains' => $this->customFieldService->getAvailableDomains(),
            'entityTypes' => $entityTypes,
            'fieldTypes' => CustomFieldType::groupedOptions(),
        ]);
    }

    /**
     * Update the specified custom field.
     */
    public function update(Request $request, CustomFieldDefinition $customField)
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'help_text' => ['nullable', 'string', 'max:1000'],
            'is_required' => ['boolean'],
            'is_filterable' => ['boolean'],
            'is_searchable' => ['boolean'],
            'show_in_list' => ['boolean'],
            'default_value' => ['nullable', 'string'],
            'options' => ['nullable', 'array'],
            'validation_rules' => ['nullable', 'array'],
            'conditional_logic' => ['nullable', 'array'],
            'display_order' => ['integer', 'min:0'],
            'is_active' => ['boolean'],
            'security_level' => ['required', 'string', 'in:public,internal,restricted'],
        ]);

        $customField = $this->customFieldService->updateCustomField($customField, $validated);

        return redirect()->route('documents.custom-fields.show', $customField)
            ->with('success', __('Custom field updated successfully.'));
    }

    /**
     * Remove the specified custom field.
     */
    public function destroy(CustomFieldDefinition $customField)
    {
        $this->customFieldService->deleteCustomField($customField);

        return redirect()->route('documents.custom-fields.index')
            ->with('success', __('Custom field deleted successfully.'));
    }

    /**
     * Get entity types for a specific domain (AJAX endpoint).
     */
    public function getEntityTypes(Request $request)
    {
        $domain = $request->get('domain');
        
        if (!$domain) {
            return response()->json([]);
        }

        $entityTypes = $this->customFieldService->getEntityTypesForDomain($domain);

        return response()->json($entityTypes);
    }

    /**
     * Update field display order (AJAX endpoint).
     */
    public function updateOrder(Request $request)
    {
        $validated = $request->validate([
            'field_orders' => ['required', 'array'],
            'field_orders.*' => ['integer', 'min:0'],
        ]);

        $this->customFieldService->updateFieldOrder($validated['field_orders']);

        return response()->json(['success' => true]);
    }

    /**
     * Bulk actions for custom fields.
     */
    public function bulkAction(Request $request)
    {
        $validated = $request->validate([
            'action' => ['required', 'string', 'in:activate,deactivate,delete'],
            'field_ids' => ['required', 'array'],
            'field_ids.*' => ['integer', 'exists:custom_field_definitions,id'],
        ]);

        $fields = CustomFieldDefinition::whereIn('id', $validated['field_ids'])->get();
        $count = 0;

        foreach ($fields as $field) {
            try {
                switch ($validated['action']) {
                    case 'activate':
                        $this->customFieldService->updateCustomField($field, ['is_active' => true]);
                        $count++;
                        break;
                    case 'deactivate':
                        $this->customFieldService->updateCustomField($field, ['is_active' => false]);
                        $count++;
                        break;
                    case 'delete':
                        $this->customFieldService->deleteCustomField($field);
                        $count++;
                        break;
                }
            } catch (\Exception $e) {
                // Log error but continue with other fields
                \Log::error("Bulk action failed for field {$field->id}: " . $e->getMessage());
            }
        }

        $actionName = match ($validated['action']) {
            'activate' => 'activated',
            'deactivate' => 'deactivated',
            'delete' => 'deleted',
        };

        return back()->with('success', __(':count custom fields :action successfully.', [
            'count' => $count,
            'action' => $actionName,
        ]));
    }

    /**
     * Export custom fields configuration.
     */
    public function export(Request $request)
    {
        $filters = $request->only(['domain', 'entity_type', 'field_type', 'is_active']);
        $customFields = $this->customFieldService->getCustomFields($filters, 1000); // Get all for export

        $csvData = [];
        
        // Header row
        $headers = [
            'ID', 'Domain', 'Entity Type', 'Field Key', 'Label', 'Field Type',
            'Required', 'Filterable', 'Searchable', 'Show in List', 'Active',
            'Security Level', 'Created By', 'Created At'
        ];
        $csvData[] = $headers;
        
        // Data rows
        foreach ($customFields->items() as $field) {
            $csvData[] = [
                $field->id,
                $field->domain ?? '',
                $field->entity_type,
                $field->field_key,
                $field->label,
                $field->field_type->value,
                $field->is_required ? 'Yes' : 'No',
                $field->is_filterable ? 'Yes' : 'No',
                $field->is_searchable ? 'Yes' : 'No',
                $field->show_in_list ? 'Yes' : 'No',
                $field->is_active ? 'Yes' : 'No',
                $field->security_level,
                $field->creator?->name ?? '',
                $field->created_at->format('Y-m-d H:i:s'),
            ];
        }
        
        // Generate CSV content
        $output = fopen('php://temp', 'r+');
        foreach ($csvData as $row) {
            fputcsv($output, $row);
        }
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        $filename = 'custom-fields-' . now()->format('Y-m-d') . '.csv';

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }
}