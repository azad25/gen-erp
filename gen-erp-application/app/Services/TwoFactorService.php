<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Manages TOTP-based two-factor authentication.
 */
class TwoFactorService
{
    /**
     * Generate a new 2FA secret for a user.
     *
     * @return array{secret: string, qr_url: string}
     */
    public function enable(User $user): array
    {
        $secret = $this->generateSecret();

        $user->update([
            'two_factor_secret' => encrypt($secret),
            'two_factor_recovery_codes' => encrypt(json_encode($this->generateRecoveryCodes())),
            'two_factor_confirmed_at' => null,
        ]);

        $qrUrl = $this->buildOtpAuthUrl($secret, $user->email);

        return [
            'secret' => $secret,
            'qr_url' => $qrUrl,
        ];
    }

    /**
     * Confirm 2FA setup with a valid TOTP code.
     */
    public function confirm(User $user, string $code): bool
    {
        if (! $this->verify($user, $code)) {
            return false;
        }

        $user->update(['two_factor_confirmed_at' => now()]);

        return true;
    }

    /**
     * Verify a TOTP code against the user's secret.
     */
    public function verify(User $user, string $code): bool
    {
        if (! $user->two_factor_secret) {
            return false;
        }

        $secret = decrypt($user->two_factor_secret);

        // TOTP verification using HMAC-based algorithm
        return $this->verifyTotp($secret, $code);
    }

    /**
     * Disable 2FA for a user.
     */
    public function disable(User $user): void
    {
        $user->update([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);
    }

    /**
     * Verify a recovery code and mark it as used.
     */
    public function verifyRecoveryCode(User $user, string $code): bool
    {
        if (! $user->two_factor_recovery_codes) {
            return false;
        }

        $codes = json_decode(decrypt($user->two_factor_recovery_codes), true);

        if (! in_array($code, $codes, true)) {
            return false;
        }

        // Remove used code
        $remaining = array_values(array_diff($codes, [$code]));
        $user->update([
            'two_factor_recovery_codes' => encrypt(json_encode($remaining)),
        ]);

        return true;
    }

    /**
     * Get remaining recovery codes.
     *
     * @return array<int, string>
     */
    public function getRecoveryCodes(User $user): array
    {
        if (! $user->two_factor_recovery_codes) {
            return [];
        }

        return json_decode(decrypt($user->two_factor_recovery_codes), true);
    }

    /**
     * Check if user has 2FA enabled and confirmed.
     */
    public function isEnabled(User $user): bool
    {
        return $user->two_factor_secret !== null
            && $user->two_factor_confirmed_at !== null;
    }

    /**
     * Generate a base32 secret key.
     */
    private function generateSecret(): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < 32; $i++) {
            $secret .= $chars[random_int(0, 31)];
        }

        return $secret;
    }

    /**
     * Generate 10 recovery codes.
     *
     * @return array<int, string>
     */
    private function generateRecoveryCodes(): array
    {
        return Collection::times(10, fn () => Str::random(10))->all();
    }

    /**
     * Build otpauth:// URL for QR code generation.
     */
    private function buildOtpAuthUrl(string $secret, string $email): string
    {
        $issuer = urlencode(config('app.name', 'GenERP'));
        $account = urlencode($email);

        return "otpauth://totp/{$issuer}:{$account}?secret={$secret}&issuer={$issuer}&algorithm=SHA1&digits=6&period=30";
    }

    /**
     * Verify TOTP code using HMAC-SHA1.
     *
     * Accepts current and previous/next time windows for clock skew.
     */
    private function verifyTotp(string $secret, string $code): bool
    {
        $timeSlice = (int) floor(time() / 30);

        // Check current and ±1 time windows for clock skew tolerance
        for ($i = -1; $i <= 1; $i++) {
            $calculatedCode = $this->generateTotp($secret, $timeSlice + $i);
            if (hash_equals($calculatedCode, str_pad($code, 6, '0', STR_PAD_LEFT))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate a TOTP code for a given time slice.
     */
    private function generateTotp(string $secret, int $timeSlice): string
    {
        $secretKey = $this->base32Decode($secret);
        $time = pack('N*', 0, $timeSlice);
        $hash = hash_hmac('sha1', $time, $secretKey, true);
        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
        $otp = (
            ((ord($hash[$offset]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF)
        ) % 1000000;

        return str_pad((string) $otp, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Decode a base32-encoded string.
     */
    private function base32Decode(string $input): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $buffer = 0;
        $bitsLeft = 0;
        $result = '';

        for ($i = 0, $len = strlen($input); $i < $len; $i++) {
            $val = strpos($chars, $input[$i]);
            if ($val === false) {
                continue;
            }
            $buffer = ($buffer << 5) | $val;
            $bitsLeft += 5;
            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $result .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }

        return $result;
    }
}
