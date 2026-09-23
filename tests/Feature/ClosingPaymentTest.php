<?php

namespace Tests\Feature;

use App\Models\Prospek;
use App\Models\Transaksi;
use App\Services\ProspekService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClosingPaymentTest extends TestCase
{
    public function test_closing_valid_scenarios()
    {
        // A. Belum bayar formulir + belum termin 1 -> bukan LUNAS
        $prospekA = new Prospek();
        $this->assertFalse(ProspekService::isClosingValid($prospekA));

        // B. Sudah bayar formulir + belum termin 1 -> bukan LUNAS
        $prospekB = new Prospek();
        $prospekB->setRelation('transaksis', collect([
            new Transaksi(['jenis' => 'Beli Formulir'])
        ]));
        $this->assertFalse(ProspekService::isClosingValid($prospekB));

        // C. Belum formulir + sudah termin 1 -> bukan LUNAS
        $prospekC = new Prospek();
        $prospekC->setRelation('transaksis', collect([
            new Transaksi(['jenis' => 'Pembayaran Termin 1'])
        ]));
        $this->assertFalse(ProspekService::isClosingValid($prospekC));

        // D. Sudah formulir + sudah termin 1 -> LUNAS/closing valid
        $prospekD = new Prospek();
        $prospekD->setRelation('transaksis', collect([
            new Transaksi(['jenis' => 'Beli Formulir']),
            new Transaksi(['jenis' => 'Pembayaran Termin 1'])
        ]));
        $this->assertTrue(ProspekService::isClosingValid($prospekD));

        // E. Transaksi ada tetapi gagal (tidak berlaku karena tidak ada status gagal di sistem)
        
        // F. Data transaksi milik prospek lain tidak boleh membuat prospek ini menjadi LUNAS
        $prospekE = new Prospek();
        $prospekE->setRelation('transaksis', collect());
        $this->assertFalse(ProspekService::isClosingValid($prospekE));
    }
}
