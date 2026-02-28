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

        // TODO: Phase 7 — dispatch ProcessImportJob::dispatch($job)->onQueue('imports');

        return $job;
    }

    /**
     * Get path to download template Excel for an entity type.
     */
    public function getTemplate(string $entityType): string
    {
        return storage_path("app/import-templates/{$entityType}.csv");
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
