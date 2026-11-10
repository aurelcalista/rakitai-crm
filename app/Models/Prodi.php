<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prodi extends Model
{
    protected $fillable = [
        'kode',
        'nama',
        'jenjang',
        'fakultas',
        'kuota',
        'spp',
        'ukt',
        'ukt_reguler',
        'status',
        'biaya_pendaftaran',
        'biaya_perlengkapan',
        'biaya_dpp',
        'biaya_uang_gedung',
        'ukt_options',
    ];

    protected $casts = [
        'ukt_options'        => 'array',
        'biaya_pendaftaran'  => 'integer',
        'biaya_perlengkapan' => 'integer',
        'biaya_dpp'          => 'integer',
        'biaya_uang_gedung'  => 'integer',
    ];

    public const UKT_PERIODS = [
        '1_bulan'  => 'Per Bulan (1 Bulan)',
        '3_bulan'  => 'Per 3 Bulan (Triwulan)',
        '6_bulan'  => 'Per 6 Bulan (Semester)',
        '12_bulan' => 'Per 12 Bulan (1 Tahun)',
        '24_bulan' => 'Per 24 Bulan (2 Tahun)',
        '36_bulan' => 'Per 36 Bulan (3 Tahun)',
        '48_bulan' => 'Per 48 Bulan (4 Tahun)',
    ];

    /**
     * Skema default 7 periode pilihan UKT.
     *
     * @return array<string, array{key: string, label: string, nominal: int, is_active: bool}>
     */
    public static function defaultUktOptions(): array
    {
        $options = [];
        foreach (self::UKT_PERIODS as $key => $label) {
            $options[$key] = [
                'key'       => $key,
                'label'     => $label,
                'nominal'   => 0,
                'is_active' => in_array($key, ['1_bulan', '6_bulan', '12_bulan']),
            ];
        }
        return $options;
    }

    /**
     * Accessor: pastikan ke-7 pilihan periode UKT selalu tersedia dengan struktur yang lengkap.
     */
    public function getUktOptionsAttribute($value): array
    {
        $stored = [];
        if (!empty($value)) {
            $stored = is_array($value) ? $value : json_decode($value, true);
        }

        $defaults = self::defaultUktOptions();
        $merged = [];

        foreach ($defaults as $key => $def) {
            if (isset($stored[$key]) && is_array($stored[$key])) {
                $merged[$key] = [
                    'key'       => $key,
                    'label'     => $stored[$key]['label'] ?? $def['label'],
                    'nominal'   => (int) ($stored[$key]['nominal'] ?? 0),
                    'is_active' => (bool) ($stored[$key]['is_active'] ?? false),
                ];
            } else {
                $merged[$key] = $def;
            }
        }

        return $merged;
    }

    /**
     * Dapatkan hanya opsi periode UKT yang diaktifkan oleh admin.
     *
     * @return array<string, array{key: string, label: string, nominal: int, is_active: bool}>
     */
    public function getActiveUktOptions(): array
    {
        return array_filter($this->ukt_options, fn($opt) => !empty($opt['is_active']));
    }

    /**
     * Hitung total biaya 1x bayar (Pendaftaran + Perlengkapan + DPP + Uang Gedung).
     */
    public function getTotalOneTimeBiaya(): int
    {
        return (int) $this->biaya_pendaftaran
            + (int) $this->biaya_perlengkapan
            + (int) $this->biaya_dpp
            + (int) $this->biaya_uang_gedung;
    }

    public function prospeks()
    {
        return $this->hasMany(Prospek::class, 'prodi_id');
    }
}
