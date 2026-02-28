<?php

namespace App\Jobs;

use App\Models\IntegrationLog;
use App\Models\SyncSchedule;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/** Executes a scheduled sync for a company integration. */
class RunSyncJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $backoff = 120;

    public function __construct(
        public readonly int $syncScheduleId,
    ) {
        $this->onQueue('integrations');
    }

    public function handle(): void
    {
        $schedule = SyncSchedule::with('companyIntegration.integration')->find($this->syncScheduleId);

        if (! $schedule || ! $schedule->is_active) {
            return;
        }

        $integration = $schedule->companyIntegration->integration;
        $startTime = microtime(true);

        try {
            // The integration's sync handler is resolved from the integration config
            $syncHandlerClass = $integration->config_schema['sync_handler'] ?? null;

            if ($syncHandlerClass && class_exists($syncHandlerClass)) {
                $handler = app($syncHandlerClass);
                $handler->sync($schedule);
            }

            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            IntegrationLog::success(
                $schedule->company_integration_id,
                "sync.{$schedule->entity_type}.{$schedule->direction}",
                $durationMs,
                $schedule->company_id,
            );
        } catch (Throwable $e) {
            IntegrationLog::failure(
                $schedule->company_integration_id,
                "sync.{$schedule->entity_type}.{$schedule->direction}",
                $e->getMessage(),
                $schedule->company_id,
            );

            throw $e;
        }
    }
}
