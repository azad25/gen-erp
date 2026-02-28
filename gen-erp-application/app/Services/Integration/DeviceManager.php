<?php

namespace App\Services\Integration;

use App\Jobs\SyncDeviceJob;
use App\Models\IoTDevice;
use Illuminate\Support\Facades\Log;
use Throwable;

/** Manages IoT device discovery, connection, and sync orchestration. */
class DeviceManager
{
    /** Sync all active devices for a company. */
    public function syncAll(int $companyId): int
    {
        $dispatched = 0;

        IoTDevice::where('company_id', $companyId)
            ->where('is_active', true)
            ->each(function (IoTDevice $device) use (&$dispatched): void {
                SyncDeviceJob::dispatch($device->id)->onQueue('integrations');
                $dispatched++;
            });

        return $dispatched;
    }

    /** Ping all active devices and update their status. */
    public function pingAll(int $companyId): array
    {
        $results = [];

        IoTDevice::where('company_id', $companyId)
            ->where('is_active', true)
            ->each(function (IoTDevice $device) use (&$results): void {
                $results[$device->id] = $this->pingDevice($device);
            });

        return $results;
    }

    /** Ping a single device and update its status. */
    public function pingDevice(IoTDevice $device): bool
    {
        try {
            $driver = $this->resolveDriver($device);

            if (! $driver->connect($device->config)) {
                $device->markOffline();

                return false;
            }

            $isAlive = $driver->ping();
            $driver->disconnect();

            if ($isAlive) {
                $device->markOnline();
            } else {
                $device->markOffline();
            }

            return $isAlive;
        } catch (Throwable $e) {
            Log::error("DeviceManager: ping failed for device {$device->id}", [
                'error' => $e->getMessage(),
            ]);
            $device->markError();

            return false;
        }
    }

    /** Resolve the driver class for a device. */
    public function resolveDriver(IoTDevice $device): DeviceDriver
    {
        $driverClass = $device->driver_class;

        if (! class_exists($driverClass)) {
            throw new \RuntimeException("Driver class {$driverClass} not found for device {$device->id}");
        }

        return app($driverClass);
    }
}
