<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TahunAkademik extends Model
{
    protected $fillable = ['nama', 'status'];

    // ──────────────────────────────────────────────────────────────
    // Lifecycle Guardrail
    // ──────────────────────────────────────────────────────────────

    /**
     * Guardrail: Maksimal 2 Tahun Akademik yang dapat aktif secara bersamaan.
     * Mencegah admin asal-asalan mengaktifkan tahun agar integritas data tetap terjaga.
     */
    protected static function booted(): void
    {
        static::saving(function (TahunAkademik $model) {
            if ($model->status === 'Aktif') {
                $otherActiveCount = self::where('id', '!=', $model->id)
                    ->where('status', 'Aktif')
                    ->count();

                if ($otherActiveCount >= 2) {
                    throw new \DomainException(
                        'Maksimal hanya 2 Tahun Akademik yang dapat aktif secara bersamaan. Nonaktifkan salah satu terlebih dahulu.'
                    );
                }
            }
        });
    }

    // ──────────────────────────────────────────────────────────────
    // Static Helpers
    // ──────────────────────────────────────────────────────────────

    /**
     * Return the latest active TahunAkademik (default primary), or null if none is configured.
     * Prefer AkademikService::getAktif() for cached access.
     */
    public static function getAktif(): ?self
    {
        return self::where('status', 'Aktif')->orderBy('nama', 'desc')->first();
    }

    /**
     * Return all active TahunAkademik collection (maksimal 2).
     */
    public static function getAktifList()
    {
        return self::where('status', 'Aktif')->orderBy('nama', 'desc')->get();
    }

    /**
     * Return the active TahunAkademik, throwing if none is configured.
     * Prefer AkademikService::getAktifOrFail() for cached access.
     *
     * @throws \RuntimeException
     */
    public static function getAktifOrFail(): self
    {
        return self::getAktif() ?? throw new \RuntimeException(
            'Tidak ada Tahun Akademik aktif. Konfigurasi sistem belum lengkap.'
        );
    }

    // ──────────────────────────────────────────────────────────────
    // Scopes
    // ──────────────────────────────────────────────────────────────

    public function scopeAktif($query)
    {
        return $query->where('status', 'Aktif');
    }

    // ──────────────────────────────────────────────────────────────
    // Relations
    // ──────────────────────────────────────────────────────────────

    public function prospeks(): HasMany
    {
        return $this->hasMany(Prospek::class, 'academic_year_id');
    }

    public function kunjungans(): HasMany
    {
        return $this->hasMany(Kunjungan::class, 'academic_year_id');
    }

    public function targets(): HasMany
    {
        return $this->hasMany(Target::class, 'academic_year_id');
    }

    public function transaksis(): HasMany
    {
        return $this->hasMany(Transaksi::class, 'academic_year_id');
    }
}
