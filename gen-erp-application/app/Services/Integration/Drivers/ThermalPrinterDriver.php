<?php

namespace App\Services\Integration\Drivers;

use App\Models\CompanyIntegration;
use App\Services\Integration\BaseNativeIntegration;
use Illuminate\Support\Facades\Log;

/**
 * ESC/POS thermal receipt printer driver.
 * Supports 80mm and 58mm thermal printers via network TCP or USB.
 */
class ThermalPrinterDriver extends BaseNativeIntegration
{
    public function slug(): string
    {
        return 'thermal-printer';
    }

    public function install(CompanyIntegration $ci): void
    {
        $this->registerHook($ci, 'pos.sale.completed', self::class . '@onSaleCompleted');
    }

    public function uninstall(CompanyIntegration $ci): void
    {
        $ci->hooks()->delete();
    }

    /**
     * Send raw ESC/POS commands to a network printer.
     *
     * @param array<string, mixed> $config ['ip' => string, 'port' => int]
     */
    public function printRaw(array $config, string $escPosData): bool
    {
        $ip = $config['ip'] ?? null;
        $port = $config['port'] ?? 9100;

        if (! $ip) {
            Log::warning('ThermalPrinter: No IP configured');
            return false;
        }

        try {
            $socket = @fsockopen($ip, $port, $errno, $errstr, 5);
            if (! $socket) {
                Log::error("ThermalPrinter: Connection failed — {$errstr}");
                return false;
            }

            fwrite($socket, $escPosData);
            fclose($socket);

            return true;
        } catch (\Throwable $e) {
            Log::error('ThermalPrinter: Print failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Open the cash drawer via ESC/POS kick command.
     *
     * @param array<string, mixed> $config
     */
    public function openCashDrawer(array $config): bool
    {
        // ESC/POS cash drawer kick: ESC p 0 25 250
        $kickCommand = "\x1B\x70\x00\x19\xFA";
        return $this->printRaw($config, $kickCommand);
    }

    /** Hook handler: auto-print receipt when a POS sale is completed. */
    public function onSaleCompleted(array $payload): void
    {
        Log::info('ThermalPrinter: Sale completed, receipt ready', [
            'sale_id' => $payload['sale_id'] ?? null,
        ]);
        // TODO: Phase 11 full implementation — build ESC/POS from receipt data and send to printer
    }
}
