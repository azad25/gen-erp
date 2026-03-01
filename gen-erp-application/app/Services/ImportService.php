<?php

namespace App\Services;

use App\Models\Company;
use App\Models\ImportJob;
use App\Models\User;

/**
 * Manages bulk data import operations.
 */
class ImportService
{
    /**
     * Queue an import job for processing.
     */
    public function queueImport(Company $company, string $entityType, string $filePath, User $user, string $originalFilename): ImportJob
    {
        $allowedTypes = ['products', 'customers', 'suppliers', 'employees', 'opening_stock'];

        if (! in_array($entityType, $allowedTypes, true)) {
            throw new \InvalidArgumentException(__('Unsupported entity type: :type', ['type' => $entityType]));
        }

        $job = ImportJob::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'entity_type' => $entityType,
            'file_path' => $filePath,
            'original_filename' => $originalFilename,
            'status' => 'pending',
            'created_by' => $user->id,
        ]);

        \App\Jobs\ProcessImportJob::dispatch($job);

        return $job;
    }

    /**
     * Get path to download template for an entity type.
     * Returns CSV template by default, can generate Excel/TXT on request.
     */
    public function getTemplate(string $entityType, string $format = 'csv'): string
    {
        $templates = [
            'csv' => storage_path("app/import-templates/{$entityType}.csv"),
            'xlsx' => storage_path("app/import-templates/{$entityType}.xlsx"),
            'txt' => storage_path("app/import-templates/{$entityType}.txt"),
        ];

        return $templates[$format] ?? $templates['csv'];
    }

    /**
     * Generate import template file if it doesn't exist.
     */
    public function ensureTemplateExists(string $entityType, string $format = 'csv'): void
    {
        $templatePath = $this->getTemplate($entityType, $format);

        if (file_exists($templatePath)) {
            return;
        }

        $headers = $this->getEntityHeaders($entityType);
        $this->generateTemplateFile($templatePath, $headers, $format);
    }

    /**
     * Get column headers for an entity type.
     */
    private function getEntityHeaders(string $entityType): array
    {
        return match ($entityType) {
            'products' => ['name', 'sku', 'selling_price', 'cost_price', 'product_type', 'unit'],
            'customers' => ['name', 'phone', 'email', 'address'],
            'suppliers' => ['name', 'phone', 'email', 'address'],
            'employees' => ['name', 'employee_id', 'designation', 'phone', 'email'],
            'opening_stock' => ['product_sku', 'quantity', 'warehouse_name'],
            default => [],
        };
    }

    /**
     * Generate template file with headers.
     */
    private function generateTemplateFile(string $path, array $headers, string $format): void
    {
        $directory = dirname($path);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        match ($format) {
            'csv' => $this->generateCsvTemplate($path, $headers),
            'xlsx' => $this->generateExcelTemplate($path, $headers),
            'txt' => $this->generateTxtTemplate($path, $headers),
            default => throw new \RuntimeException("Unsupported template format: {$format}"),
        };
    }

    /**
     * Generate CSV template.
     */
    private function generateCsvTemplate(string $path, array $headers): void
    {
        $fp = fopen($path, 'w');
        fputcsv($fp, $headers);
        fclose($fp);
    }

    /**
     * Generate Excel template.
     */
    private function generateExcelTemplate(string $path, array $headers): void
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Write headers
        foreach ($headers as $col => $header) {
            $sheet->setCellValueByColumnAndRow($col + 1, 1, $header);
        }

        // Style header row
        $headerRange = 'A1:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers)) . '1';
        $sheet->getStyle($headerRange)->getFont()->setBold(true);

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($path);
    }

    /**
     * Generate TXT template (pipe-separated).
     */
    private function generateTxtTemplate(string $path, array $headers): void
    {
        file_put_contents($path, implode('|', $headers) . "\n");
    }

    /**
     * Mark import as processing.
     */
    public function markProcessing(ImportJob $job, int $totalRows): void
    {
        $job->update([
            'status' => 'processing',
            'total_rows' => $totalRows,
            'started_at' => now(),
        ]);
    }

    /**
     * Record a successful row import.
     */
    public function recordSuccess(ImportJob $job): void
    {
        $job->increment('processed_rows');
        $job->increment('created_rows');
    }

    /**
     * Record a failed row import.
     *
     * @param  array{row: int, field: string, message: string}  $error
     */
    public function recordFailure(ImportJob $job, array $error): void
    {
        $job->increment('processed_rows');
        $job->increment('failed_rows');

        $errors = $job->errors ?? [];
        $errors[] = $error;
        $job->update(['errors' => $errors]);
    }

    /**
     * Mark import as completed.
     */
    public function markCompleted(ImportJob $job): void
    {
        $status = $job->failed_rows > 0 && $job->created_rows === 0 ? 'failed' : 'completed';
        $job->update([
            'status' => $status,
            'completed_at' => now(),
        ]);
    }
}
