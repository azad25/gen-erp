<?php

namespace App\Jobs;

use App\Models\AuditLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Creates an immutable AuditLog record. Fails silently to avoid breaking main requests.
 */
class RecordAuditLog implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $event,
        public readonly string $auditableType,
        public readonly int|string $auditableId,
        public readonly array $oldValues,
        public readonly array $newValues,
        public readonly ?int $userId,
        public readonly ?int $companyId,
        public readonly ?string $ipAddress,
        public readonly ?string $userAgent,
    ) {
        $this->onQueue('audit');
    }

    public function handle(): void
    {
        try {
            if (! $this->companyId) {
                return;
            }

            AuditLog::create([
                'company_id' => $this->companyId,
                'user_id' => $this->userId,
                'event' => $this->event,
                'auditable_type' => $this->auditableType,
                'auditable_id' => $this->auditableId,
                'old_values' => ! empty($this->oldValues) ? $this->oldValues : null,
                'new_values' => ! empty($this->newValues) ? $this->newValues : null,
                'ip_address' => $this->ipAddress,
                'user_agent' => $this->userAgent,
            ]);
        } catch (Throwable $e) {
            Log::channel('stderr')->error('Audit log recording failed: '.$e->getMessage(), [
                'event' => $this->event,
                'auditable' => $this->auditableType.'#'.$this->auditableId,
            ]);
        }
    }
}
