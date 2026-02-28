<?php

namespace App\Jobs;

use App\Models\IntegrationHook;
use App\Models\IntegrationLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/** Executes a single integration hook handler asynchronously. */
class RunHookHandlerJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public readonly int $hookRecordId,
        public readonly string $serializedArgs,
    ) {
        $this->onQueue('integrations');
    }

    public function handle(): void
    {
        $hookRecord = IntegrationHook::find($this->hookRecordId);

        if (! $hookRecord || ! $hookRecord->is_active) {
            return;
        }

        $args = unserialize($this->serializedArgs);
        $startTime = microtime(true);

        try {
            $handler = app($hookRecord->handler_class);
            $handler->handle(...$args);

            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            IntegrationLog::success(
                $hookRecord->company_integration_id,
                $hookRecord->hook_name,
                $durationMs,
                $hookRecord->company_id,
            );
        } catch (Throwable $e) {
            IntegrationLog::failure(
                $hookRecord->company_integration_id,
                $hookRecord->hook_name,
                $e->getMessage(),
                $hookRecord->company_id,
            );

            throw $e; // Re-throw so Laravel retries (up to 3x)
        }
    }
}
