<?php

namespace App\Services;

use App\Enums\BusinessType;
use App\Models\AlertRule;
use App\Models\Company;
use App\Models\EntityAlias;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowStatus;
use App\Models\WorkflowTransition;
use Database\Seeders\DefaultUnitsSeeder;

/**
 * Applies business-type-specific defaults (entity aliases, settings, workflows) to a newly created company.
 */
class BusinessTypeTemplateService
{
    /**
     * Static mapping of business-type-specific entity aliases.
     *
     * @return array<string, array<string, string>>
     */
    public static function aliasDefaults(): array
    {
        return [
            BusinessType::PHARMACY->value => [
                'customer' => 'Patient',
                'product' => 'Medicine',
                'supplier' => 'Distributor',
            ],
            BusinessType::SCHOOL->value => [
                'customer' => 'Student',
                'product' => 'Fee Head',
                'supplier' => 'Vendor',
            ],
            BusinessType::RMG->value => [
                'customer' => 'Buyer',
                'order' => 'Work Order',
                'product' => 'Style',
            ],
            BusinessType::RESTAURANT->value => [
                'product' => 'Menu Item',
                'purchase_order' => 'Requisition',
            ],
            BusinessType::NGO->value => [
                'customer' => 'Beneficiary',
                'invoice' => 'Grant Receipt',
            ],
        ];
    }

    /**
     * Apply all business type defaults to the given company.
     */
    public function apply(Company $company): void
    {
        $this->applyEntityAliases($company);
        $this->applyDefaultSettings($company);
        $this->applyDefaultWorkflows($company);
        $this->applyDefaultAlertRules($company);
        (new DefaultUnitsSeeder)->seedForCompany($company);
    }

    /**
     * Create EntityAlias records based on the company's business type.
     */
    private function applyEntityAliases(Company $company): void
    {
        $businessType = $company->business_type instanceof BusinessType
            ? $company->business_type->value
            : $company->business_type;

        $aliases = self::aliasDefaults()[$businessType] ?? [];

        foreach ($aliases as $entityKey => $alias) {
            EntityAlias::withoutGlobalScopes()->updateOrCreate(
                [
                    'company_id' => $company->id,
                    'entity_key' => $entityKey,
                ],
                [
                    'alias' => $alias,
                ]
            );
        }
    }

    /**
     * Set default company settings JSON based on business type.
     */
    private function applyDefaultSettings(Company $company): void
    {
        $businessType = $company->business_type instanceof BusinessType
            ? $company->business_type
            : BusinessType::from($company->business_type);

        $settings = [
            'simplified_mode' => $businessType->simplifiedModeDefault(),
            'invoice_prefix' => 'INV',
            'po_prefix' => 'PO',
            'date_format' => 'd M Y',
            'time_format' => 'h:i A',
            'fiscal_year_start' => '07-01',
        ];

        $company->update(['settings' => $settings]);
    }

    /**
     * Create default workflow definitions based on business type complexity.
     */
    private function applyDefaultWorkflows(Company $company): void
    {
        $businessType = $company->business_type instanceof BusinessType
            ? $company->business_type
            : BusinessType::from($company->business_type);

        $complexity = $this->workflowComplexity($businessType);

        match ($complexity) {
            'simple' => $this->applySimpleWorkflows($company),
            'standard' => $this->applyStandardWorkflows($company),
            'enterprise' => $this->applyEnterpriseWorkflows($company),
        };
    }

    private function workflowComplexity(BusinessType $type): string
    {
        return match ($type) {
            BusinessType::RETAIL, BusinessType::RESTAURANT, BusinessType::FREELANCER => 'simple',
            BusinessType::WHOLESALE, BusinessType::MANUFACTURING, BusinessType::SERVICE,
            BusinessType::PHARMACY, BusinessType::SCHOOL, BusinessType::NGO => 'standard',
            BusinessType::RMG, BusinessType::GOVERNMENT => 'enterprise',
        };
    }

    private function applySimpleWorkflows(Company $company): void
    {
        // Simple PO: Draft → Approved → Received → Closed
        $po = $this->createDefinition($company, 'purchase_order', 'Simple PO');
        $this->createStatuses($po, $company, [
            ['key' => 'draft', 'label' => 'Draft', 'color' => 'gray', 'is_initial' => true, 'order' => 0],
            ['key' => 'approved', 'label' => 'Approved', 'color' => 'success', 'order' => 1],
            ['key' => 'received', 'label' => 'Received', 'color' => 'info', 'order' => 2],
            ['key' => 'closed', 'label' => 'Closed', 'color' => 'gray', 'is_terminal' => true, 'order' => 3],
        ]);
        $this->createTransitions($po, $company, [
            ['from' => 'draft', 'to' => 'approved', 'label' => 'Approve', 'roles' => ['owner', 'admin']],
            ['from' => 'approved', 'to' => 'received', 'label' => 'Mark Received', 'roles' => ['owner', 'admin', 'employee']],
            ['from' => 'received', 'to' => 'closed', 'label' => 'Close', 'roles' => ['owner', 'admin']],
        ]);

        // Simple SO: Draft → Confirmed → Delivered → Closed
        $so = $this->createDefinition($company, 'sales_order', 'Simple Sales');
        $this->createStatuses($so, $company, [
            ['key' => 'draft', 'label' => 'Draft', 'color' => 'gray', 'is_initial' => true, 'order' => 0],
            ['key' => 'confirmed', 'label' => 'Confirmed', 'color' => 'success', 'order' => 1],
            ['key' => 'delivered', 'label' => 'Delivered', 'color' => 'info', 'order' => 2],
            ['key' => 'closed', 'label' => 'Closed', 'color' => 'gray', 'is_terminal' => true, 'order' => 3],
        ]);
        $this->createTransitions($so, $company, [
            ['from' => 'draft', 'to' => 'confirmed', 'label' => 'Confirm', 'roles' => ['owner', 'admin']],
            ['from' => 'confirmed', 'to' => 'delivered', 'label' => 'Mark Delivered', 'roles' => ['owner', 'admin', 'employee']],
            ['from' => 'delivered', 'to' => 'closed', 'label' => 'Close', 'roles' => ['owner', 'admin']],
        ]);
    }

    private function applyStandardWorkflows(Company $company): void
    {
        // Standard PO: Draft → Pending Approval → Approved → Sent → Received → Closed
        $po = $this->createDefinition($company, 'purchase_order', 'Standard PO Approval');
        $this->createStatuses($po, $company, [
            ['key' => 'draft', 'label' => 'Draft', 'color' => 'gray', 'is_initial' => true, 'order' => 0],
            ['key' => 'pending_approval', 'label' => 'Pending Approval', 'color' => 'warning', 'order' => 1],
            ['key' => 'approved', 'label' => 'Approved', 'color' => 'success', 'order' => 2],
            ['key' => 'sent', 'label' => 'Sent to Supplier', 'color' => 'info', 'order' => 3],
            ['key' => 'received', 'label' => 'Received', 'color' => 'info', 'order' => 4],
            ['key' => 'closed', 'label' => 'Closed', 'color' => 'gray', 'is_terminal' => true, 'order' => 5],
        ]);
        $this->createTransitions($po, $company, [
            ['from' => 'draft', 'to' => 'pending_approval', 'label' => 'Submit for Approval', 'roles' => ['owner', 'admin', 'employee']],
            ['from' => 'pending_approval', 'to' => 'approved', 'label' => 'Approve', 'roles' => ['owner', 'admin']],
            ['from' => 'approved', 'to' => 'sent', 'label' => 'Send to Supplier', 'roles' => ['owner', 'admin']],
            ['from' => 'sent', 'to' => 'received', 'label' => 'Mark Received', 'roles' => ['owner', 'admin', 'employee']],
            ['from' => 'received', 'to' => 'closed', 'label' => 'Close', 'roles' => ['owner', 'admin']],
            ['from' => 'pending_approval', 'to' => 'draft', 'label' => 'Reject', 'roles' => ['owner', 'admin']],
        ]);

        // Standard Expense: Draft → Submitted → Approved → Paid
        $exp = $this->createDefinition($company, 'expense_claim', 'Standard Expense Approval');
        $this->createStatuses($exp, $company, [
            ['key' => 'draft', 'label' => 'Draft', 'color' => 'gray', 'is_initial' => true, 'order' => 0],
            ['key' => 'submitted', 'label' => 'Submitted', 'color' => 'warning', 'order' => 1],
            ['key' => 'approved', 'label' => 'Approved', 'color' => 'success', 'order' => 2],
            ['key' => 'paid', 'label' => 'Paid', 'color' => 'info', 'is_terminal' => true, 'order' => 3],
        ]);
        $this->createTransitions($exp, $company, [
            ['from' => 'draft', 'to' => 'submitted', 'label' => 'Submit', 'roles' => ['owner', 'admin', 'employee']],
            ['from' => 'submitted', 'to' => 'approved', 'label' => 'Approve', 'roles' => ['owner', 'admin']],
            ['from' => 'approved', 'to' => 'paid', 'label' => 'Mark Paid', 'roles' => ['owner', 'admin']],
            ['from' => 'submitted', 'to' => 'draft', 'label' => 'Reject', 'roles' => ['owner', 'admin']],
        ]);
    }

    private function applyEnterpriseWorkflows(Company $company): void
    {
        // Enterprise PO: 8-step with department and management review
        $po = $this->createDefinition($company, 'purchase_order', 'Enterprise PO Approval');
        $this->createStatuses($po, $company, [
            ['key' => 'draft', 'label' => 'Draft', 'color' => 'gray', 'is_initial' => true, 'order' => 0],
            ['key' => 'dept_review', 'label' => 'Department Review', 'color' => 'warning', 'order' => 1],
            ['key' => 'finance_review', 'label' => 'Finance Review', 'color' => 'warning', 'order' => 2],
            ['key' => 'mgmt_approval', 'label' => 'Management Approval', 'color' => 'warning', 'order' => 3],
            ['key' => 'approved', 'label' => 'Approved', 'color' => 'success', 'order' => 4],
            ['key' => 'sent', 'label' => 'Sent', 'color' => 'info', 'order' => 5],
            ['key' => 'received', 'label' => 'Received', 'color' => 'info', 'order' => 6],
            ['key' => 'closed', 'label' => 'Closed', 'color' => 'gray', 'is_terminal' => true, 'order' => 7],
        ]);
        $this->createTransitions($po, $company, [
            ['from' => 'draft', 'to' => 'dept_review', 'label' => 'Submit to Department', 'roles' => ['owner', 'admin', 'employee']],
            ['from' => 'dept_review', 'to' => 'finance_review', 'label' => 'Forward to Finance', 'roles' => ['owner', 'admin']],
            ['from' => 'finance_review', 'to' => 'mgmt_approval', 'label' => 'Forward to Management', 'roles' => ['owner', 'admin']],
            ['from' => 'mgmt_approval', 'to' => 'approved', 'label' => 'Approve', 'roles' => ['owner']],
            ['from' => 'approved', 'to' => 'sent', 'label' => 'Send', 'roles' => ['owner', 'admin']],
            ['from' => 'sent', 'to' => 'received', 'label' => 'Receive', 'roles' => ['owner', 'admin', 'employee']],
            ['from' => 'received', 'to' => 'closed', 'label' => 'Close', 'roles' => ['owner', 'admin']],
            ['from' => 'dept_review', 'to' => 'draft', 'label' => 'Reject', 'roles' => ['owner', 'admin']],
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────

    private function createDefinition(Company $company, string $documentType, string $name): WorkflowDefinition
    {
        return WorkflowDefinition::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'document_type' => $documentType,
            'name' => $name,
            'is_active' => true,
            'is_default' => true,
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $statuses
     */
    private function createStatuses(WorkflowDefinition $definition, Company $company, array $statuses): void
    {
        foreach ($statuses as $s) {
            WorkflowStatus::withoutGlobalScopes()->create([
                'workflow_definition_id' => $definition->id,
                'company_id' => $company->id,
                'key' => $s['key'],
                'label' => $s['label'],
                'color' => $s['color'] ?? 'gray',
                'is_initial' => $s['is_initial'] ?? false,
                'is_terminal' => $s['is_terminal'] ?? false,
                'display_order' => $s['order'] ?? 0,
            ]);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $transitions
     */
    private function createTransitions(WorkflowDefinition $definition, Company $company, array $transitions): void
    {
        foreach ($transitions as $i => $t) {
            WorkflowTransition::withoutGlobalScopes()->create([
                'workflow_definition_id' => $definition->id,
                'company_id' => $company->id,
                'from_status_key' => $t['from'],
                'to_status_key' => $t['to'],
                'label' => $t['label'],
                'allowed_roles' => $t['roles'],
                'requires_approval' => $t['requires_approval'] ?? false,
                'display_order' => $i,
            ]);
        }
    }

    /**
     * Create default alert rules based on business type.
     */
    private function applyDefaultAlertRules(Company $company): void
    {
        $businessType = $company->business_type instanceof BusinessType
            ? $company->business_type
            : BusinessType::from($company->business_type);

        // All businesses: low stock alert
        AlertRule::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'name' => 'Low Stock Alert',
            'entity_type' => 'product',
            'trigger_field' => 'stock_quantity',
            'operator' => 'lt',
            'trigger_value' => '10',
            'channels' => ['in_app', 'email'],
            'target_roles' => ['owner', 'admin'],
            'message_template' => 'Stock for {name} is below {stock_quantity} units.',
            'repeat_behaviour' => 'daily_max',
            'cooldown_minutes' => 1440,
            'is_active' => true,
        ]);

        // Pharmacy: expiry alert
        if ($businessType === BusinessType::PHARMACY) {
            AlertRule::withoutGlobalScopes()->create([
                'company_id' => $company->id,
                'name' => 'Medicine Expiry Alert',
                'entity_type' => 'product',
                'trigger_field' => 'expiry_date',
                'operator' => 'lt',
                'trigger_value' => now()->addDays(30)->toDateString(),
                'channels' => ['in_app', 'email'],
                'target_roles' => ['owner', 'admin'],
                'message_template' => '{name} is expiring on {expiry_date}.',
                'repeat_behaviour' => 'once',
                'cooldown_minutes' => 0,
                'is_active' => true,
            ]);
        }
    }
}
