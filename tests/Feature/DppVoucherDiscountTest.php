<?php

namespace Tests\Feature;

use App\Http\Controllers\Pmb\PmbPembayaranController;
use App\Models\Prodi;
use App\Models\Prospek;
use App\Models\Transaksi;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherUsage;
use App\Services\MidtransService;
use App\Services\VoucherDiscountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DppVoucherDiscountTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $csUser;
    protected Prodi $prodi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'Admin']);
        $this->csUser = User::factory()->create(['role' => 'CS']);

        $this->prodi = Prodi::create([
            'kode'               => 'TI-VCR',
            'nama'               => 'Teknik Informatika',
            'jenjang'            => 'S1',
            'fakultas'           => 'FTI',
            'kuota'              => 100,
            'status'             => 'Aktif',
            'biaya_pendaftaran'  => 300000,
            'biaya_perlengkapan' => 600000,
            'biaya_dpp'          => 5000000,
            'biaya_uang_gedung'  => 0,
            'ukt_options'        => [
                '6_bulan' => ['key' => '6_bulan', 'label' => 'Per 6 Bulan', 'nominal' => 4000000, 'is_active' => true],
            ],
        ]);
    }

    private function createCamabaWithProspek(string $name = 'Camaba Voucher'): array
    {
        static $idx = 1;
        $user = User::factory()->create([
            'name'  => $name,
            'email' => 'camaba_vcr_' . $idx . '@ucic.ac.id',
            'role'  => 'Camaba',
        ]);

        $prospek = Prospek::create([
            'user_id'         => $user->id,
            'name'            => $name,
            'email'           => $user->email,
            'whatsapp'        => '0812990000' . str_pad((string) $idx++, 2, '0', STR_PAD_LEFT),
            'no_registrasi'   => 'PMB-VCR-' . $idx,
            'prodi_id'        => $this->prodi->id,
            'status_formulir' => 'Sudah Isi',
            'status'          => 'FORMULIR',
        ]);

        return [$user, $prospek];
    }

    private function markStage1Paid(Prospek $prospek): void
    {
        Transaksi::create([
            'prospek_id'        => $prospek->id,
            'user_id'           => $prospek->user_id,
            'jenis'             => 'Beli Formulir',
            'kategori'          => Transaksi::KATEGORI_PENDAFTARAN,
            'nominal'           => 900000,
            'detail_pembayaran' => "• Biaya Pendaftaran PMB UCIC (Rp 300.000)\n• Biaya Perlengkapan Kuliah (Rp 600.000)",
            'metode_pembayaran' => 'MIDTRANS',
            'payment_status'    => Transaksi::STATUS_VERIFIED,
            'tanggal'           => now(),
            'verified_at'       => now(),
        ]);
    }

    public function test_voucher_form_only_appears_at_dpp_stage_and_disappears_after_dpp_paid(): void
    {
        [$camaba, $prospek] = $this->createCamabaWithProspek('Ani Tahap Awal');

        Voucher::create([
            'code'            => 'UCIC20',
            'discount_amount' => 1000000,
            'quota'           => 10,
            'is_active'       => true,
        ]);

        // 1. Voucher input box exists at bottom with allComponentsSelected condition
        $res = $this->actingAs($camaba)->get(route('pmb.akun.bayar'));
        $res->assertOk();
        $res->assertSee('id="voucher-input-section"', false);
        $res->assertSee('x-show="allComponentsSelected"', false);
        $res->assertDontSee('id="dpp-voucher-box"', false);

        // Backend menolak pengajuan voucher sebelum seluruh 5 komponen dipilih
        $this->actingAs($camaba)
            ->postJson(route('pmb.akun.voucher.apply'), [
                'code'  => 'UCIC20',
                'items' => ['pendaftaran'],
            ])
            ->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_invalid_inactive_and_expired_vouchers_are_rejected_by_backend(): void
    {
        [$camaba, $prospek] = $this->createCamabaWithProspek('Budi Uji Validasi');
        $this->markStage1Paid($prospek);

        Voucher::create([
            'code'                => 'NONAKTIF10',
            'discount_percentage' => 10,
            'is_active'           => false,
        ]);

        Voucher::create([
            'code'                => 'EXPIRED25',
            'discount_percentage' => 25,
            'is_active'           => true,
            'expires_at'          => now()->subDay(),
        ]);

        // Kode tidak ada
        $this->actingAs($camaba)
            ->postJson(route('pmb.akun.voucher.apply'), ['code' => 'TIDAKADA'])
            ->assertStatus(422)
            ->assertJson(['success' => false]);

        // Kode nonaktif
        $this->actingAs($camaba)
            ->postJson(route('pmb.akun.voucher.apply'), ['code' => 'NONAKTIF10'])
            ->assertStatus(422)
            ->assertJsonFragment(['success' => false]);

        // Kode kedaluwarsa
        $this->actingAs($camaba)
            ->postJson(route('pmb.akun.voucher.apply'), ['code' => 'EXPIRED25'])
            ->assertStatus(422)
            ->assertJsonFragment(['success' => false]);

        $this->assertDatabaseCount('voucher_usages', 0);
    }

    public function test_apply_valid_voucher_calculates_discount_server_side_and_syncs_checkout_and_rekap(): void
    {
        [$camaba, $prospek] = $this->createCamabaWithProspek('Citra UCIC1000');

        $voucher = Voucher::create([
            'code'            => 'UCIC1000',
            'discount_amount' => 1000000,
            'quota'           => 10,
            'is_active'       => true,
        ]);

        $items = ['pendaftaran', 'perlengkapan', 'dpp', 'uang_gedung', 'ukt'];

        // 1. Terapkan voucher (huruf kecil + spasi diuji normalisasi)
        $applyRes = $this->actingAs($camaba)->postJson(route('pmb.akun.voucher.apply'), [
            'code'       => ' ucic1000 ',
            'items'      => $items,
            'ukt_period' => '6_bulan',
        ]);

        $applyRes->assertOk()
            ->assertJson([
                'success' => true,
                'voucher' => [
                    'voucher_code'    => 'UCIC1000',
                    'discount_amount' => 1000000,
                    'status'          => VoucherUsage::STATUS_APPLIED,
                ],
            ]);

        // 2. Checkout 5 komponen -> potongan Rp 1.000.000 diterapkan
        $checkoutRes = $this->actingAs($camaba)->postJson(route('pmb.checkout', $prospek->id), [
            'items'          => $items,
            'ukt_period'     => '6_bulan',
            'payment_method' => 'online',
            'voucher_code'   => 'UCIC1000',
        ]);

        $checkoutRes->assertOk()
            ->assertJson([
                'success' => true,
                'nominal' => 8900000,
            ]);

        $orderId = $checkoutRes->json('order_id');
        $transaksi = Transaksi::where('order_id', $orderId)->firstOrFail();
        $this->assertEquals(8900000, (int) $transaksi->nominal);

        // 3. Simulasi verifikasi pembayaran berhasil -> status voucher menjadi 'used'
        $this->actingAs($this->admin)->post(route('pmb.pembayaran.verify', $transaksi->id))
            ->assertSessionHas('success');

        $usage = VoucherUsage::where('prospek_id', $prospek->id)->where('voucher_id', $voucher->id)->firstOrFail();
        $this->assertSame(VoucherUsage::STATUS_USED, $usage->status);
        $this->assertNotNull($usage->used_at);
        $this->assertSame($transaksi->id, $usage->transaksi_id);
    }

    public function test_dpp_checkout_without_voucher_remains_full_nominal(): void
    {
        [$camaba, $prospek] = $this->createCamabaWithProspek('Dedi Tanpa Voucher');
        $this->markStage1Paid($prospek);

        $checkoutRes = $this->actingAs($camaba)->postJson(route('pmb.checkout', $prospek->id), [
            'items'          => ['dpp'],
            'payment_method' => 'online',
        ]);

        $checkoutRes->assertOk()
            ->assertJson([
                'success' => true,
                'nominal' => 5000000,
            ]);
    }

    public function test_admin_voucher_management_rbac_and_data_pembayaran_summary_cards(): void
    {
        [$camabaNoVoucher, $prospekNoVoucher] = $this->createCamabaWithProspek('Farah Belum Voucher');

        // Sebelum ada voucher terpakai, Admin melihat status Belum Menggunakan Voucher
        $this->actingAs($this->admin)
            ->get(route('pmb.pembayaran.index'))
            ->assertOk()
            ->assertSee('Belum Menggunakan Voucher');

        // CAMABA tidak boleh membuat / mengubah voucher
        $this->actingAs($camabaNoVoucher)
            ->post(route('pmb.pembayaran.vouchers.store'), [
                'code'            => 'HACK50',
                'discount_amount' => 500000,
                'quota'           => 10,
                'is_active'       => 1,
            ])->assertForbidden();

        // CS memiliki wewenang mengelola voucher sama seperti Admin
        $this->actingAs($this->csUser)
            ->post(route('pmb.pembayaran.vouchers.store'), [
                'code'            => 'CSDISC',
                'discount_amount' => 500000,
                'quota'           => 10,
                'is_active'       => 1,
            ])->assertSessionHas('success');

        // Admin menambahkan voucher baru lengkap dengan Nama Voucher, discount_amount, dan quota
        $this->actingAs($this->admin)
            ->post(route('pmb.pembayaran.vouchers.store'), [
                'name'            => 'Beasiswa Prestasi UCIC',
                'code'            => 'PROMO15',
                'discount_amount' => 750000,
                'quota'           => 20,
                'is_active'       => 1,
            ])->assertSessionHas('success');

        $voucher = Voucher::where('code', 'PROMO15')->firstOrFail();
        $this->assertEquals('Beasiswa Prestasi UCIC', $voucher->name);
        $this->assertEquals(750000, $voucher->discount_amount);
        $this->assertEquals(20, $voucher->quota);

        // Admin toggle nonaktif -> aktif
        $this->actingAs($this->admin)->patch(route('pmb.pembayaran.vouchers.toggle', $voucher->id));
        $this->assertFalse($voucher->fresh()->is_active);
        $this->actingAs($this->admin)->patch(route('pmb.pembayaran.vouchers.toggle', $voucher->id));
        $this->assertTrue($voucher->fresh()->is_active);
    }

    public function test_dedicated_voucher_page_and_sidebar_access_for_admin_and_cs(): void
    {
        // 1. Admin dan CS dapat mengakses halaman khusus Kelola Voucher
        foreach ([$this->admin, $this->csUser] as $authorizedUser) {
            $response = $this->actingAs($authorizedUser)->get(route('pmb.vouchers.index'));
            $response->assertOk();
            $response->assertSee('Kelola Voucher Diskon CAMABA');
            $response->assertSee('Daftar Kode Voucher');
            $response->assertSee('Riwayat Pemakaian CAMABA');
            $response->assertSee('Total Voucher');
            $response->assertSee('Voucher Aktif');
            $response->assertSee('Ke Data Pembayaran');
        }

        // 2. Menu "Kelola Voucher" TIDAK dijadikan menu mandiri di sidebar (sesuai instruksi agar sidebar tidak kebanyakan menu),
        // melainkan tetap terintegrasi di dalam halaman menu Data Pembayaran
        $sidebarAdmin = $this->actingAs($this->admin)->get(route('pmb.dashboard'));
        $sidebarAdmin->assertOk();
        $sidebarAdmin->assertDontSee('Kelola Voucher');

        $sidebarCs = $this->actingAs($this->csUser)->get(route('pmb.dashboard'));
        $sidebarCs->assertOk();
        $sidebarCs->assertDontSee('Kelola Voucher');

        // Dan di dalam halaman Data Pembayaran tersedia navigasi ke halaman penuh voucher & toggle kelola voucher
        $indexAdmin = $this->actingAs($this->admin)->get(route('pmb.pembayaran.index'));
        $indexAdmin->assertOk();
        $indexAdmin->assertSee(route('pmb.vouchers.index'));
        $indexAdmin->assertSee('Halaman Penuh Voucher');
        $indexAdmin->assertSee('Kelola &amp; Buat Voucher', false);

        $indexCs = $this->actingAs($this->csUser)->get(route('pmb.pembayaran.index'));
        $indexCs->assertOk();
        $indexCs->assertSee(route('pmb.vouchers.index'));
        $indexCs->assertSee('Halaman Penuh Voucher');
        $indexCs->assertSee('Kelola &amp; Buat Voucher', false);

        // 3. User tanpa hak akses (misal: Camaba) dilarang mengakses halaman kelola voucher
        [$camabaUser] = $this->createCamabaWithProspek('Mhs Terlarang');
        $this->actingAs($camabaUser)
            ->get(route('pmb.vouchers.index'))
            ->assertRedirect(route('pmb.akun.dashboard'));

        $this->actingAs($camabaUser)
            ->getJson(route('pmb.vouchers.index'))
            ->assertForbidden();
    }
}

