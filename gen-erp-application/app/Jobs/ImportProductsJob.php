<?php

namespace App\Jobs;

use App\Models\Company;
use App\Services\ProductService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Queued bulk product import. Dispatched from ProductImportAction.
 */
class ImportProductsJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function __construct(
        public readonly Company $company,
        public readonly array $rows,
        public readonly int $userId,
    ) {
        $this->onQueue('imports');
    }

    /**
     * Unique per company to prevent parallel duplicate imports.
     */
    public function uniqueId(): string
    {
        return 'product-import-'.$this->company->id;
    }

    public function handle(ProductService $productService): void
    {
        $result = $productService->bulkCreate($this->company, $this->rows);

        Log::info('Product import completed', [
            'company_id' => $this->company->id,
            'created' => $result['created'],
            'failed' => $result['failed'],
        ]);

        // TODO: Phase 3+ — send in-app notification to $this->userId with result summary
        // Notification::make()->title("Import complete: {$result['created']} created, {$result['failed']} failed")->sendToDatabase(User::find($this->userId));
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Product import job failed', [
            'company_id' => $this->company->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
