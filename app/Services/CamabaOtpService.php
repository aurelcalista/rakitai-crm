<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class CamabaOtpService
{
    public const OTP_LENGTH = 6;
    public const EXPIRES_MINUTES = 5;
    public const RESEND_COOLDOWN_SECONDS = 60;
    public const MAX_ATTEMPTS = 5;

    /**
     * Normalize email for consistent OTP binding and cache keying.
     */
    public function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }

    /**
     * Get remaining resend cooldown in seconds for an email.
     */
    public function getCooldownRemaining(string $email): int
    {
        $normalized = $this->normalizeEmail($email);
        $cooldown = Cache::get($this->cooldownCacheKey($normalized));

        if (!is_array($cooldown) || !isset($cooldown['available_at'])) {
            return 0;
        }

        $remaining = (int) $cooldown['available_at'] - now()->timestamp;

        return max(0, $remaining);
    }

    /**
     * Check whether an unexpired OTP currently exists for the email.
     */
    public function hasActiveOtp(string $email): bool
    {
        $normalized = $this->normalizeEmail($email);
        $record = Cache::get($this->otpCacheKey($normalized));

        if (!is_array($record)) {
            return false;
        }

        return ($record['email'] ?? '') === $normalized
            && now()->timestamp <= (int) ($record['expires_at'] ?? 0)
            && (int) ($record['attempts'] ?? 0) < self::MAX_ATTEMPTS;
    }

    /**
     * Generate and store a hashed one-time OTP bound to the given email.
     *
     * @return array{ok: bool, reason?: string, otp?: string, expires_minutes?: int, cooldown_remaining: int}
     */
    public function issue(string $email): array
    {
        $normalized = $this->normalizeEmail($email);
        $cooldownRemaining = $this->getCooldownRemaining($normalized);

        if ($cooldownRemaining > 0) {
            return [
                'ok' => false,
                'reason' => 'cooldown',
                'cooldown_remaining' => $cooldownRemaining,
            ];
        }

        $otp = str_pad((string) random_int(0, 999999), self::OTP_LENGTH, '0', STR_PAD_LEFT);
        $expiresAt = now()->addMinutes(self::EXPIRES_MINUTES);
        $cooldownUntil = now()->addSeconds(self::RESEND_COOLDOWN_SECONDS);

        Cache::put($this->otpCacheKey($normalized), [
            'email' => $normalized,
            'otp_hash' => Hash::make($this->hashPayload($normalized, $otp)),
            'attempts' => 0,
            'expires_at' => $expiresAt->timestamp,
            'issued_at' => now()->timestamp,
        ], $expiresAt);

        Cache::put($this->cooldownCacheKey($normalized), [
            'available_at' => $cooldownUntil->timestamp,
        ], $cooldownUntil);

        return [
            'ok' => true,
            'otp' => $otp,
            'expires_minutes' => self::EXPIRES_MINUTES,
            'cooldown_remaining' => self::RESEND_COOLDOWN_SECONDS,
        ];
    }

    /**
     * Clear any newly issued OTP and cooldown (used when mail delivery fails).
     */
    public function clearIssuedOtp(string $email): void
    {
        $normalized = $this->normalizeEmail($email);
        Cache::forget($this->otpCacheKey($normalized));
        Cache::forget($this->cooldownCacheKey($normalized));
    }

    /**
     * Verify a submitted OTP for the given email with attempt limiting and single-use enforcement.
     *
     * @return array{ok: bool, reason?: string, message?: string, remaining_attempts?: int}
     */
    public function verify(string $email, string $otp): array
    {
        $normalized = $this->normalizeEmail($email);
        $cleanOtp = trim($otp);
        $otpKey = $this->otpCacheKey($normalized);

        return Cache::lock($this->lockCacheKey($normalized), 5)->block(3, function () use ($normalized, $cleanOtp, $otpKey) {
            $record = Cache::get($otpKey);

            if (!is_array($record) || ($record['email'] ?? '') !== $normalized) {
                return [
                    'ok' => false,
                    'reason' => 'missing',
                    'message' => 'Kode OTP tidak ditemukan atau sudah digunakan. Silakan minta kode OTP baru.',
                ];
            }

            $expiresAt = (int) ($record['expires_at'] ?? 0);
            if (now()->timestamp > $expiresAt) {
                Cache::forget($otpKey);

                return [
                    'ok' => false,
                    'reason' => 'expired',
                    'message' => 'Kode OTP sudah kedaluwarsa. Silakan minta kode OTP baru.',
                ];
            }

            $attempts = (int) ($record['attempts'] ?? 0);
            if ($attempts >= self::MAX_ATTEMPTS) {
                Cache::forget($otpKey);

                return [
                    'ok' => false,
                    'reason' => 'max_attempts',
                    'message' => 'Batas percobaan verifikasi OTP tercapai. Silakan minta kode OTP baru.',
                ];
            }

            if (!Hash::check($this->hashPayload($normalized, $cleanOtp), (string) ($record['otp_hash'] ?? ''))) {
                $attempts++;

                if ($attempts >= self::MAX_ATTEMPTS) {
                    Cache::forget($otpKey);

                    return [
                        'ok' => false,
                        'reason' => 'max_attempts',
                        'message' => 'Kode OTP salah dan batas percobaan telah tercapai. Silakan minta kode OTP baru.',
                    ];
                }

                $record['attempts'] = $attempts;
                $ttlSeconds = max(1, $expiresAt - now()->timestamp);
                Cache::put($otpKey, $record, Carbon::now()->addSeconds($ttlSeconds));

                $remaining = self::MAX_ATTEMPTS - $attempts;

                return [
                    'ok' => false,
                    'reason' => 'invalid',
                    'remaining_attempts' => $remaining,
                    'message' => "Kode OTP tidak valid. Sisa percobaan: {$remaining} kali.",
                ];
            }

            // Single-use: consume OTP immediately before authentication completes
            Cache::forget($otpKey);

            return ['ok' => true];
        });
    }

    private function hashPayload(string $normalizedEmail, string $otp): string
    {
        return $normalizedEmail . '|' . $otp;
    }

    private function otpCacheKey(string $normalizedEmail): string
    {
        return 'pmb_camaba_otp:' . hash('sha256', $normalizedEmail);
    }

    private function cooldownCacheKey(string $normalizedEmail): string
    {
        return 'pmb_camaba_otp_cooldown:' . hash('sha256', $normalizedEmail);
    }

    private function lockCacheKey(string $normalizedEmail): string
    {
        return 'pmb_camaba_otp_lock:' . hash('sha256', $normalizedEmail);
    }
}

