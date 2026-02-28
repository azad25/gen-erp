<?php

namespace App\Services\Integration;

use App\Jobs\RunSyncJob;
use App\Models\SyncSchedule;

/** Manages scheduled bidirectional data synchronisation for integrations. */
class SyncEngine
{
    /** Run all due sync schedules. */
    public function runDue(): void
    {
        SyncSchedule::where('is_active', true)
            ->where('next_run_at', '<=', now())
            ->chunk(50, function ($schedules): void {
                foreach ($schedules as $schedule) {
                    RunSyncJob::dispatch($schedule->id)->onQueue('integrations');

                    $schedule->update([
                        'last_run_at' => now(),
                        'next_run_at' => $schedule->calculateNextRunAt(),
                    ]);
                }
            });
    }

    /** Manually trigger a sync for a specific schedule. */
    public function runNow(SyncSchedule $schedule): void
    {
        RunSyncJob::dispatch($schedule->id)->onQueue('integrations');
        $schedule->update(['last_run_at' => now()]);
    }

    /** Pause all syncs for a company integration. */
    public function pauseAll(int $companyIntegrationId): void
    {
        SyncSchedule::where('company_integration_id', $companyIntegrationId)
            ->update(['is_active' => false]);
    }

    /** Resume all syncs for a company integration. */
    public function resumeAll(int $companyIntegrationId): void
    {
        SyncSchedule::where('company_integration_id', $companyIntegrationId)
            ->update([
                'is_active' => true,
                'next_run_at' => now(),
            ]);
    }
}
