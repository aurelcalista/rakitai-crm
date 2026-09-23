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
}
