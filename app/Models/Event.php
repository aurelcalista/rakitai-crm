<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama',
        'type_id',
        'tanggal',
        'waktu_mulai',
        'waktu_selesai',
        'tanggal_mulai',
        'tanggal_selesai',
        'lokasi',
        'deskripsi',
        'eo_id',
        'status',
        'dokumentasi',
        'absen_peserta',
        'dosen_id',
        'dosen_pemateri',
        'prodi_id',
        'sekolah_id',
        'perusahaan_id',
        'qr_code',
        'academic_year_id',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (!$model->qr_code) {
                $model->qr_code = self::generateUniqueQrToken($model);
            }
            if (!$model->academic_year_id) {
                $aktif = TahunAkademik::getAktif();
                if ($aktif) {
                    $model->academic_year_id = $aktif->id;
                }
            }
        });
    }

    /**
     * Generate a unique session QR token for the event attendance.
     */
    public static function generateUniqueQrToken(?Event $model = null): string
    {
        $uniquePart = Str::random(12);
        $datePart = now()->format('Ymd');
        return 'EVT-QR-' . $datePart . '-' . strtoupper($uniquePart);
    }

    protected $casts = [
        'tanggal' => 'date',
        'waktu_mulai' => 'datetime:H:i',
        'waktu_selesai' => 'datetime:H:i',
    ];

    /**
     * Get the EO (Creator) of the event.
     */
    public function eo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'eo_id');
    }

    /**
     * Get the Dosen Pemateri of the event.
     */
    public function dosen(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dosen_id');
    }

    /**
     * Get the Prodi of the event.
     */
    public function prodi(): BelongsTo
    {
        return $this->belongsTo(Prodi::class, 'prodi_id');
    }

    /**
     * Get the Sekolah target.
     */
    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class, 'sekolah_id');
    }

    /**
     * Get the Perusahaan target.
     */
    public function perusahaan(): BelongsTo
    {
        return $this->belongsTo(Perusahaan::class, 'perusahaan_id');
    }

    /**
     * Get the master data type of the event.
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(MasterData::class, 'type_id');
    }

    /**
     * Get the SPVs assigned to this event.
     */
    public function spvs(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'event_spv', 'event_id', 'spv_id')->withTimestamps();
    }

    /**
     * Get the Sales assigned to this event.
     */
    public function sales(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'event_sales', 'event_id', 'sales_id')
            ->withPivot(['assigned_by_spv_id', 'google_event_id'])
            ->withTimestamps();
    }
}
