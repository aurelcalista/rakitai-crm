<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prospek extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'type', 'category', 'pic', 'pic_phone', 'whatsapp',
        'status', 'stage_number', 'potential', 'ai_training', 'notes',
        'wilayah_id', 'sales_id', 'cs_id', 'owner_id', 'source',
        'sekolah_id', 'perusahaan_id',
        'lost_reason', 'lost_note',
        'follow_up_count', 'tahun_akademik',
    ];

    protected static function booted()
    {
        static::saving(function ($prospek) {
            if (empty($prospek->tahun_akademik)) {
                $prospek->tahun_akademik = '2027/2028';
            }
            if ($prospek->sekolah_id) {
                $sekolah = Sekolah::find($prospek->sekolah_id);
                if ($sekolah && $sekolah->sales_id) {
                    $prospek->sales_id = $sekolah->sales_id;
                }
            } elseif ($prospek->perusahaan_id) {
                $perusahaan = Perusahaan::find($prospek->perusahaan_id);
                if ($perusahaan && $perusahaan->sales_id) {
                    $prospek->sales_id = $perusahaan->sales_id;
                }
            }
        });
    }

    /**
     * 8 Status Pipeline Standar PMB & Backward-Compatibility Map
     */
    public const STAGES = [
        // 8 Status Resmi SPV P0
        'BARU'                => 1,
        'KONTAK'              => 2,
        'HANGAT'              => 3,
        'PANAS'               => 4,
        'FORMULIR'            => 5,
        'BERKAS'              => 6,
        'LUNAS'               => 7,
        'DINGIN'              => 8,

        // Legacy / Backward Compatibility
        'Baru'                => 1,
        'Cold Lead'           => 1,
        'Interested'          => 2,
        'Follow Up'           => 3,
        'Follow Up 1'         => 3,
        'Negosiasi'           => 4,
        'Beli Formulir'       => 5,
        'Pembayaran Termin 1' => 6,
        'Mendaftar'           => 7,
        'Closing'             => 7,
        'Lost'                => 8,
        'Ditolak/Batal'       => 8,
        'Ditolak / Batal'     => 8,
    ];

    public const PIPELINE_8_STAGES = [
        'BARU',
        'KONTAK',
        'HANGAT',
        'PANAS',
        'FORMULIR',
        'BERKAS',
        'LUNAS',
        'DINGIN',
    ];

    /**
     * Syarat Maba Lunas: Pembayaran Formulir + Pembayaran Termin 1 (Status 07 LUNAS)
     * Beli formulir saja TIDAK dihitung lunas.
     */
    public function isMabaLunas(): bool
    {
        $transaksis = $this->relationLoaded('transaksis')
            ? $this->transaksis
            : ($this->exists ? $this->transaksis()->get() : collect());

        $hasFormulir = $transaksis->contains('jenis', 'Beli Formulir');
        $hasTermin1  = $transaksis->contains('jenis', 'Pembayaran Termin 1');

        // Syarat mutlak: Formulir + Termin 1
        if ($hasFormulir && $hasTermin1) {
            return true;
        }

        // Jika status LUNAS / Closing dan sudah ada bukti pembayaran Termin 1
        if (in_array(strtoupper($this->status), ['LUNAS', '07 LUNAS', 'CLOSING']) && $hasTermin1) {
            return true;
        }

        return false;
    }

    public const LOST_REASONS = [
        'Tidak tertarik',
        'Tidak dapat dihubungi',
        'Membatalkan',
        'Memilih kampus lain',
        'Lainnya',
    ];

    /**
     * Check if the given user is the active Sales handler of this prospect.
     */
    public function isHandledBySales(\App\Models\User $user): bool
    {
        return $this->sales_id === $user->id;
    }

    /**
     * Check if the given user is the active CS handler of this prospect.
     */
    public function isHandledByCs(\App\Models\User $user): bool
    {
        return $this->cs_id === $user->id;
    }

    /**
     * Determine the current active handler role label.
     */
    public function activeHandlerLabel(): string
    {
        // If handed over to CS, CS is the active handler
        if ($this->cs_id && $this->cs) {
            return 'CS — ' . $this->cs->name;
        }
        if ($this->sales_id && $this->sales) {
            return 'Sales — ' . $this->sales->name;
        }
        return 'Belum Ada';
    }

    /**
     * Check if the user is the currently active handler.
     */
    public function isActiveHandler(\App\Models\User $user): bool
    {
        if ($this->cs_id) {
            return $this->isHandledByCs($user);
        }
        return $this->isHandledBySales($user);
    }

    public function wilayah()
    {
        return $this->belongsTo(Wilayah::class);
    }

    public function sekolah()
    {
        return $this->belongsTo(Sekolah::class);
    }

    public function perusahaan()
    {
        return $this->belongsTo(Perusahaan::class);
    }

    public function sales()
    {
        return $this->belongsTo(User::class, 'sales_id');
    }

    public function cs()
    {
        return $this->belongsTo(User::class, 'cs_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function followUps()
    {
        return $this->hasMany(FollowUp::class);
    }

    public function timelines()
    {
        return $this->hasMany(ProspekTimeline::class);
    }

    public function transaksis()
    {
        return $this->hasMany(Transaksi::class);
    }

    /**
     * Scope to filter prospects belonging to a specific sales user.
     */
    public function scopeForSales($query, int $salesId)
    {
        return $query->where('sales_id', $salesId);
    }

    /**
     * Scope to filter prospects belonging to any sales under a supervisor.
     */
    public function scopeForSupervisor($query, int $supervisorId)
    {
        $subordinateIds = User::where('supervisor_id', $supervisorId)
            ->where('role', 'Sales')
            ->pluck('id');

        return $query->whereIn('sales_id', $subordinateIds);
    }

    /**
     * Scope: active prospects (not Lost or Closing).
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['Lost', 'Closing']);
    }
}
