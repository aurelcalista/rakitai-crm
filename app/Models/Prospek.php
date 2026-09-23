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
        'sekolah_id', 'perusahaan_id', 'prodi_id',
        'lost_reason', 'lost_note',
        'follow_up_count', 'active_follow_up_count',
        'handover_at', 'academic_year_id', 'tahun_akademik', 'kelas',
    ];

    protected $casts = [
        'handover_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::saving(function ($prospek) {
            // Keep the legacy string column in sync with the active TA when creating
            if (empty($prospek->tahun_akademik)) {
                $aktivNama = \App\Services\AkademikService::getAktifNama();
                if ($aktivNama) {
                    $prospek->tahun_akademik = $aktivNama;
                }
            }
            if (empty($prospek->academic_year_id)) {
                $aktifTa = \App\Models\TahunAkademik::getAktif();
                if ($aktifTa) {
                    $prospek->academic_year_id = $aktifTa->id;
                }
            }

            if (empty($prospek->sales_id)) {
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
            }

            // Auto-handover to CS when status enters handover stages ('FORMULIR', 'BERKAS', 'LUNAS')
            if (in_array($prospek->status, ['FORMULIR', 'BERKAS', 'LUNAS'])) {
                if (is_null($prospek->cs_id) || empty($prospek->handover_at)) {
                    $cs = \App\Models\User::where('role', 'CS')
                        ->when($prospek->wilayah_id, fn($q) => $q->where('wilayah_id', $prospek->wilayah_id))
                        ->where('status', 'Aktif')
                        ->first();
                        
                    if (!$cs) {
                        $cs = \App\Models\User::where('role', 'CS')->where('status', 'Aktif')->first();
                    }
                    
                    if ($cs) {
                        $prospek->cs_id = $cs->id;
                        $prospek->active_follow_up_count = 0; // Reset follow-up for CS
                        $prospek->handover_at = now();
                    }
                }
            }
        });

        static::creating(function ($model) {
            if (!$model->academic_year_id) {
                $aktif = TahunAkademik::getAktif();
                if ($aktif) {
                    $model->academic_year_id = $aktif->id;
                }
            }
        });

        static::created(function ($prospek) {
            try {
                $auth = auth()->user();
                $actorName = $auth ? "{$auth->name} ({$auth->role})" : "Sistem";

                // Notifikasi ke Sales jika prospek di-assign ke Sales oleh user lain (SPV/Admin/dll)
                if ($prospek->sales_id && (!$auth || $auth->id !== (int)$prospek->sales_id)) {
                    $sales = \App\Models\User::find($prospek->sales_id);
                    if ($sales) {
                        $sekolahName = $prospek->sekolah?->nama ?? $prospek->perusahaan?->nama ?? '';
                        $infoText = $sekolahName ? " ({$sekolahName})" : '';
                        $sales->notify(new \App\Notifications\CrmActivityNotification(
                            title: "👤 Prospek Baru Ditugaskan",
                            message: "{$actorName} menugaskan prospek baru: {$prospek->name}{$infoText}.",
                            type: 'info',
                            link: '/prospek',
                            icon: '👤',
                            senderName: $auth?->name,
                            senderRole: $auth?->role,
                            action: 'prospek_assigned'
                        ));
                    }
                }

                // Notifikasi ke CS jika prospek langsung di-assign ke CS
                if ($prospek->cs_id && (!$auth || $auth->id !== (int)$prospek->cs_id)) {
                    $cs = \App\Models\User::find($prospek->cs_id);
                    if ($cs) {
                        $cs->notify(new \App\Notifications\CrmActivityNotification(
                            title: "📋 Prospek Baru (CS)",
                            message: "{$actorName} menugaskan prospek baru: {$prospek->name}.",
                            type: 'info',
                            link: '/prospek',
                            icon: '📋',
                            senderName: $auth?->name,
                            senderRole: $auth?->role,
                            action: 'prospek_assigned_cs'
                        ));
                    }
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Error sending prospek created notification: ' . $e->getMessage());
            }
        });

        static::updated(function ($prospek) {
            try {
                $auth = auth()->user();
                $actorName = $auth ? $auth->name : 'Sistem';

                // Log handover to Timeline if it just happened
                if ($prospek->wasChanged('cs_id') && $prospek->cs_id) {
                    $cs = \App\Models\User::find($prospek->cs_id);
                    \App\Models\ProspekTimeline::create([
                        'prospek_id' => $prospek->id,
                        'user_id' => $cs?->id,
                        'title' => 'Handover',
                        'notes' => 'Prospek dialihkan ke CS: ' . ($cs?->name ?? 'CS Staff'),
                        'time' => now(),
                    ]);

                    // 1. Notifikasi ke CS penerima pelimpahan
                    if ($cs && (!$auth || $auth->id !== $cs->id)) {
                        $cs->notify(new \App\Notifications\CrmActivityNotification(
                            title: "📋 Pelimpahan Prospek (Handover CS)",
                            message: "Prospek '{$prospek->name}' telah dilimpahkan ke Anda untuk tahapan formulir/pendaftaran.",
                            type: 'warning',
                            link: '/prospek',
                            icon: '📋',
                            senderName: $auth?->name,
                            senderRole: $auth?->role,
                            action: 'cs_handover'
                        ));
                    }

                    // 2. Notifikasi ke SPV yang membawahi Sales prospek ini
                    $sales = $prospek->sales ?? \App\Models\User::find($prospek->sales_id);
                    $spv = $sales?->supervisor;
                    if ($spv && (!$auth || $auth->id !== $spv->id)) {
                        $spv->notify(new \App\Notifications\CrmActivityNotification(
                            title: "✨ Handover Prospek ke CS",
                            message: "Prospek '{$prospek->name}' (" . ($sales?->name ?? 'Sales') . ") masuk tahap Formulir dan dilimpahkan ke CS " . ($cs?->name ?? 'Staff CS') . ".",
                            type: 'info',
                            link: '/spv/prospek',
                            icon: '✨',
                            senderName: $sales?->name,
                            senderRole: 'Sales',
                            action: 'cs_handover_spv'
                        ));
                    }
                }

                // Notifikasi pengalihan sales_id ke Sales baru
                if ($prospek->wasChanged('sales_id') && $prospek->sales_id) {
                    $newSales = \App\Models\User::find($prospek->sales_id);
                    if ($newSales && (!$auth || $auth->id !== $newSales->id)) {
                        $newSales->notify(new \App\Notifications\CrmActivityNotification(
                            title: "👤 Pengalihan Prospek",
                            message: "Prospek '{$prospek->name}' telah dialihkan kepada Anda oleh {$actorName}.",
                            type: 'info',
                            link: '/prospek',
                            icon: '👤',
                            senderName: $auth?->name,
                            senderRole: $auth?->role,
                            action: 'prospek_reassigned'
                        ));
                    }
                }

                // Notifikasi Closing / LUNAS ke SPV & Head of Marketing (HM)
                if ($prospek->wasChanged('status') && strtoupper((string)$prospek->status) === 'LUNAS') {
                    $sales = $prospek->sales ?? \App\Models\User::find($prospek->sales_id);
                    $spv = $sales?->supervisor ?? ($prospek->wilayah_id ? \App\Models\User::where('role', 'SPV')->where('wilayah_id', $prospek->wilayah_id)->first() : null);

                    // Notifikasi ke SPV
                    if ($spv && (!$auth || $auth->id !== $spv->id)) {
                        $spv->notify(new \App\Notifications\CrmActivityNotification(
                            title: "🎉 Closing Berhasil!",
                            message: "Selamat! Prospek '{$prospek->name}' berhasil LUNAS melalui {$actorName}.",
                            type: 'success',
                            link: '/spv/pipeline',
                            icon: '🎓',
                            senderName: $actorName,
                            senderRole: $auth?->role ?? 'Sales',
                            action: 'closing_lunas'
                        ));
                    }

                    // Notifikasi ke semua HM
                    $hms = \App\Models\User::where('role', 'HM')->where('status', 'Aktif')->get();
                    foreach ($hms as $hm) {
                        if (!$auth || $auth->id !== $hm->id) {
                            $hm->notify(new \App\Notifications\CrmActivityNotification(
                                title: "🎉 Closing PMB Baru!",
                                message: "{$actorName} berhasil closing calon mahasiswa '{$prospek->name}' (LUNAS).",
                                type: 'success',
                                link: '/hm/pipeline',
                                icon: '🎓',
                                senderName: $actorName,
                                senderRole: $auth?->role ?? 'Sales',
                                action: 'closing_lunas_hm'
                            ));
                        }
                    }
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Error sending prospek updated notification: ' . $e->getMessage());
            }
        });
    }

    public const ACTIVE_STAGES = [
        'BARU',
        'KONTAK',
        'HANGAT',
        'PANAS',
        'FORMULIR',
        'BERKAS',
        'LUNAS',
        'DINGIN',
    ];

    public const PIPELINE_8_STAGES = self::ACTIVE_STAGES;

    /**
     * 10 Opsi Baku Dropdown Sumber Informasi Resmi PRD Bab 8.1.1
     */
    public const SOURCES = [
        'Teman/Keluarga/Saudara',
        'Sekolah',
        'Sosial Media (Facebook, Instagram, X)',
        'Website CIC',
        'Brosur/Poster',
        'Sekretariat Kampus (Walk-in)',
        'Pameran/Expo/University Day',
        'Acara Kampus',
        'MGBK/Miniclass',
        'Spanduk/Baliho',
        'Lainnya',
    ];

    /**
     * 8 Status Pipeline Standar PMB & Backward-Compatibility Map
     */
    public const STAGES = [
        // 8 Pipeline Wajib Resmi PMB TA 2027/2028
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
        'Lead In'             => 1,
        'Cold Lead'           => 1,
        'Interested'          => 2,
        'Follow Up 1'         => 2,
        'Follow Up'           => 3,
        'Warm Lead'           => 3,
        'Hot Lead'            => 4,
        'Negosiasi'           => 4,
        'Beli Formulir'       => 5,
        'Mendaftar'           => 5,
        'Lulus Tes'           => 6,
        'Pembayaran Termin 1' => 7,
        'Closing (Lunas)'     => 7,
        'Closing'             => 7,
        'Lost'                => 8,
        'Ditolak/Batal'       => 8,
        'Ditolak / Batal'     => 8,
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

        // Jika status LUNAS dan sudah ada bukti pembayaran Termin 1
        if (strtoupper($this->status) === 'LUNAS' && $hasTermin1) {
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

    public function prodi()
    {
        return $this->belongsTo(Prodi::class, 'prodi_id');
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
     * Get SLA status for CS Handover.
     */
    public function getSlaStatusAttribute()
    {
        if (!$this->cs_id || !$this->handover_at) {
            return 'N/A';
        }

        // Cek follow-up pertama dari CS
        $firstCsFollowUp = $this->followUps()
            ->where('sales_id', $this->cs_id)
            ->orderBy('created_at', 'asc')
            ->first();

        if ($firstCsFollowUp) {
            // Cek apakah waktu follow-up <= 2 jam (120 menit) dari handover
            $diffMinutes = $this->handover_at->diffInMinutes($firstCsFollowUp->created_at);
            return $diffMinutes <= 120 ? 'Sesuai SLA' : 'Terlambat';
        }

        // Belum difollow-up, cek apakah sudah lewat 2 jam
        $diffMinutes = $this->handover_at->diffInMinutes(now());
        return $diffMinutes <= 120 ? 'Dalam SLA' : 'Terlambat';
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
        return $query->whereNotIn('status', ['DINGIN', 'LUNAS']);
    }
}
