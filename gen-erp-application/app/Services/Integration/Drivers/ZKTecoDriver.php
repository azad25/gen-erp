<?php

namespace App\Services\Integration\Drivers;

use App\Models\CompanyIntegration;
use App\Services\Integration\BaseNativeIntegration;
use App\Services\Integration\DeviceDriver;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ZKTeco biometric attendance device driver.
 * Supports F22, K40, F18, UA300E models via SOAP/HTTP API.
 */
class ZKTecoDriver extends BaseNativeIntegration implements DeviceDriver
{
    /** @var array{ip?: string, port?: int} */
    private array $credentials = [];

    public function slug(): string
    {
        return 'zkteco-biometric';
    }

    public function install(CompanyIntegration $ci): void
    {
        $this->registerHook($ci, 'attendance.synced', self::class . '@onAttendanceSynced');
        $this->createSyncSchedule($ci, 'attendance', 'pull', 'every_15_minutes');
    }

    public function uninstall(CompanyIntegration $ci): void
    {
        $ci->hooks()->delete();
        $ci->syncSchedules()->delete();
    }

    /** @param array{ip?: string, port?: int} $credentials */
    public function connect(array $credentials): bool
    {
        $this->credentials = $credentials;
        $ip = $credentials['ip'] ?? null;
        $port = $credentials['port'] ?? 4370;

        if (! $ip) {
            Log::warning('ZKTeco: No IP configured');
            return false;
        }

        try {
            $response = Http::timeout(5)->get("http://{$ip}:{$port}/iclock/getinfo");
            return $response->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    public function ping(): bool
    {
        return $this->connect($this->credentials);
    }

    /** @return array<int, array<string, mixed>> */
    public function pull(): array
    {
        $ip = $this->credentials['ip'] ?? null;
        $port = $this->credentials['port'] ?? 4370;

        if (! $ip) {
            return [];
        }

        try {
            $response = Http::timeout(30)
                ->get("http://{$ip}:{$port}/iclock/cdata", [
                    'table' => 'ATTLOG',
                    'Stamp' => 0,
                ]);

            if (! $response->successful()) {
                return [];
            }

            return $this->parseAttendanceLog($response->body());
        } catch (\Throwable $e) {
            Log::error('ZKTeco pull failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /** @param array<string, mixed> $data */
    public function push(array $data): bool
    {
        Log::info('ZKTeco push not implemented');
        return true;
    }

    public function disconnect(): void
    {
        $this->credentials = [];
    }

    /** @return array{status: string, message: string, last_data_at: ?string} */
    public function getStatus(): array
    {
        $connected = ! empty($this->credentials['ip']);
        return [
            'status' => $connected ? 'connected' : 'disconnected',
            'message' => $connected ? 'Device ready' : 'No credentials set',
            'last_data_at' => null,
        ];
    }

    /**
     * Parse ZKTeco attendance log format.
     *
     * @return array<int, array<string, mixed>>
     */
    private function parseAttendanceLog(string $raw): array
    {
        $records = [];
        $lines = explode("\n", trim($raw));

        foreach ($lines as $line) {
            $parts = explode("\t", trim($line));
            if (count($parts) >= 3) {
                $records[] = [
                    'biometric_id' => $parts[0] ?? '',
                    'timestamp' => $parts[1] ?? '',
                    'status' => (int) ($parts[2] ?? 0),
                    'verify_type' => (int) ($parts[3] ?? 0),
                ];
            }
        }

        return $records;
    }

    /** Handler for attendance.synced hook. */
    public function onAttendanceSynced(array $payload): void
    {
        Log::info('ZKTeco attendance synced', ['count' => count($payload['records'] ?? [])]);
    }
}
