<?php

namespace App\Jobs;

use App\Models\IntegrationLog;
use App\Models\IoTDevice;
use App\Services\Integration\DeviceManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/** Syncs data from a single IoT device (pull + optional push). */
class SyncDeviceJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 2;

    public int $backoff = 30;

    public function __construct(
        public readonly int $deviceId,
    ) {
        $this->onQueue('integrations');
    }

    public function handle(DeviceManager $manager): void
    {
        $device = IoTDevice::find($this->deviceId);

        if (! $device || ! $device->is_active) {
            return;
        }

        $device->markSyncing();
        $startTime = microtime(true);

        try {
            $driver = $manager->resolveDriver($device);

            if (! $driver->connect($device->config)) {
                $device->markOffline();

                return;
            }

            $data = $driver->pull();
            $driver->disconnect();

            $device->update([
                'status' => 'online',
                'last_sync_at' => now(),
                'last_ping_at' => now(),
            ]);

            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            IntegrationLog::create([
                'company_id' => $device->company_id,
                'company_integration_id' => $device->company_integration_id,
                'direction' => 'device',
                'hook_name' => "device.sync.{$device->device_type->value}",
                'status' => 'success',
                'duration_ms' => $durationMs,
                'response_body' => ['records_count' => count($data)],
                'created_at' => now(),
            ]);
        } catch (Throwable $e) {
            $device->markError();

            IntegrationLog::create([
                'company_id' => $device->company_id,
                'company_integration_id' => $device->company_integration_id,
                'direction' => 'device',
                'hook_name' => "device.sync.{$device->device_type->value}",
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'created_at' => now(),
            ]);

            throw $e;
        }
    }
}
