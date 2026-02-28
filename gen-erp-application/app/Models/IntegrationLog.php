<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Integration activity log — every hook, API call, and device sync logged. */
class IntegrationLog extends Model
{
    use BelongsToCompany;

    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'company_integration_id',
        'direction',
        'hook_name',
        'endpoint',
        'request_body',
        'response_status',
        'response_body',
        'duration_ms',
        'status',
        'error_message',
        'retry_count',
        'created_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'request_body' => 'array',
            'response_body' => 'array',
            'response_status' => 'integer',
            'duration_ms' => 'integer',
            'retry_count' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function companyIntegration(): BelongsTo
    {
        return $this->belongsTo(CompanyIntegration::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** Log a successful hook/integration execution. */
    public static function success(int $companyIntegrationId, string $hookName, int $durationMs, ?int $companyId = null): self
    {
        return static::create([
            'company_id' => $companyId ?? activeCompany()?->id,
            'company_integration_id' => $companyIntegrationId,
            'direction' => 'outbound',
            'hook_name' => $hookName,
            'duration_ms' => $durationMs,
            'status' => 'success',
            'created_at' => now(),
        ]);
    }

    /** Log a failed hook/integration execution. */
    public static function failure(int $companyIntegrationId, string $hookName, string $errorMessage, ?int $companyId = null): self
    {
        return static::create([
            'company_id' => $companyId ?? activeCompany()?->id,
            'company_integration_id' => $companyIntegrationId,
            'direction' => 'outbound',
            'hook_name' => $hookName,
            'status' => 'failed',
            'error_message' => $errorMessage,
            'created_at' => now(),
        ]);
    }
}
