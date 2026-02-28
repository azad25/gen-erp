<?php

namespace App\Models\Traits;

use App\Models\AuditLog;
use App\Services\CompanyContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Throwable;

/**
 * Auto-logs create/update/delete events to the audit log via model events.
 *
 * Add `use LogsAudit;` to any model that should be tracked.
 */
trait LogsAudit
{
    /**
     * Fields that should never appear in audit logs.
     *
     * @var array<int, string>
     */
    protected static array $auditExclude = [
        'password', 'remember_token', 'two_factor_secret',
        'two_factor_recovery_codes', 'nid_number',
    ];

    public static function bootLogsAudit(): void
    {
        static::created(function ($model): void {
            static::recordAudit('created', $model, [], $model->getAttributes());
        });

        static::updated(function ($model): void {
            $dirty = $model->getDirty();
            if (empty($dirty)) {
                return;
            }

            $old = collect($model->getOriginal())
                ->only(array_keys($dirty))
                ->all();

            static::recordAudit('updated', $model, $old, $dirty);
        });

        static::deleted(function ($model): void {
            static::recordAudit('deleted', $model, $model->getOriginal(), []);
        });
    }

    /**
     * Write an audit log entry, filtering out sensitive fields.
     *
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     */
    private static function recordAudit(string $event, $model, array $old, array $new): void
    {
        try {
            $companyId = CompanyContext::hasActive()
                ? CompanyContext::activeId()
                : ($model->company_id ?? null);

            if (! $companyId) {
                return;
            }

            // Filter sensitive fields
            $old = collect($old)->except(static::$auditExclude)->all();
            $new = collect($new)->except(static::$auditExclude)->all();

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
            Log::channel('stderr')->error('Auto audit log failed: '.$e->getMessage(), [
                'event' => $event,
                'model' => get_class($model).'#'.$model->getKey(),
            ]);
        }
    }
}
