<?php

namespace App\Domain\Document\Services;

use App\Domain\Document\Models\Form;
use App\Domain\Document\Models\FormField;
use App\Domain\Document\Models\FormSubmission;
use App\Services\CompanyContext;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Service for managing forms and form submissions.
 */
class FormService
{
    /**
     * Get paginated forms for the active company.
     */
    public function getForms(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Form::query()
            ->with(['creator', 'fields'])
            ->withCount(['submissions']);

        // Apply filters
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                  ->orWhere('description', 'like', "%{$filters['search']}%");
            });
        }

        if (isset($filters['is_public'])) {
            $query->where('is_public', $filters['is_public']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Create a new form.
     */
    public function createForm(array $data): Form
    {
        return DB::transaction(function () use ($data) {
            $form = Form::create([
                'company_id' => CompanyContext::activeId(),
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'slug' => $this->generateUniqueSlug($data['name']),
                'is_public' => $data['is_public'] ?? false,
                'is_active' => $data['is_active'] ?? true,
                'settings' => $data['settings'] ?? [],
                'created_by' => auth()->id(),
            ]);

            // Create form fields if provided
            if (!empty($data['fields'])) {
                $this->createFormFields($form, $data['fields']);
            }

            return $form->load(['fields', 'creator']);
        });
    }

    /**
     * Update an existing form.
     */
    public function updateForm(Form $form, array $data): Form
    {
        return DB::transaction(function () use ($form, $data) {
            // Update form basic info
            $form->update([
                'name' => $data['name'] ?? $form->name,
                'description' => $data['description'] ?? $form->description,
                'is_public' => $data['is_public'] ?? $form->is_public,
                'is_active' => $data['is_active'] ?? $form->is_active,
                'settings' => $data['settings'] ?? $form->settings,
            ]);

            // Update slug if name changed
            if (isset($data['name']) && $data['name'] !== $form->getOriginal('name')) {
                $form->update(['slug' => $this->generateUniqueSlug($data['name'], $form->id)]);
            }

            // Update fields if provided
            if (isset($data['fields'])) {
                $this->updateFormFields($form, $data['fields']);
            }

            return $form->load(['fields', 'creator']);
        });
    }

    /**
     * Delete a form and all its data.
     */
    public function deleteForm(Form $form): bool
    {
        return DB::transaction(function () use ($form) {
            // Delete all submissions first
            $form->submissions()->delete();
            
            // Delete all fields
            $form->fields()->delete();
            
            // Delete the form
            return $form->delete();
        });
    }

    /**
     * Submit form data.
     */
    public function submitForm(Form $form, array $data, ?int $userId = null): FormSubmission
    {
        // Validate form data
        $this->validateFormSubmission($form, $data);

        return FormSubmission::create([
            'form_id' => $form->id,
            'submitted_by' => $userId,
            'submission_data' => $data,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'status' => 'pending',
            'submitted_at' => now(),
        ]);
    }

    /**
     * Get form submissions with pagination.
     */
    public function getFormSubmissions(Form $form, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $form->submissions()->with(['submitter']);

        // Apply filters
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('submitted_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('submitted_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('submitted_at', 'desc')->paginate($perPage);
    }

    /**
     * Create form fields.
     */
    private function createFormFields(Form $form, array $fieldsData): void
    {
        foreach ($fieldsData as $index => $fieldData) {
            FormField::create([
                'form_id' => $form->id,
                'field_key' => $fieldData['field_key'],
                'field_type' => $fieldData['field_type'],
                'label' => $fieldData['label'],
                'placeholder' => $fieldData['placeholder'] ?? null,
                'help_text' => $fieldData['help_text'] ?? null,
                'is_required' => $fieldData['is_required'] ?? false,
                'validation_rules' => $fieldData['validation_rules'] ?? [],
                'options' => $fieldData['options'] ?? [],
                'settings' => $fieldData['settings'] ?? [],
                'display_order' => $fieldData['display_order'] ?? $index,
                'is_active' => $fieldData['is_active'] ?? true,
            ]);
        }
    }

    /**
     * Update form fields.
     */
    private function updateFormFields(Form $form, array $fieldsData): void
    {
        // Delete existing fields
        $form->fields()->delete();
        
        // Create new fields
        $this->createFormFields($form, $fieldsData);
    }

    /**
     * Validate form submission data.
     */
    private function validateFormSubmission(Form $form, array $data): void
    {
        $rules = $form->getValidationRules();
        
        $validator = Validator::make($data, $rules);
        
        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }
    }

    /**
     * Generate unique slug for form.
     */
    private function generateUniqueSlug(string $name, ?int $excludeId = null): string
    {
        $baseSlug = \Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        $query = Form::where('slug', $slug);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        while ($query->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
            
            $query = Form::where('slug', $slug);
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }
        }

        return $slug;
    }

    /**
     * Get form by slug for public access.
     */
    public function getFormBySlug(string $slug): ?Form
    {
        return Form::where('slug', $slug)
            ->active()
            ->with(['fields' => function ($query) {
                $query->active()->orderBy('display_order');
            }])
            ->first();
    }

    /**
     * Export form submissions to CSV.
     */
    public function exportSubmissions(Form $form): string
    {
        $submissions = $form->submissions()->with(['submitter'])->get();
        $fields = $form->fields()->active()->orderBy('display_order')->get();
        
        $csvData = [];
        
        // Header row
        $headers = ['ID', 'Submitted At', 'Submitted By'];
        foreach ($fields as $field) {
            $headers[] = $field->label;
        }
        $csvData[] = $headers;
        
        // Data rows
        foreach ($submissions as $submission) {
            $row = [
                $submission->id,
                $submission->submitted_at->format('Y-m-d H:i:s'),
                $submission->submitter?->name ?? 'Anonymous',
            ];
            
            foreach ($fields as $field) {
                $value = $submission->getFieldValue($field->field_key);
                $row[] = is_array($value) ? implode(', ', $value) : $value;
            }
            
            $csvData[] = $row;
        }
        
        // Generate CSV content
        $output = fopen('php://temp', 'r+');
        foreach ($csvData as $row) {
            fputcsv($output, $row);
        }
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }
}