<?php

namespace App\Models\Traits;

use App\Jobs\RecordAuditLog;
use App\Services\CompanyContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Automatically dispatches audit log entries on Eloquent create, update, and delete events.
 *
 * Apply to models that need change tracking (Company, User, CompanyUser, etc.).
 */
trait Auditable
{
    /**
     * Fields to exclude from audit log capture for security.
     *
     * @var array<int, string>
     */
    protected static array $auditExclude = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
        'password_changed_at',
    ];

    public static function bootAuditable(): void
    {
        static::created(function (Model $model): void {
            static::dispatchAuditLog('created', $model, [], $model->getAttributes());
        });

        static::updated(function (Model $model): void {
            $old = $model->getOriginal();
            $new = $model->getAttributes();
            static::dispatchAuditLog('updated', $model, $old, $new);
        });

        static::deleted(function (Model $model): void {
            static::dispatchAuditLog('deleted', $model, $model->getAttributes(), []);
        });
    }

    /**
     * Dispatch the audit log job with filtered values.
     *
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    private static function dispatchAuditLog(string $event, Model $model, array $oldValues, array $newValues): void
    {
        $filtered = fn (array $values): array => collect($values)
            ->except(static::$auditExclude)
            ->all();

        RecordAuditLog::dispatch(
            event: $event,
            auditableType: $model->getMorphClass(),
            auditableId: $model->getKey(),
            oldValues: $filtered($oldValues),
            newValues: $filtered($newValues),
            userId: Auth::id(),
            companyId: CompanyContext::hasActive() ? CompanyContext::activeId() : ($model->company_id ?? null),
            ipAddress: Request::ip(),
            userAgent: Request::userAgent(),
        );
    }
}
