<?php

namespace Tests\Feature;

use App\Domain\Document\Models\Form;
use App\Domain\Document\Models\FormField;
use App\Domain\Auth\Models\Company;
use App\Domain\Auth\Models\CompanyUser;
use App\Domain\Auth\Models\User;
use App\Services\CompanyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormControllerTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->company = Company::factory()->create();
        $this->user = User::factory()->create();
        
        CompanyUser::factory()->owner()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
        ]);
        
        CompanyContext::setActive($this->company);
        $this->actingAs($this->user);
    }

    public function test_can_view_forms_index(): void
    {
        Form::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->get(route('documents.forms.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Documents/Forms/Index')
                ->has('forms.data', 3)
        );
    }

    public function test_can_view_form_builder(): void
    {
        $response = $this->get(route('documents.forms.builder'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Documents/Forms/Builder')
        );
    }

    public function test_can_create_form(): void
    {
        $formData = [
            'name' => 'Test Form',
            'description' => 'A test form',
            'is_public' => true,
            'is_active' => true,
            'settings' => ['success_message' => 'Thank you!'],
            'fields' => [
                [
                    'field_key' => 'name',
                    'field_type' => 'text',
                    'label' => 'Full Name',
                    'is_required' => true,
                    'display_order' => 0,
                ],
            ],
        ];

        $response = $this->postJson(route('documents.forms.store'), $formData);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'name',
                'description',
                'slug',
                'is_public',
                'is_active',
                'fields',
            ],
        ]);

        $this->assertDatabaseHas('forms', [
            'name' => 'Test Form',
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertDatabaseHas('form_fields', [
            'field_key' => 'name',
            'label' => 'Full Name',
        ]);
    }

    public function test_can_show_form(): void
    {
        $form = Form::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        FormField::factory()->create([
            'form_id' => $form->id,
        ]);

        $response = $this->getJson(route('documents.forms.show', $form));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'name',
                'fields',
                'creator',
            ],
        ]);
    }

    public function test_can_update_form(): void
    {
        $form = Form::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        $updateData = [
            'name' => 'Updated Form Name',
            'description' => 'Updated description',
            'is_public' => false,
        ];

        $response = $this->putJson(route('documents.forms.update', $form), $updateData);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data',
        ]);

        $this->assertDatabaseHas('forms', [
            'id' => $form->id,
            'name' => 'Updated Form Name',
            'description' => 'Updated description',
            'is_public' => false,
        ]);
    }

    public function test_can_delete_form(): void
    {
        $form = Form::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->deleteJson(route('documents.forms.destroy', $form));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Form deleted successfully.',
        ]);

        $this->assertSoftDeleted('forms', [
            'id' => $form->id,
        ]);
    }

    public function test_can_submit_form(): void
    {
        $form = Form::factory()->create([
            'company_id' => $this->company->id,
            'is_public' => true,
            'is_active' => true,
        ]);

        FormField::factory()->text()->create([
            'form_id' => $form->id,
            'field_key' => 'name',
            'label' => 'Full Name',
            'is_required' => true,
        ]);

        $submissionData = [
            'name' => 'John Doe',
        ];

        $response = $this->postJson(route('documents.forms.submit', $form), $submissionData);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'form_id',
                'submission_data',
                'status',
            ],
        ]);

        $this->assertDatabaseHas('form_submissions', [
            'form_id' => $form->id,
            'status' => 'pending',
        ]);
        
        $submission = $form->submissions()->first();
        $this->assertEquals($submissionData, $submission->submission_data);
    }

    public function test_can_get_form_submissions(): void
    {
        $form = Form::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        // Create some submissions
        $form->submissions()->createMany([
            [
                'submission_data' => ['name' => 'John Doe'],
                'status' => 'pending',
                'submitted_at' => now(),
            ],
            [
                'submission_data' => ['name' => 'Jane Smith'],
                'status' => 'processed',
                'submitted_at' => now(),
            ],
        ]);

        $response = $this->getJson(route('documents.forms.submissions', $form));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'data' => [
                    '*' => [
                        'id',
                        'submission_data',
                        'status',
                        'submitted_at',
                    ],
                ],
                'current_page',
                'total',
            ],
        ]);
    }

    public function test_cannot_access_other_company_forms(): void
    {
        $otherCompany = Company::factory()->create();
        $otherForm = Form::factory()->create([
            'company_id' => $otherCompany->id,
        ]);

        $response = $this->get(route('documents.forms.show', $otherForm));

        $response->assertStatus(404);
    }

    public function test_form_validation_on_create(): void
    {
        $response = $this->postJson(route('documents.forms.store'), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    public function test_can_export_form_submissions(): void
    {
        $form = Form::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        FormField::factory()->create([
            'form_id' => $form->id,
            'field_key' => 'name',
            'label' => 'Full Name',
        ]);

        $form->submissions()->create([
            'submission_data' => ['name' => 'John Doe'],
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        $response = $this->get(route('documents.forms.export', $form));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $response->assertHeader('content-disposition', 'attachment; filename="form-' . $form->slug . '-submissions-' . now()->format('Y-m-d') . '.csv"');
    }

    public function test_can_get_public_form_by_slug(): void
    {
        $form = Form::factory()->create([
            'company_id' => $this->company->id,
            'slug' => 'public-form',
            'is_public' => true,
            'is_active' => true,
        ]);

        FormField::factory()->create([
            'form_id' => $form->id,
            'is_active' => true,
        ]);

        $response = $this->getJson(route('documents.forms.public', 'public-form'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'name',
                'description',
                'fields',
            ],
        ]);
    }
}