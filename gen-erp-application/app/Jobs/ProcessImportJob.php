<?php

namespace App\Jobs;

use App\Events\ImportProgressUpdated;
use App\Models\ImportJob;
use App\Services\ImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/** Processes a bulk CSV import job in the background. */
class ProcessImportJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $timeout = 3600; // 1 hour max

    public function __construct(
        public readonly ImportJob $importJob,
    ) {
        $this->onQueue('imports');
    }

    public function uniqueId(): string
    {
        return 'import-' . $this->importJob->id;
    }

    public function handle(ImportService $importService): void
    {
        $job = $this->importJob;

        if (! file_exists(storage_path("app/{$job->file_path}"))) {
            $importService->recordFailure($job, [
                'row' => 0,
                'field' => 'file',
                'message' => 'Import file not found',
            ]);
            $importService->markCompleted($job);

            return;
        }

        $rows = $this->parseCsv(storage_path("app/{$job->file_path}"));

        if (empty($rows)) {
            $importService->markCompleted($job);

            return;
        }

        $importService->markProcessing($job, count($rows));

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // +2 for header + 0-index

            try {
                $this->processRow($job->entity_type, $row, $job->company_id);
                $importService->recordSuccess($job);
            } catch (\Throwable $e) {
                $importService->recordFailure($job, [
                    'row' => $rowNumber,
                    'field' => 'general',
                    'message' => $e->getMessage(),
                ]);
            }

            // Broadcast progress every 10 rows
            if ($rowNumber % 10 === 0 || $rowNumber === count($rows) + 1) {
                $job->refresh();
                event(new ImportProgressUpdated(
                    $job->company_id,
                    $job->id,
                    $job->processed_rows,
                    $job->total_rows,
                    $job->created_rows,
                    $job->failed_rows,
                    'processing',
                ));
            }
        }

        $importService->markCompleted($job);
        $job->refresh();

        event(new ImportProgressUpdated(
            $job->company_id,
            $job->id,
            $job->processed_rows,
            $job->total_rows,
            $job->created_rows,
            $job->failed_rows,
            $job->status,
        ));
    }

    /**
     * Parse CSV file into array of associative rows.
     *
     * @return array<int, array<string, string>>
     */
    private function parseCsv(string $filePath): array
    {
        $handle = fopen($filePath, 'r');
        if (! $handle) {
            return [];
        }

        $headers = fgetcsv($handle);
        if (! $headers) {
            fclose($handle);

            return [];
        }

        // Normalize headers
        $headers = array_map(fn ($h) => strtolower(trim($h)), $headers);

        $rows = [];
        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) === count($headers)) {
                $rows[] = array_combine($headers, $data);
            }
        }

        fclose($handle);

        return $rows;
    }

    /** Process a single row based on entity type. */
    private function processRow(string $entityType, array $row, int $companyId): void
    {
        match ($entityType) {
            'products' => $this->importProduct($row, $companyId),
            'customers' => $this->importCustomer($row, $companyId),
            'suppliers' => $this->importSupplier($row, $companyId),
            'employees' => $this->importEmployee($row, $companyId),
            default => throw new \RuntimeException("Unknown entity type: {$entityType}"),
        };
    }

    private function importProduct(array $row, int $companyId): void
    {
        $validated = Validator::make($row, [
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:100',
            'selling_price' => 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
        ])->validate();

        \App\Models\Product::withoutGlobalScopes()->updateOrCreate(
            ['company_id' => $companyId, 'sku' => $validated['sku']],
            [
                'name' => $validated['name'],
                'selling_price' => (int) (($validated['selling_price'] ?? 0) * 100),
                'cost_price' => (int) (($validated['cost_price'] ?? 0) * 100),
                'company_id' => $companyId,
            ],
        );
    }

    private function importCustomer(array $row, int $companyId): void
    {
        $validated = Validator::make($row, [
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
        ])->validate();

        \App\Models\Customer::withoutGlobalScopes()->updateOrCreate(
            ['company_id' => $companyId, 'phone' => $validated['phone'] ?? null],
            [
                'name' => $validated['name'],
                'email' => $validated['email'] ?? null,
                'company_id' => $companyId,
            ],
        );
    }

    private function importSupplier(array $row, int $companyId): void
    {
        $validated = Validator::make($row, [
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
        ])->validate();

        \App\Models\Supplier::withoutGlobalScopes()->updateOrCreate(
            ['company_id' => $companyId, 'phone' => $validated['phone'] ?? null],
            [
                'name' => $validated['name'],
                'email' => $validated['email'] ?? null,
                'company_id' => $companyId,
            ],
        );
    }

    private function importEmployee(array $row, int $companyId): void
    {
        $validated = Validator::make($row, [
            'name' => 'required|string|max:255',
            'employee_id' => 'required|string|max:50',
            'designation' => 'nullable|string|max:100',
        ])->validate();

        \App\Models\Employee::withoutGlobalScopes()->updateOrCreate(
            ['company_id' => $companyId, 'employee_id' => $validated['employee_id']],
            [
                'name' => $validated['name'],
                'designation' => $validated['designation'] ?? null,
                'company_id' => $companyId,
            ],
        );
    }
}
