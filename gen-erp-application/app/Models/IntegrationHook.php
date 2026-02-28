<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Hook registration — maps a hook name to a handler class for a company integration. */
class IntegrationHook extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'company_integration_id',
        'hook_name',
        'handler_class',
        'priority',
        'is_active',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'priority' => 'integer',
            'is_active' => 'boolean',
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

    /** Scope to active hooks for a given hook name. */
    public function scopeForHook($query, string $hookName, int $companyId)
    {
        return $query->where('company_id', $companyId)
            ->where('hook_name', $hookName)
            ->where('is_active', true)
            ->orderBy('priority');
    }
}
