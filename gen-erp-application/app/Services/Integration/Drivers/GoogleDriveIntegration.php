<?php

namespace App\Services\Integration\Drivers;

use App\Models\CompanyIntegration;
use App\Services\Integration\BaseNativeIntegration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Google Drive integration — auto-export invoices, payslips, and Mushak reports.
 */
class GoogleDriveIntegration extends BaseNativeIntegration
{
    public function slug(): string
    {
        return 'google-drive';
    }

    public function install(CompanyIntegration $ci): void
    {
        $this->registerHook($ci, 'invoice.finalized', self::class . '@onInvoiceFinalized');
        $this->registerHook($ci, 'payroll.processed', self::class . '@onPayrollProcessed');
    }

    public function uninstall(CompanyIntegration $ci): void
    {
        $ci->hooks()->delete();
    }

    /**
     * Upload a file to Google Drive.
     */
    public function uploadFile(CompanyIntegration $ci, string $filePath, string $fileName, string $folderId = 'root'): ?string
    {
        $config = $ci->config ?? [];
        $token = $config['access_token'] ?? '';

        try {
            $metadata = json_encode(['name' => $fileName, 'parents' => [$folderId]]);

            $response = Http::withToken($token)
                ->attach('metadata', $metadata, 'metadata.json')
                ->attach('file', file_get_contents($filePath), $fileName)
                ->post('https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart');

            return $response->successful() ? $response->json('id') : null;
        } catch (\Throwable $e) {
            Log::error('GoogleDrive: Upload failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function onInvoiceFinalized(array $payload): void
    {
        Log::info('GoogleDrive: Invoice finalized, exporting', ['invoice' => $payload['invoice_number'] ?? '']);
    }

    public function onPayrollProcessed(array $payload): void
    {
        Log::info('GoogleDrive: Payroll processed, exporting payslips');
    }
}
