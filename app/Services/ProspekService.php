<?php

namespace App\Services;

use App\Models\Prospek;

class ProspekService
{
    /**
     * Menentukan apakah prospek ini valid untuk masuk status LUNAS (Closing).
     * Syarat Closing: 
     * 1. Transaksi Beli Formulir/registrasi sudah dibayar
     * 2. DAN Transaksi Pembayaran Termin 1 sudah dibayar.
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
        $hasTermin1  = $transaksis->contains('jenis', 'Pembayaran Termin 1');

        return $hasFormulir && $hasTermin1;
    }
}
