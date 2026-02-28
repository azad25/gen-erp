<?php

namespace App\Services;

use App\Models\SecurityEvent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Throwable;

/**
 * Logs security-significant events for monitoring and compliance.
 */
class SecurityEventService
{
    /**
     * Record a security event.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function log(string $eventType, ?int $userId = null, ?int $companyId = null, array $metadata = []): void
    {
        try {
            $companyId = $companyId ?? (CompanyContext::hasActive() ? CompanyContext::activeId() : null);
            $userId = $userId ?? Auth::id();

            SecurityEvent::create([
                'company_id' => $companyId,
                'user_id' => $userId,
                'event_type' => $eventType,
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
                'metadata' => ! empty($metadata) ? $metadata : null,
            ]);
        } catch (Throwable $e) {
            Log::channel('stderr')->error('Security event logging failed: '.$e->getMessage(), [
                'event_type' => $eventType,
                'user_id' => $userId,
            ]);
        }
    }

    /**
     * Log a failed 2FA attempt.
     */
    public function logFailed2FA(?int $userId = null): void
    {
        $this->log(SecurityEvent::TYPE_FAILED_2FA, $userId);
    }

    /**
     * Log an account lockout.
     */
    public function logAccountLocked(?int $userId = null, string $reason = ''): void
    {
        $this->log(SecurityEvent::TYPE_ACCOUNT_LOCKED, $userId, metadata: ['reason' => $reason]);
    }

    /**
     * Log a mass data export.
     */
    public function logMassExport(string $entityType, int $recordCount): void
    {
        $this->log(SecurityEvent::TYPE_MASS_EXPORT, metadata: [
            'entity_type' => $entityType,
            'record_count' => $recordCount,
        ]);
    }
}
