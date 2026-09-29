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
}
