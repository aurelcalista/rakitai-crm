<?php

namespace Tests\Feature;

use App\Http\Controllers\Pmb\PmbPembayaranController;
use App\Models\Prodi;
use App\Models\Prospek;
use App\Models\Transaksi;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherUsage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PmbPembayaranTrashTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $hm;
    protected User $cs;
    protected User $sales;
    protected Prodi $prodi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['name' => 'Admin Keuangan UCIC', 'role' => 'Admin']);
        $this->hm = User::factory()->create(['name' => 'Head Marketing', 'role' => 'HM']);
        $this->cs = User::factory()->create(['name' => 'CS PMB', 'role' => 'CS']);
        $this->sales = User::factory()->create(['name' => 'Sales PMB', 'role' => 'Sales']);

        $this->prodi = Prodi::create([
            'kode'               => 'SI-TRASH',
            'nama'               => 'Sistem Informasi',
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

    private function createCamabaWithPayments(string $name = 'Budi Santoso', bool $withTransactions = true, bool $withVoucher = true): array
    {
        static $counter = 1;
        $num = $counter++;

        $camabaUser = User::factory()->create([
            'name'  => $name,
            'email' => "camaba_trash_{$num}@ucic.ac.id",
            'role'  => 'Camaba',
        ]);

        Storage::disk('public')->put("pmb/bukti/bukti_{$num}.jpg", 'dummy-proof-image-bytes');

        $prospek = Prospek::create([
            'user_id'         => $camabaUser->id,
            'name'            => $name,
            'email'           => $camabaUser->email,
            'whatsapp'        => '0812345000' . str_pad((string) $num, 2, '0', STR_PAD_LEFT),
            'no_registrasi'   => 'PMB-2026-' . str_pad((string) $num, 4, '0', STR_PAD_LEFT),
            'prodi_id'        => $this->prodi->id,
            'status'          => 'Pembayaran Termin 1',
            'status_formulir' => 'Sudah Isi',
            'berkas_data'     => [
                'bukti_transfer' => [
                    'path' => "pmb/bukti/bukti_{$num}.jpg",
                    'name' => "bukti_{$num}.jpg",
                ],
            ],
        ]);

        $transactions = [];
        $voucherUsage = null;

        if ($withTransactions) {
            $transactions[] = Transaksi::create([
                'prospek_id'        => $prospek->id,
                'user_id'           => $this->admin->id,
                'order_id'          => "PMB-REG-{$num}",
                'jenis'             => 'Pendaftaran PMB',
                'kategori'          => Transaksi::KATEGORI_PENDAFTARAN,
                'nominal'           => 300000,
                'detail_pembayaran' => '• Biaya Pendaftaran PMB',
                'metode_pembayaran' => 'TRANSFER_MANUAL',
                'payment_status'    => Transaksi::STATUS_VERIFIED,
                'tanggal'           => now(),
                'notes'             => 'Bukti transfer diverifikasi via WA',
            ]);

            $transactions[] = Transaksi::create([
                'prospek_id'        => $prospek->id,
                'user_id'           => $this->admin->id,
                'order_id'          => "PMB-PRL-{$num}",
                'jenis'             => 'Biaya Perlengkapan',
                'kategori'          => 'Biaya Perlengkapan',
                'nominal'           => 600000,
                'detail_pembayaran' => '• Biaya Perlengkapan',
                'metode_pembayaran' => 'MIDTRANS',
                'payment_type'      => 'qris',
                'payment_status'    => Transaksi::STATUS_VERIFIED,
                'tanggal'           => now(),
            ]);

            if ($withVoucher) {
                $voucher = Voucher::create([
                    'code'                => "UCIC20_{$num}",
                    'discount_percentage' => 20,
                    'is_active'           => true,
                    'created_by'          => $this->admin->id,
                ]);

                $dppTrx = Transaksi::create([
                    'prospek_id'        => $prospek->id,
                    'user_id'           => $this->admin->id,
                    'order_id'          => "PMB-DPP-{$num}",
                    'jenis'             => 'DPP',
                    'kategori'          => 'DPP',
                    'nominal'           => 4000000,
                    'detail_pembayaran' => "• DPP (Diskon Voucher {$voucher->code} 20%)",
                    'metode_pembayaran' => 'TRANSFER_MANUAL',
                    'payment_status'    => Transaksi::STATUS_VERIFIED,
                    'tanggal'           => now(),
                ]);

                $transactions[] = $dppTrx;

                $voucherUsage = VoucherUsage::create([
                    'voucher_id'          => $voucher->id,
                    'prospek_id'          => $prospek->id,
                    'transaksi_id'        => $dppTrx->id,
                    'voucher_code'        => $voucher->code,
                    'discount_percentage' => 20,
                    'dpp_before_discount' => 5000000,
                    'discount_amount'     => 1000000,
                    'dpp_after_discount'  => 4000000,
                    'status'              => VoucherUsage::STATUS_USED,
                    'used_at'             => now(),
                ]);
            }
        }

        return [
            'user'          => $camabaUser,
            'prospek'       => $prospek->fresh(['prodi', 'transaksis', 'voucherUsages']),
            'transactions'  => $transactions,
            'voucher_usage' => $voucherUsage,
            'proof_path'    => "pmb/bukti/bukti_{$num}.jpg",
        ];
    }

    public function test_1_to_3_soft_delete_moves_data_to_trash_and_hides_from_main_list(): void
    {
        Storage::fake('public');
        $data = $this->createCamabaWithPayments('Siti Nurhaliza');
        $prospek = $data['prospek'];

        // Admin melihat data aktif di daftar utama & tombol Hapus / Tab Trash
        $indexBefore = $this->actingAs($this->admin)->get(route('pmb.pembayaran.index'));
        $indexBefore->assertOk();
        $indexBefore->assertSee('Siti Nurhaliza');
        $indexBefore->assertSee($prospek->no_registrasi);
        $indexBefore->assertSee('Trash (Tempat Sampah)');

        // 1. Menghapus satu data pembayaran via Soft Delete
        $deleteRes = $this->actingAs($this->admin)->delete(route('pmb.pembayaran.destroy', $prospek->id));
        $deleteRes->assertRedirect();
        $deleteRes->assertSessionHas('success');

        // Soft deleted: masih ada di DB dengan deleted_at dan deleted_by terisi
        $this->assertSoftDeleted('prospeks', ['id' => $prospek->id]);
        $trashedModel = Prospek::onlyTrashed()->find($prospek->id);
        $this->assertNotNull($trashedModel);
        $this->assertEquals($this->admin->id, $trashedModel->deleted_by);

        // 2. Hilang dari daftar utama Data Pembayaran, hitungan stage card, voucher aktif, & Export CSV
        $indexAfter = $this->actingAs($this->admin)->get(route('pmb.pembayaran.index'));
        $indexAfter->assertOk();
        $indexAfter->assertSee("Data pembayaran Siti Nurhaliza ({$prospek->no_registrasi}) berhasil dipindahkan ke Trash.");
        $this->assertFalse($indexAfter->viewData('rekapList')->contains('id', $prospek->id));
        $this->assertEquals(0, $indexAfter->viewData('countSemua'));
        $this->assertSame(0, $indexAfter->viewData('voucherSummaryUsages')->count());
        $this->assertEquals(1, $indexAfter->viewData('trashCount'));

        // Pastikan CSV export juga mengecualikan data yang di-soft-delete
        $exportRes = $this->actingAs($this->admin)->get(route('pmb.pembayaran.export'));
        $exportRes->assertOk();
        $this->assertStringNotContainsString($prospek->no_registrasi, $exportRes->streamedContent());

        // 3. Muncul di halaman Trash beserta seluruh metadata & info pembayaran + admin penghapus
        $trashRes = $this->actingAs($this->admin)->get(route('pmb.pembayaran.trash'));
        $trashRes->assertOk();
        $trashRes->assertSee('Siti Nurhaliza');
        $trashRes->assertSee($prospek->no_registrasi);
        $trashRes->assertSee($prospek->email);
        $trashRes->assertSee('Sistem Informasi');
        $trashRes->assertSee('Admin Keuangan UCIC');
        $trashRes->assertSee('3 Transaksi');
        $trashRes->assertSee($data['voucher_usage']->voucher_code);

        // Search filter di Trash bekerja
        $searchFound = $this->actingAs($this->admin)->get(route('pmb.pembayaran.trash', ['search' => $prospek->no_registrasi]));
        $searchFound->assertOk()->assertSee('Siti Nurhaliza');

        $searchMissing = $this->actingAs($this->admin)->get(route('pmb.pembayaran.trash', ['search' => 'TIDAK-ADA-999']));
        $searchMissing->assertOk()->assertSee('Tidak ada data di Trash yang cocok');
        $this->assertSame(0, $searchMissing->viewData('trashedList')->count());
    }

    public function test_4_to_7_and_10_restore_returns_data_intact_without_duplication(): void
    {
        Storage::fake('public');
        $data = $this->createCamabaWithPayments('Andi Wijaya');
        $prospek = $data['prospek'];
        $proofPath = $data['proof_path'];
        $voucherUsage = $data['voucher_usage'];

        $rekapBefore = PmbPembayaranController::calculateRekap($prospek);
        $prospekCountBefore = Prospek::withTrashed()->count();
        $trxCountBefore = Transaksi::where('prospek_id', $prospek->id)->count();
        $voucherCountBefore = VoucherUsage::where('prospek_id', $prospek->id)->count();

        // Soft delete ke Trash
        $this->actingAs($this->admin)->delete(route('pmb.pembayaran.destroy', $prospek->id))
            ->assertRedirect();

        // Pastikan transaksi, voucher, dan bukti pembayaran tidak terhapus saat masuk Trash
        $this->assertEquals($trxCountBefore, Transaksi::where('prospek_id', $prospek->id)->count());
        $this->assertDatabaseHas('voucher_usages', [
            'id'              => $voucherUsage->id,
            'voucher_code'    => $voucherUsage->voucher_code,
            'discount_amount' => 1000000,
        ]);
        Storage::disk('public')->assertExists($proofPath);

        // 4. Pulihkan data dari Trash
        $restoreRes = $this->actingAs($this->admin)->post(route('pmb.pembayaran.restore', $prospek->id));
        $restoreRes->assertRedirect(route('pmb.pembayaran.trash'));
        $restoreRes->assertSessionHas('success');

        // Trash sekarang kosong (empty state muncul)
        $trashAfterRestore = $this->actingAs($this->admin)->get(route('pmb.pembayaran.trash'));
        $trashAfterRestore->assertOk()->assertSee('Trash Data Pembayaran masih kosong');

        // 5. Kembali tampil di daftar utama
        $indexRestored = $this->actingAs($this->admin)->get(route('pmb.pembayaran.index'));
        $indexRestored->assertOk();
        $indexRestored->assertSee('Andi Wijaya');
        $indexRestored->assertSee($prospek->no_registrasi);

        // 6 & 7. Seluruh informasi pembayaran, bukti, dan voucher tetap utuh & perhitungan sama persis
        $restoredProspek = Prospek::with(['prodi', 'transaksis', 'voucherUsages'])->findOrFail($prospek->id);
        $this->assertNull($restoredProspek->deleted_at);
        $this->assertNull($restoredProspek->deleted_by);

        $rekapAfter = PmbPembayaranController::calculateRekap($restoredProspek);
        $this->assertEquals($rekapBefore['total_kewajiban'], $rekapAfter['total_kewajiban']);
        $this->assertEquals($rekapBefore['total_terbayar'], $rekapAfter['total_terbayar']);
        $this->assertEquals($rekapBefore['total_sisa'], $rekapAfter['total_sisa']);
        $this->assertEquals($rekapBefore['status_keseluruhan'], $rekapAfter['status_keseluruhan']);
        $this->assertEquals($rekapBefore['rincian']['dpp']['voucher']['voucher_code'], $rekapAfter['rincian']['dpp']['voucher']['voucher_code']);

        Storage::disk('public')->assertExists($proofPath);
        $this->assertEquals($proofPath, $restoredProspek->berkas_data['bukti_transfer']['path']);

        // 10. Tidak ada duplikasi data setelah restore
        $this->assertEquals($prospekCountBefore, Prospek::withTrashed()->count());
        $this->assertEquals($trxCountBefore, Transaksi::where('prospek_id', $prospek->id)->count());
        $this->assertEquals($voucherCountBefore, VoucherUsage::where('prospek_id', $prospek->id)->count());
    }

    public function test_8_force_delete_blocks_records_with_financial_history_and_deletes_empty_records_safely(): void
    {
        Storage::fake('public');

        // Kasus A: Data memiliki transaksi pembayaran & voucher -> DILARANG hapus permanen
        $withFinance = $this->createCamabaWithPayments('Dewi Lestari', true, true);
        $finProspek = $withFinance['prospek'];
        $this->actingAs($this->admin)->delete(route('pmb.pembayaran.destroy', $finProspek->id));

        $blockedRes = $this->actingAs($this->admin)->delete(route('pmb.pembayaran.force-delete', $finProspek->id));
        $blockedRes->assertRedirect(route('pmb.pembayaran.trash'));
        $blockedRes->assertSessionHas('error');

        $this->assertSoftDeleted('prospeks', ['id' => $finProspek->id]);
        $this->assertDatabaseHas('transaksis', ['prospek_id' => $finProspek->id]);
        $this->assertDatabaseHas('voucher_usages', ['prospek_id' => $finProspek->id]);
        Storage::disk('public')->assertExists($withFinance['proof_path']);

        // Kasus B: Data dummy/kosong tanpa transaksi & tanpa voucher -> BOLEH hapus permanen (file fisik tetap aman)
        $noFinance = $this->createCamabaWithPayments('Eko Kosong', false, false);
        $emptyProspek = $noFinance['prospek'];
        $this->actingAs($this->admin)->delete(route('pmb.pembayaran.destroy', $emptyProspek->id));

        $forceRes = $this->actingAs($this->admin)->delete(route('pmb.pembayaran.force-delete', $emptyProspek->id));
        $forceRes->assertRedirect(route('pmb.pembayaran.trash'));
        $forceRes->assertSessionHas('success');

        $this->assertDatabaseMissing('prospeks', ['id' => $emptyProspek->id]);
        // Pastikan file fisik bukti/berkas tidak ikut terhapus
        Storage::disk('public')->assertExists($noFinance['proof_path']);
    }

    public function test_9_non_admin_roles_cannot_delete_access_trash_restore_or_force_delete(): void
    {
        Storage::fake('public');
        $data = $this->createCamabaWithPayments('Rina Protected', true, false);
        $prospek = $data['prospek'];

        // CS dan HM bisa mengakses Data Pembayaran index, tetapi TIDAK boleh melihat tab Trash atau tombol Hapus
        foreach ([$this->cs, $this->hm] as $roleUser) {
            $res = $this->actingAs($roleUser)->get(route('pmb.pembayaran.index'));
            $res->assertOk();
            $res->assertDontSee(route('pmb.pembayaran.trash'));
            $res->assertDontSee('Hapus Data Pembayaran ke Trash');
        }

        // Semua role selain Admin (HM, CS, Sales, Camaba) ditolak pada aksi Trash / Delete / Restore / Force Delete
        foreach ([$this->hm, $this->cs, $this->sales, $data['user']] as $unauthorizedUser) {
            $this->actingAs($unauthorizedUser)
                ->delete(route('pmb.pembayaran.destroy', $prospek->id))
                ->assertForbidden();

            $this->actingAs($unauthorizedUser)
                ->getJson(route('pmb.pembayaran.trash'))
                ->assertForbidden();

            // Manipulasi URL langsung ke restore/force-delete ditolak
            $this->actingAs($unauthorizedUser)
                ->post(route('pmb.pembayaran.restore', $prospek->id))
                ->assertForbidden();

            $this->actingAs($unauthorizedUser)
                ->delete(route('pmb.pembayaran.force-delete', $prospek->id))
                ->assertForbidden();
        }

        // Pastikan Admin pun tidak bisa memanipulasi restore / force-delete pada data aktif yang BELUM masuk Trash
        $this->actingAs($this->admin)
            ->post(route('pmb.pembayaran.restore', $prospek->id))
            ->assertNotFound();

        $this->actingAs($this->admin)
            ->delete(route('pmb.pembayaran.force-delete', $prospek->id))
            ->assertNotFound();

        $this->assertDatabaseHas('prospeks', ['id' => $prospek->id, 'deleted_at' => null]);
    }
}
