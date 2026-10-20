<?php

namespace App\Rules;

use App\Support\WhatsAppHelper;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class IndonesianWhatsApp implements ValidationRule
{
    /**
     * @param bool $allowEmpty If true, null/empty string is treated as valid (use with 'nullable')
     */
    public function __construct(private bool $allowEmpty = true)
    {
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || trim((string) $value) === '') {
            if (!$this->allowEmpty) {
                $fail('Nomor WhatsApp wajib diisi.');
            }
            return;
        }

        $str = trim((string) $value);

        // Disallowed formats specified in requirements:
        // 1. Started with 08 or 0
        // 2. Started with 628 without '+'
        // 3. Started with +6208 or contains +620
        // 4. Repeated prefix +62+62
        if (WhatsAppHelper::isExplicitlyDisallowed($str)) {
            $fail('Gunakan format nomor WhatsApp +62. Masukkan nomor setelah prefix +62 tanpa angka 0 di awal.');
            return;
        }

        // Clean spaces and hyphens
        $clean = preg_replace('/[\s\-]/', '', $str);

        // If local input without prefix from form (e.g. 81234567890)
        if (preg_match('/^8[0-9]{8,12}$/', $clean)) {
            return;
        }

        // If standard international format (+628xxxxxxxx)
        if (preg_match(WhatsAppHelper::VALID_REGEX, $clean)) {
            return;
        }

        // Provide specific and helpful error messages based on actual error cause
        $digits = preg_replace('/[^0-9]/', '', $clean);
        $subscriber = preg_replace('/^(62|0)+/', '', $digits);

        if (!str_starts_with($subscriber, '8')) {
            $fail('Nomor WhatsApp harus diawali angka 8 setelah +62 (contoh: 81234567890). Angka ' . ($subscriber[0] ?? '') . ' bukan nomor seluler WhatsApp yang valid.');
            return;
        }

        if (strlen($subscriber) < 9) {
            $fail('Nomor WhatsApp terlalu pendek (' . strlen($subscriber) . ' digit). Nomor WhatsApp minimal 9 sampai 13 digit angka setelah +62.');
            return;
        }

        if (strlen($subscriber) > 13) {
            $fail('Nomor WhatsApp terlalu panjang (' . strlen($subscriber) . ' digit). Maksimal 13 digit angka setelah +62.');
            return;
        }

        // Fallback error
        $fail('Gunakan format nomor WhatsApp +62 yang valid diawali angka 8 (contoh: 81234567890).');
    }
}
