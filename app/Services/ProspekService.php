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

        $hasFormulir = $transaksis->contains('jenis', 'Beli Formulir');

        // Pembayaran Termin 1 HARUS berstatus verified
        $hasVerifiedTermin1 = $transaksis
            ->where('jenis', 'Pembayaran Termin 1')
            ->where('payment_status', Transaksi::STATUS_VERIFIED)
            ->isNotEmpty();

        return $hasFormulir && $hasVerifiedTermin1;
    }

    /**
     * Cek apakah ada transaksi Termin 1 yang masih pending verifikasi.
     */
    public static function hasPendingVerification(Prospek $prospek): bool
    {
        return $prospek->transaksis()
            ->where('jenis', 'Pembayaran Termin 1')
            ->where('payment_status', Transaksi::STATUS_PENDING)
            ->exists();
    }
}
