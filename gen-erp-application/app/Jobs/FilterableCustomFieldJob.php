<?php

namespace App\Jobs;

use App\Models\CustomFieldDefinition;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Creates a MySQL generated column + index for a filterable custom field.
 *
 * NOTE: This requires the entity table to have a `custom_fields` JSON column.
 * That column will be added when entity tables (products, customers, etc.) are
 * created in Phase 3. For now, this job is ready but will only execute when the
 * entity table has the prerequisite JSON column.
 */
class FilterableCustomFieldJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly CustomFieldDefinition $definition,
    ) {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        try {
            $tableName = $this->resolveTableName($this->definition->entity_type);
            $fieldKey = $this->definition->field_key;
            $columnName = 'cf_'.preg_replace('/[^a-z0-9_]/', '', $fieldKey);

            // Ensure column name doesn't exceed MySQL's 64-char limit
            $columnName = substr($columnName, 0, 64);

            // Safety: check the table exists and has the custom_fields JSON column
            if (! Schema::hasTable($tableName)) {
                Log::info("FilterableCustomFieldJob: Table '{$tableName}' does not exist yet. Skipping.");

                return;
            }

            if (! Schema::hasColumn($tableName, 'custom_fields')) {
                Log::info("FilterableCustomFieldJob: Table '{$tableName}' missing 'custom_fields' JSON column. Skipping.");

                return;
            }

            // Check column does not already exist
            if (Schema::hasColumn($tableName, $columnName)) {
                Log::info("FilterableCustomFieldJob: Column '{$columnName}' already exists on '{$tableName}'. Skipping.");
                $this->definition->withoutGlobalScopes()->update(['generated_column_name' => $columnName]);

                return;
            }

            // Determine generated column SQL expression based on field type
            $jsonPath = '$.\"'.$fieldKey.'\"';
            $columnType = $this->resolveColumnType();

            // Add generated virtual column using raw SQL (one of the allowed exceptions)
            DB::statement(
                "ALTER TABLE `{$tableName}` ADD COLUMN `{$columnName}` {$columnType} "
                ."GENERATED ALWAYS AS (JSON_UNQUOTE(JSON_EXTRACT(`custom_fields`, '{$jsonPath}'))) VIRTUAL"
            );

            // Add index on the generated column
            DB::statement(
                "ALTER TABLE `{$tableName}` ADD INDEX `idx_{$columnName}` (`{$columnName}`)"
            );

            // Record the generated column name on the definition
            $this->definition->withoutGlobalScopes()->update(['generated_column_name' => $columnName]);

            Log::info("FilterableCustomFieldJob: Created generated column '{$columnName}' on '{$tableName}'.");
        } catch (Throwable $e) {
            Log::error('FilterableCustomFieldJob failed: '.$e->getMessage(), [
                'definition_id' => $this->definition->id,
                'entity_type' => $this->definition->entity_type,
                'field_key' => $this->definition->field_key,
            ]);
        }
    }

    /**
     * Map entity_type to the actual database table name.
     */
    private function resolveTableName(string $entityType): string
    {
        $map = [
            'product' => 'products',
            'customer' => 'customers',
            'supplier' => 'suppliers',
            'invoice' => 'invoices',
            'purchase_order' => 'purchase_orders',
            'expense' => 'expenses',
            'employee' => 'employees',
        ];

        return $map[$entityType] ?? $entityType.'s';
    }

    /**
     * Resolve MySQL column type for the generated column.
     */
    private function resolveColumnType(): string
    {
        return match ($this->definition->field_type->storageColumn()) {
            'value_number' => 'DECIMAL(20,4)',
            'value_boolean' => 'TINYINT(1)',
            'value_date' => 'DATE',
            default => 'VARCHAR(500)',
        };
    }
}
