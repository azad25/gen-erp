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
        $saleId = $payload['sale_id'] ?? null;
        if (!$saleId) {
            return;
        }

        $sale = \App\Models\POSSale::withoutGlobalScopes()->find($saleId);
        if (!$sale) {
            return;
        }

        $config = $sale->branch->integrationConfig('thermal-printer') ?? [];
        $ip = $config['ip'] ?? null;
        $port = $config['port'] ?? 9100;
        $width = $config['width'] ?? 80; // 80mm or 58mm

        if (!$ip) {
            Log::warning('ThermalPrinter: No IP configured for branch', [
                'sale_id' => $saleId,
                'branch_id' => $sale->branch_id,
            ]);
            return;
        }

        $escPosData = $this->buildReceipt($sale, $width);
        $this->printRaw(['ip' => $ip, 'port' => $port], $escPosData);

        Log::info('ThermalPrinter: Receipt printed', [
            'sale_id' => $saleId,
            'ip' => $ip,
            'port' => $port,
        ]);
    }

    /**
     * Build ESC/POS receipt data.
     */
    private function buildReceipt(\App\Models\POSSale $sale, int $width): string
    {
        $company = $sale->company;
        $branch = $sale->branch;
        $items = $sale->items;

        $data = '';

        // Initialize printer
        $data .= "\x1B\x40"; // Initialize

        // Center align
        $data .= "\x1B\x61\x01";

        // Company name
        $data .= $this->centerText($company->name, $width) . "\n\n";

        // Branch info
        $data .= $this->centerText($branch->name, $width) . "\n";
        $data .= $this->centerText($branch->address ?? '', $width) . "\n";
        $data .= $this->centerText('Phone: ' . ($branch->phone ?? ''), $width) . "\n\n";

        // Separator line
        $data .= $this->separatorLine($width) . "\n";

        // Receipt header
        $data .= $this->centerText("RECEIPT", $width) . "\n";
        $data .= $this->centerText("#{$sale->receipt_number}", $width) . "\n";
        $data .= $this->centerText($sale->sale_date->format('d M Y H:i'), $width) . "\n\n";

        // Separator line
        $data .= $this->separatorLine($width) . "\n";

        // Left align for items
        $data .= "\x1B\x61\x00";

        // Table header
        $data .= $this->padRight("ITEM", 30) . $this->padLeft("QTY", 5) . $this->padLeft("PRICE", 10) . $this->padLeft("TOTAL", 10) . "\n";
        $data .= $this->separatorLine($width) . "\n";

        // Items
        foreach ($items as $item) {
            $name = mb_substr($item->product->name, 0, 30);
            $qty = $item->quantity;
            $price = number_format($item->unit_price / 100, 2);
            $total = number_format($item->line_total / 100, 2);

            $data .= $this->padRight($name, 30) . $this->padLeft($qty, 5) . $this->padLeft($price, 10) . $this->padLeft($total, 10) . "\n";
        }

        // Separator line
        $data .= $this->separatorLine($width) . "\n\n";

        // Totals
        $subtotal = number_format($sale->subtotal / 100, 2);
        $tax = number_format($sale->tax_amount / 100, 2);
        $total = number_format($sale->total_amount / 100, 2);
        $paid = number_format($sale->paid_amount / 100, 2);
        $change = number_format($sale->change_amount / 100, 2);

        $data .= $this->padRight("SUBTOTAL", 40) . $this->padLeft("৳{$subtotal}", 10) . "\n";
        $data .= $this->padRight("TAX", 40) . $this->padLeft("৳{$tax}", 10) . "\n";
        $data .= $this->separatorLine($width) . "\n";
        $data .= $this->padRight("TOTAL", 40) . $this->padLeft("৳{$total}", 10) . "\n";
        $data .= $this->padRight("PAID", 40) . $this->padLeft("৳{$paid}", 10) . "\n";
        $data .= $this->padRight("CHANGE", 40) . $this->padLeft("৳{$change}", 10) . "\n\n";

        // Footer
        $data .= "\x1B\x61\x01"; // Center align
        $data .= $this->centerText("Thank you for shopping with us!", $width) . "\n\n";
        $data .= $this->centerText("Powered by GenERP BD", $width) . "\n\n\n";

        // Cut paper
        $data .= "\x1D\x56\x66\x00"; // Cut paper with partial cut

        return $data;
    }

    /**
     * Center text within receipt width.
     */
    private function centerText(string $text, int $width): string
    {
        $textLength = mb_strlen($text);
        if ($textLength >= $width) {
            return mb_substr($text, 0, $width);
        }
        $padding = ($width - $textLength) / 2;
        return str_repeat(' ', (int) $padding) . $text;
    }

    /**
     * Create separator line.
     */
    private function separatorLine(int $width): string
    {
        return str_repeat('-', $width);
    }

    /**
     * Pad text to the right.
     */
    private function padRight(string $text, int $length): string
    {
        return str_pad(mb_substr($text, 0, $length), $length, ' ');
    }

    /**
     * Pad text to the left.
     */
    private function padLeft(string $text, int $length): string
    {
        return str_pad(mb_substr($text, 0, $length), $length, ' ', STR_PAD_LEFT);
    }
}
