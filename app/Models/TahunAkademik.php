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
     * Guardrail: if this model is saved as 'Aktif' through the normal
     * Eloquent lifecycle (e.g. direct ->save()), deactivate all others.
     *
     * For atomic production activation, prefer AkademikService::activate()
     * which uses a DB-level lock.
     */
    protected static function booted(): void
    {
        static::saving(function (TahunAkademik $model) {
            if ($model->status === 'Aktif') {
                self::where('id', '!=', $model->id)
                    ->where('status', 'Aktif')
                    ->update(['status' => 'Non-Aktif']);
            }
        });
    }

    // ──────────────────────────────────────────────────────────────
    // Static Helpers
    // ──────────────────────────────────────────────────────────────

    /**
     * Return the currently active TahunAkademik, or null if none is configured.
     * Prefer AkademikService::getAktif() for cached access.
     */
    public static function getAktif(): ?self
    {
        return self::where('status', 'Aktif')->first();
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
