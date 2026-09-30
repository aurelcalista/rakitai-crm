<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Kunjungan extends Model
{
    use HasFactory;

    protected $fillable = [
        'nomor', 'tanggal', 'tahun_akademik', 'waktu', 'sales_id', 'prodi_id', 'prodi_ids', 'jenis',
        'tujuan_id', 'tujuan_kunjungan', 'hasil', 'catatan', 'status', 'event_id', 'kehadiran',
        // Detail fields added for Sales visit reports
        'nama_institusi', 'tier', 'budget_maksimum', 'alamat', 'lokasi_penugasan', 'pic_name', 'pic_whatsapp', 'foto_path',
        'is_outside_radius', 'status_verifikasi', 'status_lokasi', 'jarak_meter', 'qr_code',
        // School-specific
        'potensi_mahasiswa', 'detail_potensi_mahasiswa', 'kesediaan_training_ai',
        // Corporate-specific
        'bidang_usaha', 'potensi_s1', 'potensi_s2', 'potensi_csr',
        // Lecturer for Training
        'dosen_id', 'dosen_pemateri',
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

            if (!$model->qr_code) {
                $model->qr_code = self::generateUniqueQrToken($model);
            }

            if (!$model->waktu) {
                $model->waktu = now()->format('H:i:s');
            }
        });

        static::created(function ($kunjungan) {
            try {
                $sales = $kunjungan->sales ?? \App\Models\User::find($kunjungan->sales_id);
                $spv = $sales?->supervisor;
                $instName = $kunjungan->nama_institusi ?? $kunjungan->sekolah?->nama ?? $kunjungan->perusahaan?->nama ?? 'Institusi';

                if ($spv && (!auth()->check() || auth()->id() !== $spv->id)) {
                    $spv->notify(new \App\Notifications\CrmActivityNotification(
                        title: "📍 Laporan Kunjungan Baru",
                        message: "Sales " . ($sales?->name ?? 'Sales') . " telah melaporkan kunjungan ke {$instName}.",
                        type: 'success',
                        link: '/spv/kunjungan',
                        icon: '📍',
                        senderName: $sales?->name,
                        senderRole: 'Sales',
                        action: 'kunjungan_created'
                    ));
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Error sending kunjungan notification: ' . $e->getMessage());
            }
        });
    }

    /**
     * Generate unique session QR token for the visit.
     */
    public static function generateUniqueQrToken(?Kunjungan $model = null): string
    {
        $uniquePart = Str::random(12);
        $datePart = now()->format('Ymd');
        return 'KNJ-QR-' . $datePart . '-' . strtoupper($uniquePart);
    }

    protected $casts = [
        'tanggal' => 'date',
        'prodi_ids' => 'array',
        'kesediaan_training_ai' => 'boolean',
        'is_verified' => 'boolean',
        'is_outside_radius' => 'boolean',
        'lat' => 'decimal:8',
        'lng' => 'decimal:8',
        'jarak_meter' => 'float',
        'budget_maksimum' => 'decimal:2',
    ];

    /**
     * Get array/collection of selected Prodis
     */
    public function getProdisListAttribute()
    {
        if (!empty($this->prodi_ids) && is_array($this->prodi_ids)) {
            return Prodi::whereIn('id', $this->prodi_ids)->get();
        }
        if ($this->prodi_id && $this->prodi) {
            return collect([$this->prodi]);
        }
        return collect([]);
    }

    /**
     * Get comma separated names of selected Prodis
     */
    public function getProdiNamesAttribute(): string
    {
        $list = $this->prodis_list;
        if ($list->isEmpty()) {
            return $this->prodi?->nama ?? '-';
        }
        return $list->pluck('nama')->implode(', ');
    }

    /**
     * Standard terminology: Potensi Mahasiswa (bukan Potensi Beasiswa)
     */
    public function getPotensiMahasiswaAttribute()
    {
        return $this->attributes['potensi_mahasiswa'] ?? $this->attributes['potensi_beasiswa'] ?? null;
    }

    public function sales()
    {
        return $this->belongsTo(User::class, 'sales_id');
    }

    public function prodi()
    {
        return $this->belongsTo(Prodi::class, 'prodi_id');
    }

    public function dosen()
    {
        return $this->belongsTo(User::class, 'dosen_id');
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

    public function prospek()
    {
        return $this->belongsTo(Prospek::class, 'tujuan_id');
    }

    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id');
    }
}
