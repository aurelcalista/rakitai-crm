<?php

namespace App\Services;

use App\Models\Prospek;
use App\Models\Transaksi;
use App\Models\Voucher;
use App\Models\VoucherUsage;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class VoucherDiscountService
{
    public const VERIFIED_STATUSES = [
        Transaksi::STATUS_VERIFIED,
        'Paid',
        'paid',
        'verified',
        'settlement',
        'capture',
        'success',
    ];

    /**
     * Hitung nominal potongan & total DPP setelah diskon secara otoritatif di backend.
     * Formula:
     * - Potongan DPP = Nominal DPP * Persentase Diskon / 100
     * - Total DPP = Nominal DPP - Potongan DPP
     *
     * @return array{discount_amount: int, total_after_discount: int}
     */
    public static function calculateDiscount(int $nominalDpp, float $percentage): array
    {
        $nominalDpp = max(0, $nominalDpp);
        $percentage = max(0.0, min(100.0, $percentage));

        $discountAmount = (int) round(($nominalDpp * $percentage) / 100);
        $discountAmount = max(0, min($nominalDpp, $discountAmount));
        $finalAmount = max(0, $nominalDpp - $discountAmount);

        return [
            'discount_amount'      => $discountAmount,
            'total_after_discount' => $finalAmount,
        ];
    }

    /**
     * Ambil nominal DPP dasar dari Prodi pilihan CAMABA.
     */
    public static function getBaseDppPrice(?Prospek $prospek): int
    {
        $prodi = $prospek?->prodi;
        return ($prodi && isset($prodi->biaya_dpp)) ? (int) $prodi->biaya_dpp : 500000;
    }

    /**
     * Hitung total nominal DPP yang sudah dibayar & terverifikasi (termasuk pembayaran sebagian).
     */
    public static function getVerifiedDppPaidAmount(?Prospek $prospek): int
    {
        if (!$prospek) {
            return 0;
        }

        $baseDpp = self::getBaseDppPrice($prospek);
        $verifiedTransaksis = $prospek->relationLoaded('transaksis')
            ? $prospek->transaksis->filter(fn($t) => in_array(strtolower((string) $t->payment_status), array_map('strtolower', self::VERIFIED_STATUSES), true))
            : $prospek->transaksis()->with('voucherUsage')->whereIn('payment_status', self::VERIFIED_STATUSES)->get();

        $paid = 0;

        foreach ($verifiedTransaksis as $vt) {
            $detail = (string) $vt->detail_pembayaran;
            $jenis = (string) $vt->jenis;
            $cat = (string) $vt->kategori;
            $nom = (int) round((float) $vt->nominal);

            $hasPerlengkapan = str_contains($detail, 'Perlengkapan') || $jenis === 'Perlengkapan';
            $hasPendaftaran  = str_contains($detail, 'Pendaftaran') || str_contains($detail, 'Formulir')
                || (empty($detail) && in_array($jenis, ['Beli Formulir', 'Pendaftaran', 'Biaya Pendaftaran'], true));
            $hasDpp          = str_contains($detail, 'DPP') || str_contains($detail, 'Pengembangan Pendidikan') || $jenis === 'DPP';
            $hasGedung       = str_contains($detail, 'Gedung') || str_contains($detail, 'Uang Gedung') || $jenis === 'Gedung';
            $hasUkt          = str_contains($detail, 'UKT') || str_contains($detail, 'Kuliah Tunggal') || $jenis === 'UKT';
            $hasOneTime      = $hasPerlengkapan || $hasPendaftaran || $hasDpp || $hasGedung;

            $isDp = (in_array($cat, Transaksi::getCategoryAliases(Transaksi::KATEGORI_DP), true)
                || str_contains($detail, 'Down Payment')
                || (str_contains($detail, 'DP') && !str_contains($detail, 'DPP') && !str_contains($detail, 'DP Daftar'))
                || $jenis === 'DP/Konversi') && !$hasOneTime && !$hasUkt && $jenis !== 'Beli Formulir';

            $isPelunasan = (in_array($cat, Transaksi::getCategoryAliases(Transaksi::KATEGORI_PELUNASAN), true)
                || str_contains($detail, 'Pelunasan')
                || $jenis === 'Pelunasan'
                || $hasUkt) && !$hasOneTime && $jenis !== 'Beli Formulir';

            if ($isDp || $isPelunasan) {
                continue;
            }

            if ($hasDpp) {
                $otherCount = ($hasPerlengkapan ? 1 : 0) + ($hasPendaftaran ? 1 : 0) + ($hasGedung ? 1 : 0);
                if ($otherCount === 0) {
                    $paid += $nom;
                } else {
                    $usage = $vt->relationLoaded('voucherUsage') ? $vt->voucherUsage : $vt->voucherUsage()->first();
                    if ($usage && $usage->dpp_after_discount !== null) {
                        $paid += (int) $usage->dpp_after_discount;
                    } elseif (preg_match('/(?:DPP|Pengembangan Pendidikan)[^\n]*?Rp\s*([\d\.]+)/i', $detail, $m)) {
                        $paid += (int) str_replace('.', '', $m[1]);
                    } else {
                        $paid += $baseDpp;
                    }
                }
            } elseif (empty($detail) && !in_array($jenis, ['Perlengkapan', 'Pendaftaran', 'Gedung', 'UKT'], true)) {
                if (in_array($nom, [500000, 800000, 1100000, 1400000], true)) {
                    $paid += 500000;
                }
            }
        }

        return $paid;
    }

    /**
     * Ambil riwayat penggunaan voucher aktif (used atau applied) untuk prospek ini.
     * Menjaga konsistensi bila voucher yang belum checkout dinonaktifkan/kedaluwarsa atau transaksi ditolak.
     */
    public static function getEffectiveDppUsage(?Prospek $prospek): ?VoucherUsage
    {
        if (!$prospek) {
            return null;
        }

        $used = VoucherUsage::with(['voucher', 'transaksi'])
            ->where('prospek_id', $prospek->id)
            ->where('status', VoucherUsage::STATUS_USED)
            ->latest('id')
            ->first();

        if ($used) {
            return $used;
        }

        $appliedList = VoucherUsage::with(['voucher', 'transaksi'])
            ->where('prospek_id', $prospek->id)
            ->where('status', VoucherUsage::STATUS_APPLIED)
            ->orderByDesc('id')
            ->get();

        foreach ($appliedList as $applied) {
            if ($applied->transaksi) {
                $status = strtolower((string) $applied->transaksi->payment_status);
                if (in_array($status, array_map('strtolower', self::VERIFIED_STATUSES), true)) {
                    $applied->update([
                        'status'  => VoucherUsage::STATUS_USED,
                        'used_at' => $applied->used_at ?? now(),
                    ]);
                    return $applied->fresh(['voucher', 'transaksi']);
                }

                $rejectedStatuses = array_map('strtolower', [
                    Transaksi::STATUS_REJECTED, 'Failed', 'failed', 'rejected', 'deny', 'expire', 'cancel', 'batal',
                ]);
                if (in_array($status, $rejectedStatuses, true)) {
                    $applied->update(['status' => VoucherUsage::STATUS_CANCELLED]);
                    continue;
                }
            } else {
                if (!$applied->voucher || !$applied->voucher->isUsable()) {
                    $applied->update(['status' => VoucherUsage::STATUS_CANCELLED]);
                    continue;
                }
            }

            return $applied;
        }

        return null;
    }

    /**
     * Ringkasan lengkap tagihan DPP (harga dasar, pembayaran sebagian, diskon voucher, sisa wajib bayar).
     *
     * @return array{
     *     base_price: int,
     *     paid_partial: int,
     *     remaining_before_discount: int,
     *     active_usage: ?VoucherUsage,
     *     discount_amount: int,
     *     remaining_payable: int,
     *     is_paid: bool
     * }
     */
    public static function getDppSummary(?Prospek $prospek): array
    {
        $basePrice = self::getBaseDppPrice($prospek);
        $paidPartial = self::getVerifiedDppPaidAmount($prospek);
        $remainingBeforeDiscount = max(0, $basePrice - $paidPartial);

        $usage = self::getEffectiveDppUsage($prospek);
        $discountAmount = $usage ? (int) $usage->discount_amount : 0;
        $discountAmount = max(0, min($basePrice, $discountAmount));

        $remainingPayable = max(0, $basePrice - $discountAmount - $paidPartial);
        $isPaid = ($basePrice <= 0)
            ? true
            : ($remainingPayable <= 0 && ($paidPartial > 0 || ($usage && $usage->status === VoucherUsage::STATUS_USED)));

        return [
            'base_price'                => $basePrice,
            'paid_partial'              => $paidPartial,
            'remaining_before_discount' => $remainingBeforeDiscount,
            'active_usage'              => $usage,
            'discount_amount'           => $discountAmount,
            'remaining_payable'         => $remainingPayable,
            'is_paid'                   => $isPaid,
        ];
    }

    /**
     * Ambil nominal potongan DPP aktif/terpakai untuk pengurangan kewajiban total (anti-pengurangan ganda).
     */
    public static function getActiveOrUsedDiscountAmount(?Prospek $prospek): int
    {
        $summary = self::getDppSummary($prospek);
        return (int) $summary['discount_amount'];
    }

    /**
     * Validasi dan terapkan kode voucher ke tagihan DPP CAMABA (otoritatif di backend).
     *
     * @throws InvalidArgumentException
     */
    public static function applyVoucher(Prospek $prospek, string $rawCode): VoucherUsage
    {
        $normalizedCode = strtoupper(trim($rawCode));
        if ($normalizedCode === '') {
            throw new InvalidArgumentException('Silakan masukkan kode voucher terlebih dahulu.');
        }

        return DB::transaction(function () use ($prospek, $normalizedCode) {
            $lockedProspek = Prospek::with(['prodi', 'transaksis'])
                ->where('id', $prospek->id)
                ->lockForUpdate()
                ->firstOrFail();

            // 1. Pastikan CAMABA sudah mencapai tahap pembayaran DPP (Pendaftaran & Perlengkapan lunas)
            $gate = MidtransService::getSequentialGateState($lockedProspek);
            if (empty($gate['unlocked']['dpp'])) {
                throw new InvalidArgumentException('Voucher diskon hanya dapat digunakan setelah Anda mencapai tahap pembayaran DPP (Biaya Pendaftaran dan Biaya Perlengkapan lunas).');
            }

            // 2. Pastikan DPP belum lunas & masih memiliki sisa tagihan
            $summary = self::getDppSummary($lockedProspek);
            if (!empty($summary['is_paid']) || $summary['remaining_before_discount'] <= 0) {
                throw new InvalidArgumentException('Biaya DPP Anda sudah lunas atau gratis (Rp 0), sehingga voucher tidak dapat digunakan.');
            }

            // 3. Validasi kode voucher dari database
            $voucher = Voucher::whereRaw('UPPER(code) = ?', [$normalizedCode])
                ->lockForUpdate()
                ->first();

            if (!$voucher) {
                throw new InvalidArgumentException('Kode voucher tidak valid atau tidak ditemukan.');
            }

            if (!$voucher->is_active) {
                throw new InvalidArgumentException("Voucher '{$voucher->code}' sedang tidak aktif dan tidak dapat digunakan.");
            }

            if ($voucher->isExpired()) {
                throw new InvalidArgumentException("Kode voucher '{$voucher->code}' sudah kedaluwarsa.");
            }

            // 4. Cegah penggunaan voucher ganda / berulang oleh CAMABA yang sama
            $alreadyUsedAny = VoucherUsage::where('prospek_id', $lockedProspek->id)
                ->where('status', VoucherUsage::STATUS_USED)
                ->lockForUpdate()
                ->first();

            if ($alreadyUsedAny) {
                if ((int) $alreadyUsedAny->voucher_id === (int) $voucher->id) {
                    throw new InvalidArgumentException("Kode voucher '{$voucher->code}' sudah pernah Anda gunakan sebelumnya.");
                }
                throw new InvalidArgumentException('Anda sudah menggunakan voucher diskon pada pembayaran DPP sebelumnya dan tidak dapat menggunakan voucher lagi.');
            }

            $alreadyAppliedSame = VoucherUsage::where('prospek_id', $lockedProspek->id)
                ->where('voucher_id', $voucher->id)
                ->where('status', VoucherUsage::STATUS_APPLIED)
                ->lockForUpdate()
                ->first();

            if ($alreadyAppliedSame) {
                throw new InvalidArgumentException("Voucher '{$voucher->code}' sudah diterapkan pada tagihan DPP Anda.");
            }

            // Batalkan voucher lain yang hanya berstatus 'applied' (belum terbayar) agar tidak menumpuk
            VoucherUsage::where('prospek_id', $lockedProspek->id)
                ->where('status', VoucherUsage::STATUS_APPLIED)
                ->update(['status' => VoucherUsage::STATUS_CANCELLED]);

            // 5. Hitung potongan dari sisa tagihan DPP sebelum diskon
            $baseBeforeDiscount = (int) $summary['remaining_before_discount'];
            $calc = self::calculateDiscount($baseBeforeDiscount, (float) $voucher->discount_percentage);

            return VoucherUsage::create([
                'voucher_id'          => $voucher->id,
                'prospek_id'          => $lockedProspek->id,
                'voucher_code'        => $voucher->code,
                'voucher_name'        => $voucher->name,
                'discount_percentage' => (float) $voucher->discount_percentage,
                'dpp_before_discount' => $baseBeforeDiscount,
                'discount_amount'     => $calc['discount_amount'],
                'dpp_after_discount'  => $calc['total_after_discount'],
                'status'              => VoucherUsage::STATUS_APPLIED,
            ]);
        });
    }

    /**
     * Lepas voucher yang baru berstatus 'applied' (belum terbayar) dari tagihan DPP CAMABA.
     *
     * @throws InvalidArgumentException
     */
    public static function removeVoucher(Prospek $prospek): bool
    {
        return DB::transaction(function () use ($prospek) {
            $used = VoucherUsage::where('prospek_id', $prospek->id)
                ->where('status', VoucherUsage::STATUS_USED)
                ->exists();

            if ($used) {
                throw new InvalidArgumentException('Voucher yang sudah digunakan pada transaksi lunas tidak dapat dibatalkan.');
            }

            $updated = VoucherUsage::where('prospek_id', $prospek->id)
                ->where('status', VoucherUsage::STATUS_APPLIED)
                ->update(['status' => VoucherUsage::STATUS_CANCELLED]);

            return $updated > 0;
        });
    }

    /**
     * Validasi ulang voucher berstatus 'applied' saat CAMABA melakukan checkout pembayaran DPP.
     *
     * @throws InvalidArgumentException
     */
    public static function validateActiveAppliedVoucherForCheckout(Prospek $prospek): ?VoucherUsage
    {
        $usage = self::getEffectiveDppUsage($prospek);
        if (!$usage || $usage->status !== VoucherUsage::STATUS_APPLIED) {
            return null;
        }

        if ($usage->transaksi_id === null) {
            $voucher = $usage->voucher()->first();
            if (!$voucher || !$voucher->isUsable()) {
                $usage->update(['status' => VoucherUsage::STATUS_CANCELLED]);
                throw new InvalidArgumentException("Voucher '{$usage->voucher_code}' sudah tidak aktif atau kedaluwarsa.");
            }
        }

        return $usage;
    }

    /**
     * Hubungkan transaksi checkout yang mencakup item DPP ke snapshot penggunaan voucher.
     */
    public static function attachTransaksiToActiveUsage(Prospek $prospek, Transaksi $transaksi): ?VoucherUsage
    {
        $usage = VoucherUsage::where('prospek_id', $prospek->id)
            ->where('status', VoucherUsage::STATUS_APPLIED)
            ->latest('id')
            ->first();

        if (!$usage) {
            return null;
        }

        $usage->update(['transaksi_id' => $transaksi->id]);
        return $usage->fresh();
    }

    /**
     * Sinkronisasi status VoucherUsage ketika payment_status pada Transaksi berubah.
     */
    public static function syncUsageForTransaksi(Transaksi $transaksi): void
    {
        $usages = VoucherUsage::where('transaksi_id', $transaksi->id)->get();
        if ($usages->isEmpty()) {
            return;
        }

        $statusLower = strtolower((string) $transaksi->payment_status);
        $isVerified = in_array($statusLower, array_map('strtolower', self::VERIFIED_STATUSES), true);
        $isRejected = in_array($statusLower, [
            Transaksi::STATUS_REJECTED, 'failed', 'rejected', 'deny', 'expire', 'cancel', 'batal',
        ], true);

        foreach ($usages as $usage) {
            if ($isVerified) {
                $usage->update([
                    'status'  => VoucherUsage::STATUS_USED,
                    'used_at' => $usage->used_at ?? now(),
                ]);
            } elseif ($isRejected && $usage->status === VoucherUsage::STATUS_APPLIED) {
                $usage->update([
                    'status' => VoucherUsage::STATUS_CANCELLED,
                ]);
            }
        }
    }

    /**
     * Format ringkasan voucher untuk respons API & UI.
     *
     * @return array<string, mixed>|null
     */
    public static function formatUsageForDisplay(?VoucherUsage $usage): ?array
    {
        if (!$usage) {
            return null;
        }

        return [
            'id'                    => $usage->id,
            'voucher_id'            => $usage->voucher_id,
            'voucher_code'          => $usage->voucher_code,
            'voucher_name'          => $usage->voucher_name ?? $usage->voucher?->name,
            'discount_percentage'   => (float) $usage->discount_percentage,
            'percentage_formatted'  => $usage->formatted_percentage,
            'dpp_before_discount'   => (int) $usage->dpp_before_discount,
            'discount_amount'       => (int) $usage->discount_amount,
            'dpp_after_discount'    => (int) $usage->dpp_after_discount,
            'status'                => $usage->status,
            'status_label'          => $usage->status_label,
            'used_at'               => $usage->used_at?->format('d M Y, H:i'),
        ];
    }
}

