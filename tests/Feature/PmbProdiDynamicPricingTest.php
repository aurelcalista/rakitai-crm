<?php

namespace Tests\Feature;

use App\Models\Prodi;
use App\Models\Prospek;
use App\Models\Transaksi;
use App\Models\User;
use App\Services\MidtransService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PmbProdiDynamicPricingTest extends TestCase
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

    protected function createCamabaWithProspek(?Prodi $prodi = null, string $statusFormulir = 'Belum'): array
    {
        $camaba = User::create([
            'name'     => 'Camaba Dinamis',
            'email'    => 'camaba.' . uniqid() . '@example.com',
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

    public function test_katalog_links_to_profil_with_prodi_id_and_profil_preselects_chosen_prodi(): void
    {
        $prodiA = Prodi::create([
            'kode'               => 'TI-S1',
            'nama'               => 'Teknik Informatika',
            'jenjang'            => 'S1',
            'fakultas'           => 'FTI',
            'kuota'              => 100,
            'status'             => 'Aktif',
            'biaya_pendaftaran'  => 250000,
            'biaya_perlengkapan' => 750000,
            'biaya_dpp'          => 1200000,
            'biaya_uang_gedung'  => 1500000,
            'ukt_options'        => [
                '6_bulan' => ['key' => '6_bulan', 'label' => 'Per 6 Bulan (Semester)', 'nominal' => 4500000, 'is_active' => true],
            ],
        ]);

        $prodiB = Prodi::create([
            'kode'               => 'SI-S1',
            'nama'               => 'Sistem Informasi',
            'jenjang'            => 'S1',
            'fakultas'           => 'FTI',
            'kuota'              => 80,
            'status'             => 'Aktif',
            'biaya_pendaftaran'  => 350000,
            'biaya_perlengkapan' => 850000,
            'biaya_dpp'          => 950000,
            'biaya_uang_gedung'  => 2000000,
            'ukt_options'        => [
                '6_bulan' => ['key' => '6_bulan', 'label' => 'Per 6 Bulan (Semester)', 'nominal' => 5200000, 'is_active' => true],
            ],
        ]);

        [$camaba] = $this->createCamabaWithProspek(null, 'Belum');

        // 1. Katalog PMB harus mengarahkan tombol "Pilih di Form Pendaftaran" membawa ?prodi_id={id}
        $katalogRes = $this->actingAs($camaba)->get(route('pmb.akun.katalog'));
        $katalogRes->assertOk();
        $katalogRes->assertSee(route('pmb.akun.profil', ['prodi_id' => $prodiA->id]), false);
        $katalogRes->assertSee(route('pmb.akun.profil', ['prodi_id' => $prodiB->id]), false);

        // 2. Membuka /pmb/akun/profil?prodi_id=B otomatis mem-preselect Prodi B beserta harganya
        $profilRes = $this->actingAs($camaba)->get(route('pmb.akun.profil', ['prodi_id' => $prodiB->id]));
        $profilRes->assertOk();
        $profilRes->assertSee("selectedProdiId: '{$prodiB->id}'", false);
        $profilRes->assertSee('"biaya_uang_gedung":2000000');
        $profilRes->assertSee('"biaya_pendaftaran":350000');
    }

    public function test_submitting_form_pendaftaran_saves_prodi_and_pembayaran_displays_admin_prodi_prices(): void
    {
        $prodi = Prodi::create([
            'kode'               => 'DKV-S1',
            'nama'               => 'Desain Komunikasi Visual',
            'jenjang'            => 'S1',
            'fakultas'           => 'FTI',
            'kuota'              => 60,
            'status'             => 'Aktif',
            'biaya_pendaftaran'  => 275000,
            'biaya_perlengkapan' => 925000,
            'biaya_dpp'          => 1300000,
            'biaya_uang_gedung'  => 2500000,
            'ukt_options'        => [
                '6_bulan' => ['key' => '6_bulan', 'label' => 'Per 6 Bulan (Semester)', 'nominal' => 4800000, 'is_active' => true],
            ],
        ]);

        [$camaba, $prospek] = $this->createCamabaWithProspek(null, 'Belum');

        // Submit Form Pendaftaran memilih Prodi DKV
        $submitRes = $this->actingAs($camaba)->post(route('pmb.akun.form-pendaftaran.update'), [
            'name'     => 'Camaba DKV',
            'whatsapp' => '+6281234567890',
            'prodi_id' => $prodi->id,
        ]);

        $submitRes->assertRedirect(route('pmb.akun.bayar'));

        $prospek->refresh();
        $this->assertEquals($prodi->id, $prospek->prodi_id);
        $this->assertEquals('Sudah Isi', $prospek->status_formulir);

        // Halaman pembayaran hanya menampilkan 5 checkbox berurutan sesuai data prodi Admin tanpa Down Payment / Pelunasan
        $bayarRes = $this->actingAs($camaba)->get(route('pmb.akun.bayar'));
        $bayarRes->assertOk();
        $bayarRes->assertDontSee('Kategori Pembayaran Mahasiswa');
        $bayarRes->assertDontSee('Down Payment (DP)');
        $bayarRes->assertDontSee('Pelunasan Biaya Kuliah');
        $bayarRes->assertSeeInOrder([
            'id="check-pendaftaran"',
            'id="check-perlengkapan"',
            'id="check-dpp"',
            'id="check-uang-gedung"',
            'id="check-ukt"',
        ], false);
        $bayarRes->assertSeeInOrder([
            'Biaya Pendaftaran',
            'Biaya Perlengkapan',
            'DPP',
            'Uang Gedung',
            'UKT',
        ]);
        $bayarRes->assertSee('Desain Komunikasi Visual');
        $bayarRes->assertSee('Rp 275.000');
        $bayarRes->assertSee('Rp 925.000');
        $bayarRes->assertSee('Rp 1.300.000');
        $bayarRes->assertSee('Rp 2.500.000');
        $bayarRes->assertSee('Rp 4.800.000');
    }

    protected function settleOrder(string $orderId, int $nominal): void
    {
        $grossAmount = number_format($nominal, 2, '.', '');
        $statusCode = '200';
        $signature = hash('sha512', $orderId . $statusCode . $grossAmount . config('services.midtrans.server_key'));

        $webhookRes = $this->postJson(route('pmb.midtrans.notification'), [
            'order_id'           => $orderId,
            'status_code'        => $statusCode,
            'gross_amount'       => $grossAmount,
            'transaction_status' => 'settlement',
            'payment_type'       => 'bank_transfer',
            'signature_key'      => $signature,
        ]);
        $webhookRes->assertOk()->assertJson(['status' => 'success']);
    }

    public function test_sequential_payment_flow_enforces_stage_order_when_uang_gedung_positive(): void
    {
        $prodi = Prodi::create([
            'kode'               => 'MAN-S1',
            'nama'               => 'Manajemen',
            'jenjang'            => 'S1',
            'fakultas'           => 'FEB',
            'kuota'              => 120,
            'status'             => 'Aktif',
            'biaya_pendaftaran'  => 250000,
            'biaya_perlengkapan' => 750000,
            'biaya_dpp'          => 1200000,
            'biaya_uang_gedung'  => 1500000,
            'ukt_options'        => [
                '6_bulan' => ['key' => '6_bulan', 'label' => 'Per 6 Bulan (Semester)', 'nominal' => 4200000, 'is_active' => true],
            ],
        ]);

        [$camaba, $prospek] = $this->createCamabaWithProspek($prodi, 'Sudah Isi');

        // 0. Awalnya DPP, Uang Gedung, dan UKT masih terkunci
        foreach (['dpp', 'uang_gedung', 'ukt'] as $lockedKey) {
            $this->actingAs($camaba)->postJson(route('pmb.checkout', $prospek->id), [
                'category' => Transaksi::KATEGORI_PENDAFTARAN,
                'items'    => [$lockedKey],
                'amount'   => 1000,
            ])->assertStatus(422)->assertJson(['success' => false]);
        }

        $bayar0 = $this->actingAs($camaba)->get(route('pmb.akun.bayar'));
        $bayar0->assertOk();
        $bayar0->assertSee('dpp: false', false);
        $bayar0->assertSee('uang_gedung: false', false);
        $bayar0->assertSee('ukt: false', false);

        // 1. Bayar Biaya Pendaftaran dulu saja (250k)
        $resDaftar = $this->actingAs($camaba)->postJson(route('pmb.checkout', $prospek->id), [
            'category' => Transaksi::KATEGORI_PENDAFTARAN,
            'items'    => ['pendaftaran'],
        ]);
        $resDaftar->assertOk()->assertJson(['nominal' => 250000]);
        $this->settleOrder($resDaftar->json('order_id'), 250000);

        // Setelah pendaftaran lunas tapi perlengkapan belum -> DPP tetap terkunci!
        $this->actingAs($camaba)->postJson(route('pmb.checkout', $prospek->id), [
            'category' => Transaksi::KATEGORI_PENDAFTARAN,
            'items'    => ['dpp'],
        ])->assertStatus(422);

        $bayar1 = $this->actingAs($camaba)->get(route('pmb.akun.bayar'));
        $bayar1->assertSee('Bayar Biaya Perlengkapan terlebih dahulu untuk membuka pembayaran DPP.');

        // 2. Bayar Biaya Perlengkapan (750k)
        $resPerlengkapan = $this->actingAs($camaba)->postJson(route('pmb.checkout', $prospek->id), [
            'category' => Transaksi::KATEGORI_PENDAFTARAN,
            'items'    => ['perlengkapan'],
        ]);
        $resPerlengkapan->assertOk()->assertJson(['nominal' => 750000]);
        $this->settleOrder($resPerlengkapan->json('order_id'), 750000);

        // Setelah pendaftaran & perlengkapan lunas -> DPP terbuka, Uang Gedung & UKT masih terkunci
        $bayar2 = $this->actingAs($camaba)->get(route('pmb.akun.bayar'));
        $bayar2->assertSee('dpp: true', false);
        $bayar2->assertSee('uang_gedung: false', false);
        $bayar2->assertSee('ukt: false', false);
        $this->actingAs($camaba)->postJson(route('pmb.checkout', $prospek->id), [
            'category' => Transaksi::KATEGORI_PENDAFTARAN,
            'items'    => ['uang_gedung'],
        ])->assertStatus(422);

        // 3. Bayar DPP (1.200k)
        $resDpp = $this->actingAs($camaba)->postJson(route('pmb.checkout', $prospek->id), [
            'category' => Transaksi::KATEGORI_PENDAFTARAN,
            'items'    => ['dpp'],
        ]);
        $resDpp->assertOk()->assertJson(['nominal' => 1200000]);
        $trxDpp = Transaksi::where('order_id', $resDpp->json('order_id'))->firstOrFail();
        $snapDpp = app(MidtransService::class)->buildSnapPayload($trxDpp);
        $this->assertEquals('PMB-DPP', $snapDpp['item_details'][0]['id']);
        $this->assertEquals(1200000, $snapDpp['transaction_details']['gross_amount']);
        $this->settleOrder($resDpp->json('order_id'), 1200000);

        // Habis bayar DPP -> Uang Gedung terbuka, UKT masih terkunci karena Uang Gedung > 0
        $this->actingAs($camaba)->postJson(route('pmb.checkout', $prospek->id), [
            'category' => Transaksi::KATEGORI_PENDAFTARAN,
            'items'    => ['ukt'],
        ])->assertStatus(422);

        // 4. Bayar Uang Gedung (1.500k)
        $resGedung = $this->actingAs($camaba)->postJson(route('pmb.checkout', $prospek->id), [
            'category' => Transaksi::KATEGORI_PENDAFTARAN,
            'items'    => ['uang_gedung'],
        ]);
        $resGedung->assertOk()->assertJson(['nominal' => 1500000]);
        $trxGedung = Transaksi::where('order_id', $resGedung->json('order_id'))->firstOrFail();
        $snapGedung = app(MidtransService::class)->buildSnapPayload($trxGedung);
        $this->assertEquals('PMB-GEDUNG', $snapGedung['item_details'][0]['id']);
        $this->assertEquals(1500000, $snapGedung['transaction_details']['gross_amount']);
        $this->settleOrder($resGedung->json('order_id'), 1500000);

        // 5. Terakhir UKT terbuka & bisa dibayar (4.200k)
        $resUkt = $this->actingAs($camaba)->postJson(route('pmb.checkout', $prospek->id), [
            'category'   => Transaksi::KATEGORI_PENDAFTARAN,
            'items'      => ['ukt'],
            'ukt_period' => '6_bulan',
        ]);
        $resUkt->assertOk()->assertJson(['nominal' => 4200000]);
        $this->settleOrder($resUkt->json('order_id'), 4200000);

        $paidItems = MidtransService::resolvePaidItems($prospek->fresh());
        $this->assertTrue($paidItems['pendaftaran']);
        $this->assertTrue($paidItems['perlengkapan']);
        $this->assertTrue($paidItems['dpp']);
        $this->assertTrue($paidItems['uang_gedung']);
        $this->assertTrue($paidItems['ukt']);
    }

    public function test_zero_rupiah_component_and_non_default_active_ukt_option_are_handled_correctly(): void
    {
        $prodi = Prodi::create([
            'kode'               => 'AKT-S1',
            'nama'               => 'Akuntansi',
            'jenjang'            => 'S1',
            'fakultas'           => 'FEB',
            'kuota'              => 90,
            'status'             => 'Aktif',
            'biaya_pendaftaran'  => 0,       // Gratis
            'biaya_perlengkapan' => 400000,
            'biaya_dpp'          => 0,       // Gratis
            'biaya_uang_gedung'  => 800000,
            'ukt_options'        => [
                '1_bulan' => ['key' => '1_bulan', 'label' => 'Per 1 Bulan', 'nominal' => 650000, 'is_active' => true],
                '6_bulan' => ['key' => '6_bulan', 'label' => 'Per 6 Bulan (Semester)', 'nominal' => 9999999, 'is_active' => false],
            ],
        ]);

        [$camaba, $prospek] = $this->createCamabaWithProspek($prodi, 'Sudah Isi');

        // Total tagihan = perlengkapan (400k) + uang_gedung (800k) + UKT aktif pertama (650k) = 1.850.000
        $this->assertEquals(1850000, MidtransService::getTotalTagihan($prospek));

        $bayarRes = $this->actingAs($camaba)->get(route('pmb.akun.bayar'));
        $bayarRes->assertOk();
        $bayarRes->assertSee('Rp 0 (Gratis)');
        $bayarRes->assertSee('Rp 400.000');
        $bayarRes->assertSee('Rp 800.000');
        $bayarRes->assertSee('Rp 650.000');
        $bayarRes->assertDontSee('9.999.999');

        // Memilih komponen Rp 0 saja harus ditolak
        $resZero = $this->actingAs($camaba)->postJson(route('pmb.checkout', $prospek->id), [
            'category' => Transaksi::KATEGORI_PENDAFTARAN,
            'items'    => ['pendaftaran'],
        ]);
        $resZero->assertStatus(422)
            ->assertJson(['success' => false]);

        // Memilih pendaftaran (Rp 0) + perlengkapan (400k) -> hanya menagih 400k
        $resPartial = $this->actingAs($camaba)->postJson(route('pmb.checkout', $prospek->id), [
            'category' => Transaksi::KATEGORI_PENDAFTARAN,
            'items'    => ['pendaftaran', 'perlengkapan'],
        ]);
        $resPartial->assertOk()
            ->assertJson([
                'success' => true,
                'nominal' => 400000,
            ]);
    }

    public function test_when_uang_gedung_is_zero_dpp_settlement_unlocks_ukt_directly(): void
    {
        $prodi = Prodi::create([
            'kode'               => 'BD-S1',
            'nama'               => 'Bisnis Digital',
            'jenjang'            => 'S1',
            'fakultas'           => 'FEB',
            'kuota'              => 100,
            'status'             => 'Aktif',
            'biaya_pendaftaran'  => 250000,
            'biaya_perlengkapan' => 750000,
            'biaya_dpp'          => 1200000,
            'biaya_uang_gedung'  => 0,
            'ukt_options'        => [
                '6_bulan' => ['key' => '6_bulan', 'label' => 'Per 6 Bulan (Semester)', 'nominal' => 4300000, 'is_active' => true],
            ],
        ]);
        // Total 1x bayar = 250k + 750k + 1.200k + 0 = 2.200.000
        // Total tagihan = 2.200.000 + 4.300.000 = 6.500.000

        [$camaba, $prospek] = $this->createCamabaWithProspek($prodi, 'Sudah Isi');

        // 1. Bayar pendaftaran + perlengkapan bersamaan (1.000.000)
        $resStage1 = $this->actingAs($camaba)->postJson(route('pmb.checkout', $prospek->id), [
            'category' => Transaksi::KATEGORI_PENDAFTARAN,
            'items'    => ['pendaftaran', 'perlengkapan'],
        ]);
        $resStage1->assertOk()->assertJson(['nominal' => 1000000]);
        $this->settleOrder($resStage1->json('order_id'), 1000000);

        $prospek->refresh();
        $paid1 = MidtransService::resolvePaidItems($prospek);
        $this->assertTrue($paid1['pendaftaran']);
        $this->assertTrue($paid1['perlengkapan']);
        $this->assertFalse($paid1['dpp']);

        // Belum bayar DPP -> UKT masih terkunci
        $this->actingAs($camaba)->postJson(route('pmb.checkout', $prospek->id), [
            'category'   => Transaksi::KATEGORI_PENDAFTARAN,
            'items'      => ['ukt'],
            'ukt_period' => '6_bulan',
        ])->assertStatus(422);

        // 2. Bayar DPP (1.200.000)
        $resDpp = $this->actingAs($camaba)->postJson(route('pmb.checkout', $prospek->id), [
            'category' => Transaksi::KATEGORI_PENDAFTARAN,
            'items'    => ['dpp'],
        ]);
        $resDpp->assertOk()->assertJson(['nominal' => 1200000]);
        $this->settleOrder($resDpp->json('order_id'), 1200000);

        // 3. Karena Uang Gedung = 0, setelah DPP lunas UKT langsung terbuka!
        $bayarAfterDpp = $this->actingAs($camaba)->get(route('pmb.akun.bayar'));
        $bayarAfterDpp->assertOk();
        $bayarAfterDpp->assertSee('ukt: true', false);
        $bayarAfterDpp->assertSee('Gratis (Rp 0)');

        $resUkt = $this->actingAs($camaba)->postJson(route('pmb.checkout', $prospek->id), [
            'category'   => Transaksi::KATEGORI_PENDAFTARAN,
            'items'      => ['ukt'],
            'ukt_period' => '6_bulan',
        ]);
        $resUkt->assertOk()->assertJson([
            'success' => true,
            'nominal' => 4300000,
        ]);
        $this->settleOrder($resUkt->json('order_id'), 4300000);

        $regStatus = MidtransService::getCategoryPaymentStatus($prospek->fresh(), Transaksi::KATEGORI_PENDAFTARAN);
        $this->assertEquals(2200000, $regStatus['total_tagihan']);
        $this->assertEquals(2200000, $regStatus['sudah_dibayar']);
        $this->assertEquals(0, $regStatus['sisa_tagihan']);
        $this->assertEquals('LUNAS', $regStatus['status']);
    }

    public function test_switching_prodi_or_updating_prodi_price_does_not_reuse_stale_pending_transaction(): void
    {
        $prodiAwal = Prodi::create([
            'kode'               => 'MI-D3',
            'nama'               => 'Manajemen Informatika',
            'jenjang'            => 'D3',
            'fakultas'           => 'FTI',
            'kuota'              => 50,
            'status'             => 'Aktif',
            'biaya_pendaftaran'  => 250000,
            'biaya_perlengkapan' => 600000,
            'biaya_dpp'          => 500000,
            'biaya_uang_gedung'  => 0,
        ]);

        $prodiBaru = Prodi::create([
            'kode'               => 'KA-D3',
            'nama'               => 'Komputerisasi Akuntansi',
            'jenjang'            => 'D3',
            'fakultas'           => 'FTI',
            'kuota'              => 50,
            'status'             => 'Aktif',
            'biaya_pendaftaran'  => 375000,
            'biaya_perlengkapan' => 600000,
            'biaya_dpp'          => 500000,
            'biaya_uang_gedung'  => 0,
        ]);

        [$camaba, $prospek] = $this->createCamabaWithProspek($prodiAwal, 'Sudah Isi');

        // 1. Checkout pertama menggunakan Prodi Awal (250k)
        $res1 = $this->actingAs($camaba)->postJson(route('pmb.checkout', $prospek->id), [
            'category' => Transaksi::KATEGORI_PENDAFTARAN,
            'items'    => ['pendaftaran'],
        ]);
        $res1->assertOk()->assertJson(['nominal' => 250000]);
        $order1 = $res1->json('order_id');

        // 2. Pendaftar berpindah prodi (atau Admin menaikkan harga prodi) menjadi 375k
        $prospek->update(['prodi_id' => $prodiBaru->id]);

        $res2 = $this->actingAs($camaba)->postJson(route('pmb.checkout', $prospek->id), [
            'category' => Transaksi::KATEGORI_PENDAFTARAN,
            'items'    => ['pendaftaran'],
        ]);
        $res2->assertOk()->assertJson(['nominal' => 375000]);
        $this->assertNotEquals($order1, $res2->json('order_id'));
    }

    public function test_checkout_ukt_with_selected_active_period_and_rejects_inactive_period(): void
    {
        $prodi = Prodi::create([
            'kode'               => 'SI-S1-UKT',
            'nama'               => 'Sistem Informasi',
            'jenjang'            => 'S1',
            'fakultas'           => 'FTI',
            'kuota'              => 80,
            'status'             => 'Aktif',
            'biaya_pendaftaran'  => 0,
            'biaya_perlengkapan' => 0,
            'biaya_dpp'          => 0,
            'biaya_uang_gedung'  => 0,
            'ukt_options'        => [
                '1_bulan'  => ['key' => '1_bulan', 'label' => 'Per Bulan (1 Bulan)', 'nominal' => 700000, 'is_active' => true],
                '3_bulan'  => ['key' => '3_bulan', 'label' => 'Per 3 Bulan (Triwulan)', 'nominal' => 2000000, 'is_active' => false],
                '48_bulan' => ['key' => '48_bulan', 'label' => 'Per 48 Bulan (4 Tahun)', 'nominal' => 28000000, 'is_active' => true],
            ],
        ]);

        [$camaba, $prospek] = $this->createCamabaWithProspek($prodi, 'Sudah Isi');

        // 1. Halaman bayar menampilkan opsi UKT aktif saja di dropdown #select-ukt-period meskipun komponen 1-4 bernilai Rp 0
        $bayarRes = $this->actingAs($camaba)->get(route('pmb.akun.bayar'));
        $bayarRes->assertOk();
        $bayarRes->assertSee('id="select-ukt-period"', false);
        $bayarRes->assertSee('value="1_bulan"', false);
        $bayarRes->assertSee('value="48_bulan"', false);
        $bayarRes->assertDontSee('value="3_bulan"', false);

        // 2. Periode nonaktif harus ditolak (422)
        $badRes = $this->actingAs($camaba)->postJson(route('pmb.checkout', $prospek->id), [
            'category'   => Transaksi::KATEGORI_PENDAFTARAN,
            'items'      => ['ukt'],
            'ukt_period' => '3_bulan',
        ]);
        $badRes->assertStatus(422)->assertJson(['success' => false]);

        // 3. Checkout UKT periode aktif 48_bulan berhasil & sinkron dengan Snap payload
        $okRes = $this->actingAs($camaba)->postJson(route('pmb.checkout', $prospek->id), [
            'category'   => Transaksi::KATEGORI_PENDAFTARAN,
            'items'      => ['pendaftaran', 'ukt'],
            'ukt_period' => '48_bulan',
        ]);
        $okRes->assertOk()->assertJson([
            'success' => true,
            'nominal' => 28000000,
        ]);

        $trx = Transaksi::where('order_id', $okRes->json('order_id'))->firstOrFail();
        $snapPayload = app(MidtransService::class)->buildSnapPayload($trx);
        $this->assertEquals(28000000, $snapPayload['transaction_details']['gross_amount']);
        $this->assertEquals('PMB-UKT-48-BULAN', $snapPayload['item_details'][0]['id']);
        $this->assertEquals(28000000, $snapPayload['item_details'][0]['price']);
    }
}
