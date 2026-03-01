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

        // Send in-app notification to user with result summary
        $user = \App\Models\User::find($this->userId);
        $company = $this->company;
        if ($user && $company) {
            $event = \App\Enums\NotificationEvent::tryFrom('import_complete');
            if ($event) {
                $variables = [
                    'created' => $result['created'],
                    'failed' => $result['failed'],
                ];
                app(NotificationService::class)->send($event, $company, $variables, [$this->userId]);
            }
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Product import job failed', [
            'company_id' => $this->company->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
