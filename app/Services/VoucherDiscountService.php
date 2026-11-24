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
     * Cek apakah seluruh 5 komponen pembayaran (Pendaftaran, Perlengkapan, DPP, Uang Gedung, UKT)
     * telah terpilih / terpenuhi (termasuk jika komponen bernilai Rp 0 / Gratis atau sudah lunas).
     *
     * @param array<string>|null $selectedItems
     */
    public static function hasAllComponentsSelected(?Prospek $prospek, ?array $selectedItems = null, ?string $uktPeriod = null): bool
    {
        if (!$prospek) {
            return false;
        }

        $catalog = MidtransService::getBiayaCatalog($prospek, $uktPeriod);
        $paidItems = MidtransService::resolvePaidItems($prospek);
        $selected = is_array($selectedItems) ? $selectedItems : [];

        $allComponents = ['pendaftaran', 'perlengkapan', 'dpp', 'uang_gedung', 'ukt'];

        foreach ($allComponents as $comp) {
            $isExplicitlySelected = in_array($comp, $selected, true);
            $isPaid = !empty($paidItems[$comp]);
            $isFree = ($comp === 'dpp')
                ? ((int) ($catalog['dpp']['base_price'] ?? 0) <= 0)
                : ((int) ($catalog[$comp]['price'] ?? 0) <= 0);

            if (!$isExplicitlySelected && !$isPaid && !$isFree) {
                return false;
            }
        }

        return true;
    }

    /**
     * Hitung nominal potongan tetap (Fixed Amount Nominal) & total setelah diskon.
     * Formula:
     * - Potongan = min(Eligible Total, max(0, Discount Amount))
     * - Total Setelah Diskon = max(0, Eligible Total - Potongan)
     *
     * @return array{discount_amount: int, total_after_discount: int}
     */
    public static function calculateDiscount(int $eligibleTotal, int $discountAmount): array
    {
        $eligibleTotal  = max(0, $eligibleTotal);
        $discountAmount = max(0, $discountAmount);
        $appliedDiscount = min($eligibleTotal, $discountAmount);
        $finalAmount    = max(0, $eligibleTotal - $appliedDiscount);

        return [
            'discount_amount'      => $appliedDiscount,
            'total_after_discount' => $finalAmount,
        ];
    }

    /**
     * Ambil nominal DPP dasar dari Prodi pilihan CAMABA.
     */
    public static function getBaseDppPrice(?Prospek $prospek): int
    {
        $prodi = $prospek?->prodi ?: ($prospek?->prodi_id ? \App\Models\Prodi::find($prospek->prodi_id) : null);
        return ($prodi && array_key_exists('biaya_dpp', $prodi->getAttributes()) && $prodi->biaya_dpp !== null)
            ? (int) $prodi->biaya_dpp
            : 500000;
    }

    /**
     * Hitung total nominal DPP yang sudah dibayar & terverifikasi.
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
            $nom = (int) round((float) $vt->nominal);

            $hasPerlengkapan = str_contains($detail, 'Perlengkapan') || $jenis === 'Perlengkapan';
            $hasPendaftaran  = str_contains($detail, 'Pendaftaran') || str_contains($detail, 'Formulir')
                || (empty($detail) && in_array($jenis, ['Beli Formulir', 'Pendaftaran', 'Biaya Pendaftaran'], true));
            $hasDpp          = str_contains($detail, 'DPP') || str_contains($detail, 'Pengembangan Pendidikan') || $jenis === 'DPP';
            $hasGedung       = str_contains($detail, 'Gedung') || str_contains($detail, 'Uang Gedung') || $jenis === 'Gedung';

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
                    $applied->voucher?->refreshUsedQuota();
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
     * Hitung total nilai nominal seluruh 5 komponen tagihan pendaftaran.
     */
    public static function getEligiblePaymentTotal(?Prospek $prospek, ?string $uktPeriod = null): int
    {
        if (!$prospek) {
            return 0;
        }

        $prodi = $prospek->prodi ?: ($prospek->prodi_id ? \App\Models\Prodi::find($prospek->prodi_id) : null);
        $total = 0;
        $total += ($prodi && array_key_exists('biaya_pendaftaran', $prodi->getAttributes()) && $prodi->biaya_pendaftaran !== null) ? (int) $prodi->biaya_pendaftaran : (int) config('services.midtrans.biaya_pendaftaran', 300000);
        $total += ($prodi && array_key_exists('biaya_perlengkapan', $prodi->getAttributes()) && $prodi->biaya_perlengkapan !== null) ? (int) $prodi->biaya_perlengkapan : 600000;
        $total += ($prodi && array_key_exists('biaya_dpp', $prodi->getAttributes()) && $prodi->biaya_dpp !== null) ? (int) $prodi->biaya_dpp : 500000;
        $total += ($prodi && array_key_exists('biaya_uang_gedung', $prodi->getAttributes()) && $prodi->biaya_uang_gedung !== null) ? (int) $prodi->biaya_uang_gedung : 0;

        $activeUkt = MidtransService::getActiveUktCatalogOptions($prospek);
        if (!empty($activeUkt)) {
            $periodKey = $uktPeriod && isset($activeUkt[$uktPeriod]) ? $uktPeriod : (array_key_first($activeUkt) ?: null);
            if ($periodKey && isset($activeUkt[$periodKey])) {
                $total += (int) ($activeUkt[$periodKey]['nominal'] ?? 0);
            }
        }

        return $total;
    }

    /**
     * Ringkasan lengkap tagihan dan voucher untuk CAMABA dan Admin.
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
    public static function getDppSummary(?Prospek $prospek, ?string $uktPeriod = null): array
    {
        $eligibleTotal = self::getEligiblePaymentTotal($prospek, $uktPeriod);
        $basePrice = self::getBaseDppPrice($prospek);
        $paidPartial = self::getVerifiedDppPaidAmount($prospek);
        $remainingBeforeDiscount = max(0, $basePrice - $paidPartial);

        $usage = self::getEffectiveDppUsage($prospek);
        $discountAmount = $usage ? (int) $usage->discount_amount : 0;
        $discountAmount = max(0, min($eligibleTotal > 0 ? $eligibleTotal : $basePrice, $discountAmount));

        $remainingPayable = max(0, $basePrice - $discountAmount - $paidPartial);
        $isPaid = ($basePrice <= 0)
            ? true
            : ($remainingPayable <= 0 && ($paidPartial > 0 || ($usage && $usage->status === VoucherUsage::STATUS_USED)));

        return [
            'base_price'                => $basePrice,
            'eligible_total'            => $eligibleTotal,
            'paid_partial'              => $paidPartial,
            'remaining_before_discount' => $remainingBeforeDiscount,
            'active_usage'              => $usage,
            'discount_amount'           => $discountAmount,
            'remaining_payable'         => $remainingPayable,
            'is_paid'                   => $isPaid,
        ];
    }

    /**
     * Ambil nominal potongan aktif/terpakai untuk pengurangan kewajiban total.
     */
    public static function getActiveOrUsedDiscountAmount(?Prospek $prospek): int
    {
        $summary = self::getDppSummary($prospek);
        return (int) $summary['discount_amount'];
    }

    /**
     * Validasi dan terapkan kode voucher diskon tetap (Fixed IDR) ke tagihan CAMABA.
     * Persyaratan:
     * - CAMABA harus memilih seluruh 5 komponen pembayaran (Pendaftaran, Perlengkapan, DPP, Uang Gedung, UKT).
     * - 1 Akun CAMABA hanya dapat meredeem 1 voucher.
     * - 1 Voucher hanya dapat digunakan 1 kali per akun.
     * - Voucher harus valid, aktif, dan memiliki sisa kuota (quota > used_quota).
     * - Diskon nominal tetap langsung memotong total pembayaran.
     *
     * @throws InvalidArgumentException
     */
    public static function applyVoucher(
        Prospek $prospek,
        string $rawCode,
        ?array $selectedItems = null,
        ?string $uktPeriod = null
    ): VoucherUsage {
        $normalizedCode = strtoupper(trim($rawCode));
        if ($normalizedCode === '') {
            throw new InvalidArgumentException('Silakan masukkan kode voucher terlebih dahulu.');
        }

        return DB::transaction(function () use ($prospek, $normalizedCode, $selectedItems, $uktPeriod) {
            $lockedProspek = Prospek::with(['prodi', 'transaksis'])
                ->where('id', $prospek->id)
                ->lockForUpdate()
                ->firstOrFail();

            // 1. Validasi: jika parameter selectedItems disertakan, pastikan SELURUH 5 komponen terpilih / terpenuhi (termasuk komponen Rp 0 / Gratis)
            if ($selectedItems !== null) {
                if (!self::hasAllComponentsSelected($lockedProspek, $selectedItems, $uktPeriod)) {
                    throw new InvalidArgumentException('Voucher hanya dapat digunakan jika seluruh 5 komponen pembayaran (Biaya Pendaftaran, Biaya Perlengkapan, DPP, Uang Gedung, dan UKT) telah dipilih.');
                }
            }

            // 2. Validasi kode voucher dari database dengan row-level locking
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

            // 3. Validasi kuota voucher
            $confirmedUsageCount = VoucherUsage::where('voucher_id', $voucher->id)
                ->where('status', VoucherUsage::STATUS_USED)
                ->lockForUpdate()
                ->count();

            $effectiveUsed = max((int) $voucher->used_quota, $confirmedUsageCount);
            $remainingQuota = max(0, (int) $voucher->quota - $effectiveUsed);
            if ($remainingQuota <= 0) {
                throw new InvalidArgumentException("Kuota penggunaan voucher '{$voucher->code}' sudah habis.");
            }

            // 4. Cegah penggunaan voucher ganda / berulang oleh CAMABA yang sama:
            // "Each CAMABA account can only redeem one voucher. A single account cannot use multiple vouchers. A redeemed voucher cannot be reused by the same account."
            $alreadyUsedAny = VoucherUsage::where('prospek_id', $lockedProspek->id)
                ->where('status', VoucherUsage::STATUS_USED)
                ->lockForUpdate()
                ->first();

            if ($alreadyUsedAny) {
                if ((int) $alreadyUsedAny->voucher_id === (int) $voucher->id) {
                    throw new InvalidArgumentException("Kode voucher '{$voucher->code}' sudah pernah Anda gunakan sebelumnya.");
                }
                throw new InvalidArgumentException('Akun Anda sudah pernah menggunakan voucher pada transaksi sebelumnya. Setiap akun hanya dapat menggunakan 1 voucher.');
            }

            // Cek apakah voucher yang sama sudah berstatus applied pada akun ini
            $alreadyAppliedSame = VoucherUsage::where('prospek_id', $lockedProspek->id)
                ->where('voucher_id', $voucher->id)
                ->where('status', VoucherUsage::STATUS_APPLIED)
                ->lockForUpdate()
                ->first();

            if ($alreadyAppliedSame) {
                return $alreadyAppliedSame;
            }

            // Batalkan voucher lain yang hanya berstatus 'applied' (belum terbayar) agar tidak menumpuk
            VoucherUsage::where('prospek_id', $lockedProspek->id)
                ->where('status', VoucherUsage::STATUS_APPLIED)
                ->update(['status' => VoucherUsage::STATUS_CANCELLED]);

            // 5. Hitung nominal potongan tetap IDR dari total eligible
            $eligibleTotal = self::getEligiblePaymentTotal($lockedProspek, $uktPeriod);

            // Nominal potongan diambil dari discount_amount tetap
            $nominalDiscount = (int) $voucher->discount_amount;
            if ($nominalDiscount <= 0 && (float) ($voucher->discount_percentage ?? 0) > 0) {
                // Fallback untuk voucher lama yang dibuat dengan persentase
                $calc = self::calculateDiscount($eligibleTotal, (int) round(($eligibleTotal * (float) $voucher->discount_percentage) / 100));
            } else {
                $calc = self::calculateDiscount($eligibleTotal, $nominalDiscount);
            }

            return VoucherUsage::create([
                'voucher_id'            => $voucher->id,
                'prospek_id'            => $lockedProspek->id,
                'voucher_code'          => $voucher->code,
                'voucher_name'          => $voucher->name,
                'discount_amount'       => $calc['discount_amount'],
                'total_before_discount' => $eligibleTotal,
                'total_after_discount'  => $calc['total_after_discount'],
                'dpp_before_discount'   => $eligibleTotal,
                'dpp_after_discount'    => $calc['total_after_discount'],
                'discount_percentage'   => 0,
                'status'                => VoucherUsage::STATUS_APPLIED,
            ]);
        });
    }

    /**
     * Lepas voucher yang baru berstatus 'applied' (belum terbayar) dari tagihan CAMABA.
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
     * Validasi ulang voucher berstatus 'applied' saat CAMABA melakukan checkout.
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
            $voucher = $usage->voucher()->lockForUpdate()->first();
            if (!$voucher || !$voucher->isUsable()) {
                $usage->update(['status' => VoucherUsage::STATUS_CANCELLED]);
                throw new InvalidArgumentException("Voucher '{$usage->voucher_code}' sudah tidak dapat digunakan (tidak aktif, kedaluwarsa, atau kuota habis).");
            }
        }

        return $usage;
    }

    /**
     * Hubungkan transaksi checkout ke snapshot penggunaan voucher.
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
     * Sinkronisasi status VoucherUsage dan kuota voucher ketika status Transaksi berubah.
     * - Hanya kurangi kuota jika transaksi terverifikasi lunas (VERIFIED / SUCCESS).
     * - Transaksi gagal/batal/ditolak TIDAK mengurangi kuota.
     * - Menggunakan database transaction dan row locking untuk mencegah race condition.
     */
    public static function syncUsageForTransaksi(Transaksi $transaksi): void
    {
        DB::transaction(function () use ($transaksi) {
            $usages = VoucherUsage::where('transaksi_id', $transaksi->id)
                ->lockForUpdate()
                ->get();

            if ($usages->isEmpty()) {
                return;
            }

            $statusLower = strtolower((string) $transaksi->payment_status);
            $isVerified = in_array($statusLower, array_map('strtolower', self::VERIFIED_STATUSES), true);
            $isRejected = in_array($statusLower, [
                Transaksi::STATUS_REJECTED, 'failed', 'rejected', 'deny', 'expire', 'cancel', 'batal',
            ], true);

            foreach ($usages as $usage) {
                $voucher = Voucher::where('id', $usage->voucher_id)->lockForUpdate()->first();

                if ($isVerified) {
                    if ($usage->status !== VoucherUsage::STATUS_USED) {
                        // Pastikan kuota tidak terlewati pada saat verifikasi
                        if ($voucher) {
                            $confirmedUses = VoucherUsage::where('voucher_id', $voucher->id)
                                ->where('status', VoucherUsage::STATUS_USED)
                                ->where('id', '!=', $usage->id)
                                ->count();

                            if ($confirmedUses >= $voucher->quota) {
                                $usage->update(['status' => VoucherUsage::STATUS_CANCELLED]);
                                continue;
                            }
                        }

                        $usage->update([
                            'status'  => VoucherUsage::STATUS_USED,
                            'used_at' => $usage->used_at ?? now(),
                        ]);

                        $voucher?->refreshUsedQuota();
                    }
                } elseif ($isRejected) {
                    $usage->update([
                        'status' => VoucherUsage::STATUS_CANCELLED,
                    ]);
                    $voucher?->refreshUsedQuota();
                }
            }
        });
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

        $formattedDiscount = 'Rp ' . number_format((int) $usage->discount_amount, 0, ',', '.');

        return [
            'id'                    => $usage->id,
            'voucher_id'            => $usage->voucher_id,
            'voucher_code'          => $usage->voucher_code,
            'voucher_name'          => $usage->voucher_name ?? $usage->voucher?->name,
            'discount_amount'       => (int) $usage->discount_amount,
            'discount_formatted'    => $formattedDiscount,
            'total_before_discount' => (int) ($usage->total_before_discount ?? $usage->dpp_before_discount),
            'total_after_discount'  => (int) ($usage->total_after_discount ?? $usage->dpp_after_discount),
            'dpp_before_discount'   => (int) ($usage->total_before_discount ?? $usage->dpp_before_discount),
            'dpp_after_discount'    => (int) ($usage->total_after_discount ?? $usage->dpp_after_discount),
            'discount_percentage'   => (float) ($usage->discount_percentage ?? 0),
            'percentage_formatted'  => $usage->formatted_percentage,
            'status'                => $usage->status,
            'status_label'          => $usage->status_label,
            'used_at'               => $usage->used_at?->format('d M Y, H:i'),
        ];
    }
}
