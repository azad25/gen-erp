<?php

namespace App\Http\Controllers\Document;

use App\Domain\Document\Models\Form;
use App\Domain\Document\Services\FormService;
use App\Http\Controllers\Controller;
use App\Support\Enums\FormFieldType;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controller for managing forms in the Document domain.
 */
class FormController extends Controller
{
    public function __construct(
        private FormService $formService
    ) {}

    /**
     * Display a listing of forms.
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'is_public', 'is_active']);
        $forms = $this->formService->getForms($filters, $request->integer('per_page', 15));

        return Inertia::render('Documents/Forms/Index', [
            'forms' => $forms,
            'filters' => $filters,
        ]);
    }

    /**
     * Show the form builder.
     */
    public function builder(): Response
    {
        return Inertia::render('Documents/Forms/Builder', [
            'fieldTypes' => FormFieldType::groupedOptions(),
            'form' => null,
        ]);
    }

    /**
     * Show the form builder.
     */
    public function create(): Response
    {
        return Inertia::render('Documents/Forms/Builder', [
            'fieldTypes' => FormFieldType::groupedOptions(),
            'form' => null,
        ]);
    }

    /**
     * Store a newly created form.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_public' => ['boolean'],
            'is_active' => ['boolean'],
            'settings' => ['nullable', 'array'],
            'fields' => ['nullable', 'array'],
            'fields.*.field_key' => ['required', 'string', 'max:100'],
            'fields.*.field_type' => ['required', 'string'],
            'fields.*.label' => ['required', 'string', 'max:255'],
            'fields.*.placeholder' => ['nullable', 'string', 'max:255'],
            'fields.*.help_text' => ['nullable', 'string', 'max:1000'],
            'fields.*.is_required' => ['boolean'],
            'fields.*.validation_rules' => ['nullable', 'array'],
            'fields.*.options' => ['nullable', 'array'],
            'fields.*.settings' => ['nullable', 'array'],
            'fields.*.display_order' => ['integer', 'min:0'],
            'fields.*.is_active' => ['boolean'],
        ]);

        $form = $this->formService->createForm($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('Form created successfully.'),
                'data' => $form->load(['fields', 'creator']),
            ], 201);
        }

        return redirect()->route('documents.forms.show', $form)
            ->with('success', __('Form created successfully.'));
    }

    /**
     * Display the specified form.
     */
    public function show(Request $request, Form $form)
    {
        $form->load(['fields' => function ($query) {
            $query->active()->orderBy('display_order');
        }, 'creator']);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $form,
            ]);
        }

        return Inertia::render('Documents/Forms/Show', [
            'form' => $form,
            'submissionsCount' => $form->submissions()->count(),
        ]);
    }

    /**
     * Show the form builder for editing.
     */
    public function edit(Form $form): Response
    {
        $form->load(['fields' => function ($query) {
            $query->orderBy('display_order');
        }]);

        return Inertia::render('Documents/Forms/Builder', [
            'fieldTypes' => FormFieldType::groupedOptions(),
            'form' => $form,
        ]);
    }

    /**
     * Update the specified form.
     */
    public function update(Request $request, Form $form)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_public' => ['boolean'],
            'is_active' => ['boolean'],
            'settings' => ['nullable', 'array'],
            'fields' => ['nullable', 'array'],
            'fields.*.field_key' => ['required', 'string', 'max:100'],
            'fields.*.field_type' => ['required', 'string'],
            'fields.*.label' => ['required', 'string', 'max:255'],
            'fields.*.placeholder' => ['nullable', 'string', 'max:255'],
            'fields.*.help_text' => ['nullable', 'string', 'max:1000'],
            'fields.*.is_required' => ['boolean'],
            'fields.*.validation_rules' => ['nullable', 'array'],
            'fields.*.options' => ['nullable', 'array'],
            'fields.*.settings' => ['nullable', 'array'],
            'fields.*.display_order' => ['integer', 'min:0'],
            'fields.*.is_active' => ['boolean'],
        ]);

        $form = $this->formService->updateForm($form, $validated);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('Form updated successfully.'),
                'data' => $form,
            ]);
        }

        return redirect()->route('documents.forms.show', $form)
            ->with('success', __('Form updated successfully.'));
    }

    /**
     * Remove the specified form.
     */
    public function destroy(Request $request, Form $form)
    {
        $this->formService->deleteForm($form);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('Form deleted successfully.'),
            ]);
        }

        return redirect()->route('documents.forms.index')
            ->with('success', __('Form deleted successfully.'));
    }

    /**
     * Submit form data (authenticated users).
     */
    public function submit(Request $request, Form $form)
    {
        // Get form validation rules
        $rules = $form->getValidationRules();
        $validated = $request->validate($rules);

        // Submit the form
        $submission = $this->formService->submitForm(
            $form,
            $validated,
            auth()->id()
        );

        return response()->json([
            'success' => true,
            'message' => __('Form submitted successfully.'),
            'data' => $submission,
        ], 201);
    }

    /**
     * Show form submissions.
     */
    public function submissions(Request $request, Form $form)
    {
        $filters = $request->only(['status', 'date_from', 'date_to']);
        $submissions = $this->formService->getFormSubmissions($form, $filters, $request->integer('per_page', 15));

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $submissions,
            ]);
        }

        return Inertia::render('Documents/Forms/Submissions', [
            'form' => $form,
            'submissions' => $submissions,
            'filters' => $filters,
        ]);
    }

    /**
     * Export form submissions.
     */
    public function exportSubmissions(Form $form)
    {
        $csv = $this->formService->exportSubmissions($form);
        $filename = "form-{$form->slug}-submissions-" . now()->format('Y-m-d') . '.csv';

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Public form display (for form filling).
     */
    public function publicForm(Request $request, string $slug)
    {
        $form = $this->formService->getFormBySlug($slug);

        if (!$form) {
            abort(404, 'Form not found');
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $form,
            ]);
        }

        return Inertia::render('Public/FormFill', [
            'form' => $form,
        ]);
    }

    /**
     * Handle public form submission.
     */
    public function submitPublicForm(Request $request, string $slug)
    {
        $form = $this->formService->getFormBySlug($slug);

        if (!$form) {
            abort(404, 'Form not found');
        }

        // Get form validation rules
        $rules = $form->getValidationRules();
        $validated = $request->validate($rules);

        // Submit the form
        $submission = $this->formService->submitForm(
            $form,
            $validated,
            auth()->id()
        );

        $settings = $form->getFormSettings();

        // Redirect based on form settings
        if ($settings['redirect_url']) {
            return redirect($settings['redirect_url'])
                ->with('success', $settings['success_message']);
        }

        return back()->with('success', $settings['success_message']);
    }

    /**
     * Duplicate a form.
     */
    public function duplicate(Form $form)
    {
        $formData = [
            'name' => $form->name . ' (Copy)',
            'description' => $form->description,
            'is_public' => false, // Always make copies private initially
            'is_active' => false, // Always make copies inactive initially
            'settings' => $form->settings,
            'fields' => $form->fields->map(function ($field) {
                return [
                    'field_key' => $field->field_key . '_copy',
                    'field_type' => $field->field_type,
                    'label' => $field->label,
                    'placeholder' => $field->placeholder,
                    'help_text' => $field->help_text,
                    'is_required' => $field->is_required,
                    'validation_rules' => $field->validation_rules,
                    'options' => $field->options,
                    'settings' => $field->settings,
                    'display_order' => $field->display_order,
                    'is_active' => $field->is_active,
                ];
            })->toArray(),
        ];

        $newForm = $this->formService->createForm($formData);

        return redirect()->route('documents.forms.edit', $newForm)
            ->with('success', __('Form duplicated successfully.'));
    }
}