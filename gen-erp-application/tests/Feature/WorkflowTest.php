<?php

use App\Domain\Auth\Models\Company;
use App\Domain\Auth\Models\CompanyUser;
use App\Domain\Auth\Models\User;
use App\Domain\Workflow\Models\WorkflowDefinition;
use App\Domain\Workflow\Models\WorkflowStatus;
use App\Domain\Workflow\Models\WorkflowTransition;
use App\Domain\Workflow\Models\WorkflowInstance;
use App\Domain\Workflow\Models\WorkflowHistory;
use App\Domain\Workflow\Models\WorkflowApproval;
use App\Domain\Workflow\Services\WorkflowService;
use App\Services\CompanyContext;
use App\Support\Enums\ApprovalStatus;

beforeEach(function (): void {
    $this->company = Company::factory()->create();
    CompanyContext::setActive($this->company);
    
    $this->user = User::factory()->create();
    CompanyUser::factory()->create([
        'company_id' => $this->company->id,
        'user_id' => $this->user->id,
        'role' => 'manager',
        'is_active' => true,
    ]);
    
    $this->workflowService = app(WorkflowService::class);
});

test('workflow definition can be created with statuses and transitions', function (): void {
    $definition = WorkflowDefinition::create([
        'company_id' => $this->company->id,
        'name' => 'Purchase Order Approval',
        'document_type' => 'purchase_order',
        'is_active' => true,
        'is_default' => true,
    ]);

    // Create statuses
    $draft = WorkflowStatus::create([
        'company_id' => $this->company->id,
        'workflow_definition_id' => $definition->id,
        'key' => 'draft',
        'label' => 'Draft',
        'is_initial' => true,
        'is_terminal' => false,
    ]);

    $pending = WorkflowStatus::create([
        'company_id' => $this->company->id,
        'workflow_definition_id' => $definition->id,
        'key' => 'pending_approval',
        'label' => 'Pending Approval',
        'is_initial' => false,
        'is_terminal' => false,
    ]);

    $approved = WorkflowStatus::create([
        'company_id' => $this->company->id,
        'workflow_definition_id' => $definition->id,
        'key' => 'approved',
        'label' => 'Approved',
        'is_initial' => false,
        'is_terminal' => true,
    ]);

    // Create transition
    WorkflowTransition::create([
        'company_id' => $this->company->id,
        'workflow_definition_id' => $definition->id,
        'from_status_key' => 'draft',
        'to_status_key' => 'pending_approval',
        'label' => 'Submit for Approval',
        'allowed_roles' => ['manager', 'employee'],
        'requires_approval' => false,
    ]);

    expect($definition->statuses)->toHaveCount(3);
    expect($definition->transitions)->toHaveCount(1);
    expect($definition->initialStatus()->key)->toBe('draft');
});

test('workflow instance can be initialized for document', function (): void {
    // Create workflow definition
    $definition = WorkflowDefinition::create([
        'company_id' => $this->company->id,
        'name' => 'Purchase Order Approval',
        'document_type' => 'purchase_order',
        'is_active' => true,
        'is_default' => true,
    ]);

    WorkflowStatus::create([
        'company_id' => $this->company->id,
        'workflow_definition_id' => $definition->id,
        'key' => 'draft',
        'label' => 'Draft',
        'is_initial' => true,
        'is_terminal' => false,
    ]);

    $this->actingAs($this->user);

    $instance = $this->workflowService->initialise('purchase_order', 123);

    expect($instance->company_id)->toBe($this->company->id);
    expect($instance->document_type)->toBe('purchase_order');
    expect($instance->document_id)->toBe(123);
    expect($instance->current_status_key)->toBe('draft');
    expect($instance->started_at)->not->toBeNull();

    // Check history was created
    expect($instance->history)->toHaveCount(1);
    expect($instance->history->first()->to_status_key)->toBe('draft');
    expect($instance->history->first()->comment)->toBe('Workflow initialised.');
});

test('workflow transition executes successfully for allowed user', function (): void {
    // Setup workflow
    $definition = WorkflowDefinition::create([
        'company_id' => $this->company->id,
        'name' => 'Order Approval',
        'document_type' => 'sales_order',
        'is_active' => true,
        'is_default' => true,
    ]);

    WorkflowStatus::create([
        'company_id' => $this->company->id,
        'workflow_definition_id' => $definition->id,
        'key' => 'draft',
        'label' => 'Draft',
        'is_initial' => true,
        'is_terminal' => false,
    ]);

    WorkflowStatus::create([
        'company_id' => $this->company->id,
        'workflow_definition_id' => $definition->id,
        'key' => 'submitted',
        'label' => 'Submitted',
        'is_initial' => false,
        'is_terminal' => false,
    ]);

    $transition = WorkflowTransition::create([
        'company_id' => $this->company->id,
        'workflow_definition_id' => $definition->id,
        'from_status_key' => 'draft',
        'to_status_key' => 'submitted',
        'label' => 'Submit Order',
        'allowed_roles' => ['manager'],
        'requires_approval' => false,
    ]);

    $this->actingAs($this->user);

    $instance = $this->workflowService->initialise('sales_order', 456);
    $history = $this->workflowService->transition($instance, $transition, $this->user, 'Submitting order');

    $instance->refresh();
    expect($instance->current_status_key)->toBe('submitted');
    expect($history->from_status_key)->toBe('draft');
    expect($history->to_status_key)->toBe('submitted');
    expect($history->comment)->toBe('Submitting order');
});

test('workflow transition throws exception for unauthorized user', function (): void {
    // Setup workflow with restricted transition
    $definition = WorkflowDefinition::create([
        'company_id' => $this->company->id,
        'name' => 'Restricted Workflow',
        'document_type' => 'expense_claim',
        'is_active' => true,
        'is_default' => true,
    ]);

    WorkflowStatus::create([
        'company_id' => $this->company->id,
        'workflow_definition_id' => $definition->id,
        'key' => 'draft',
        'label' => 'Draft',
        'is_initial' => true,
        'is_terminal' => false,
    ]);

    WorkflowStatus::create([
        'company_id' => $this->company->id,
        'workflow_definition_id' => $definition->id,
        'key' => 'approved',
        'label' => 'Approved',
        'is_initial' => false,
        'is_terminal' => true,
    ]);

    $transition = WorkflowTransition::create([
        'company_id' => $this->company->id,
        'workflow_definition_id' => $definition->id,
        'from_status_key' => 'draft',
        'to_status_key' => 'approved',
        'label' => 'Approve',
        'allowed_roles' => ['admin'], // User has 'manager' role
        'requires_approval' => false,
    ]);

    $this->actingAs($this->user);

    $instance = $this->workflowService->initialise('expense_claim', 789);

    expect(fn () => $this->workflowService->transition($instance, $transition, $this->user))
        ->toThrow(RuntimeException::class, 'User does not have permission to execute this transition');
});

test('workflow approval process creates approval records', function (): void {
    // Create approver user
    $approver = User::factory()->create();
    CompanyUser::factory()->create([
        'company_id' => $this->company->id,
        'user_id' => $approver->id,
        'role' => 'admin',
        'is_active' => true,
    ]);

    // Setup workflow with approval
    $definition = WorkflowDefinition::create([
        'company_id' => $this->company->id,
        'name' => 'Approval Workflow',
        'document_type' => 'purchase_order',
        'is_active' => true,
        'is_default' => true,
    ]);

    WorkflowStatus::create([
        'company_id' => $this->company->id,
        'workflow_definition_id' => $definition->id,
        'key' => 'draft',
        'label' => 'Draft',
        'is_initial' => true,
        'is_terminal' => false,
    ]);

    WorkflowStatus::create([
        'company_id' => $this->company->id,
        'workflow_definition_id' => $definition->id,
        'key' => 'approved',
        'label' => 'Approved',
        'is_initial' => false,
        'is_terminal' => true,
    ]);

    $transition = WorkflowTransition::create([
        'company_id' => $this->company->id,
        'workflow_definition_id' => $definition->id,
        'from_status_key' => 'draft',
        'to_status_key' => 'approved',
        'label' => 'Request Approval',
        'allowed_roles' => ['manager'],
        'requires_approval' => true,
        'approver_roles' => ['admin'],
    ]);

    $this->actingAs($this->user);

    $instance = $this->workflowService->initialise('purchase_order', 101);
    $this->workflowService->transition($instance, $transition, $this->user, 'Please approve');

    // Check approval record was created
    $approval = WorkflowApproval::where('workflow_instance_id', $instance->id)->first();
    expect($approval)->not->toBeNull();
    expect($approval->approver_id)->toBe($approver->id);
    expect($approval->status)->toBe(ApprovalStatus::PENDING);

    // Instance should still be in draft status (pending approval)
    $instance->refresh();
    expect($instance->current_status_key)->toBe('draft');
});

test('workflow approval completion executes transition', function (): void {
    // Create approver
    $approver = User::factory()->create();
    CompanyUser::factory()->create([
        'company_id' => $this->company->id,
        'user_id' => $approver->id,
        'role' => 'admin',
        'is_active' => true,
    ]);

    // Setup workflow
    $definition = WorkflowDefinition::create([
        'company_id' => $this->company->id,
        'name' => 'Approval Test',
        'document_type' => 'purchase_order',
        'is_active' => true,
        'is_default' => true,
    ]);

    WorkflowStatus::create([
        'company_id' => $this->company->id,
        'workflow_definition_id' => $definition->id,
        'key' => 'draft',
        'label' => 'Draft',
        'is_initial' => true,
        'is_terminal' => false,
    ]);

    WorkflowStatus::create([
        'company_id' => $this->company->id,
        'workflow_definition_id' => $definition->id,
        'key' => 'approved',
        'label' => 'Approved',
        'is_initial' => false,
        'is_terminal' => true,
    ]);

    $transition = WorkflowTransition::create([
        'company_id' => $this->company->id,
        'workflow_definition_id' => $definition->id,
        'from_status_key' => 'draft',
        'to_status_key' => 'approved',
        'label' => 'Approve',
        'allowed_roles' => ['manager'],
        'requires_approval' => true,
        'approver_roles' => ['admin'],
    ]);

    $this->actingAs($this->user);

    // Initialize and request approval
    $instance = $this->workflowService->initialise('purchase_order', 202);
    $this->workflowService->transition($instance, $transition, $this->user);

    // Approve the request
    $approval = WorkflowApproval::where('workflow_instance_id', $instance->id)->first();
    $this->workflowService->respondToApproval($approval, ApprovalStatus::APPROVED, $approver, 'Looks good');

    // Check instance moved to approved status
    $instance->refresh();
    expect($instance->current_status_key)->toBe('approved');
    expect($instance->completed_at)->not->toBeNull();

    // Check approval record updated
    $approval->refresh();
    expect($approval->status)->toBe(ApprovalStatus::APPROVED);
    expect($approval->comment)->toBe('Looks good');
});

test('workflow approval rejection reverts to original status', function (): void {
    // Create approver
    $approver = User::factory()->create();
    CompanyUser::factory()->create([
        'company_id' => $this->company->id,
        'user_id' => $approver->id,
        'role' => 'admin',
        'is_active' => true,
    ]);

    // Setup workflow
    $definition = WorkflowDefinition::create([
        'company_id' => $this->company->id,
        'name' => 'Rejection Test',
        'document_type' => 'expense_claim',
        'is_active' => true,
        'is_default' => true,
    ]);

    WorkflowStatus::create([
        'company_id' => $this->company->id,
        'workflow_definition_id' => $definition->id,
        'key' => 'draft',
        'label' => 'Draft',
        'is_initial' => true,
        'is_terminal' => false,
    ]);

    WorkflowStatus::create([
        'company_id' => $this->company->id,
        'workflow_definition_id' => $definition->id,
        'key' => 'approved',
        'label' => 'Approved',
        'is_initial' => false,
        'is_terminal' => true,
    ]);

    $transition = WorkflowTransition::create([
        'company_id' => $this->company->id,
        'workflow_definition_id' => $definition->id,
        'from_status_key' => 'draft',
        'to_status_key' => 'approved',
        'label' => 'Request Approval',
        'allowed_roles' => ['manager'],
        'requires_approval' => true,
        'approver_roles' => ['admin'],
    ]);

    $this->actingAs($this->user);

    // Initialize and request approval
    $instance = $this->workflowService->initialise('expense_claim', 303);
    $this->workflowService->transition($instance, $transition, $this->user);

    // Reject the request
    $approval = WorkflowApproval::where('workflow_instance_id', $instance->id)->first();
    $this->workflowService->respondToApproval($approval, ApprovalStatus::REJECTED, $approver, 'Needs more details');

    // Check instance reverted to draft status
    $instance->refresh();
    expect($instance->current_status_key)->toBe('draft');
    expect($instance->completed_at)->toBeNull();

    // Check approval record updated
    $approval->refresh();
    expect($approval->status)->toBe(ApprovalStatus::REJECTED);
    expect($approval->comment)->toBe('Needs more details');
});

test('workflow service returns current status for document', function (): void {
    // Setup workflow
    $definition = WorkflowDefinition::create([
        'company_id' => $this->company->id,
        'name' => 'Status Test',
        'document_type' => 'sales_order',
        'is_active' => true,
        'is_default' => true,
    ]);

    $status = WorkflowStatus::create([
        'company_id' => $this->company->id,
        'workflow_definition_id' => $definition->id,
        'key' => 'processing',
        'label' => 'Processing',
        'is_initial' => true,
        'is_terminal' => false,
    ]);

    $this->actingAs($this->user);

    $instance = $this->workflowService->initialise('sales_order', 404);

    $currentStatus = $this->workflowService->currentStatus('sales_order', 404);
    expect($currentStatus->key)->toBe('processing');
    expect($currentStatus->label)->toBe('Processing');

    // Test non-existent document
    $noStatus = $this->workflowService->currentStatus('sales_order', 999);
    expect($noStatus)->toBeNull();
});

test('Company A workflow instances not visible to Company B', function (): void {
    $companyB = Company::factory()->create();
    
    // Create workflow for Company A
    $definitionA = WorkflowDefinition::create([
        'company_id' => $this->company->id,
        'name' => 'Company A Workflow',
        'document_type' => 'purchase_order',
        'is_active' => true,
        'is_default' => true,
    ]);

    WorkflowStatus::create([
        'company_id' => $this->company->id,
        'workflow_definition_id' => $definitionA->id,
        'key' => 'draft',
        'label' => 'Draft',
        'is_initial' => true,
        'is_terminal' => false,
    ]);

    $this->actingAs($this->user);
    $instanceA = $this->workflowService->initialise('purchase_order', 505);

    // Switch to Company B
    CompanyContext::setActive($companyB);

    // Company B should not see Company A's workflow instances
    expect(WorkflowInstance::all())->toHaveCount(0);
    expect(WorkflowDefinition::all())->toHaveCount(0);
    expect(WorkflowHistory::all())->toHaveCount(0);

    // Verify data exists without global scopes
    expect(WorkflowInstance::withoutGlobalScopes()->count())->toBe(1);
    expect(WorkflowDefinition::withoutGlobalScopes()->count())->toBe(1);
    expect(WorkflowHistory::withoutGlobalScopes()->count())->toBe(1);
});