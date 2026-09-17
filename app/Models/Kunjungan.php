<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kunjungan extends Model
{
    use HasFactory;

    protected $fillable = [
        'nomor', 'tanggal', 'waktu', 'sales_id', 'jenis',
        'tujuan_id', 'tujuan_kunjungan', 'hasil', 'catatan', 'status',
        // Detail fields added for Sales visit reports
        'nama_institusi', 'alamat', 'pic_name', 'pic_whatsapp', 'foto_path',
        // School-specific
        'potensi_beasiswa', 'detail_beasiswa', 'kesediaan_training_ai',
        // Corporate-specific
        'bidang_usaha', 'potensi_s1', 'potensi_s2', 'potensi_csr',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'kesediaan_training_ai' => 'boolean',
    ];

    public function sales()
    {
        return $this->belongsTo(User::class, 'sales_id');
    }

    public function sekolah()
    {
        return $this->belongsTo(Sekolah::class, 'tujuan_id');
    }

    public function perusahaan()
    {
        return $this->belongsTo(Perusahaan::class, 'tujuan_id');
    }
    public function tujuan()
    {
        if ($this->jenis === 'Sekolah') {
            return $this->belongsTo(Sekolah::class, 'tujuan_id');
        }
        return $this->belongsTo(Perusahaan::class, 'tujuan_id');
    }
}
