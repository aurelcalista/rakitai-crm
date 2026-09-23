<?php

namespace App\Services;

use App\Models\TahunAkademik;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Central mechanism for Tahun Akademik (Academic Year) management.
 *
 * PRD requirement: Exactly ONE active academic year at any given time.
 * - Use getAktif()       → returns ?TahunAkademik (null if none active)
 * - Use getAktifOrFail() → throws if no active TA (configuration problem)
 * - Use activate()       → atomic activation with DB lock (prevents race conditions)
 *
 * IMPORTANT: Never fall back to a hardcoded year string in business logic.
 * "No active TA" is a configuration/data-integrity problem that must be visible.
 */
class AkademikService
{
    /** In-memory cache for the current request lifecycle. */
    private static ?TahunAkademik $cached = null;

    /**
     * Get the currently active Tahun Akademik, or null if none is configured.
     * Result is cached for the duration of the current request.
     */
    public static function getAktif(): ?TahunAkademik
    {
        if (self::$cached === null) {
            self::$cached = TahunAkademik::where('status', 'Aktif')->first();
        }

        return self::$cached;
    }

    /**
     * Get the ID of the active Tahun Akademik, or null.
     */
    public static function getAktifId(): ?int
    {
        return self::getAktif()?->id;
    }

    /**
     * Get the nama (e.g. "2027/2028") of the active Tahun Akademik, or null.
     */
    public static function getAktifNama(): ?string
    {
        return self::getAktif()?->nama;
    }

    /**
     * Get the active Tahun Akademik, throwing a RuntimeException if none is configured.
     *
     * Use this in business logic that REQUIRES an active academic year.
     * A missing active TA is a data-integrity problem, not a graceful edge case.
     *
     * @throws RuntimeException
     */
    public static function getAktifOrFail(): TahunAkademik
    {
        $ta = self::getAktif();

        if ($ta === null) {
            throw new RuntimeException(
                'Tidak ada Tahun Akademik aktif. Konfigurasi sistem belum lengkap — hubungi Administrator.'
            );
        }

        return $ta;
    }

    /**
     * Atomically activate a Tahun Akademik.
     *
     * Uses a DB transaction with a SELECT FOR UPDATE lock on the tahun_akademiks table
     * to prevent two simultaneous requests from creating two active academic years.
     *
     * After this call:
     * - The target TA is Aktif.
     * - All other TAs are Non-Aktif.
     * - The in-memory cache is refreshed.
     *
     * @throws \Throwable
     */
    public static function activate(TahunAkademik $ta): void
    {
        DB::transaction(function () use ($ta) {
            // Lock all rows to prevent concurrent activation
            DB::table('tahun_akademiks')->lockForUpdate()->get();

            // Deactivate all others first
            TahunAkademik::where('id', '!=', $ta->id)
                ->where('status', 'Aktif')
                ->update(['status' => 'Non-Aktif']);

            // Activate the target TA
            $ta->status = 'Aktif';
            $ta->saveQuietly(); // skip booted() observer to avoid double-update
        });

        // Bust the per-request cache after activation
        self::$cached = null;
    }

    /**
     * Flush the in-memory cache (useful in tests between assertions).
     */
    public static function flushCache(): void
    {
        self::$cached = null;
    }
}
