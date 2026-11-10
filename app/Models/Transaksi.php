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

    // Kategori transaksi resmi PMB & Legacy
    const KATEGORI_DP              = 'DP';
    const KATEGORI_PENDAFTARAN     = 'Biaya Pendaftaran';
    const KATEGORI_PELUNASAN       = 'Pelunasan';
    const KATEGORI_DP_DAFTAR       = 'DP Daftar';
    const KATEGORI_DP_PERLENGKAPAN = 'DP Perlengkapan';
    const KATEGORI_LUNAS           = 'Lunas';
    const KATEGORI_KELUAR          = 'Keluar';

    const KATEGORI_LIST = [
        self::KATEGORI_DP,
        self::KATEGORI_PENDAFTARAN,
        self::KATEGORI_PELUNASAN,
        self::KATEGORI_DP_DAFTAR,
        self::KATEGORI_DP_PERLENGKAPAN,
        self::KATEGORI_LUNAS,
        self::KATEGORI_KELUAR,
    ];

    /**
     * Normalisasi kategori pembayaran ke standar resmi PMB.
     */
    public static function normalizeCategory(?string $category): string
    {
        $cat = strtolower(trim((string) $category));
        if (in_array($cat, ['dp', 'dp perlengkapan', 'down payment', 'dp/konversi', 'termin 1'])) {
            return self::KATEGORI_DP;
        }
        if (in_array($cat, ['pendaftaran', 'biaya pendaftaran', 'dp daftar', 'beli formulir', 'formulir'])) {
            return self::KATEGORI_PENDAFTARAN;
        }
        if (in_array($cat, ['pelunasan', 'lunas', 'termin 2', 'ukt'])) {
            return self::KATEGORI_PELUNASAN;
        }
        return self::KATEGORI_PENDAFTARAN;
    }

    /**
     * Dapatkan daftar alias kategori untuk pencocokan transaksi lama & baru.
     *
     * @return array<string>
     */
    public static function getCategoryAliases(string $category): array
    {
        $normalized = self::normalizeCategory($category);
        return match ($normalized) {
            self::KATEGORI_DP => [self::KATEGORI_DP, self::KATEGORI_DP_PERLENGKAPAN, 'DP/Konversi', 'Pembayaran Termin 1'],
            self::KATEGORI_PENDAFTARAN => [self::KATEGORI_PENDAFTARAN, 'Pendaftaran', 'Beli Formulir', 'Pendaftaran PMB', self::KATEGORI_DP_DAFTAR],
            self::KATEGORI_PELUNASAN => [self::KATEGORI_PELUNASAN, self::KATEGORI_LUNAS, 'Pembayaran Termin 2', 'UKT'],
            default => [$category],
        };
    }

    protected $fillable = [
        'order_id',
        'prospek_id',
        'user_id',
        'jenis',
        'kategori',
        'nominal',
        'detail_pembayaran',
        'tanggal',
        'transaction_time',
        'notes',
        'academic_year_id',
        'metode_pembayaran',
        'payment_type',
        'payment_status',
        'fraud_status',
        'snap_token',
        'snap_redirect_url',
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
            if (empty($model->jenis)) {
                $model->jenis = match ($model->kategori) {
                    self::KATEGORI_DP => 'DP/Konversi',
                    self::KATEGORI_PELUNASAN => 'Pelunasan',
                    default => 'Beli Formulir',
                };
            }
            if (empty($model->order_id)) {
                $pid = $model->prospek_id ?: rand(100, 999);
                $model->order_id = 'PMB-UCIC-' . $pid . '-' . time() . rand(10, 99);
            }
        });

        static::created(function ($transaksi) {
            try {
                // Notifikasi resmi ke seluruh role yang berwenang (Admin, CS, HM, SPV, Sales)
                \App\Services\PaymentNotificationService::notifyAllRoles(
                    $transaksi,
                    $transaksi->payment_status === self::STATUS_VERIFIED ? 'verified' : 'pending'
                );
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Error sending transaksi notification: ' . $e->getMessage());
            }
        });
    }

    protected $casts = [
        'tanggal'          => 'datetime',
        'transaction_time' => 'datetime',
        'verified_at'      => 'datetime',
        'rejected_at'      => 'datetime',
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
        return $this->metode_detail;
    }

    /**
     * Dapatkan label metode pembayaran detail yang akurat dan mudah dibaca (mis. QRIS (Midtrans), BCA VA, dll).
     */
    public function getMetodeDetailAttribute(): string
    {
        $rawMetode = strtoupper(trim((string) $this->metode_pembayaran));
        $paymentType = strtolower(trim((string) $this->payment_type));

        return match (true) {
            str_contains($paymentType, 'qris') => 'QRIS (Midtrans)',
            str_contains($paymentType, 'gopay') => 'GoPay (Midtrans)',
            str_contains($paymentType, 'shopee') => 'ShopeePay (Midtrans)',
            str_contains($paymentType, 'echannel') || str_contains($paymentType, 'mandiri') => 'Mandiri VA (Midtrans)',
            str_contains($paymentType, 'bca') => 'BCA VA (Midtrans)',
            str_contains($paymentType, 'bni') => 'BNI VA (Midtrans)',
            str_contains($paymentType, 'bri') => 'BRI VA (Midtrans)',
            str_contains($paymentType, 'permata') => 'Permata VA (Midtrans)',
            str_contains($paymentType, 'cimb') => 'CIMB VA (Midtrans)',
            str_contains($paymentType, 'danamon') => 'Danamon VA (Midtrans)',
            str_contains($paymentType, 'credit_card') => 'Kartu Kredit (Midtrans)',
            str_contains($paymentType, 'cstore') || str_contains($paymentType, 'indomaret') || str_contains($paymentType, 'alfamart') => 'Gerai Retail (Midtrans)',
            str_contains($paymentType, 'bank_transfer') || str_contains($paymentType, 'va') => 'Virtual Account (Midtrans)',
            $rawMetode === 'MIDTRANS' && !empty($paymentType) => strtoupper($paymentType) . ' (Midtrans)',
            $rawMetode === 'MIDTRANS' => 'Midtrans (Online)',
            $rawMetode === 'BANK_TRANSFER' => 'Transfer Bank',
            $rawMetode === 'TRANSFER_MANUAL' || str_contains($rawMetode, 'MANUAL') => 'Transfer Manual',
            $rawMetode === 'OFFLINE' || str_contains($rawMetode, 'TUNAI') || str_contains($rawMetode, 'KASIR') => 'Kasir PMB / Tunai',
            isset(self::METODE_PEMBAYARAN[$this->metode_pembayaran]) => self::METODE_PEMBAYARAN[$this->metode_pembayaran],
            !empty($this->metode_pembayaran) => ucwords(str_replace('_', ' ', strtolower($this->metode_pembayaran))),
            default => 'Belum Memilih',
        };
    }

    /**
     * Dapatkan informasi badge styling untuk metode pembayaran.
     *
     * @return array{name: string, sub: string, badge_class: string, icon: string}
     */
    public function getMetodeBadgeAttribute(): array
    {
        $rawMetode = strtoupper(trim((string) $this->metode_pembayaran));
        $paymentType = strtolower(trim((string) $this->payment_type));

        if (str_contains($paymentType, 'qris')) {
            return [
                'name'        => 'QRIS',
                'sub'         => 'Midtrans',
                'badge_class' => 'bg-rose-50 text-rose-700 border-rose-200',
                'icon'        => 'qris',
            ];
        }

        if (str_contains($paymentType, 'gopay') || str_contains($paymentType, 'shopee')) {
            $name = str_contains($paymentType, 'gopay') ? 'GoPay' : 'ShopeePay';
            return [
                'name'        => $name,
                'sub'         => 'Midtrans',
                'badge_class' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                'icon'        => 'ewallet',
            ];
        }

        if (str_contains($paymentType, 'bca') || str_contains($paymentType, 'bni') || str_contains($paymentType, 'bri') || str_contains($paymentType, 'mandiri') || str_contains($paymentType, 'echannel') || str_contains($paymentType, 'permata') || str_contains($paymentType, 'cimb') || str_contains($paymentType, 'danamon')) {
            $name = match(true) {
                str_contains($paymentType, 'bca') => 'BCA VA',
                str_contains($paymentType, 'bni') => 'BNI VA',
                str_contains($paymentType, 'bri') => 'BRI VA',
                str_contains($paymentType, 'mandiri') || str_contains($paymentType, 'echannel') => 'Mandiri VA',
                str_contains($paymentType, 'permata') => 'Permata VA',
                str_contains($paymentType, 'cimb') => 'CIMB VA',
                str_contains($paymentType, 'danamon') => 'Danamon VA',
                default => 'Virtual Account',
            };
            return [
                'name'        => $name,
                'sub'         => 'Midtrans',
                'badge_class' => 'bg-blue-50 text-blue-700 border-blue-200',
                'icon'        => 'va',
            ];
        }

        if (str_contains($paymentType, 'credit_card')) {
            return [
                'name'        => 'Kartu Kredit',
                'sub'         => 'Midtrans',
                'badge_class' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                'icon'        => 'card',
            ];
        }

        if ($rawMetode === 'MIDTRANS' || str_contains($paymentType, 'bank_transfer') || str_contains($paymentType, 'va')) {
            return [
                'name'        => 'Virtual Account',
                'sub'         => 'Midtrans',
                'badge_class' => 'bg-blue-50 text-blue-700 border-blue-200',
                'icon'        => 'va',
            ];
        }

        if ($rawMetode === 'TRANSFER_MANUAL' || str_contains($rawMetode, 'MANUAL')) {
            return [
                'name'        => 'Transfer Manual',
                'sub'         => 'Rekening Kampus',
                'badge_class' => 'bg-amber-50 text-amber-800 border-amber-200',
                'icon'        => 'manual',
            ];
        }

        if ($rawMetode === 'OFFLINE' || str_contains($rawMetode, 'TUNAI') || str_contains($rawMetode, 'KASIR')) {
            return [
                'name'        => 'Kasir / Tunai',
                'sub'         => 'Offline di Kampus',
                'badge_class' => 'bg-slate-50 text-slate-700 border-slate-200',
                'icon'        => 'cash',
            ];
        }

        return [
            'name'        => !empty($this->metode_pembayaran) ? ucwords(str_replace('_', ' ', strtolower($this->metode_pembayaran))) : 'Transfer Bank',
            'sub'         => 'Sistem',
            'badge_class' => 'bg-slate-50 text-slate-600 border-slate-200',
            'icon'        => 'bank',
        ];
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
