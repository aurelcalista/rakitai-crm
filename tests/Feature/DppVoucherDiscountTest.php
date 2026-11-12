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
            'code'                => 'UCIC20',
            'discount_percentage' => 20,
            'is_active'           => true,
        ]);

        // 1. Belum bayar Pendaftaran & Perlengkapan -> form voucher tidak boleh tampil
        $resBeforeStage1 = $this->actingAs($camaba)->get(route('pmb.akun.bayar'));
        $resBeforeStage1->assertOk();
        $resBeforeStage1->assertDontSee('Gunakan Voucher');
        $resBeforeStage1->assertDontSee('dpp-voucher-box');

        // Backend menolak pengajuan voucher sebelum tahap DPP
        $this->actingAs($camaba)
            ->postJson(route('pmb.akun.voucher.apply'), ['code' => 'UCIC20'])
            ->assertStatus(422)
            ->assertJson(['success' => false]);

        // 2. Baru bayar Pendaftaran saja (Perlengkapan belum) -> masih belum masuk tahap DPP
        Transaksi::create([
            'prospek_id'        => $prospek->id,
            'user_id'           => $camaba->id,
            'jenis'             => 'Beli Formulir',
            'kategori'          => Transaksi::KATEGORI_PENDAFTARAN,
            'nominal'           => 300000,
            'detail_pembayaran' => '• Biaya Pendaftaran PMB UCIC (Rp 300.000)',
            'metode_pembayaran' => 'MIDTRANS',
            'payment_status'    => Transaksi::STATUS_VERIFIED,
            'tanggal'           => now(),
            'verified_at'       => now(),
        ]);

        $resHalfStage1 = $this->actingAs($camaba)->get(route('pmb.akun.bayar'));
        $resHalfStage1->assertOk();
        $resHalfStage1->assertDontSee('Gunakan Voucher');

        // 3. Perlengkapan juga lunas -> masuk tahap DPP -> form voucher muncul!
        Transaksi::create([
            'prospek_id'        => $prospek->id,
            'user_id'           => $camaba->id,
            'jenis'             => 'Beli Formulir',
            'kategori'          => Transaksi::KATEGORI_PENDAFTARAN,
            'nominal'           => 600000,
            'detail_pembayaran' => '• Biaya Perlengkapan Kuliah (Rp 600.000)',
            'metode_pembayaran' => 'MIDTRANS',
            'payment_status'    => Transaksi::STATUS_VERIFIED,
            'tanggal'           => now(),
            'verified_at'       => now(),
        ]);

        $resAtDpp = $this->actingAs($camaba)->get(route('pmb.akun.bayar'));
        $resAtDpp->assertOk();
        $resAtDpp->assertSee('Gunakan Voucher');
        $resAtDpp->assertSee('dpp-voucher-box');

        // 4. DPP dibayar lunas tanpa voucher -> form voucher tidak tampil lagi
        Transaksi::create([
            'prospek_id'        => $prospek->id,
            'user_id'           => $camaba->id,
            'jenis'             => 'Beli Formulir',
            'kategori'          => Transaksi::KATEGORI_PENDAFTARAN,
            'nominal'           => 5000000,
            'detail_pembayaran' => '• Biaya Dana Pengembangan Pendidikan (DPP) (Rp 5.000.000)',
            'metode_pembayaran' => 'MIDTRANS',
            'payment_status'    => Transaksi::STATUS_VERIFIED,
            'tanggal'           => now(),
            'verified_at'       => now(),
        ]);

        $resAfterDpp = $this->actingAs($camaba)->get(route('pmb.akun.bayar'));
        $resAfterDpp->assertOk();
        $resAfterDpp->assertDontSee('Gunakan Voucher');
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
        [$camaba, $prospek] = $this->createCamabaWithProspek('Citra UCIC20');
        $this->markStage1Paid($prospek);

        $voucher = Voucher::create([
            'code'                => 'UCIC20',
            'discount_percentage' => 20,
            'is_active'           => true,
        ]);

        // 1. Terapkan voucher (huruf kecil + spasi diuji normalisasi)
        $applyRes = $this->actingAs($camaba)->postJson(route('pmb.akun.voucher.apply'), [
            'code'                => ' ucic20 ',
            'discount_percentage' => 99, // Upaya manipulasi frontend harus diabaikan
            'discount_amount'     => 4999999,
        ]);

        $applyRes->assertOk()
            ->assertJson([
                'success' => true,
                'voucher' => [
                    'voucher_code'        => 'UCIC20',
                    'discount_percentage' => 20,
                    'dpp_before_discount' => 5000000,
                    'discount_amount'     => 1000000,
                    'dpp_after_discount'  => 4000000,
                    'status'              => VoucherUsage::STATUS_APPLIED,
                ],
                'dpp_summary' => [
                    'base_price'                => 5000000,
                    'remaining_before_discount' => 5000000,
                    'discount_amount'           => 1000000,
                    'remaining_payable'         => 4000000,
                ],
            ]);

        // 2. Pastikan kode yang sama tidak dapat dipakai berulang untuk pengurangan ganda
        $this->actingAs($camaba)
            ->postJson(route('pmb.akun.voucher.apply'), ['code' => 'UCIC20'])
            ->assertStatus(422);

        // 3. Checkout DPP -> nominal wajib bayar tepat Rp 4.000.000 (bukan 5jt)
        $checkoutRes = $this->actingAs($camaba)->postJson(route('pmb.checkout', $prospek->id), [
            'items'               => ['dpp'],
            'payment_method'      => 'online',
            'discount_percentage' => 95, // Manipulasi checkout harus diabaikan
        ]);

        $checkoutRes->assertOk()
            ->assertJson([
                'success'           => true,
                'nominal'           => 4000000,
                'nominal_formatted' => 'Rp 4.000.000',
            ]);

        $orderId = $checkoutRes->json('order_id');
        $transaksi = Transaksi::where('order_id', $orderId)->firstOrFail();
        $this->assertEquals(4000000, (int) $transaksi->nominal);

        // Pastikan payload Midtrans Snap item_details & gross_amount konsisten Rp 4.000.000
        $snapPayload = app(MidtransService::class)->buildSnapPayload($transaksi);
        $this->assertEquals(4000000, $snapPayload['transaction_details']['gross_amount']);
        $this->assertEquals(4000000, array_sum(array_column($snapPayload['item_details'], 'price')));

        // 4. Simulasi verifikasi pembayaran berhasil -> status voucher menjadi 'used' & DPP lunas
        $this->actingAs($this->admin)->post(route('pmb.pembayaran.verify', $transaksi->id))
            ->assertSessionHas('success');

        $usage = VoucherUsage::where('prospek_id', $prospek->id)->where('voucher_id', $voucher->id)->firstOrFail();
        $this->assertSame(VoucherUsage::STATUS_USED, $usage->status);
        $this->assertNotNull($usage->used_at);
        $this->assertSame($transaksi->id, $usage->transaksi_id);

        // Tahap selanjutnya (UKT karena Uang Gedung Rp 0) otomatis terbuka
        $gateAfter = MidtransService::getSequentialGateState($prospek->fresh(['prodi', 'transaksis']));
        $this->assertTrue($gateAfter['paid']['dpp']);
        $this->assertTrue($gateAfter['unlocked']['ukt']);

        // Tidak boleh bisa memakai voucher yang sama lagi setelah lunas
        $this->actingAs($camaba)
            ->postJson(route('pmb.akun.voucher.apply'), ['code' => 'UCIC20'])
            ->assertStatus(422);

        // 5. Jika Admin mengubah persentase voucher & menonaktifkan voucher setelah transaksi berhasil,
        // riwayat CAMABA HARUS tetap menyimpan 20% & potongan Rp 1.000.000
        $this->actingAs($this->admin)->put(route('pmb.pembayaran.vouchers.update', $voucher->id), [
            'discount_percentage' => 50,
            'is_active'           => '0',
        ])->assertSessionHas('success');

        $rekap = PmbPembayaranController::calculateRekap($prospek->fresh(['prodi', 'transaksis']));
        $this->assertEquals(4000000, $rekap['rincian']['dpp']['kewajiban']);
        $this->assertEquals(5000000, $rekap['rincian']['dpp']['kewajiban_awal']);
        $this->assertEquals(1000000, $rekap['rincian']['dpp']['diskon_voucher']);
        $this->assertEquals(4000000, $rekap['rincian']['dpp']['terbayar']);
        $this->assertEquals(0, $rekap['rincian']['dpp']['sisa']);
        $this->assertTrue($rekap['rincian']['dpp']['is_lunas']);
        $this->assertSame('UCIC20', $rekap['dpp_voucher']['voucher_code']);
        $this->assertEquals(20.0, $rekap['dpp_voucher']['discount_percentage']);
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

    public function test_partial_dpp_payment_calculates_remaining_bill_correctly_without_double_discount(): void
    {
        [$camaba, $prospek] = $this->createCamabaWithProspek('Eka Cicil DPP');
        $this->markStage1Paid($prospek);

        // Pembayaran sebagian DPP sebesar Rp 2.000.000 dari Rp 5.000.000
        Transaksi::create([
            'prospek_id'        => $prospek->id,
            'user_id'           => $this->admin->id,
            'jenis'             => 'DPP',
            'kategori'          => Transaksi::KATEGORI_PENDAFTARAN,
            'nominal'           => 2000000,
            'detail_pembayaran' => '• Pembayaran Sebagian DPP (Rp 2.000.000)',
            'metode_pembayaran' => 'TRANSFER_MANUAL',
            'payment_status'    => Transaksi::STATUS_VERIFIED,
            'tanggal'           => now(),
            'verified_at'       => now(),
        ]);

        // Pastikan DPP belum dianggap lunas dan sisa sebelum diskon = Rp 3.000.000
        $summaryBefore = VoucherDiscountService::getDppSummary($prospek->fresh(['prodi', 'transaksis']));
        $this->assertFalse($summaryBefore['is_paid']);
        $this->assertEquals(3000000, $summaryBefore['remaining_before_discount']);

        Voucher::create([
            'code'                => 'UCIC20',
            'discount_percentage' => 20,
            'is_active'           => true,
        ]);

        // Gunakan voucher pada sisa tagihan Rp 3.000.000 -> potongan Rp 600.000 -> wajib bayar Rp 2.400.000
        $this->actingAs($camaba)->postJson(route('pmb.akun.voucher.apply'), ['code' => 'UCIC20'])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'voucher' => [
                    'dpp_before_discount' => 3000000,
                    'discount_amount'     => 600000,
                    'dpp_after_discount'  => 2400000,
                ],
                'dpp_summary' => [
                    'paid_partial'      => 2000000,
                    'discount_amount'   => 600000,
                    'remaining_payable' => 2400000,
                ],
            ]);

        $checkoutRes = $this->actingAs($camaba)->postJson(route('pmb.checkout', $prospek->id), [
            'items' => ['dpp'],
        ]);
        $checkoutRes->assertOk()->assertJson(['nominal' => 2400000]);

        $trx2 = Transaksi::where('order_id', $checkoutRes->json('order_id'))->firstOrFail();
        $this->actingAs($this->admin)->post(route('pmb.pembayaran.verify', $trx2->id));

        $rekapAfter = PmbPembayaranController::calculateRekap($prospek->fresh(['prodi', 'transaksis']));
        $this->assertEquals(4400000, $rekapAfter['rincian']['dpp']['kewajiban']);
        $this->assertEquals(4400000, $rekapAfter['rincian']['dpp']['terbayar']);
        $this->assertEquals(0, $rekapAfter['rincian']['dpp']['sisa']);
        $this->assertTrue($rekapAfter['rincian']['dpp']['is_lunas']);
    }

    public function test_admin_voucher_management_rbac_and_data_pembayaran_summary_cards(): void
    {
        [$camabaNoVoucher, $prospekNoVoucher] = $this->createCamabaWithProspek('Farah Belum Voucher');
        [$camabaUsed, $prospekUsed] = $this->createCamabaWithProspek('Gita Pakai Voucher');
        $this->markStage1Paid($prospekUsed);

        // Sebelum ada voucher terpakai, Admin melihat status Belum Menggunakan Voucher
        $this->actingAs($this->admin)
            ->get(route('pmb.pembayaran.index'))
            ->assertOk()
            ->assertSee('Belum Menggunakan Voucher');

        // CAMABA dan CS tidak boleh membuat / mengubah voucher
        $this->actingAs($camabaNoVoucher)
            ->post(route('pmb.pembayaran.vouchers.store'), [
                'code'                => 'HACK50',
                'discount_percentage' => 50,
                'is_active'           => 1,
            ])->assertForbidden();

        $this->actingAs($this->csUser)
            ->post(route('pmb.pembayaran.vouchers.store'), [
                'code'                => 'CSHACK',
                'discount_percentage' => 50,
                'is_active'           => 1,
            ])->assertForbidden();

        // Admin menambahkan voucher baru lengkap dengan Nama Voucher
        $this->actingAs($this->admin)
            ->post(route('pmb.pembayaran.vouchers.store'), [
                'name'                => 'Beasiswa Prestasi UCIC',
                'code'                => 'PROMO15',
                'discount_percentage' => 15,
                'is_active'           => 1,
            ])->assertSessionHas('success');

        $voucher = Voucher::where('code', 'PROMO15')->firstOrFail();
        $this->assertEquals('Beasiswa Prestasi UCIC', $voucher->name);

        // Admin toggle nonaktif -> aktif
        $this->actingAs($this->admin)->patch(route('pmb.pembayaran.vouchers.toggle', $voucher->id));
        $this->assertFalse($voucher->fresh()->is_active);
        $this->actingAs($this->admin)->patch(route('pmb.pembayaran.vouchers.toggle', $voucher->id));
        $this->assertTrue($voucher->fresh()->is_active);

        // Gita menggunakan voucher PROMO15 lalu membayar
        $this->actingAs($camabaUsed)->postJson(route('pmb.akun.voucher.apply'), ['code' => 'PROMO15'])
            ->assertOk()
            ->assertJsonPath('voucher.voucher_name', 'Beasiswa Prestasi UCIC');

        // Jika nama voucher master diubah Admin setelah dipakai, snapshot voucher_name pada riwayat Gita tetap utuh
        $this->actingAs($this->admin)
            ->put(route('pmb.pembayaran.vouchers.update', $voucher->id), [
                'name'                => 'Beasiswa Prestasi Diubah',
                'discount_percentage' => 25,
                'is_active'           => 1,
            ])->assertSessionHas('success');
        $this->assertEquals('Beasiswa Prestasi Diubah', $voucher->fresh()->name);

        $checkoutRes = $this->actingAs($camabaUsed)->postJson(route('pmb.checkout', $prospekUsed->id), ['items' => ['dpp']]);
        $trx = Transaksi::where('order_id', $checkoutRes->json('order_id'))->firstOrFail();
        $this->actingAs($this->admin)->post(route('pmb.pembayaran.verify', $trx->id));

        // Kembalikan persentase voucher usage snapshot assertion & cek tampilan Nama Voucher
        $resAdmin = $this->actingAs($this->admin)->get(route('pmb.pembayaran.index'));
        $resAdmin->assertOk()
            ->assertSee('PROMO15')
            ->assertSee('Beasiswa Prestasi UCIC')
            ->assertSee('Beasiswa Prestasi Diubah')
            ->assertSee('Gita Pakai Voucher')
            ->assertSee('15%')
            ->assertSee('Rp 750.000')
            ->assertSee('Rp 5.000.000')
            ->assertSee('Rp 4.250.000')
            ->assertSee('Sudah Digunakan')
            ->assertSee('Belum Menggunakan Voucher');

        unset($prospekNoVoucher);
    }
}

