<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Encrypted credential storage — AES-256 encrypted per company integration. */
class IntegrationCredential extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'company_integration_id',
        'credential_key',
        'credential_value',
        'expires_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Credential values are NOT auto-decrypted via cast.
     * Always use CredentialVault service to read/write — it enforces company binding.
     */
    protected $hidden = [
        'credential_value',
    ];

    public function companyIntegration(): BelongsTo
    {
        return $this->belongsTo(CompanyIntegration::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
