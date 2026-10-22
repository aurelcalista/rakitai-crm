<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Transaksi extends Model
{
    use HasFactory;

    // Metode pembayaran yang tersedia
    const METODE_PEMBAYARAN = [
        'bank_transfer'   => 'Transfer Bank',
        'virtual_account' => 'Virtual Account',
        'gopay'           => 'GoPay',
        'dana'            => 'DANA',
        'shopeepay'       => 'ShopeePay',
        'tunai'           => 'Kasir PMB / Tunai',
    ];

    // Status verifikasi pembayaran
    const STATUS_PENDING  = 'pending';
    const STATUS_VERIFIED = 'verified';
    const STATUS_REJECTED = 'rejected';

    // Kategori transaksi (Requirement J)
    const KATEGORI_DP_DAFTAR       = 'DP Daftar';
    const KATEGORI_DP_PERLENGKAPAN = 'DP Perlengkapan';
    const KATEGORI_LUNAS           = 'Lunas';
    const KATEGORI_KELUAR          = 'Keluar';

    const KATEGORI_LIST = [
        self::KATEGORI_DP_DAFTAR,
        self::KATEGORI_DP_PERLENGKAPAN,
        self::KATEGORI_LUNAS,
        self::KATEGORI_KELUAR,
    ];

    protected $fillable = [
        'order_id',
        'prospek_id',
        'user_id',
        'jenis',
        'kategori',
        'nominal',
        'detail_pembayaran',
        'tanggal',
        'notes',
        'academic_year_id',
        'metode_pembayaran',
        'payment_status',
        'verified_by',
        'verified_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
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
            if (empty($model->order_id)) {
                $pid = $model->prospek_id ?: rand(100, 999);
                $model->order_id = 'PMB-UCIC-' . $pid . '-' . time() . rand(10, 99);
            }
        });

        static::created(function ($transaksi) {
            try {
                $prospek = $transaksi->prospek;
                if (!$prospek) return;

                $auth = auth()->user();
                $actorName = $auth ? "{$auth->name} ({$auth->role})" : "Sistem";
                $nominalText = 'Rp ' . number_format((float)$transaksi->nominal, 0, ',', '.');

                $sales = $prospek->sales ?? ($prospek->sales_id ? \App\Models\User::find($prospek->sales_id) : null);
                $spv = $sales?->supervisor ?? ($prospek->wilayah_id ? \App\Models\User::where('role', 'SPV')->where('wilayah_id', $prospek->wilayah_id)->first() : null);
                $cs = $prospek->cs ?? ($prospek->cs_id ? \App\Models\User::find($prospek->cs_id) : null);

                $isNeedVerification  = $transaksi->payment_status === self::STATUS_PENDING;

                // 1. Notifikasi ke Sales (hanya jika yang input bukan Sales pemilik)
                if ($sales && (!$auth || $auth->id !== $sales->id)) {
                    $salesMsg = $isNeedVerification
                        ? "Transaksi {$transaksi->jenis} sebesar {$nominalText} untuk '{$prospek->name}' sedang menunggu verifikasi CS."
                        : "Pembayaran sebesar {$nominalText} ({$transaksi->jenis}) tercatat untuk prospek '{$prospek->name}' oleh {$actorName}.";
                    $sales->notify(new \App\Notifications\CrmActivityNotification(
                        title: $isNeedVerification ? "Menunggu Verifikasi CS" : "Pembayaran Masuk",
                        message: $salesMsg,
                        type: $isNeedVerification ? 'warning' : 'success',
                        link: '/prospek',
                        icon: '',
                        senderName: $auth?->name,
                        senderRole: $auth?->role,
                        action: 'transaksi_created_sales'
                    ));
                }

                // 2. Notifikasi ke SPV
                if ($spv && (!$auth || $auth->id !== $spv->id)) {
                    $spv->notify(new \App\Notifications\CrmActivityNotification(
                        title: "Pembayaran Masuk Tim",
                        message: "Pembayaran {$nominalText} ({$transaksi->jenis}) diterima untuk '{$prospek->name}' (Sales: " . ($sales?->name ?? '-') . ").",
                        type: 'success',
                        link: '/spv/pipeline',
                        icon: '',
                        senderName: $auth?->name,
                        senderRole: $auth?->role,
                        action: 'transaksi_created_spv'
                    ));
                }

                // 3. Notifikasi ke CS — seluruh CS aktif menerima notifikasi antrean verifikasi pembayaran
                if ($isNeedVerification) {
                    $csList = \App\Models\User::where('role', 'CS')->where('status', 'Aktif')->get();
                    if ($csList->isEmpty()) {
                        $csList = \App\Models\User::where('role', 'CS')->get();
                    }
                } else {
                    $csList = $cs ? collect([$cs]) : \App\Models\User::where('role', 'CS')->get();
                }

                $metodeName = self::METODE_PEMBAYARAN[$transaksi->metode_pembayaran] ?? ($transaksi->metode_pembayaran ?: 'Transfer Bank / Online');
                $csMsg = $isNeedVerification
                    ? "Ada pembayaran {$transaksi->jenis} yang perlu diverifikasi dari Sales " . ($sales?->name ?? $actorName) . " untuk calon mahasiswa '{$prospek->name}', nominal {$nominalText} via {$metodeName}."
                    : "Pembayaran {$nominalText} ({$transaksi->jenis}) tervalidasi untuk prospek '{$prospek->name}'.";

                foreach ($csList as $targetCs) {
                    if (!$auth || $auth->id !== $targetCs->id) {
                        $targetCs->notify(new \App\Notifications\CrmActivityNotification(
                            title: $isNeedVerification ? "Verifikasi Pembayaran Diperlukan" : "Pembayaran Siswa CS",
                            message: $csMsg,
                            type: $isNeedVerification ? 'warning' : 'success',
                            link: route('cs.verifikasi.index'),
                            icon: '',
                            senderName: $auth?->name,
                            senderRole: $auth?->role,
                            action: $isNeedVerification ? 'verifikasi_pembayaran_cs' : 'transaksi_created_cs'
                        ));
                    }
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Error sending transaksi notification: ' . $e->getMessage());
            }
        });
    }

    protected $casts = [
        'tanggal'     => 'datetime',
        'verified_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    /**
     * Apakah transaksi ini perlu verifikasi CS sebelum bisa Closing?
     */
    public function needsVerification(): bool
    {
        return $this->payment_status === self::STATUS_PENDING;
    }

    /**
     * Apakah transaksi ini sudah terverifikasi (atau lama tanpa metode)?
     */
    public function isVerified(): bool
    {
        return $this->payment_status === self::STATUS_VERIFIED;
    }

    /**
     * Label metode pembayaran yang mudah dibaca.
     */
    public function getMetodeLabelAttribute(): string
    {
        return self::METODE_PEMBAYARAN[$this->metode_pembayaran] ?? 'Tidak diketahui';
    }

    // ─── Relationships ──────────────────────────────────────────────

    public function prospek()
    {
        return $this->belongsTo(Prospek::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function rejecter()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /**
     * Mengembalikan checklist seluruh 5 komponen jenis transaksi untuk prospek ini:
     * Pendaftaran, Perlengkapan, Gedung, DP/Konversi, UKT.
     * Mengindikasikan komponen mana yang sudah dicentang/dibayar dan mana yang belum.
     *
     * @return array
     */
    public function getComponentsChecklist(): array
    {
        $allTrx = $this->prospek 
            ? ($this->prospek->relationLoaded('transaksis') ? $this->prospek->transaksis : $this->prospek->transaksis()->get())
            : collect([$this]);

        $definitions = [
            'Pendaftaran' => [
                'key'      => 'Pendaftaran',
                'label'    => 'Pendaftaran / Formulir',
                'short'    => 'Daftar',
                'aliases'  => ['Pendaftaran', 'Beli Formulir'],
            ],
            'Perlengkapan' => [
                'key'      => 'Perlengkapan',
                'label'    => 'Perlengkapan',
                'short'    => 'Perlengkapan',
                'aliases'  => ['Perlengkapan'],
            ],
            'Gedung' => [
                'key'      => 'Gedung',
                'label'    => 'Gedung (Sarpras)',
                'short'    => 'Gedung',
                'aliases'  => ['Gedung'],
            ],
            'DP/Konversi' => [
                'key'      => 'DP/Konversi',
                'label'    => 'DP / Konversi',
                'short'    => 'DP/Konv',
                'aliases'  => ['DP/Konversi', 'DP', 'Pembayaran Termin 1'],
            ],
            'UKT' => [
                'key'      => 'UKT',
                'label'    => 'UKT / Semester',
                'short'    => 'UKT',
                'aliases'  => ['UKT'],
            ],
        ];

        $results = [];
        foreach ($definitions as $k => $def) {
            $matched = $allTrx->first(function ($t) use ($def) {
                return in_array($t->jenis, $def['aliases']);
            });

            // Khusus Gedung: di UCIC biaya gedung selalu FREE (Rp 0, bukan tunggakan).
            // Selalu dianggap sah / diverifikasi (status green/ijo) dan tidak boleh merah/ditolak.
            if ($k === 'Gedung') {
                $results[$k] = [
                    'key'               => $k,
                    'label'             => $def['label'],
                    'short'             => $def['short'],
                    'is_checked'        => true,
                    'status'            => self::STATUS_VERIFIED,
                    'status_label'      => 'FREE (Rp 0)',
                    'nominal'           => 0,
                    'nominal_formatted' => 'FREE (Rp 0)',
                    'is_current'        => $matched ? ($matched->id === $this->id) : false,
                    'is_free'           => true,
                    'transaction_id'    => $matched?->id,
                ];
                continue;
            }

            if ($matched) {
                $status = $matched->payment_status ?: self::STATUS_VERIFIED;
                $results[$k] = [
                    'key'               => $k,
                    'label'             => $def['label'],
                    'short'             => $def['short'],
                    'is_checked'        => true,
                    'status'            => $status,
                    'status_label'      => $status === self::STATUS_VERIFIED ? 'Diverifikasi' : ($status === self::STATUS_PENDING ? 'Menunggu CS' : 'Ditolak CS'),
                    'nominal'           => (float) $matched->nominal,
                    'nominal_formatted' => 'Rp ' . number_format((float) $matched->nominal, 0, ',', '.'),
                    'is_current'        => $matched->id === $this->id,
                    'is_free'           => false,
                    'transaction_id'    => $matched->id,
                ];
            } else {
                $results[$k] = [
                    'key'               => $k,
                    'label'             => $def['label'],
                    'short'             => $def['short'],
                    'is_checked'        => false,
                    'status'            => 'unpaid',
                    'status_label'      => 'Belum Ada',
                    'nominal'           => 0,
                    'nominal_formatted' => '-',
                    'is_current'        => false,
                    'is_free'           => false,
                    'transaction_id'    => null,
                ];
            }
        }

        return $results;
    }

    /**
     * Konversi checklist ke bentuk array numerik sederhana untuk JSON modal Alpine.js.
     */
    public function getChecklistArrayForJson(): array
    {
        return array_values($this->getComponentsChecklist());
    }
}
