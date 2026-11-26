<?php

namespace App\Services;

use App\Models\TahunAkademik;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Central mechanism for Tahun Akademik (Academic Year) management.
 *
 * Aturan Bisnis:
 * - Mendukung hingga MAKSIMAL 2 Tahun Akademik yang berstatus Aktif secara bersamaan (misal 2026/2027 dan 2027/2028).
 * - getAktif()       → returns ?TahunAkademik default terbaru (orderBy nama desc)
 * - getAktifList()   → returns Collection seluruh Tahun Akademik aktif
 * - getAktifOrFail() → throws if no active TA (configuration problem)
 * - activate()       → atomic activation with DB lock (validasi max 2 aktif)
 * - deactivate()     → atomic deactivation with DB lock (validasi min 1 aktif)
 */
class AkademikService
{
    /** In-memory cache for the current request lifecycle. */
    private static ?TahunAkademik $cached = null;

    /**
     * Get the default/latest active Tahun Akademik, or null if none is configured.
     * Result is cached for the duration of the current request.
     */
    public static function getAktif(): ?TahunAkademik
    {
        if (self::$cached === null) {
            self::$cached = TahunAkademik::where('status', 'Aktif')->orderBy('nama', 'desc')->first();
        }

        return self::$cached;
    }

    /**
     * Get all active academic years (maksimal 2).
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, TahunAkademik>
     */
    public static function getAktifList()
    {
        return TahunAkademik::where('status', 'Aktif')->orderBy('nama', 'desc')->get();
    }

    /**
     * Get array of IDs for all active academic years.
     *
     * @return array<int>
     */
    public static function getAktifIds(): array
    {
        return self::getAktifList()->pluck('id')->toArray();
    }

    /**
     * Get array of nama for all active academic years (contoh: ['2027/2028', '2026/2027']).
     *
     * @return array<string>
     */
    public static function getAktifNamas(): array
    {
        return self::getAktifList()->pluck('nama')->toArray();
    }

    /**
     * Get the ID of the default active Tahun Akademik, or null.
     */
    public static function getAktifId(): ?int
    {
        return self::getAktif()?->id;
    }

    /**
     * Get the nama (e.g. "2027/2028") of the default active Tahun Akademik, or null.
     */
    public static function getAktifNama(): ?string
    {
        return self::getAktif()?->nama;
    }

    /**
     * Get the active Tahun Akademik, throwing a RuntimeException if none is configured.
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
     * Atomically activate a Tahun Akademik (max 2 active concurrently).
     *
     * @throws \Throwable
     */
    public static function activate(TahunAkademik $ta): void
    {
        DB::transaction(function () use ($ta) {
            // Lock all rows to prevent concurrent activation
            DB::table('tahun_akademiks')->lockForUpdate()->get();

            $activeCount = TahunAkademik::where('id', '!=', $ta->id)
                ->where('status', 'Aktif')
                ->count();

            if ($activeCount >= 2) {
                throw new RuntimeException(
                    'Maksimal 2 Tahun Akademik yang dapat aktif secara bersamaan. Silakan nonaktifkan salah satu periode terlebih dahulu agar data tidak rancu.'
                );
            }

            // Activate the target TA directly in DB and sync model instance
            TahunAkademik::where('id', $ta->id)->update(['status' => 'Aktif']);
            $ta->status = 'Aktif';
            $ta->syncOriginal();
        });

        // Bust the per-request cache after activation
        self::$cached = null;
    }

    /**
     * Atomically deactivate a Tahun Akademik (min 1 active must remain).
     *
     * @throws \Throwable
     */
    public static function deactivate(TahunAkademik $ta): void
    {
        DB::transaction(function () use ($ta) {
            DB::table('tahun_akademiks')->lockForUpdate()->get();

            $activeCount = TahunAkademik::where('status', 'Aktif')->count();
            if ($activeCount <= 1 && $ta->status === 'Aktif') {
                throw new RuntimeException(
                    'Minimal harus ada 1 Tahun Akademik yang berstatus Aktif sebagai basis data sistem.'
                );
            }

            TahunAkademik::where('id', $ta->id)->update(['status' => 'Non-Aktif']);
            $ta->status = 'Non-Aktif';
            $ta->syncOriginal();
        });

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
