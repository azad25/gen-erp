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
use PhpOffice\PhpSpreadsheet\IOFactory;

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

        $filePath = storage_path("app/{$job->file_path}");
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        // Detect file format and parse accordingly
        $rows = match ($extension) {
            'csv' => $this->parseCsv($filePath),
            'xlsx', 'xls' => $this->parseExcel($filePath),
            'txt' => $this->parseTxt($filePath),
            'docx' => $this->parseDocx($filePath),
            default => throw new \RuntimeException("Unsupported file format: {$extension}"),
        };

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

    /**
     * Parse Excel file (xlsx/xls) into array of associative rows.
     *
     * @return array<int, array<string, string>>
     */
    private function parseExcel(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = [];

        foreach ($worksheet->toArray() as $rowIndex => $row) {
            if ($rowIndex === 0) {
                continue; // Skip header row
            }

            $rows[] = array_map(fn ($value) => is_string($value) ? $value : (string) $value, $row);
        }

        // Get headers from first row
        $headers = $worksheet->toArray()[0] ?? [];
        $headers = array_map(fn ($h) => strtolower(trim($h)), $headers);

        // Combine headers with rows
        $result = [];
        foreach ($rows as $row) {
            if (count($row) === count($headers)) {
                $result[] = array_combine($headers, $row);
            }
        }

        return $result;
    }

    /**
     * Parse TXT file into array of associative rows.
     * Assumes pipe (|) or tab-separated values.
     *
     * @return array<int, array<string, string>>
     */
    private function parseTxt(string $filePath): array
    {
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (empty($lines)) {
            return [];
        }

        // Detect delimiter
        $firstLine = $lines[0];
        $delimiter = str_contains($firstLine, '|') ? '|' : "\t";

        $headers = array_map('strtolower', array_map('trim', explode($delimiter, $firstLine)));
        $rows = [];

        foreach (array_slice($lines, 1) as $line) {
            $data = array_map('trim', explode($delimiter, $line));
            if (count($data) === count($headers)) {
                $rows[] = array_combine($headers, $data);
            }
        }

        return $rows;
    }

    /**
     * Parse DOCX file into array of associative rows.
     * Extracts tables from DOCX and parses them.
     *
     * @return array<int, array<string, string>>
     */
    private function parseDocx(string $filePath): array
    {
        $phpWord = \PhpOffice\PhpWord\IOFactory::load($filePath);
        $sections = $phpWord->getSections();
        $rows = [];

        foreach ($sections as $section) {
            $tables = $section->getElements();
            foreach ($tables as $element) {
                if ($element instanceof \PhpOffice\PhpWord\Element\Table) {
                    $tableRows = $element->getRows();
                    if (empty($tableRows)) {
                        continue;
                    }

                    // Get headers from first row
                    $headerCells = $tableRows[0]->getCells();
                    $headers = [];
                    foreach ($headerCells as $cell) {
                        $headers[] = strtolower(trim($cell->getText()));
                    }

                    // Process data rows
                    foreach (array_slice($tableRows, 1) as $row) {
                        $cells = $row->getCells();
                        $rowData = [];
                        foreach ($cells as $cell) {
                            $rowData[] = trim($cell->getText());
                        }

                        if (count($rowData) === count($headers)) {
                            $rows[] = array_combine($headers, $rowData);
                        }
                    }

                    break; // Only process first table
                }
            }
        }

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

        $slug = \Illuminate\Support\Str::slug($validated['name']);

        \App\Models\Product::withoutGlobalScopes()->updateOrCreate(
            ['company_id' => $companyId, 'sku' => $validated['sku']],
            [
                'name' => $validated['name'],
                'slug' => $slug,
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
