<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kunjungan extends Model
{
    use HasFactory;

    protected $fillable = [
        'nomor', 'tanggal', 'tahun_akademik', 'waktu', 'sales_id', 'jenis',
        'tujuan_id', 'tujuan_kunjungan', 'hasil', 'catatan', 'status',
        // Detail fields added for Sales visit reports
        'nama_institusi', 'alamat', 'lokasi_penugasan', 'pic_name', 'pic_whatsapp', 'foto_path',
        'is_outside_radius', 'status_verifikasi',
        // School-specific
        'potensi_mahasiswa', 'detail_potensi_mahasiswa', 'kesediaan_training_ai',
        // Corporate-specific
        'bidang_usaha', 'potensi_s1', 'potensi_s2', 'potensi_csr',
        'lat', 'lng', 'is_verified', 'academic_year_id'
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (!$model->academic_year_id) {
                $aktif = TahunAkademik::getAktif();
                if ($aktif) {
                    $model->academic_year_id = $aktif->id;
                }
            }
        });
    }

    protected $casts = [
        'tanggal' => 'date',
        'kesediaan_training_ai' => 'boolean',
        'is_verified' => 'boolean',
        'is_outside_radius' => 'boolean',
        'lat' => 'decimal:8',
        'lng' => 'decimal:8',
    ];

    /**
     * Standard terminology: Potensi Mahasiswa (bukan Potensi Beasiswa)
     */
    public function getPotensiMahasiswaAttribute()
    {
        return $this->potensi_beasiswa;
    }

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
