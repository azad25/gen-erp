<?php

namespace Tests\Unit;

use App\Domain\Document\Models\Form;
use App\Domain\Document\Models\FormField;
use App\Domain\Document\Models\FormSubmission;
use App\Domain\Document\Services\FormService;
use App\Domain\Auth\Models\Company;
use App\Domain\Auth\Models\User;
use App\Services\CompanyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormServiceTest extends TestCase
{
    use RefreshDatabase;

    private FormService $formService;
    private Company $company;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->formService = app(FormService::class);
        $this->company = Company::factory()->create();
        $this->user = User::factory()->create();
        
        CompanyContext::setActive($this->company);
        $this->actingAs($this->user);
    }

    public function test_can_create_form_with_basic_data(): void
    {
        $formData = [
            'name' => 'Contact Form',
            'description' => 'A simple contact form',
            'is_public' => true,
            'is_active' => true,
        ];

        $form = $this->formService->createForm($formData);

        $this->assertInstanceOf(Form::class, $form);
        $this->assertEquals('Contact Form', $form->name);
        $this->assertEquals('A simple contact form', $form->description);
        $this->assertTrue($form->is_public);
        $this->assertTrue($form->is_active);
        $this->assertEquals($this->company->id, $form->company_id);
        $this->assertEquals($this->user->id, $form->created_by);
        $this->assertNotEmpty($form->slug);
    }

    public function test_can_create_form_with_fields(): void
    {
        $formData = [
            'name' => 'Registration Form',
            'description' => 'User registration form',
            'fields' => [
                [
                    'field_key' => 'name',
                    'field_type' => 'text',
                    'label' => 'Full Name',
                    'is_required' => true,
                    'display_order' => 0,
                ],
                [
                    'field_key' => 'email',
                    'field_type' => 'email',
                    'label' => 'Email Address',
                    'is_required' => true,
                    'display_order' => 1,
                ],
            ],
        ];

        $form = $this->formService->createForm($formData);

        $this->assertCount(2, $form->fields);
        $this->assertEquals('name', $form->fields->first()->field_key);
        $this->assertEquals('email', $form->fields->last()->field_key);
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

        $updatedForm = $this->formService->updateForm($form, $updateData);

        $this->assertEquals('Updated Form Name', $updatedForm->name);
        $this->assertEquals('Updated description', $updatedForm->description);
        $this->assertFalse($updatedForm->is_public);
    }

    public function test_can_delete_form(): void
    {
        $form = Form::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        // Create some fields and submissions
        FormField::factory()->create(['form_id' => $form->id]);
        FormSubmission::factory()->create(['form_id' => $form->id]);

        $result = $this->formService->deleteForm($form);

        $this->assertTrue($result);
        $this->assertSoftDeleted('forms', ['id' => $form->id]);
    }

    public function test_can_submit_form(): void
    {
        $form = Form::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        FormField::factory()->text()->create([
            'form_id' => $form->id,
            'field_key' => 'name',
            'field_type' => 'text',
            'is_required' => true,
        ]);

        $submissionData = [
            'name' => 'John Doe',
        ];

        $submission = $this->formService->submitForm($form, $submissionData, $this->user->id);

        $this->assertInstanceOf(FormSubmission::class, $submission);
        $this->assertEquals($form->id, $submission->form_id);
        $this->assertEquals($this->user->id, $submission->submitted_by);
        $this->assertEquals($submissionData, $submission->submission_data);
        $this->assertEquals('pending', $submission->status);
    }

    public function test_generates_unique_slug(): void
    {
        // Create first form
        $form1 = $this->formService->createForm(['name' => 'Test Form']);
        
        // Create second form with same name
        $form2 = $this->formService->createForm(['name' => 'Test Form']);

        $this->assertEquals('test-form', $form1->slug);
        $this->assertEquals('test-form-1', $form2->slug);
        $this->assertNotEquals($form1->slug, $form2->slug);
    }

    public function test_can_get_forms_with_pagination(): void
    {
        // Create multiple forms
        Form::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        $result = $this->formService->getForms([], 3);

        $this->assertEquals(3, $result->perPage());
        $this->assertEquals(5, $result->total());
        $this->assertCount(3, $result->items());
    }

    public function test_can_filter_forms(): void
    {
        Form::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Contact Form',
            'is_public' => true,
        ]);

        Form::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Internal Form',
            'is_public' => false,
        ]);

        // Test search filter
        $result = $this->formService->getForms(['search' => 'Contact']);
        $this->assertEquals(1, $result->total());

        // Test public filter
        $result = $this->formService->getForms(['is_public' => true]);
        $this->assertEquals(1, $result->total());
    }

    public function test_can_get_form_by_slug(): void
    {
        $form = Form::factory()->create([
            'company_id' => $this->company->id,
            'slug' => 'test-form',
            'is_active' => true,
        ]);

        FormField::factory()->create([
            'form_id' => $form->id,
            'is_active' => true,
        ]);

        $foundForm = $this->formService->getFormBySlug('test-form');

        $this->assertNotNull($foundForm);
        $this->assertEquals($form->id, $foundForm->id);
        $this->assertCount(1, $foundForm->fields);
    }
}