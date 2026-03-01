<?php

namespace Tests\Feature;

use App\Jobs\ProcessImportJob;
use App\Models\Company;
use App\Models\ImportJob;
use App\Models\Product;
use App\Models\User;
use App\Services\ImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class BulkImportTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->user = User::factory()->create();
        \App\Services\CompanyContext::setActive($this->company);
        $this->actingAs($this->user);
    }

    public function test_csv_import_creates_products(): void
    {
        $csvContent = "name,sku,selling_price,cost_price\nWidget A,SKU001,500,300\nWidget B,SKU002,600,400";
        $filePath = 'test-import.csv';
        $fullPath = storage_path("app/{$filePath}");
        file_put_contents($fullPath, $csvContent);

        $job = ImportJob::withoutGlobalScopes()->create([
            'company_id' => $this->company->id,
            'entity_type' => 'products',
            'file_path' => $filePath,
            'original_filename' => 'test.csv',
            'status' => 'pending',
            'created_by' => $this->user->id,
        ]);

        $importJob = new ProcessImportJob($job);
        $importJob->handle(app(ImportService::class));

        $this->assertDatabaseHas('products', [
            'company_id' => $this->company->id,
            'sku' => 'SKU001',
            'name' => 'Widget A',
        ]);

        $this->assertDatabaseHas('products', [
            'company_id' => $this->company->id,
            'sku' => 'SKU002',
            'name' => 'Widget B',
        ]);
    }

    public function test_excel_import_creates_products(): void
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'name');
        $sheet->setCellValue('B1', 'sku');
        $sheet->setCellValue('C1', 'selling_price');
        $sheet->setCellValue('D1', 'cost_price');
        $sheet->setCellValue('A2', 'Widget A');
        $sheet->setCellValue('B2', 'SKU001');
        $sheet->setCellValue('C2', 500);
        $sheet->setCellValue('D2', 300);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filePath = 'test-import.xlsx';
        $fullPath = storage_path("app/{$filePath}");
        $writer->save($fullPath);

        $job = ImportJob::withoutGlobalScopes()->create([
            'company_id' => $this->company->id,
            'entity_type' => 'products',
            'file_path' => $filePath,
            'original_filename' => 'test.xlsx',
            'status' => 'pending',
            'created_by' => $this->user->id,
        ]);

        $importJob = new ProcessImportJob($job);
        $importJob->handle(app(ImportService::class));

        $this->assertDatabaseHas('products', [
            'company_id' => $this->company->id,
            'sku' => 'SKU001',
            'name' => 'Widget A',
        ]);
    }

    public function test_txt_import_creates_products(): void
    {
        $txtContent = "name|sku|selling_price|cost_price\nWidget A|SKU001|500|300";
        $filePath = 'test-import.txt';
        $fullPath = storage_path("app/{$filePath}");
        file_put_contents($fullPath, $txtContent);

        $job = ImportJob::withoutGlobalScopes()->create([
            'company_id' => $this->company->id,
            'entity_type' => 'products',
            'file_path' => $filePath,
            'original_filename' => 'test.txt',
            'status' => 'pending',
            'created_by' => $this->user->id,
        ]);

        $importJob = new ProcessImportJob($job);
        $importJob->handle(app(ImportService::class));

        $this->assertDatabaseHas('products', [
            'company_id' => $this->company->id,
            'sku' => 'SKU001',
            'name' => 'Widget A',
        ]);
    }

    public function test_import_handles_validation_errors(): void
    {
        $csvContent = "name,sku,selling_price,cost_price\n,SKU001,500,300"; // Missing name
        $filePath = 'test-import-validation.csv';
        $fullPath = storage_path("app/{$filePath}");
        file_put_contents($fullPath, $csvContent);

        $job = ImportJob::withoutGlobalScopes()->create([
            'company_id' => $this->company->id,
            'entity_type' => 'products',
            'file_path' => $filePath,
            'original_filename' => 'test.csv',
            'status' => 'pending',
            'created_by' => $this->user->id,
        ]);

        $importJob = new ProcessImportJob($job);
        $importJob->handle(app(ImportService::class));

        $job->refresh();
        $this->assertGreaterThan(0, $job->failed_rows);
        $this->assertNotEmpty($job->errors);
    }

    public function test_import_tracks_progress(): void
    {
        $csvContent = "name,sku,selling_price,cost_price\n" . implode("\n", array_map(
            fn ($i) => "Widget {$i},SKU{$i}," . (500 + $i) . "," . (300 + $i),
            range(1, 15)
        ));
        $filePath = 'test-import-progress.csv';
        $fullPath = storage_path("app/{$filePath}");
        file_put_contents($fullPath, $csvContent);

        $job = ImportJob::withoutGlobalScopes()->create([
            'company_id' => $this->company->id,
            'entity_type' => 'products',
            'file_path' => $filePath,
            'original_filename' => 'test.csv',
            'status' => 'pending',
            'created_by' => $this->user->id,
        ]);

        $importJob = new ProcessImportJob($job);
        $importJob->handle(app(ImportService::class));

        $job->refresh();
        $this->assertEquals('completed', $job->status);
        $this->assertEquals(15, $job->total_rows);
        $this->assertEquals(15, $job->processed_rows);
    }
}
