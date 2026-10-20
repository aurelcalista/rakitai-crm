<?php

namespace App\Support;

class WhatsAppHelper
{
    /**
     * Regex for a valid standardized Indonesian WhatsApp number:
     * Must start with +628 followed by 8 to 12 digits (total 11 to 15 characters).
     */
    public const VALID_REGEX = '/^\+628[0-9]{8,12}$/';

    /**
     * Check if a raw input string explicitly violates WhatsApp format rules.
     * Disallowed rules:
     * - Starting with '08' or '0' (e.g. 081234567890)
     * - Starting with '628' without leading '+' (e.g. 6281234567890)
     * - Starting with '+6208' or '+620' (e.g. +62081234567890)
     * - Containing '+62+62' (e.g. +62+6281234567890)
     */
    public static function isExplicitlyDisallowed(?string $number): bool
    {
        if ($number === null || trim((string) $number) === '') {
            return false;
        }

        $str = trim((string) $number);

        // Disallow 08... or 0...
        if (str_starts_with($str, '0')) {
            return true;
        }

        // Disallow 628... or 62... without leading +
        if (str_starts_with($str, '62') && !str_starts_with($str, '+62')) {
            return true;
        }

        // Disallow +6208... or +620...
        if (str_starts_with($str, '+620')) {
            return true;
        }

        // Disallow repeated prefix +62+62...
        if (str_contains($str, '+62+62')) {
            return true;
        }

        return false;
    }

    /**
     * Extract only subscriber digits (stripping country prefix +62, 62, or leading 0).
     * Used for populating form inputs where [+62] prefix is displayed separately.
     * e.g. "+6281234567890" -> "81234567890"
     *      "081234567890"   -> "81234567890"
     *      "6281234567890"  -> "81234567890"
     */
    public static function normalizeForDisplay(?string $number): string
    {
        if ($number === null || $number === '' || $number === '-') {
            return '';
        }

        $digits = preg_replace('/[^0-9]/', '', (string) $number);
        if (empty($digits)) {
            return '';
        }

        // Remove leading 62 or 0 repeatedly
        $cleaned = preg_replace('/^(62|0)+/', '', $digits);

        return $cleaned ?? '';
    }

    /**
     * Prepares an input value for validation and storage.
     * - If the user entered subscriber digits after an uneditable +62 prefix (e.g. "81234567890"),
     *   prepends "+62" -> "+6281234567890".
     * - If already "+6281234567890", keeps it as-is.
     * - If explicitly disallowed (e.g. "08...", "628...", "+6208..."), leaves it untouched
     *   so that validation rules catch and reject it with specific error messages.
     */
    public static function prepareForValidation(?string $number): ?string
    {
        if ($number === null || trim((string) $number) === '') {
            return null;
        }

        $str = trim((string) $number);

        // Leave explicitly disallowed values untouched so validator rejects them
        if (self::isExplicitlyDisallowed($str)) {
            return $str;
        }

        // Clean any spaces or dashes from user input
        $clean = preg_replace('/[\s\-]/', '', $str);

        // If user typed local subscriber number (starts with 8 and is numeric)
        if (preg_match('/^8[0-9]{8,12}$/', $clean)) {
            return '+62' . $clean;
        }

        // If already starts with +628 and valid length
        if (preg_match('/^\+628[0-9]{8,12}$/', $clean)) {
            return $clean;
        }

        return $str;
    }

    /**
     * Check if a number satisfies the strict standardized Indonesian WhatsApp format.
     */
    public static function isValid(?string $number): bool
    {
        if ($number === null || trim((string) $number) === '') {
            return false;
        }

        $str = trim((string) $number);

        if (self::isExplicitlyDisallowed($str)) {
            return false;
        }

        return (bool) preg_match(self::VALID_REGEX, $str);
    }

    /**
     * Format a value for safe database storage.
     * Ensures canonical +628xxxxxxxx format.
     */
    public static function formatForDatabase(?string $number): ?string
    {
        if ($number === null || trim((string) $number) === '' || $number === '-') {
            return null;
        }

        $prepared = self::prepareForValidation($number);
        if ($prepared && preg_match(self::VALID_REGEX, $prepared)) {
            return $prepared;
        }

        // Fallback for legacy numbers: extract digits and prefix +62
        $digits = self::normalizeForDisplay($number);
        if (!empty($digits) && str_starts_with($digits, '8')) {
            return '+62' . $digits;
        }

        return $prepared ?: $number;
    }

    /**
     * Standard human-readable display string (e.g. for detail views or table lists).
     */
    public static function formatDisplay(?string $number): string
    {
        if (empty($number) || $number === '-') {
            return '-';
        }

        $digits = self::normalizeForDisplay($number);
        if (empty($digits)) {
            return $number;
        }

        return '+62' . $digits;
    }

    /**
     * Format for wa.me API link: https://wa.me/628xxxxxxxx
     */
    public static function toWaMeUrl(?string $number): string
    {
        if (empty($number) || $number === '-') {
            return '#';
        }

        $digits = self::normalizeForDisplay($number);
        if (empty($digits)) {
            return '#';
        }

        return 'https://wa.me/62' . $digits;
    }
}
