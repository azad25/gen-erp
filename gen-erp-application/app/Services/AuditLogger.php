<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Throwable;

/**
 * Service for creating manual audit log entries (settings changes, logins, plan changes).
 */
class AuditLogger
{
    /**
     * Record a manual audit log entry.
     *
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     */
    public function log(string $event, Model $model, array $old = [], array $new = []): void
    {
        try {
            $companyId = CompanyContext::hasActive()
                ? CompanyContext::activeId()
                : ($model->company_id ?? null);

            if (! $companyId) {
                return;
            }

            AuditLog::create([
                'company_id' => $companyId,
                'user_id' => Auth::id(),
                'event' => $event,
                'auditable_type' => $model->getMorphClass(),
                'auditable_id' => $model->getKey(),
                'old_values' => ! empty($old) ? $old : null,
                'new_values' => ! empty($new) ? $new : null,
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
            ]);
        } catch (Throwable $e) {
            Log::channel('stderr')->error('Manual audit log failed: '.$e->getMessage(), [
                'event' => $event,
                'model' => get_class($model).'#'.$model->getKey(),
            ]);
        }
    }
}
