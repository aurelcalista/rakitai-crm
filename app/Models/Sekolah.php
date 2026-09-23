<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sekolah extends Model
{
    protected $fillable = [
        'kode', 'nama', 'tier', 'kategori_id', 'wilayah_id', 'kecamatan', 
        'alamat', 'telepon', 'email', 'website', 'pic_name', 'pic_jabatan', 'pic_phone', 'status',
        'lat', 'lng'
    ];

    /**
     * Get maximum budget based on school Tier.
     */
    public function getMaxBudgetAttribute(): float
    {
        $tier = strtoupper($this->tier ?? 'B');
        $budgets = config('crm.school_tier_budgets', [
            'A' => 10000000,
            'B' => 5000000,
            'C' => 2500000,
        ]);

        return (float) ($budgets[$tier] ?? $budgets['B'] ?? 5000000);
    }

    public function kategori()
    {
        return $this->belongsTo(MasterData::class, 'kategori_id');
    }

    public function wilayah()
    {
        return $this->belongsTo(Wilayah::class, 'wilayah_id');
    }

    public function kunjungans()
    {
        return $this->hasMany(Kunjungan::class, 'tujuan_id')->where('jenis', 'Sekolah');
    }

    public function sales()
    {
        return $this->belongsTo(User::class, 'sales_id');
    }

    /**
     * Get all active schools, auto-syncing any missing schools added by EO (Events) or Sales (Kunjungan).
     */
    public static function getDynamicSchools()
    {
        try {
            $eventSchools = \Illuminate\Support\Facades\DB::table('events')
                ->whereNotNull('nama_institusi')
                ->where('nama_institusi', '!=', '')
                ->where(function ($q) {
                    $q->where('jenis_institusi', 'Sekolah')
                      ->orWhereNull('jenis_institusi');
                })
                ->select('nama_institusi', 'alamat', 'pic_name', 'pic_whatsapp')
                ->get();

            foreach ($eventSchools as $item) {
                $name = trim($item->nama_institusi);
                if ($name !== '') {
                    self::firstOrCreate(
                        ['nama' => $name],
                        [
                            'kode' => 'SCH-' . strtoupper(\Illuminate\Support\Str::random(6)),
                            'alamat' => $item->alamat,
                            'pic_name' => $item->pic_name,
                            'pic_phone' => $item->pic_whatsapp,
                            'status' => 'Aktif'
                        ]
                    );
                }
            }

            $visitSchools = \Illuminate\Support\Facades\DB::table('kunjungans')
                ->where('jenis', 'Sekolah')
                ->whereNotNull('nama_institusi')
                ->where('nama_institusi', '!=', '')
                ->select('nama_institusi', 'alamat', 'pic_name', 'pic_whatsapp')
                ->get();

            foreach ($visitSchools as $item) {
                $name = trim($item->nama_institusi);
                if ($name !== '') {
                    self::firstOrCreate(
                        ['nama' => $name],
                        [
                            'kode' => 'SCH-' . strtoupper(\Illuminate\Support\Str::random(6)),
                            'alamat' => $item->alamat,
                            'pic_name' => $item->pic_name,
                            'pic_phone' => $item->pic_whatsapp,
                            'status' => 'Aktif'
                        ]
                    );
                }
            }
        } catch (\Throwable $e) {
            // Log or ignore safely
        }

        return self::where('status', 'Aktif')->orderBy('nama')->get();
    }
}
