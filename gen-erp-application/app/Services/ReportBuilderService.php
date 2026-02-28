<?php

namespace App\Services;

use App\Models\SavedReport;
use Illuminate\Support\Facades\Log;

/**
 * Executes report queries, exports, and scheduling for the report builder.
 */
class ReportBuilderService
{
    public function __construct(
        private readonly CustomFieldService $customFieldService,
    ) {}

    /**
     * Get available fields for an entity type (including custom fields).
     *
     * @return array<int, array{key: string, label: string}>
     */
    public function getAvailableFields(string $entityType): array
    {
        // Base fields per entity type
        $base = match ($entityType) {
            'customer' => [
                ['key' => 'id', 'label' => __('ID')],
                ['key' => 'name', 'label' => __('Name')],
                ['key' => 'email', 'label' => __('Email')],
                ['key' => 'phone', 'label' => __('Phone')],
                ['key' => 'district', 'label' => __('District')],
                ['key' => 'balance', 'label' => __('Balance')],
                ['key' => 'created_at', 'label' => __('Created At')],
            ],
            'product' => [
                ['key' => 'id', 'label' => __('ID')],
                ['key' => 'name', 'label' => __('Name')],
                ['key' => 'sku', 'label' => __('SKU')],
                ['key' => 'price', 'label' => __('Price')],
                ['key' => 'stock_quantity', 'label' => __('Stock Quantity')],
                ['key' => 'category', 'label' => __('Category')],
                ['key' => 'created_at', 'label' => __('Created At')],
            ],
            'invoice', 'sales' => [
                ['key' => 'id', 'label' => __('ID')],
                ['key' => 'invoice_number', 'label' => __('Invoice Number')],
                ['key' => 'customer_name', 'label' => __('Customer')],
                ['key' => 'total', 'label' => __('Total')],
                ['key' => 'status', 'label' => __('Status')],
                ['key' => 'due_date', 'label' => __('Due Date')],
                ['key' => 'created_at', 'label' => __('Created At')],
            ],
            'purchase', 'purchases' => [
                ['key' => 'id', 'label' => __('ID')],
                ['key' => 'po_number', 'label' => __('PO Number')],
                ['key' => 'supplier_name', 'label' => __('Supplier')],
                ['key' => 'total', 'label' => __('Total')],
                ['key' => 'status', 'label' => __('Status')],
                ['key' => 'created_at', 'label' => __('Created At')],
            ],
            'employee', 'employees' => [
                ['key' => 'id', 'label' => __('ID')],
                ['key' => 'name', 'label' => __('Name')],
                ['key' => 'department', 'label' => __('Department')],
                ['key' => 'designation', 'label' => __('Designation')],
                ['key' => 'joining_date', 'label' => __('Joining Date')],
            ],
            'expense', 'expenses' => [
                ['key' => 'id', 'label' => __('ID')],
                ['key' => 'description', 'label' => __('Description')],
                ['key' => 'amount', 'label' => __('Amount')],
                ['key' => 'category', 'label' => __('Category')],
                ['key' => 'date', 'label' => __('Date')],
            ],
            default => [
                ['key' => 'id', 'label' => __('ID')],
                ['key' => 'created_at', 'label' => __('Created At')],
            ],
        };

        // Append custom fields
        $customDefs = $this->customFieldService->getDefinitions($entityType);
        foreach ($customDefs as $def) {
            $base[] = ['key' => "cf_{$def->field_key}", 'label' => $def->label];
        }

        return $base;
    }

    /**
     * Execute a saved report and return results.
     *
     * @return array{columns: array<int, string>, rows: array<int, array<string, mixed>>, chart_data: array<string, mixed>}
     */
    public function run(SavedReport $report): array
    {
        // TODO: Phase 3 — implement actual entity queries
        // For now return empty structured result
        return [
            'columns' => $report->selected_fields ?? [],
            'rows' => [],
            'chart_data' => [
                'labels' => [],
                'datasets' => [],
            ],
        ];
    }

    /**
     * Export a report to file (stub for Phase 3).
     */
    public function export(SavedReport $report, string $format): string
    {
        // TODO: Phase 3+ — generate PDF/Excel file
        Log::info('Report export requested', [
            'report_id' => $report->id,
            'format' => $format,
        ]);

        return '';
    }

    /**
     * Set up scheduled report dispatch (stub for Phase 3).
     */
    public function schedule(SavedReport $report): void
    {
        $report->update([
            'is_scheduled' => true,
            'last_run_at' => now(),
        ]);
    }
}
