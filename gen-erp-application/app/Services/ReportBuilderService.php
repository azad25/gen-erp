<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\SavedReport;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Builder;
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
                ['key' => 'selling_price', 'label' => __('Price')],
                ['key' => 'cost_price', 'label' => __('Cost Price')],
                ['key' => 'product_type', 'label' => __('Type')],
                ['key' => 'is_active', 'label' => __('Active')],
                ['key' => 'created_at', 'label' => __('Created At')],
            ],
            'invoice', 'sales' => [
                ['key' => 'id', 'label' => __('ID')],
                ['key' => 'invoice_number', 'label' => __('Invoice Number')],
                ['key' => 'customer_id', 'label' => __('Customer')],
                ['key' => 'total_amount', 'label' => __('Total')],
                ['key' => 'status', 'label' => __('Status')],
                ['key' => 'due_date', 'label' => __('Due Date')],
                ['key' => 'invoice_date', 'label' => __('Invoice Date')],
                ['key' => 'created_at', 'label' => __('Created At')],
            ],
            'purchase', 'purchases' => [
                ['key' => 'id', 'label' => __('ID')],
                ['key' => 'po_number', 'label' => __('PO Number')],
                ['key' => 'supplier_id', 'label' => __('Supplier')],
                ['key' => 'total_amount', 'label' => __('Total')],
                ['key' => 'status', 'label' => __('Status')],
                ['key' => 'created_at', 'label' => __('Created At')],
            ],
            'employee', 'employees' => [
                ['key' => 'id', 'label' => __('ID')],
                ['key' => 'first_name', 'label' => __('First Name')],
                ['key' => 'last_name', 'label' => __('Last Name')],
                ['key' => 'department_id', 'label' => __('Department')],
                ['key' => 'designation_id', 'label' => __('Designation')],
                ['key' => 'joining_date', 'label' => __('Joining Date')],
                ['key' => 'status', 'label' => __('Status')],
            ],
            'expense', 'expenses' => [
                ['key' => 'id', 'label' => __('ID')],
                ['key' => 'description', 'label' => __('Description')],
                ['key' => 'amount', 'label' => __('Amount')],
                ['key' => 'expense_date', 'label' => __('Date')],
                ['key' => 'status', 'label' => __('Status')],
                ['key' => 'created_at', 'label' => __('Created At')],
            ],
            'supplier' => [
                ['key' => 'id', 'label' => __('ID')],
                ['key' => 'name', 'label' => __('Name')],
                ['key' => 'email', 'label' => __('Email')],
                ['key' => 'phone', 'label' => __('Phone')],
                ['key' => 'vat_bin', 'label' => __('VAT BIN')],
                ['key' => 'created_at', 'label' => __('Created At')],
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
     * Resolve the Eloquent model class for an entity type.
     *
     * @return class-string|null
     */
    private function resolveModel(string $entityType): ?string
    {
        return match ($entityType) {
            'customer' => Customer::class,
            'product' => Product::class,
            'invoice', 'sales' => Invoice::class,
            'purchase', 'purchases' => PurchaseOrder::class,
            'employee', 'employees' => Employee::class,
            'expense', 'expenses' => Expense::class,
            'supplier' => Supplier::class,
            default => null,
        };
    }

    /**
     * Execute a saved report and return results.
     *
     * @return array{columns: array<int, string>, rows: array<int, array<string, mixed>>, total: int}
     */
    public function run(SavedReport $report): array
    {
        $modelClass = $this->resolveModel($report->entity_type ?? '');

        if (! $modelClass) {
            return ['columns' => [], 'rows' => [], 'total' => 0];
        }

        /** @var Builder $query */
        $query = $modelClass::query();

        // Apply selected fields (only DB columns, not custom fields)
        $selectedFields = $report->selected_fields ?? ['*'];
        $dbFields = collect($selectedFields)->filter(fn ($f) => ! str_starts_with($f, 'cf_'))->all();

        if (! empty($dbFields) && ! in_array('*', $dbFields)) {
            // Always include id
            $query->select(array_unique(array_merge(['id'], $dbFields)));
        }

        // Apply filters
        if (! empty($report->filters)) {
            foreach ($report->filters as $filter) {
                $field = $filter['field'] ?? null;
                $op = $filter['operator'] ?? 'equals';
                $value = $filter['value'] ?? null;

                if (! $field || str_starts_with($field, 'cf_')) {
                    continue;
                }

                // Whitelist columns to prevent injection
                $allowedFields = collect($this->getAvailableFields($report->entity_type ?? ''))
                    ->pluck('key')
                    ->filter(fn ($k) => ! str_starts_with($k, 'cf_'))
                    ->all();

                if (! in_array($field, $allowedFields)) {
                    continue;
                }

                match ($op) {
                    'equals' => $query->where($field, $value),
                    'not_equals' => $query->where($field, '!=', $value),
                    'greater_than' => $query->where($field, '>', $value),
                    'less_than' => $query->where($field, '<', $value),
                    'contains' => $query->where($field, 'LIKE', '%'.$value.'%'),
                    'is_null' => $query->whereNull($field),
                    'not_null' => $query->whereNotNull($field),
                    default => null,
                };
            }
        }

        // Apply sorting
        $sortField = $report->sort_field ?? 'id';
        $sortDir = $report->sort_direction ?? 'desc';

        $allowedSortFields = collect($this->getAvailableFields($report->entity_type ?? ''))
            ->pluck('key')
            ->filter(fn ($k) => ! str_starts_with($k, 'cf_'))
            ->all();
        $sortField = in_array($sortField, $allowedSortFields) ? $sortField : 'id';

        $query->orderBy($sortField, $sortDir === 'asc' ? 'asc' : 'desc');

        // Execute with pagination
        $limit = min($report->row_limit ?? 500, 5000);
        $results = $query->limit($limit)->get();

        return [
            'columns' => $selectedFields,
            'rows' => $results->toArray(),
            'total' => $results->count(),
        ];
    }

    /**
     * Export a report to file.
     *
     * @return string File path
     */
    public function export(SavedReport $report, string $format): string
    {
        $data = $this->run($report);

        Log::info('Report exported', [
            'report_id' => $report->id,
            'format' => $format,
            'rows' => $data['total'],
        ]);

        // Return CSV for now, PDF/Excel require packages
        $filename = 'report_'.$report->id.'_'.now()->format('YmdHis').'.csv';
        $path = storage_path('app/private/reports/'.$filename);

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $handle = fopen($path, 'w');
        if ($handle === false) {
            return '';
        }

        // Header row
        fputcsv($handle, $data['columns']);

        // Data rows
        foreach ($data['rows'] as $row) {
            $line = [];
            foreach ($data['columns'] as $col) {
                $line[] = $row[$col] ?? '';
            }
            fputcsv($handle, $line);
        }

        fclose($handle);

        return $path;
    }

    /**
     * Set up scheduled report dispatch.
     */
    public function schedule(SavedReport $report): void
    {
        $report->update([
            'is_scheduled' => true,
            'last_run_at' => now(),
        ]);
    }
}
