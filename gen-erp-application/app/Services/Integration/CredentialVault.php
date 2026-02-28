<?php

namespace App\Services\Integration;

use App\Models\IntegrationCredential;
use Illuminate\Support\Facades\Crypt;
use RuntimeException;

/**
 * Encrypts and retrieves integration credentials per company.
 * Company_id is bound into the encrypted payload to prevent cross-company theft.
 */
class CredentialVault
{
    /** Store an encrypted credential for a company integration. */
    public function store(int $companyId, int $companyIntegrationId, string $key, string $value): void
    {
        $payload = json_encode(['value' => $value, 'company_id' => $companyId]);
        $encrypted = Crypt::encryptString($payload);

        IntegrationCredential::updateOrCreate(
            [
                'company_id' => $companyId,
                'company_integration_id' => $companyIntegrationId,
                'credential_key' => $key,
            ],
            ['credential_value' => $encrypted],
        );
    }

    /** Retrieve and decrypt a credential, verifying company binding. */
    public function retrieve(int $companyId, int $companyIntegrationId, string $key): string
    {
        $record = IntegrationCredential::where('company_id', $companyId)
            ->where('company_integration_id', $companyIntegrationId)
            ->where('credential_key', $key)
            ->firstOrFail();

        $payload = json_decode(Crypt::decryptString($record->credential_value), true);

        if (($payload['company_id'] ?? null) !== $companyId) {
            throw new RuntimeException('Credential company_id mismatch — possible tampering');
        }

        return $payload['value'];
    }

    /** Check if a credential exists for a company integration. */
    public function has(int $companyId, int $companyIntegrationId, string $key): bool
    {
        return IntegrationCredential::where('company_id', $companyId)
            ->where('company_integration_id', $companyIntegrationId)
            ->where('credential_key', $key)
            ->exists();
    }

    /** Delete a credential. */
    public function delete(int $companyId, int $companyIntegrationId, string $key): void
    {
        IntegrationCredential::where('company_id', $companyId)
            ->where('company_integration_id', $companyIntegrationId)
            ->where('credential_key', $key)
            ->delete();
    }

    /** Store OAuth tokens (access_token, refresh_token, expires_at). */
    public function storeOAuthTokens(int $companyId, int $companyIntegrationId, array $tokens): void
    {
        $this->store($companyId, $companyIntegrationId, 'access_token', $tokens['access_token']);

        if (isset($tokens['refresh_token'])) {
            $this->store($companyId, $companyIntegrationId, 'refresh_token', $tokens['refresh_token']);
        }

        if (isset($tokens['expires_at'])) {
            $this->store($companyId, $companyIntegrationId, 'expires_at', (string) $tokens['expires_at']);
        }
    }

    /** Retrieve all OAuth tokens for a company integration. */
    public function getOAuthTokens(int $companyId, int $companyIntegrationId): array
    {
        return [
            'access_token' => $this->retrieve($companyId, $companyIntegrationId, 'access_token'),
            'refresh_token' => $this->has($companyId, $companyIntegrationId, 'refresh_token')
                ? $this->retrieve($companyId, $companyIntegrationId, 'refresh_token')
                : null,
            'expires_at' => $this->has($companyId, $companyIntegrationId, 'expires_at')
                ? $this->retrieve($companyId, $companyIntegrationId, 'expires_at')
                : null,
        ];
    }
}
