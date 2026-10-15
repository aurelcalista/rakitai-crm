<?php

namespace App\Services;

use App\Models\Prospek;
use App\Models\Transaksi;

class ProspekService
{
    /**
     * Menentukan apakah prospek ini valid untuk masuk status LUNAS (Closing).
     *
     * Syarat Closing:
     * 1. Transaksi "Beli Formulir" ada (tidak perlu verifikasi CS).
     * 2. DAN Transaksi "Pembayaran Termin 1" yang sudah VERIFIED oleh CS.
     *
     * Transaksi lama (tanpa metode_pembayaran) dianggap sudah verified (default DB).
     *
     * @param Prospek $prospek
     * @return bool
     */
    public static function isClosingValid(Prospek $prospek): bool
    {
        $transaksis = $prospek->relationLoaded('transaksis')
            ? $prospek->transaksis
            : ($prospek->exists ? $prospek->transaksis()->get() : collect());

        $hasFormulir = $transaksis->contains('jenis', 'Beli Formulir') || $transaksis->contains('jenis', 'Pendaftaran');

        // Pembayaran Termin 1 / Komponen lain yang berstatus verified
        $hasVerifiedPayment = $transaksis
            ->filter(function ($t) {
                $status = strtolower($t->payment_status ?? Transaksi::STATUS_VERIFIED);
                $isMainPayment = in_array($t->jenis, ['Pembayaran Termin 1', 'DP/Konversi', 'UKT', 'Perlengkapan'])
                    || ($t->kategori === Transaksi::KATEGORI_LUNAS);
                return $isMainPayment && $status === Transaksi::STATUS_VERIFIED;
            })
            ->isNotEmpty();

        return ($hasFormulir && $hasVerifiedPayment) || $transaksis->where('payment_status', Transaksi::STATUS_VERIFIED)->where('kategori', Transaksi::KATEGORI_LUNAS)->isNotEmpty();
    }

    /**
     * Cek apakah ada transaksi yang masih pending verifikasi.
     */
    public static function hasPendingVerification(Prospek $prospek): bool
    {
        return $prospek->transaksis()
            ->where('payment_status', Transaksi::STATUS_PENDING)
            ->exists();
    }
}
