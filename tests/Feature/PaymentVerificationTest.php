<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\Prospek;
use App\Models\Transaksi;
use App\Models\User;
use App\Services\ProspekService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $sales;
    protected User $cs;
    protected Prospek $prospek;

    protected function setUp(): void
    {
        parent::setUp();

        // Buat users
        $this->sales = User::factory()->create(['role' => 'Sales']);
        $this->cs    = User::factory()->create(['role' => 'CS']);

        // Buat prospek milik Sales
        $this->prospek = Prospek::factory()->create([
            'sales_id'   => $this->sales->id,
            'status'     => 'PROSPEK',
            'stage_number' => 2,
        ]);
    }

    // ═══════════════════════════════════════════════════════
    // 1. Sales bisa membuat transaksi
    // ═══════════════════════════════════════════════════════
    public function test_sales_can_create_transaksi()
    {
        $this->actingAs($this->sales);
        $response = $this->post(route('prospek.transaksi', $this->prospek->id), [
            'jenis'   => 'Beli Formulir',
            'nominal' => 500000,
            'tanggal' => now()->toDateString(),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('transaksis', [
            'prospek_id' => $this->prospek->id,
            'jenis'      => 'Beli Formulir',
        ]);
    }

    // ═══════════════════════════════════════════════════════
    // 2-6. Sales bisa memilih jenis pembayaran (semua metode)
    // ═══════════════════════════════════════════════════════
    public function test_metode_virtual_account_available()
    {
        $this->actingAs($this->sales);
        // Pastikan Formulir dulu
        Transaksi::factory()->create(['prospek_id' => $this->prospek->id, 'jenis' => 'Beli Formulir', 'payment_status' => 'verified']);

        $response = $this->post(route('prospek.transaksi', $this->prospek->id), [
            'jenis'             => 'Pembayaran Termin 1',
            'nominal'           => 3000000,
            'tanggal'           => now()->toDateString(),
            'metode_pembayaran' => 'virtual_account',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('transaksis', [
            'jenis'             => 'Pembayaran Termin 1',
            'metode_pembayaran' => 'virtual_account',
            'payment_status'    => 'pending',
        ]);
    }

    public function test_metode_gopay_available()
    {
        $this->actingAs($this->sales);
        Transaksi::factory()->create(['prospek_id' => $this->prospek->id, 'jenis' => 'Beli Formulir', 'payment_status' => 'verified']);

        $response = $this->post(route('prospek.transaksi', $this->prospek->id), [
            'jenis'             => 'Pembayaran Termin 1',
            'nominal'           => 3000000,
            'tanggal'           => now()->toDateString(),
            'metode_pembayaran' => 'gopay',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('transaksis', ['metode_pembayaran' => 'gopay', 'payment_status' => 'pending']);
    }

    public function test_metode_dana_available()
    {
        $this->actingAs($this->sales);
        Transaksi::factory()->create(['prospek_id' => $this->prospek->id, 'jenis' => 'Beli Formulir', 'payment_status' => 'verified']);

        $response = $this->post(route('prospek.transaksi', $this->prospek->id), [
            'jenis'             => 'Pembayaran Termin 1',
            'nominal'           => 3000000,
            'tanggal'           => now()->toDateString(),
            'metode_pembayaran' => 'dana',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('transaksis', ['metode_pembayaran' => 'dana', 'payment_status' => 'pending']);
    }

    public function test_metode_bank_transfer_mandiri_available()
    {
        $this->actingAs($this->sales);
        Transaksi::factory()->create(['prospek_id' => $this->prospek->id, 'jenis' => 'Beli Formulir', 'payment_status' => 'verified']);

        $response = $this->post(route('prospek.transaksi', $this->prospek->id), [
            'jenis'             => 'Pembayaran Termin 1',
            'nominal'           => 3000000,
            'tanggal'           => now()->toDateString(),
            'metode_pembayaran' => 'bank_transfer',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('transaksis', ['metode_pembayaran' => 'bank_transfer', 'payment_status' => 'pending']);
    }

    // ═══════════════════════════════════════════════════════
    // 7. Rekening Mandiri aktif tersedia
    // ═══════════════════════════════════════════════════════
    public function test_active_mandiri_account_accessible()
    {
        BankAccount::create([
            'bank_name'      => 'Mandiri',
            'account_number' => '1234567890',
            'account_name'   => 'Universitas CIC',
            'is_active'      => true,
        ]);

        $accounts = BankAccount::where('bank_name', 'Mandiri')->where('is_active', true)->get();
        $this->assertNotEmpty($accounts);
        $this->assertEquals('1234567890', $accounts->first()->account_number);
    }

    // ═══════════════════════════════════════════════════════
    // 8. Transaksi baru berstatus pending
    // ═══════════════════════════════════════════════════════
    public function test_new_termin1_transaction_is_pending()
    {
        $this->actingAs($this->sales);
        Transaksi::factory()->create(['prospek_id' => $this->prospek->id, 'jenis' => 'Beli Formulir', 'payment_status' => 'verified']);

        $this->post(route('prospek.transaksi', $this->prospek->id), [
            'jenis'             => 'Pembayaran Termin 1',
            'nominal'           => 3000000,
            'tanggal'           => now()->toDateString(),
            'metode_pembayaran' => 'gopay',
        ]);

        $trx = Transaksi::where('prospek_id', $this->prospek->id)->where('jenis', 'Pembayaran Termin 1')->first();
        $this->assertNotNull($trx);
        $this->assertEquals('pending', $trx->payment_status);
    }

    // ═══════════════════════════════════════════════════════
    // 9. CS bisa melihat transaksi pending
    // ═══════════════════════════════════════════════════════
    public function test_cs_can_see_pending_transaksi()
    {
        $trx = Transaksi::factory()->create([
            'prospek_id'        => $this->prospek->id,
            'user_id'           => $this->sales->id,
            'jenis'             => 'Pembayaran Termin 1',
            'payment_status'    => 'pending',
            'metode_pembayaran' => 'gopay',
        ]);

        $this->actingAs($this->cs);
        $response = $this->get(route('cs.verifikasi.index'));
        $response->assertOk();
        $response->assertSee($this->prospek->name);
    }

    public function test_cs_verifikasi_displays_student_name_for_school_prospect()
    {
        $schoolProspect = Prospek::factory()->create([
            'sales_id' => $this->sales->id,
            'type'     => 'Sekolah',
            'name'     => 'SMK 1 CIREBON',
            'pic'      => 'Adinda Meilia',
            'whatsapp' => '0895398505257',
        ]);

        $trx = Transaksi::factory()->create([
            'prospek_id'        => $schoolProspect->id,
            'user_id'           => $this->sales->id,
            'jenis'             => 'Pembayaran Termin 1',
            'payment_status'    => 'pending',
            'metode_pembayaran' => 'bank_transfer',
        ]);

        $this->actingAs($this->cs);
        $response = $this->get(route('cs.verifikasi.index'));
        $response->assertOk();

        // Must display the student/camaba name (Adinda Meilia)
        $response->assertSee('Adinda Meilia');
        // Must also display the school name
        $response->assertSee('SMK 1 CIREBON');
    }

    // ═══════════════════════════════════════════════════════
    // 10-12. CS bisa verify → Prospek jadi LUNAS
    // ═══════════════════════════════════════════════════════
    public function test_cs_can_verify_transaksi_and_prospek_becomes_lunas()
    {
        // Setup: Formulir sudah ada
        Transaksi::factory()->create([
            'prospek_id'     => $this->prospek->id,
            'user_id'        => $this->sales->id,
            'jenis'          => 'Beli Formulir',
            'payment_status' => 'verified',
        ]);

        $trx = Transaksi::factory()->create([
            'prospek_id'        => $this->prospek->id,
            'user_id'           => $this->sales->id,
            'jenis'             => 'Pembayaran Termin 1',
            'payment_status'    => 'pending',
            'metode_pembayaran' => 'gopay',
        ]);

        $this->actingAs($this->cs);
        $response = $this->post(route('cs.verifikasi.verify', $trx->id));
        $response->assertRedirect();

        $trx->refresh();
        $this->assertEquals('verified', $trx->payment_status);
        $this->assertEquals($this->cs->id, $trx->verified_by);

        $this->prospek->refresh();
        $this->assertEquals('LUNAS', $this->prospek->status);
    }

    // ═══════════════════════════════════════════════════════
    // 13-14. Target Closing masuk ke Sales yang input transaksi
    // ═══════════════════════════════════════════════════════
    public function test_after_verify_closing_counts_for_sales_not_cs()
    {
        Transaksi::factory()->create([
            'prospek_id'     => $this->prospek->id,
            'user_id'        => $this->sales->id,
            'jenis'          => 'Beli Formulir',
            'payment_status' => 'verified',
        ]);
        $trx = Transaksi::factory()->create([
            'prospek_id'        => $this->prospek->id,
            'user_id'           => $this->sales->id,
            'jenis'             => 'Pembayaran Termin 1',
            'payment_status'    => 'pending',
            'metode_pembayaran' => 'gopay',
        ]);

        $this->actingAs($this->cs);
        $this->post(route('cs.verifikasi.verify', $trx->id));

        // Prospek harus LUNAS dan sales_id tetap milik Sales asli, bukan CS
        $this->prospek->refresh();
        $this->assertEquals('LUNAS', $this->prospek->status);
        $this->assertEquals($this->sales->id, $this->prospek->sales_id, 'Sales ID harus tetap milik Sales asli, bukan CS');

        // CS tidak mendapat kredit target — verified_by adalah CS, tapi sales_id tidak berubah
        $this->assertNotEquals($this->cs->id, $this->prospek->sales_id);

        // Transaksi yang di-verify oleh CS tercatat verified_by milik CS
        $trx->refresh();
        $this->assertEquals($this->cs->id, $trx->verified_by);
        $this->assertNotEquals($this->cs->id, $trx->user_id, 'user_id transaksi tetap Sales asli yang input');
    }

    // ═══════════════════════════════════════════════════════
    // 16. CS bisa reject
    // ═══════════════════════════════════════════════════════
    public function test_cs_can_reject_transaksi()
    {
        $trx = Transaksi::factory()->create([
            'prospek_id'        => $this->prospek->id,
            'user_id'           => $this->sales->id,
            'jenis'             => 'Pembayaran Termin 1',
            'payment_status'    => 'pending',
            'metode_pembayaran' => 'bank_transfer',
        ]);

        $this->actingAs($this->cs);
        $response = $this->post(route('cs.verifikasi.reject', $trx->id), [
            'rejection_reason' => 'Bukti transfer tidak valid',
        ]);
        $response->assertRedirect();

        $trx->refresh();
        $this->assertEquals('rejected', $trx->payment_status);
        $this->assertEquals('Bukti transfer tidak valid', $trx->rejection_reason);
    }

    // ═══════════════════════════════════════════════════════
    // 17. Reject tidak menambah target (prospek tidak LUNAS)
    // ═══════════════════════════════════════════════════════
    public function test_reject_does_not_make_prospek_lunas()
    {
        Transaksi::factory()->create([
            'prospek_id'     => $this->prospek->id,
            'user_id'        => $this->sales->id,
            'jenis'          => 'Beli Formulir',
            'payment_status' => 'verified',
        ]);
        $trx = Transaksi::factory()->create([
            'prospek_id'        => $this->prospek->id,
            'user_id'           => $this->sales->id,
            'jenis'             => 'Pembayaran Termin 1',
            'payment_status'    => 'pending',
            'metode_pembayaran' => 'gopay',
        ]);

        $this->actingAs($this->cs);
        $this->post(route('cs.verifikasi.reject', $trx->id), ['rejection_reason' => 'Test']);

        $this->prospek->refresh();
        $this->assertNotEquals('LUNAS', $this->prospek->status);
    }

    // ═══════════════════════════════════════════════════════
    // 18. Sales tidak bisa verifikasi sendiri
    // ═══════════════════════════════════════════════════════
    public function test_sales_cannot_verify_own_transaksi()
    {
        $trx = Transaksi::factory()->create([
            'prospek_id'        => $this->prospek->id,
            'user_id'           => $this->sales->id,
            'jenis'             => 'Pembayaran Termin 1',
            'payment_status'    => 'pending',
            'metode_pembayaran' => 'gopay',
        ]);

        $this->actingAs($this->sales);
        $response = $this->post(route('cs.verifikasi.verify', $trx->id));

        // Sales tidak punya akses ke route ini (middleware role:CS,Admin,HM)
        $response->assertStatus(403);
    }

    // ═══════════════════════════════════════════════════════
    // 19. Sales tidak bisa memaksa status menjadi verified
    // ═══════════════════════════════════════════════════════
    public function test_sales_cannot_force_verified_status_via_direct_request()
    {
        $this->actingAs($this->sales);
        // Coba akses route verifikasi langsung sebagai Sales
        $trx = Transaksi::factory()->create([
            'prospek_id'        => $this->prospek->id,
            'user_id'           => $this->sales->id,
            'jenis'             => 'Pembayaran Termin 1',
            'payment_status'    => 'pending',
            'metode_pembayaran' => 'gopay',
        ]);

        $response = $this->post(route('cs.verifikasi.verify', $trx->id));
        $response->assertStatus(403);

        $trx->refresh();
        $this->assertEquals('pending', $trx->payment_status);
    }

    // ═══════════════════════════════════════════════════════
    // 20. Verify dua kali tidak double count
    // ═══════════════════════════════════════════════════════
    public function test_double_verify_is_idempotent()
    {
        Transaksi::factory()->create([
            'prospek_id'     => $this->prospek->id,
            'user_id'        => $this->sales->id,
            'jenis'          => 'Beli Formulir',
            'payment_status' => 'verified',
        ]);
        $trx = Transaksi::factory()->create([
            'prospek_id'        => $this->prospek->id,
            'user_id'           => $this->sales->id,
            'jenis'             => 'Pembayaran Termin 1',
            'payment_status'    => 'pending',
            'metode_pembayaran' => 'gopay',
        ]);

        $this->actingAs($this->cs);
        // Verifikasi pertama
        $this->post(route('cs.verifikasi.verify', $trx->id));
        $this->prospek->refresh();
        $this->assertEquals('LUNAS', $this->prospek->status);

        // Verifikasi kedua — seharusnya redirect dengan info, tidak mengubah apapun lagi
        $response = $this->post(route('cs.verifikasi.verify', $trx->id));
        $response->assertRedirect();

        // Prospek tetap LUNAS, tidak ada transaksi baru
        $this->prospek->refresh();
        $this->assertEquals('LUNAS', $this->prospek->status);
        $trx->refresh();
        $this->assertEquals('verified', $trx->payment_status);

        // Jumlah transaksi tidak bertambah
        $this->assertEquals(2, Transaksi::where('prospek_id', $this->prospek->id)->count());
    }

    // ═══════════════════════════════════════════════════════
    // 21. Data transaksi lama tetap aman (default verified)
    // ═══════════════════════════════════════════════════════
    public function test_legacy_transaksi_without_metode_is_still_verified()
    {
        $legacyTrx = Transaksi::factory()->create([
            'prospek_id'        => $this->prospek->id,
            'user_id'           => $this->sales->id,
            'jenis'             => 'Pembayaran Termin 1',
            'metode_pembayaran' => null,
            'payment_status'    => 'verified', // default dari DB
        ]);

        $this->assertEquals('verified', $legacyTrx->payment_status);
        $this->assertTrue($legacyTrx->isVerified());
    }

    // ═══════════════════════════════════════════════════════
    // 22. ProspekService::isClosingValid hanya hitun verified Termin 1
    // ═══════════════════════════════════════════════════════
    public function test_closing_valid_requires_verified_termin1()
    {
        Transaksi::factory()->create([
            'prospek_id'     => $this->prospek->id,
            'user_id'        => $this->sales->id,
            'jenis'          => 'Beli Formulir',
            'payment_status' => 'verified',
        ]);

        // Termin 1 masih pending → belum closing
        Transaksi::factory()->create([
            'prospek_id'        => $this->prospek->id,
            'user_id'           => $this->sales->id,
            'jenis'             => 'Pembayaran Termin 1',
            'payment_status'    => 'pending',
            'metode_pembayaran' => 'gopay',
        ]);

        $this->assertFalse(ProspekService::isClosingValid($this->prospek->fresh('transaksis')));

        // Update menjadi verified → sekarang closing valid
        Transaksi::where('prospek_id', $this->prospek->id)->where('jenis', 'Pembayaran Termin 1')->update(['payment_status' => 'verified']);
        $this->assertTrue(ProspekService::isClosingValid($this->prospek->fresh('transaksis')));
    }

    // ═══════════════════════════════════════════════════════
    // 23. Sales input bank transfer dinamis dari BankAccount Admin
    // ═══════════════════════════════════════════════════════
    public function test_sales_inputs_dynamic_bank_transfer_and_notifies_cs()
    {
        // 1. Admin membuat rekening Mandiri
        $bank = BankAccount::create([
            'bank_name'      => 'Bank Mandiri',
            'account_number' => '1340019283746',
            'account_name'   => 'Yayasan Pendidikan UCIC',
            'notes'          => 'Rekening Resmi Penerimaan Mahasiswa Baru',
            'is_active'      => true,
        ]);

        // 2. Formulir sudah dibeli
        Transaksi::factory()->create([
            'prospek_id'     => $this->prospek->id,
            'user_id'        => $this->sales->id,
            'jenis'          => 'Beli Formulir',
            'payment_status' => 'verified',
        ]);

        // 3. Sales input Termin 1 via Transfer Bank Mandiri
        $this->actingAs($this->sales);
        $response = $this->post(route('prospek.transaksi', $this->prospek->id), [
            'jenis'             => 'Pembayaran Termin 1',
            'nominal'           => 1500000,
            'tanggal'           => now()->toDateString(),
            'metode_pembayaran' => 'bank_transfer',
            'bank_account_id'   => $bank->id,
            'notes'             => 'Transfer a.n Ayah Kandung - Bukti terlampir',
        ]);

        $response->assertRedirect();

        $trx = Transaksi::where('prospek_id', $this->prospek->id)->where('jenis', 'Pembayaran Termin 1')->first();
        $this->assertNotNull($trx);
        $this->assertEquals('bank_transfer', $trx->metode_pembayaran);
        $this->assertEquals('pending', $trx->payment_status);
        $this->assertStringContainsString('Bank Mandiri', $trx->notes);
        $this->assertStringContainsString('1340019283746', $trx->notes);
        $this->assertStringContainsString('Yayasan Pendidikan UCIC', $trx->notes);

        // 4. CS verifikasi transaksi
        $this->actingAs($this->cs);
        $verifyRes = $this->post(route('cs.verifikasi.verify', $trx->id));
        $verifyRes->assertRedirect();

        $this->prospek->refresh();
        $this->assertEquals('LUNAS', $this->prospek->status);
        $this->assertEquals(7, $this->prospek->stage_number);
        $this->assertEquals($this->sales->id, $this->prospek->sales_id);
    }

    // ═══════════════════════════════════════════════════════
    // 24. Sales can access Riwayat Pembayaran menu and see transactions
    // ═══════════════════════════════════════════════════════
    public function test_sales_can_access_riwayat_pembayaran_menu()
    {
        $trx = Transaksi::factory()->create([
            'prospek_id'        => $this->prospek->id,
            'user_id'           => $this->sales->id,
            'jenis'             => 'Pembayaran Termin 1',
            'nominal'           => 2500000,
            'payment_status'    => 'pending',
            'metode_pembayaran' => 'dana',
        ]);

        $this->actingAs($this->sales);
        $response = $this->get(route('sales.pembayaran.index'));
        $response->assertOk();
        $response->assertSee('Riwayat Pembayaran');
        $response->assertSee($this->prospek->name);
        $response->assertSee('2.500.000');
        $response->assertSee('Menunggu CS');
    }

    // ═══════════════════════════════════════════════════════
    // 25. Once CS verifies, Riwayat Pembayaran shows CS verifier name & status
    // ═══════════════════════════════════════════════════════
    public function test_sales_riwayat_pembayaran_shows_cs_approval_details()
    {
        // 1. Buat transaksi Termin 1
        $trx = Transaksi::factory()->create([
            'prospek_id'        => $this->prospek->id,
            'user_id'           => $this->sales->id,
            'jenis'             => 'Pembayaran Termin 1',
            'nominal'           => 3500000,
            'payment_status'    => 'pending',
            'metode_pembayaran' => 'gopay',
        ]);

        // 2. CS verifikasi
        $this->actingAs($this->cs);
        $this->post(route('cs.verifikasi.verify', $trx->id));

        // 3. Sales buka menu Riwayat Pembayaran
        $this->actingAs($this->sales);
        $response = $this->get(route('sales.pembayaran.index'));
        $response->assertOk();
        $response->assertSee('Diverifikasi CS');
        $response->assertSee('Disetujui CS: ' . $this->cs->name);
        $response->assertSee('3.500.000');
    }

    // ═══════════════════════════════════════════════════════
    // 26. Dynamic Filters (status, jenis, metode, search)
    // ═══════════════════════════════════════════════════════
    public function test_payment_history_dynamic_filters_work()
    {
        Transaksi::factory()->create([
            'prospek_id'        => $this->prospek->id,
            'user_id'           => $this->sales->id,
            'jenis'             => 'Pembayaran Termin 1',
            'nominal'           => 1000000,
            'payment_status'    => 'pending',
            'metode_pembayaran' => 'gopay',
            'notes'             => 'Ref Trx Khusus 999',
        ]);

        Transaksi::factory()->create([
            'prospek_id'        => $this->prospek->id,
            'user_id'           => $this->sales->id,
            'jenis'             => 'Beli Formulir',
            'nominal'           => 300000,
            'payment_status'    => 'verified',
            'metode_pembayaran' => 'dana',
            'notes'             => 'Formulir Pendaftaran',
        ]);

        $this->actingAs($this->sales);

        // Filter status verified
        $resVerified = $this->get(route('sales.pembayaran.index', ['status' => 'verified']));
        $resVerified->assertOk();
        $resVerified->assertSee('300.000');
        $resVerified->assertDontSee('Ref Trx Khusus 999');

        // Filter search notes
        $resSearch = $this->get(route('sales.pembayaran.index', ['search' => 'Khusus 999']));
        $resSearch->assertOk();
        $resSearch->assertSee('1.000.000');
        $resSearch->assertDontSee('Formulir Pendaftaran');
    }

    // ═══════════════════════════════════════════════════════
    // 27. SPV can access Team Payment History
    // ═══════════════════════════════════════════════════════
    public function test_spv_can_access_team_payment_history()
    {
        $spv = User::factory()->create(['role' => 'SPV']);
        $this->sales->update(['supervisor_id' => $spv->id]);

        Transaksi::factory()->create([
            'prospek_id'        => $this->prospek->id,
            'user_id'           => $this->sales->id,
            'jenis'             => 'Pembayaran Termin 1',
            'nominal'           => 2000000,
            'payment_status'    => 'verified',
            'metode_pembayaran' => 'bank_transfer',
        ]);

        $this->actingAs($spv);
        $response = $this->get(route('spv.pembayaran.index'));
        $response->assertOk();
        $response->assertSee('Riwayat Pembayaran');
        $response->assertSee($this->prospek->name);
        $response->assertSee('2.000.000');
    }

    // ═══════════════════════════════════════════════════════
    // 28. CS can view Riwayat Verifikasi tab & filter
    // ═══════════════════════════════════════════════════════
    public function test_cs_can_view_riwayat_verifikasi_and_filter()
    {
        // Verified transaction
        $verifiedTrx = Transaksi::factory()->create([
            'prospek_id'        => $this->prospek->id,
            'user_id'           => $this->sales->id,
            'jenis'             => 'Pembayaran Termin 1',
            'nominal'           => 1500000,
            'payment_status'    => 'verified',
            'verified_by'       => $this->cs->id,
            'verified_at'       => now(),
        ]);

        // Rejected transaction
        $rejectedTrx = Transaksi::factory()->create([
            'prospek_id'        => $this->prospek->id,
            'user_id'           => $this->sales->id,
            'jenis'             => 'Pembayaran Termin 1',
            'nominal'           => 500000,
            'payment_status'    => 'rejected',
            'rejected_by'       => $this->cs->id,
            'rejected_at'       => now(),
            'rejection_reason'  => 'Bukti Transfer Buram',
        ]);

        $this->actingAs($this->cs);

        // Akses tab riwayat
        $response = $this->get(route('cs.verifikasi.index', ['tab' => 'riwayat']));
        $response->assertOk();
        $response->assertSee('Riwayat Verifikasi');
        $response->assertSee('1.500.000');
        $response->assertSee('500.000');
        $response->assertSee('Bukti Transfer Buram');

        // Filter status rejected saja
        $resRejected = $this->get(route('cs.verifikasi.index', ['tab' => 'riwayat', 'status' => 'rejected']));
        $resRejected->assertOk();
        $resRejected->assertSee('Bukti Transfer Buram');
        $resRejected->assertSee('500.000');
    }

    // ═══════════════════════════════════════════════════════
    // 29. CS can view CS Dashboard with pending widget
    // ═══════════════════════════════════════════════════════
    public function test_cs_can_access_cs_dashboard_with_pending_widget()
    {
        Transaksi::factory()->create([
            'prospek_id'        => $this->prospek->id,
            'user_id'           => $this->sales->id,
            'jenis'             => 'Pembayaran Termin 1',
            'nominal'           => 1750000,
            'payment_status'    => 'pending',
            'metode_pembayaran' => 'bank_transfer',
        ]);

        $this->actingAs($this->cs);
        $response = $this->get(route('dashboard.cs'));
        $response->assertOk();
        $response->assertSee('Customer Service Lead');
        $response->assertSee('Antrean Verifikasi Pembayaran');
        $response->assertSee('1.750.000');
    }

    // ═══════════════════════════════════════════════════════
    // 30. CS input pembayaran Termin 1 langsung auto-verified & masuk riwayat
    // ═══════════════════════════════════════════════════════
    public function test_cs_creating_termin1_is_auto_verified_and_in_history()
    {
        // Prospek ditangani oleh CS dan sudah bayar formulir
        $this->prospek->update([
            'cs_id'        => $this->cs->id,
            'status'       => 'FORMULIR',
            'stage_number' => 5,
        ]);
        Transaksi::factory()->create([
            'prospek_id'     => $this->prospek->id,
            'user_id'        => $this->cs->id,
            'jenis'          => 'Beli Formulir',
            'payment_status' => 'verified',
        ]);

        $this->actingAs($this->cs);
        $response = $this->post(route('prospek.transaksi', $this->prospek->id), [
            'jenis'             => 'Pembayaran Termin 1',
            'nominal'           => 2500000,
            'tanggal'           => now()->toDateString(),
            'metode_pembayaran' => 'bank_transfer',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('transaksis', [
            'prospek_id'        => $this->prospek->id,
            'user_id'           => $this->cs->id,
            'jenis'             => 'Pembayaran Termin 1',
            'payment_status'    => 'verified',
            'verified_by'       => $this->cs->id,
        ]);

        // Prospek langsung LUNAS karena formulir + termin 1 terverifikasi
        $this->prospek->refresh();
        $this->assertEquals('LUNAS', $this->prospek->status);

        // Masuk ke tab riwayat pembayaran CS
        $resRiwayat = $this->get(route('cs.verifikasi.index', ['tab' => 'riwayat']));
        $resRiwayat->assertOk();
        $resRiwayat->assertSee('2.500.000');
    }

    // ═══════════════════════════════════════════════════════
    // 31. Sales input pembayaran Termin 1 tetap PENDING (butuh verifikasi CS)
    // ═══════════════════════════════════════════════════════
    public function test_sales_creating_termin1_remains_pending_for_cs()
    {
        $this->actingAs($this->sales);
        $response = $this->post(route('prospek.transaksi', $this->prospek->id), [
            'jenis'             => 'Pembayaran Termin 1',
            'nominal'           => 3000000,
            'tanggal'           => now()->toDateString(),
            'metode_pembayaran' => 'bank_transfer',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('transaksis', [
            'prospek_id'        => $this->prospek->id,
            'user_id'           => $this->sales->id,
            'jenis'             => 'Pembayaran Termin 1',
            'payment_status'    => 'pending',
            'verified_by'       => null,
        ]);
    }

    // ═══════════════════════════════════════════════════════
    // 32. CS dapat memverifikasi transaksi pending miliknya sendiri tanpa error
    // ═══════════════════════════════════════════════════════
    public function test_cs_can_verify_pending_transaction_even_if_self_inputted()
    {
        $this->prospek->update([
            'cs_id'        => $this->cs->id,
            'status'       => 'FORMULIR',
            'stage_number' => 5,
        ]);
        Transaksi::factory()->create([
            'prospek_id'     => $this->prospek->id,
            'user_id'        => $this->cs->id,
            'jenis'          => 'Beli Formulir',
            'payment_status' => 'verified',
        ]);

        $trx = Transaksi::factory()->create([
            'prospek_id'        => $this->prospek->id,
            'user_id'           => $this->cs->id, // diinput oleh CS
            'jenis'             => 'Pembayaran Termin 1',
            'payment_status'    => 'pending',
            'metode_pembayaran' => 'bank_transfer',
        ]);

        $this->actingAs($this->cs);
        $response = $this->post(route('cs.verifikasi.verify', $trx->id));
        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $trx->refresh();
        $this->assertEquals('verified', $trx->payment_status);
        $this->assertEquals($this->cs->id, $trx->verified_by);
    }

    // ═══════════════════════════════════════════════════════
    // 33. Filter Jurusan (Prodi) dan Kelas (Reguler/Karyawan) serta penyesuaian angka Card
    // ═══════════════════════════════════════════════════════
    public function test_cs_can_filter_verifikasi_by_prodi_and_kelas_and_cards_adjust()
    {
        $this->actingAs($this->cs);

        $prodiTI = \App\Models\Prodi::firstOrCreate(
            ['nama' => 'Teknik Informatika'],
            ['kode' => 'TI', 'status' => 'Aktif', 'jenjang' => 'S1', 'fakultas' => 'FTI']
        );
        $prodiSI = \App\Models\Prodi::firstOrCreate(
            ['nama' => 'Sistem Informasi'],
            ['kode' => 'SI', 'status' => 'Aktif', 'jenjang' => 'S1', 'fakultas' => 'FTI']
        );

        // Prospek 1: TI, Karyawan
        $prospek1 = \App\Models\Prospek::factory()->create([
            'name'        => 'Mhs TI Karyawan',
            'prodi_id'    => $prodiTI->id,
            'kelas'       => 'Karyawan',
            'wilayah_id'  => $this->cs->wilayah_id,
        ]);
        $trx1 = Transaksi::factory()->create([
            'prospek_id'     => $prospek1->id,
            'payment_status' => 'pending',
            'nominal'        => 500000,
        ]);

        // Prospek 2: TI, Reguler
        $prospek2 = \App\Models\Prospek::factory()->create([
            'name'        => 'Mhs TI Reguler',
            'prodi_id'    => $prodiTI->id,
            'kelas'       => 'Reguler',
            'wilayah_id'  => $this->cs->wilayah_id,
        ]);
        $trx2 = Transaksi::factory()->create([
            'prospek_id'     => $prospek2->id,
            'payment_status' => 'pending',
            'nominal'        => 750000,
        ]);

        // Prospek 3: SI, Karyawan
        $prospek3 = \App\Models\Prospek::factory()->create([
            'name'        => 'Mhs SI Karyawan',
            'prodi_id'    => $prodiSI->id,
            'kelas'       => 'Karyawan',
            'wilayah_id'  => $this->cs->wilayah_id,
        ]);
        $trx3 = Transaksi::factory()->create([
            'prospek_id'     => $prospek3->id,
            'payment_status' => 'pending',
            'nominal'        => 1000000,
        ]);

        // 1. Filter Jurusan Teknik Informatika saja
        $responseTI = $this->get(route('cs.verifikasi.index', ['tab' => 'pending', 'prodi_id' => $prodiTI->id]));
        $responseTI->assertOk();
        $responseTI->assertSee('Mhs TI Karyawan');
        $responseTI->assertSee('Mhs TI Reguler');
        $responseTI->assertDontSee('Mhs SI Karyawan');
        // Total pending pada card harus 2
        $this->assertEquals(2, $responseTI->viewData('totalPending'));

        // 2. Filter Kelas Karyawan saja
        $responseKaryawan = $this->get(route('cs.verifikasi.index', ['tab' => 'pending', 'kelas' => 'Karyawan']));
        $responseKaryawan->assertOk();
        $responseKaryawan->assertSee('Mhs TI Karyawan');
        $responseKaryawan->assertSee('Mhs SI Karyawan');
        $responseKaryawan->assertDontSee('Mhs TI Reguler');
        // Total pending pada card harus 2
        $this->assertEquals(2, $responseKaryawan->viewData('totalPending'));

        // 3. Filter Kombinasi: Jurusan TI DAN Kelas Karyawan
        $responseCombo = $this->get(route('cs.verifikasi.index', [
            'tab'      => 'pending',
            'prodi_id' => $prodiTI->id,
            'kelas'    => 'Karyawan',
        ]));
        $responseCombo->assertOk();
        $responseCombo->assertSee('Mhs TI Karyawan');
        $responseCombo->assertDontSee('Mhs TI Reguler');
        $responseCombo->assertDontSee('Mhs SI Karyawan');
        // Card totalPending menyesuaikan menjadi tepat 1
        $this->assertEquals(1, $responseCombo->viewData('totalPending'));
    }

    // ═══════════════════════════════════════════════════════
    // 34. Transaksi pending dari Sales muncul di SEMUA CS (tanpa sekat wilayah)
    // ═══════════════════════════════════════════════════════
    public function test_pending_transaction_from_sales_appears_to_all_cs_regardless_of_wilayah()
    {
        $wil1 = \App\Models\Wilayah::create([
            'kode'   => 'W01',
            'nama'   => 'Wilayah 1',
            'level'  => 'Kota/Kabupaten',
            'status' => 'Aktif',
        ]);
        $wil2 = \App\Models\Wilayah::create([
            'kode'   => 'W02',
            'nama'   => 'Wilayah 2',
            'level'  => 'Kota/Kabupaten',
            'status' => 'Aktif',
        ]);

        // CS 1 wilayah 1, CS 2 wilayah 2, CS 3 tanpa wilayah (wilayah_id null)
        $csWilayah1 = User::factory()->create(['role' => 'CS', 'wilayah_id' => $wil1->id]);
        $csWilayah2 = User::factory()->create(['role' => 'CS', 'wilayah_id' => $wil2->id]);
        $csGlobal   = User::factory()->create(['role' => 'CS', 'wilayah_id' => null]);

        // Sales di wilayah 2 membuat prospek di wilayah 2
        $salesWilayah2 = User::factory()->create(['role' => 'Sales', 'wilayah_id' => $wil2->id]);
        $prospekWilayah2 = \App\Models\Prospek::factory()->create([
            'sales_id'   => $salesWilayah2->id,
            'wilayah_id' => $wil2->id,
            'name'       => 'Siswa SMK Cirebon',
        ]);

        // Sales input transaksi pembayaran komponen Pendaftaran & Perlengkapan
        $this->actingAs($salesWilayah2);
        $resStore = $this->post(route('prospek.transaksi', $prospekWilayah2->id), [
            'kategori'          => 'DP Perlengkapan',
            'jenis_items'       => ['Pendaftaran', 'Perlengkapan'],
            'nominal_items'     => [
                'Pendaftaran'  => 250000,
                'Perlengkapan' => 350000,
            ],
            'tanggal'           => now()->toDateString(),
            'metode_pembayaran' => 'bank_transfer',
        ]);
        $resStore->assertRedirect();

        // Cek bahwa transaksi berstatus pending tersimpan
        $this->assertDatabaseHas('transaksis', [
            'prospek_id'     => $prospekWilayah2->id,
            'jenis'          => 'Pendaftaran',
            'payment_status' => 'pending',
        ]);
        $this->assertDatabaseHas('transaksis', [
            'prospek_id'     => $prospekWilayah2->id,
            'jenis'          => 'Perlengkapan',
            'payment_status' => 'pending',
        ]);

        // 1. CS Wilayah 1 (berbeda wilayah dengan Sales & Prospek) HARUS BISA MELIHAT
        $this->actingAs($csWilayah1);
        $responseCS1 = $this->get(route('cs.verifikasi.index'));
        $responseCS1->assertOk();
        $responseCS1->assertSee('Siswa SMK Cirebon');
        $responseCS1->assertSee('Pendaftaran');
        $responseCS1->assertSee('Perlengkapan');
        $this->assertGreaterThanOrEqual(2, $responseCS1->viewData('totalPending'));

        // Dashboard CS Wilayah 1 juga menampilkan widget pending pembayaran
        $dashCS1 = $this->get(route('dashboard.cs'));
        $dashCS1->assertOk();
        $dashCS1->assertSee('Siswa SMK Cirebon');
        $dashCS1->assertSee('Pendaftaran');

        // 2. CS Wilayah 2 HARUS BISA MELIHAT
        $this->actingAs($csWilayah2);
        $responseCS2 = $this->get(route('cs.verifikasi.index'));
        $responseCS2->assertOk();
        $responseCS2->assertSee('Siswa SMK Cirebon');

        // 3. CS Global (wilayah_id null) HARUS BISA MELIHAT
        $this->actingAs($csGlobal);
        $responseCSGlobal = $this->get(route('cs.verifikasi.index'));
        $responseCSGlobal->assertOk();
        $responseCSGlobal->assertSee('Siswa SMK Cirebon');

        // 4. CS Wilayah 1 bisa memverifikasi salah satu transaksi
        $this->actingAs($csWilayah1);
        $trxPendaftaran = Transaksi::where('prospek_id', $prospekWilayah2->id)->where('jenis', 'Pendaftaran')->first();
        $resVerify = $this->post(route('cs.verifikasi.verify', $trxPendaftaran->id));
        $resVerify->assertRedirect();

        $trxPendaftaran->refresh();
        $this->assertEquals('verified', $trxPendaftaran->payment_status);
        $this->assertEquals($csWilayah1->id, $trxPendaftaran->verified_by);
    }

    // ═══════════════════════════════════════════════════════
    // 35. Validasi backend mencegah pengajuan ulang atas transaksi yang sudah lunas/verified
    // ═══════════════════════════════════════════════════════
    public function test_backend_prevents_duplicate_submission_for_already_verified_transaction()
    {
        // 1. Prospek Adinda sudah bayar Pendaftaran dan sudah diverifikasi
        $adinda = Prospek::factory()->create([
            'sales_id' => $this->sales->id,
            'status'   => 'FORMULIR',
            'name'     => 'Adinda Putri',
        ]);

        Transaksi::create([
            'prospek_id'     => $adinda->id,
            'user_id'        => $this->sales->id,
            'jenis'          => 'Pendaftaran',
            'kategori'       => 'DP Daftar',
            'nominal'        => 500000,
            'tanggal'        => now()->toDateString(),
            'payment_status' => Transaksi::STATUS_VERIFIED,
            'verified_by'    => $this->cs->id,
            'verified_at'    => now(),
        ]);

        $this->actingAs($this->sales);

        // 2. Sales mencoba mengajukan Pendaftaran lagi -> HARUS DITOLAK
        $responseDuplicate = $this->post(route('prospek.transaksi', $adinda->id), [
            'kategori'          => 'DP Daftar',
            'jenis_items'       => ['Pendaftaran'],
            'nominal_items'     => ['Pendaftaran' => 250000],
            'tanggal'           => now()->toDateString(),
            'metode_pembayaran' => 'bank_transfer',
        ]);

        $responseDuplicate->assertRedirect();
        $responseDuplicate->assertSessionHasErrors('jenis_items');

        // Pastikan tidak ada transaksi pendaftaran kedua yang tersimpan
        $countPendaftaran = Transaksi::where('prospek_id', $adinda->id)->where('jenis', 'Pendaftaran')->count();
        $this->assertEquals(1, $countPendaftaran);
    }

    // ═══════════════════════════════════════════════════════
    // 36. Komponen belum lunas bisa diajukan & verifikasi hanya menampilkan item transaksi terkait
    // ═══════════════════════════════════════════════════════
    public function test_unpaid_component_can_be_submitted_and_verification_displays_only_submitted_item()
    {
        $adinda = Prospek::factory()->create([
            'sales_id' => $this->sales->id,
            'status'   => 'FORMULIR',
            'name'     => 'Adinda Putri',
        ]);

        // Pendaftaran sudah verified
        Transaksi::create([
            'prospek_id'     => $adinda->id,
            'user_id'        => $this->sales->id,
            'jenis'          => 'Pendaftaran',
            'kategori'       => 'DP Daftar',
            'nominal'        => 500000,
            'tanggal'        => now()->toDateString(),
            'payment_status' => Transaksi::STATUS_VERIFIED,
            'verified_by'    => $this->cs->id,
            'verified_at'    => now(),
        ]);

        $this->actingAs($this->sales);

        // Sales mengajukan pembayaran Perlengkapan Rp 750.000
        $responsePerlengkapan = $this->post(route('prospek.transaksi', $adinda->id), [
            'kategori'          => 'DP Perlengkapan',
            'jenis_items'       => ['Perlengkapan'],
            'nominal_items'     => ['Perlengkapan' => 750000],
            'tanggal'           => now()->toDateString(),
            'metode_pembayaran' => 'bank_transfer',
        ]);

        $responsePerlengkapan->assertRedirect();
        $responsePerlengkapan->assertSessionHasNoErrors();

        $trxPerlengkapan = Transaksi::where('prospek_id', $adinda->id)->where('jenis', 'Perlengkapan')->first();
        $this->assertNotNull($trxPerlengkapan);
        $this->assertEquals(750000, $trxPerlengkapan->nominal);
        $this->assertEquals('pending', $trxPerlengkapan->payment_status);

        // Halaman detail prospek menampilkan indikator lunas untuk Pendaftaran
        $resShow = $this->get(route('prospek.show', $adinda->id));
        $resShow->assertOk();
        $resShow->assertSee('Pendaftaran');
        $resShow->assertSee('LUNAS');

        // CS membuka antrean verifikasi
        $this->actingAs($this->cs);
        $resPending = $this->get(route('cs.verifikasi.index', ['tab' => 'pending']));
        $resPending->assertOk();
        $resPending->assertSee('Adinda Putri');
        $resPending->assertSee('Perlengkapan');
        $resPending->assertSee('750.000');

        // Verifikasi transaksi perlengkapan
        $resVerify = $this->post(route('cs.verifikasi.verify', $trxPerlengkapan->id));
        $resVerify->assertRedirect();

        // Riwayat Verifikasi menampilkan transaksi Perlengkapan yang sudah disetujui
        $resRiwayat = $this->get(route('cs.verifikasi.index', ['tab' => 'riwayat']));
        $resRiwayat->assertOk();
        $resRiwayat->assertSee('Adinda Putri');
        $resRiwayat->assertSee('Perlengkapan');
        $resRiwayat->assertSee('750.000');
    }

    // ═══════════════════════════════════════════════════════
    // 37. Riwayat Verifikasi mengelompokkan transaksi dari pengajuan yang sama, menjumlahkan nominal otomatis, dan menampilkan rincian transparan
    // ═══════════════════════════════════════════════════════
    public function test_riwayat_verifikasi_groups_transactions_of_same_submission_and_sums_nominal()
    {
        $sales = $this->sales;
        $cs = $this->cs;

        // Prospek 1: Fajar Pratama
        $fajar = Prospek::factory()->create([
            'sales_id' => $sales->id,
            'name'     => 'Fajar Pratama',
            'status'   => 'LUNAS',
        ]);

        $batchTime = now()->subHours(2);
        $todayDate = now()->toDateString();

        // Batch pengajuan yang sama: Pendaftaran Rp 250.000 & Perlengkapan Rp 350.000
        $trxFajar1 = Transaksi::create([
            'prospek_id'        => $fajar->id,
            'user_id'           => $sales->id,
            'jenis'             => 'Pendaftaran',
            'kategori'          => 'DP Perlengkapan',
            'nominal'           => 250000,
            'tanggal'           => $todayDate,
            'metode_pembayaran' => 'bank_transfer',
            'payment_status'    => Transaksi::STATUS_VERIFIED,
            'verified_by'       => $cs->id,
            'verified_at'       => $batchTime,
            'created_at'        => $batchTime,
            'updated_at'        => $batchTime,
        ]);

        $trxFajar2 = Transaksi::create([
            'prospek_id'        => $fajar->id,
            'user_id'           => $sales->id,
            'jenis'             => 'Perlengkapan',
            'kategori'          => 'DP Perlengkapan',
            'nominal'           => 350000,
            'tanggal'           => $todayDate,
            'metode_pembayaran' => 'bank_transfer',
            'payment_status'    => Transaksi::STATUS_VERIFIED,
            'verified_by'       => $cs->id,
            'verified_at'       => $batchTime,
            'created_at'        => $batchTime->copy()->addSecond(),
            'updated_at'        => $batchTime,
        ]);

        // Prospek 2: Sisca Lestari (tidak boleh dicampur dengan Fajar)
        $sisca = Prospek::factory()->create([
            'sales_id' => $sales->id,
            'name'     => 'Sisca Lestari',
            'status'   => 'FORMULIR',
        ]);

        $trxSisca = Transaksi::create([
            'prospek_id'        => $sisca->id,
            'user_id'           => $sales->id,
            'jenis'             => 'Pendaftaran',
            'kategori'          => 'DP Daftar',
            'nominal'           => 250000,
            'tanggal'           => $todayDate,
            'metode_pembayaran' => 'bank_transfer',
            'payment_status'    => Transaksi::STATUS_VERIFIED,
            'verified_by'       => $cs->id,
            'verified_at'       => $batchTime,
            'created_at'        => $batchTime,
            'updated_at'        => $batchTime,
        ]);

        // Transaksi Fajar dari periode berbeda (2 bulan kemudian: UKT Rp 3.000.000)
        // Tidak boleh digabungkan dengan pengajuan Pendaftaran + Perlengkapan
        $trxFajarPeriodeLain = Transaksi::create([
            'prospek_id'        => $fajar->id,
            'user_id'           => $sales->id,
            'jenis'             => 'UKT',
            'kategori'          => 'Lunas',
            'nominal'           => 3000000,
            'tanggal'           => now()->subMonths(2)->toDateString(),
            'metode_pembayaran' => 'bank_transfer',
            'payment_status'    => Transaksi::STATUS_VERIFIED,
            'verified_by'       => $cs->id,
            'verified_at'       => now()->subMonths(2),
            'created_at'        => now()->subMonths(2),
            'updated_at'        => now()->subMonths(2),
        ]);

        // Akses menu Riwayat Verifikasi oleh CS
        $this->actingAs($cs);
        $response = $this->get(route('cs.verifikasi.index', ['tab' => 'riwayat']));
        $response->assertOk();

        // 1. Fajar Pratama tampil
        $response->assertSee('Fajar Pratama');

        // 2. Transaksi Pendaftaran & Perlengkapan ada dalam rincian
        $response->assertSee('Pendaftaran');
        $response->assertSee('Perlengkapan');
        $response->assertSee('250.000');
        $response->assertSee('350.000');

        // 3. Nominal otomatis dijumlahkan: Rp 250.000 + Rp 350.000 = Rp 600.000
        $response->assertSee('600.000');
        $response->assertSee('2 Item Digabung');

        // 4. Prospek Sisca Lestari tetap berdiri sendiri
        $response->assertSee('Sisca Lestari');

        // 5. Transaksi periode lain UKT Rp 3.000.000 tetap tercatat secara terpisah
        $response->assertSee('3.000.000');
        $response->assertSee('UKT');
    }
}



