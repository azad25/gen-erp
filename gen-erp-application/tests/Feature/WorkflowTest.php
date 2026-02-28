<?php

use App\Enums\ApprovalStatus;
use App\Enums\CompanyRole;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\User;
use App\Models\WorkflowApproval;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowHistory;
use App\Models\WorkflowInstance;
use App\Models\WorkflowStatus;
use App\Models\WorkflowTransition;
use App\Services\BusinessTypeTemplateService;
use App\Services\CompanyContext;
use App\Services\WorkflowService;

// Helper: create a minimal workflow with Draft → Approved → Closed
function createTestWorkflow(Company $company): WorkflowDefinition
{
    $def = WorkflowDefinition::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'document_type' => 'purchase_order',
        'name' => 'Test PO Workflow',
        'is_active' => true,
        'is_default' => true,
    ]);

    WorkflowStatus::withoutGlobalScopes()->create([
        'workflow_definition_id' => $def->id,
        'company_id' => $company->id,
        'key' => 'draft',
        'label' => 'Draft',
        'color' => 'gray',
        'is_initial' => true,
        'display_order' => 0,
    ]);

    WorkflowStatus::withoutGlobalScopes()->create([
        'workflow_definition_id' => $def->id,
        'company_id' => $company->id,
        'key' => 'approved',
        'label' => 'Approved',
        'color' => 'success',
        'display_order' => 1,
    ]);

    WorkflowStatus::withoutGlobalScopes()->create([
        'workflow_definition_id' => $def->id,
        'company_id' => $company->id,
        'key' => 'closed',
        'label' => 'Closed',
        'color' => 'gray',
        'is_terminal' => true,
        'display_order' => 2,
    ]);

    WorkflowTransition::withoutGlobalScopes()->create([
        'workflow_definition_id' => $def->id,
        'company_id' => $company->id,
        'from_status_key' => 'draft',
        'to_status_key' => 'approved',
        'label' => 'Approve',
        'allowed_roles' => ['owner', 'admin'],
        'display_order' => 0,
    ]);

    WorkflowTransition::withoutGlobalScopes()->create([
        'workflow_definition_id' => $def->id,
        'company_id' => $company->id,
        'from_status_key' => 'approved',
        'to_status_key' => 'closed',
        'label' => 'Close',
        'allowed_roles' => ['owner'],
        'display_order' => 1,
    ]);

    return $def;
}

// ── 1. initialise() creates instance with initial status ──

test('WorkflowService initialise creates instance with initial status', function (): void {
    $company = Company::factory()->create();
    $user = User::factory()->create();
    CompanyUser::factory()->owner()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
    ]);
    CompanyContext::setActive($company);
    $this->actingAs($user);

    createTestWorkflow($company);

    $service = app(WorkflowService::class);
    $instance = $service->initialise('purchase_order', 1);

    expect($instance)->toBeInstanceOf(WorkflowInstance::class);
    expect($instance->current_status_key)->toBe('draft');
    expect($instance->history)->toHaveCount(1);
});

// ── 2. User with allowed role can execute transition ──

test('user with allowed role can execute a transition', function (): void {
    $company = Company::factory()->create();
    $user = User::factory()->create();
    CompanyUser::factory()->owner()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
    ]);
    CompanyContext::setActive($company);
    $this->actingAs($user);

    $def = createTestWorkflow($company);
    $service = app(WorkflowService::class);
    $instance = $service->initialise('purchase_order', 1);

    $transition = $def->transitions()->where('from_status_key', 'draft')->first();
    $history = $service->transition($instance, $transition, $user, 'Approving now.');

    expect($history)->toBeInstanceOf(WorkflowHistory::class);
    expect($instance->fresh()->current_status_key)->toBe('approved');
});

// ── 3. User without allowed role is rejected ──

test('user without allowed role is rejected', function (): void {
    $company = Company::factory()->create();
    $user = User::factory()->create();
    CompanyUser::factory()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
        'role' => CompanyRole::VIEWER->value,
    ]);
    CompanyContext::setActive($company);
    $this->actingAs($user);

    $def = createTestWorkflow($company);
    $service = app(WorkflowService::class);
    $instance = $service->initialise('purchase_order', 1);

    $transition = $def->transitions()->where('from_status_key', 'draft')->first();

    $this->expectException(RuntimeException::class);
    $service->transition($instance, $transition, $user);
});

// ── 4. Transition with requires_approval creates approval records ──

test('transition with requires_approval creates WorkflowApproval records', function (): void {
    $company = Company::factory()->create();
    $owner = User::factory()->create();
    $admin = User::factory()->create();
    CompanyUser::factory()->owner()->create([
        'company_id' => $company->id,
        'user_id' => $owner->id,
    ]);
    CompanyUser::factory()->create([
        'company_id' => $company->id,
        'user_id' => $admin->id,
        'role' => CompanyRole::ADMIN->value,
        'is_active' => true,
    ]);
    CompanyContext::setActive($company);
    $this->actingAs($owner);

    $def = createTestWorkflow($company);

    // Make the draft→approved transition require approval
    $transition = $def->transitions()->where('from_status_key', 'draft')->first();
    $transition->update([
        'requires_approval' => true,
        'approval_type' => 'parallel',
        'approver_roles' => ['admin'],
    ]);

    $service = app(WorkflowService::class);
    $instance = $service->initialise('purchase_order', 1);

    $service->transition($instance, $transition->fresh(), $owner);

    // Status should NOT have changed (waiting for approval)
    expect($instance->fresh()->current_status_key)->toBe('draft');

    // Approval records should exist
    $approvals = WorkflowApproval::withoutGlobalScopes()
        ->where('workflow_instance_id', $instance->id)
        ->get();
    expect($approvals)->toHaveCount(1);
    expect($approvals->first()->status)->toBe(ApprovalStatus::PENDING);
});

// ── 5. All approvers approving triggers transition ──

test('all approvers approving triggers the transition', function (): void {
    $company = Company::factory()->create();
    $owner = User::factory()->create();
    $admin = User::factory()->create();
    CompanyUser::factory()->owner()->create([
        'company_id' => $company->id,
        'user_id' => $owner->id,
    ]);
    CompanyUser::factory()->create([
        'company_id' => $company->id,
        'user_id' => $admin->id,
        'role' => CompanyRole::ADMIN->value,
        'is_active' => true,
    ]);
    CompanyContext::setActive($company);
    $this->actingAs($owner);

    $def = createTestWorkflow($company);
    $transition = $def->transitions()->where('from_status_key', 'draft')->first();
    $transition->update([
        'requires_approval' => true,
        'approval_type' => 'parallel',
        'approver_roles' => ['admin'],
    ]);

    $service = app(WorkflowService::class);
    $instance = $service->initialise('purchase_order', 1);

    $service->transition($instance, $transition->fresh(), $owner);

    // Approve
    $approval = WorkflowApproval::withoutGlobalScopes()
        ->where('workflow_instance_id', $instance->id)
        ->first();
    $service->respondToApproval($approval, ApprovalStatus::APPROVED, $admin);

    expect($instance->fresh()->current_status_key)->toBe('approved');
});

// ── 6. Rejection on parallel approval rejects transition ──

test('one approver rejecting rejects the transition', function (): void {
    $company = Company::factory()->create();
    $owner = User::factory()->create();
    $admin = User::factory()->create();
    CompanyUser::factory()->owner()->create([
        'company_id' => $company->id,
        'user_id' => $owner->id,
    ]);
    CompanyUser::factory()->create([
        'company_id' => $company->id,
        'user_id' => $admin->id,
        'role' => CompanyRole::ADMIN->value,
        'is_active' => true,
    ]);
    CompanyContext::setActive($company);
    $this->actingAs($owner);

    $def = createTestWorkflow($company);
    $transition = $def->transitions()->where('from_status_key', 'draft')->first();
    $transition->update([
        'requires_approval' => true,
        'approval_type' => 'parallel',
        'approver_roles' => ['admin'],
    ]);

    $service = app(WorkflowService::class);
    $instance = $service->initialise('purchase_order', 1);

    $service->transition($instance, $transition->fresh(), $owner);

    // Reject
    $approval = WorkflowApproval::withoutGlobalScopes()
        ->where('workflow_instance_id', $instance->id)
        ->first();
    $service->respondToApproval($approval, ApprovalStatus::REJECTED, $admin, 'Not approved.');

    // Should revert to from_status
    expect($instance->fresh()->current_status_key)->toBe('draft');
});

// ── 7. WorkflowHistory is immutable ──

test('WorkflowHistory is immutable and cannot be updated', function (): void {
    $company = Company::factory()->create();
    $user = User::factory()->create();
    CompanyUser::factory()->owner()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
    ]);
    CompanyContext::setActive($company);
    $this->actingAs($user);

    createTestWorkflow($company);

    $service = app(WorkflowService::class);
    $instance = $service->initialise('purchase_order', 1);

    $history = $instance->history()->first();

    $this->expectException(LogicException::class);
    $history->comment = 'Modified';
    $history->save();
});

// ── 8. Tenant isolation on workflow definitions ──

test('two companies have separate workflow definitions', function (): void {
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    createTestWorkflow($companyA);

    WorkflowDefinition::withoutGlobalScopes()->create([
        'company_id' => $companyB->id,
        'document_type' => 'purchase_order',
        'name' => 'Company B PO',
        'is_active' => true,
        'is_default' => true,
    ]);

    CompanyContext::setActive($companyA);
    expect(WorkflowDefinition::all())->toHaveCount(1);
    expect(WorkflowDefinition::first()->name)->toBe('Test PO Workflow');

    CompanyContext::setActive($companyB);
    expect(WorkflowDefinition::all())->toHaveCount(1);
    expect(WorkflowDefinition::first()->name)->toBe('Company B PO');
});

// ── 9. Terminal status has no available transitions ──

test('document in terminal status has no available transitions', function (): void {
    $company = Company::factory()->create();
    $user = User::factory()->create();
    CompanyUser::factory()->owner()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
    ]);
    CompanyContext::setActive($company);
    $this->actingAs($user);

    $def = createTestWorkflow($company);
    $service = app(WorkflowService::class);
    $instance = $service->initialise('purchase_order', 1);

    // Transition draft → approved → closed
    $t1 = $def->transitions()->where('from_status_key', 'draft')->first();
    $service->transition($instance, $t1, $user);

    $t2 = $def->transitions()->where('from_status_key', 'approved')->first();
    $service->transition($instance->fresh(), $t2, $user);

    expect($instance->fresh()->current_status_key)->toBe('closed');

    $available = $service->availableTransitions($instance->fresh(), $user);
    expect($available)->toHaveCount(0);
});

// ── 10. Pre-built templates applied for pharmacy ──

test('pre-built templates are applied correctly for pharmacy business type', function (): void {
    $company = Company::factory()->create([
        'business_type' => 'pharmacy',
    ]);
    CompanyContext::setActive($company);

    $templateService = app(BusinessTypeTemplateService::class);
    $templateService->apply($company);

    // Pharmacy gets "standard" complexity → Standard PO Approval workflow
    $definitions = WorkflowDefinition::all();
    expect($definitions->count())->toBeGreaterThanOrEqual(1);

    $poDef = WorkflowDefinition::where('document_type', 'purchase_order')->first();
    expect($poDef)->not->toBeNull();
    expect($poDef->name)->toBe('Standard PO Approval');
    expect($poDef->is_default)->toBeTrue();

    // Should have 6 statuses for standard PO
    $statuses = $poDef->statuses;
    expect($statuses)->toHaveCount(6);

    // Initial status should be draft
    $initial = $poDef->initialStatus();
    expect($initial->key)->toBe('draft');
});
