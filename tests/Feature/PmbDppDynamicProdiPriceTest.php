<?php

namespace Tests\Feature;

use App\Models\Prodi;
use App\Models\Prospek;
use App\Models\Transaksi;
use App\Models\User;
use App\Services\MidtransService;
use App\Services\VoucherDiscountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PmbDppDynamicProdiPriceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.midtrans.server_key'    => 'SB-Mid-server-TESTKEY123',
            'services.midtrans.client_key'    => 'SB-Mid-client-TESTKEY123',
            'services.midtrans.is_production' => false,
        ]);
    }

    protected function createCamabaWithProspek(?Prodi $prodi = null, string $statusFormulir = 'Sudah Isi'): array
    {
        $camaba = User::create([
            'name'     => 'Camaba Test DPP',
            'email'    => 'camaba.dpp.' . uniqid() . '@example.com',
            'phone'    => '0812' . rand(10000000, 99999999),
            'password' => Hash::make('secret123'),
            'role'     => 'camaba',
        ]);

        $prospek = Prospek::create([
            'user_id'         => $camaba->id,
            'name'            => $camaba->name,
            'email'           => $camaba->email,
            'whatsapp'        => $camaba->phone,
            'type'            => 'Individu',
            'source'          => 'Website',
            'status'          => 'BARU',
            'status_formulir' => $statusFormulir,
            'prodi_id'        => $prodi?->id,
        ]);

        return [$camaba, $prospek];
    }

    public function test_dpp_nominal_is_dynamic_from_admin_data_prodi_on_payment_page(): void
    {
        $prodi = Prodi::create([
            'kode'               => 'TI-DYN',
            'nama'               => 'Teknik Informatika Dynamic',
            'jenjang'            => 'S1',
            'fakultas'           => 'FTI',
            'kuota'              => 50,
            'status'             => 'Aktif',
            'biaya_pendaftaran'  => 300000,
            'biaya_perlengkapan' => 600000,
            'biaya_dpp'          => 1750000,
            'biaya_uang_gedung'  => 1000000,
            'ukt_options'        => [
                '6_bulan' => ['key' => '6_bulan', 'label' => 'Per 6 Bulan (Semester)', 'nominal' => 4500000, 'is_active' => true],
            ],
        ]);

        [$camaba, $prospek] = $this->createCamabaWithProspek($prodi, 'Sudah Isi');

        // 1. Visit pembayaran page
        $res = $this->actingAs($camaba)->get(route('pmb.akun.bayar'));
        $res->assertOk();

        // Check nominal Rp 1.750.000 is clearly rendered
        $res->assertSee('Rp 1.750.000');
        $res->assertSee('dpp: 1750000', false);
        $res->assertSee('dppBasePrice: 1750000', false);
        $res->assertSee('data-price="1750000"', false);

        // 2. Admin updates prodi's biaya_dpp in Data Prodi menu
        $prodi->update(['biaya_dpp' => 3250000]);

        // 3. CAMABA refreshes payment page: should dynamically reflect 3.250.000
        $res2 = $this->actingAs($camaba)->get(route('pmb.akun.bayar'));
        $res2->assertOk();
        $res2->assertSee('Rp 3.250.000');
        $res2->assertSee('dpp: 3250000', false);
        $res2->assertSee('dppBasePrice: 3250000', false);
        $res2->assertSee('data-price="3250000"', false);
    }

    public function test_midtrans_service_and_checkout_use_dynamic_prodi_dpp_price(): void
    {
        $prodi = Prodi::create([
            'kode'               => 'SI-DYN',
            'nama'               => 'Sistem Informasi Dynamic',
            'jenjang'            => 'S1',
            'fakultas'           => 'FTI',
            'kuota'              => 50,
            'status'             => 'Aktif',
            'biaya_pendaftaran'  => 300000,
            'biaya_perlengkapan' => 600000,
            'biaya_dpp'          => 2800000,
            'biaya_uang_gedung'  => 0,
            'ukt_options'        => [
                '6_bulan' => ['key' => '6_bulan', 'label' => 'Per 6 Bulan (Semester)', 'nominal' => 4000000, 'is_active' => true],
            ],
        ]);

        [$camaba, $prospek] = $this->createCamabaWithProspek($prodi, 'Sudah Isi');

        // Check catalog
        $catalog = MidtransService::getBiayaCatalog($prospek);
        $this->assertEquals(2800000, $catalog['dpp']['price']);
        $this->assertEquals(2800000, $catalog['dpp']['base_price']);

        // Check base price in VoucherDiscountService
        $this->assertEquals(2800000, VoucherDiscountService::getBaseDppPrice($prospek));

        // Checkout only DPP
        $checkoutRes = $this->actingAs($camaba)->postJson(route('pmb.checkout', $prospek->id), [
            'category' => Transaksi::KATEGORI_PENDAFTARAN,
            'items'    => ['dpp'],
        ]);
        $checkoutRes->assertOk();
        $this->assertEquals(2800000, $checkoutRes->json('nominal'));
    }

    public function test_when_dpp_is_zero_it_displays_gratis(): void
    {
        $prodi = Prodi::create([
            'kode'               => 'FREE-DPP',
            'nama'               => 'Prodi Free DPP',
            'jenjang'            => 'S1',
            'fakultas'           => 'FTI',
            'kuota'              => 50,
            'status'             => 'Aktif',
            'biaya_pendaftaran'  => 300000,
            'biaya_perlengkapan' => 600000,
            'biaya_dpp'          => 0,
            'biaya_uang_gedung'  => 0,
            'ukt_options'        => [
                '6_bulan' => ['key' => '6_bulan', 'label' => 'Per 6 Bulan (Semester)', 'nominal' => 4000000, 'is_active' => true],
            ],
        ]);

        [$camaba, $prospek] = $this->createCamabaWithProspek($prodi, 'Sudah Isi');

        $res = $this->actingAs($camaba)->get(route('pmb.akun.bayar'));
        $res->assertOk();
        $res->assertSee('Gratis (Rp 0)');
        $res->assertSee('dpp: 0', false);
        $res->assertSee('dppBasePrice: 0', false);
    }

    public function test_when_uang_gedung_is_free_it_counts_as_checked_and_allows_voucher_redemption(): void
    {
        $prodi = Prodi::create([
            'kode'               => 'UG-FREE',
            'nama'               => 'Prodi Free Uang Gedung',
            'jenjang'            => 'S1',
            'fakultas'           => 'FTI',
            'kuota'              => 50,
            'status'             => 'Aktif',
            'biaya_pendaftaran'  => 300000,
            'biaya_perlengkapan' => 600000,
            'biaya_dpp'          => 2000000,
            'biaya_uang_gedung'  => 0, // Uang gedung gratis!
            'ukt_options'        => [
                '6_bulan' => ['key' => '6_bulan', 'label' => 'Per 6 Bulan', 'nominal' => 4000000, 'is_active' => true],
            ],
        ]);

        [$camaba, $prospek] = $this->createCamabaWithProspek($prodi, 'Sudah Isi');

        \App\Models\Voucher::create([
            'code'            => 'DISKON300',
            'discount_amount' => 300000,
            'quota'           => 10,
            'is_active'       => true,
        ]);

        // 1. Jika masih ada komponen berbayar yang belum dipilih (misal tanpa UKT), voucher ditolak
        $rejectRes = $this->actingAs($camaba)->postJson(route('pmb.akun.voucher.apply'), [
            'code'  => 'DISKON300',
            'items' => ['pendaftaran', 'perlengkapan', 'dpp'],
        ]);
        $rejectRes->assertStatus(422);

        // 2. Ketika seluruh komponen berbayar dipilih (Pendaftaran, Perlengkapan, DPP, UKT),
        // Uang Gedung bernilai 0 (gratis) otomatis dianggap sudah tercentang / terpenuhi!
        $applyRes = $this->actingAs($camaba)->postJson(route('pmb.akun.voucher.apply'), [
            'code'  => 'DISKON300',
            'items' => ['pendaftaran', 'perlengkapan', 'dpp', 'ukt'],
        ]);
        $applyRes->assertOk();
        $this->assertEquals(300000, $applyRes->json('discount_amount'));

        // 3. Checkout dengan 4 komponen berbayar + voucher harus sukses dan diskon 300.000 terpotong
        // Total normal: 300k + 600k + 2000k + 4000k = 6.900.000 -> Setelah diskon 300k = 6.600.000
        $checkoutRes = $this->actingAs($camaba)->postJson(route('pmb.checkout', $prospek->id), [
            'category'     => Transaksi::KATEGORI_PENDAFTARAN,
            'items'        => ['pendaftaran', 'perlengkapan', 'dpp', 'ukt'],
            'voucher_code' => 'DISKON300',
        ]);
        $checkoutRes->assertOk();
        $this->assertEquals(6600000, $checkoutRes->json('nominal'));
    }
}
