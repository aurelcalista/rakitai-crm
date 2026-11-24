<?php

namespace Tests\Feature;

use App\Models\Prodi;
use App\Models\Prospek;
use App\Models\Transaksi;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherUsage;
use App\Services\MidtransService;
use App\Services\VoucherDiscountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PmbVoucherSystemUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Prodi $prodi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'Admin']);

        $this->prodi = Prodi::create([
            'kode'               => 'TI-PMB-TEST',
            'nama'               => 'Teknik Informatika',
            'jenjang'            => 'S1',
            'fakultas'           => 'FTI',
            'kuota'              => 100,
            'status'             => 'Aktif',
            'biaya_pendaftaran'  => 300000,
            'biaya_perlengkapan' => 600000,
            'biaya_dpp'          => 5000000,
            'biaya_uang_gedung'  => 1000000,
            'ukt_options'        => [
                '6_bulan' => ['key' => '6_bulan', 'label' => 'Per 6 Bulan', 'nominal' => 4000000, 'is_active' => true],
            ],
        ]);
    }

    private function createCamaba(string $name = 'Camaba Test'): array
    {
        static $idx = 1;
        $user = User::factory()->create([
            'name'  => $name . ' ' . $idx,
            'email' => 'camaba_test_' . $idx . '@ucic.ac.id',
            'role'  => 'Camaba',
        ]);

        $prospek = Prospek::create([
            'user_id'         => $user->id,
            'name'            => $user->name,
            'email'           => $user->email,
            'whatsapp'        => '08129911' . str_pad((string) $idx, 4, '0', STR_PAD_LEFT),
            'no_registrasi'   => 'PMB-REG-' . $idx,
            'prodi_id'        => $this->prodi->id,
            'status_formulir' => 'Sudah Isi',
            'status'          => 'FORMULIR',
        ]);

        $idx++;
        return [$user, $prospek];
    }

    /**
     * Scenario 1: User selects only Registration Fee -> voucher field remains hidden / rejected
     */
    /**
     * Scenario 1: User selects only Registration Fee -> voucher field remains hidden / rejected
     */
    public function test_scenario_1_user_selects_only_registration_fee_voucher_rejected(): void
    {
        [$user, $prospek] = $this->createCamaba('User Scenario 1');

        Voucher::create([
            'code'            => 'UCIC300',
            'discount_amount' => 300000,
            'quota'           => 10,
            'is_active'       => true,
        ]);

        // Attempting to apply voucher with only registration fee
        $res = $this->actingAs($user)->postJson(route('pmb.akun.voucher.apply'), [
            'code'  => 'UCIC300',
            'items' => ['pendaftaran'],
        ]);

        $res->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
        $this->assertStringContainsString('5 komponen', $res->json('message'));
    }

    /**
     * Scenario 2: User selects Registration Fee, Equipment Fee, and DPP -> voucher field remains hidden / rejected
     */
    public function test_scenario_2_user_selects_three_components_voucher_rejected(): void
    {
        [$user, $prospek] = $this->createCamaba('User Scenario 2');

        Voucher::create([
            'code'            => 'UCIC300',
            'discount_amount' => 300000,
            'quota'           => 10,
            'is_active'       => true,
        ]);

        // Attempting to apply voucher with 3 components (pendaftaran, perlengkapan, dpp)
        $res = $this->actingAs($user)->postJson(route('pmb.akun.voucher.apply'), [
            'code'  => 'UCIC300',
            'items' => ['pendaftaran', 'perlengkapan', 'dpp'],
        ]);

        $res->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
        $this->assertStringContainsString('5 komponen', $res->json('message'));
    }

    /**
     * Scenario 3: User selects all five payment components, including UKT -> voucher field appears at the bottom
     */
    public function test_scenario_3_user_selects_all_five_components_voucher_appears_and_applied(): void
    {
        [$user, $prospek] = $this->createCamaba('User Scenario 3');

        $voucher = Voucher::create([
            'code'            => 'UCIC300',
            'name'            => 'Diskon PMB 300K',
            'discount_amount' => 300000,
            'quota'           => 10,
            'is_active'       => true,
        ]);

        // 1. Check payment page contains voucher input section at bottom with allComponentsSelected
        $pageRes = $this->actingAs($user)->get(route('pmb.akun.bayar'));
        $pageRes->assertOk();
        $pageRes->assertSee('id="voucher-input-section"', false);
        $pageRes->assertSee('x-show="allComponentsSelected"', false);

        // 2. Apply with all 5 components selected
        $res = $this->actingAs($user)->postJson(route('pmb.akun.voucher.apply'), [
            'code'       => 'UCIC300',
            'items'      => ['pendaftaran', 'perlengkapan', 'dpp', 'uang_gedung', 'ukt'],
            'ukt_period' => '6_bulan',
        ]);

        $res->assertOk()
            ->assertJson([
                'success' => true,
                'voucher' => [
                    'voucher_code'       => 'UCIC300',
                    'discount_amount'    => 300000,
                    'discount_formatted' => 'Rp 300.000',
                    'status'             => VoucherUsage::STATUS_APPLIED,
                ],
            ]);
    }

    /**
     * Scenario 4: User unchecks one payment component -> voucher application is rejected
     */
    public function test_scenario_4_user_unchecks_one_component_voucher_rejected(): void
    {
        [$user, $prospek] = $this->createCamaba('User Scenario 4');

        Voucher::create([
            'code'            => 'UCIC300',
            'discount_amount' => 300000,
            'quota'           => 10,
            'is_active'       => true,
        ]);

        // Pass only 4 components (omitting ukt)
        $res = $this->actingAs($user)->postJson(route('pmb.akun.voucher.apply'), [
            'code'  => 'UCIC300',
            'items' => ['pendaftaran', 'perlengkapan', 'dpp', 'uang_gedung'],
        ]);

        $res->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    /**
     * Scenario 5: User enters a valid voucher -> fixed discount is applied correctly
     */
    public function test_scenario_5_valid_voucher_fixed_discount_applied_to_total(): void
    {
        [$user, $prospek] = $this->createCamaba('User Scenario 5');

        Voucher::create([
            'code'            => 'UCIC300',
            'discount_amount' => 300000,
            'quota'           => 10,
            'is_active'       => true,
        ]);

        // 1. Apply voucher
        $applyRes = $this->actingAs($user)->postJson(route('pmb.akun.voucher.apply'), [
            'code'       => 'UCIC300',
            'items'      => ['pendaftaran', 'perlengkapan', 'dpp', 'uang_gedung', 'ukt'],
            'ukt_period' => '6_bulan',
        ]);
        $applyRes->assertOk();

        // Total before discount: 300k (reg) + 600k (equip) + 5M (dpp) + 1M (gedung) + 4M (ukt) = 10.900.000
        // Expected total after 300k fixed discount = 10.600.000
        $checkoutRes = $this->actingAs($user)->postJson(route('pmb.checkout', $prospek->id), [
            'items'          => ['pendaftaran', 'perlengkapan', 'dpp', 'uang_gedung', 'ukt'],
            'ukt_period'     => '6_bulan',
            'payment_method' => 'online',
            'voucher_code'   => 'UCIC300',
        ]);

        $checkoutRes->assertOk()
            ->assertJson([
                'success'           => true,
                'nominal'           => 10600000,
                'nominal_formatted' => 'Rp 10.600.000',
            ]);

        $orderId = $checkoutRes->json('order_id');
        $transaksi = Transaksi::where('order_id', $orderId)->firstOrFail();
        $this->assertEquals(10600000, (int) $transaksi->nominal);
    }

    /**
     * Scenario 6: User enters an invalid voucher -> appropriate error message appears
     */
    public function test_scenario_6_invalid_voucher_returns_appropriate_error(): void
    {
        [$user, $prospek] = $this->createCamaba('User Scenario 6');

        Voucher::create([
            'code'            => 'EXPIRED_VOUCHER',
            'discount_amount' => 200000,
            'quota'           => 10,
            'is_active'       => true,
            'expires_at'      => now()->subDay(),
        ]);

        Voucher::create([
            'code'            => 'INACTIVE_VOUCHER',
            'discount_amount' => 200000,
            'quota'           => 10,
            'is_active'       => false,
        ]);

        $items = ['pendaftaran', 'perlengkapan', 'dpp', 'uang_gedung', 'ukt'];

        // Non-existent code
        $resNotFound = $this->actingAs($user)->postJson(route('pmb.akun.voucher.apply'), [
            'code'       => 'NOT_EXIST_CODE',
            'items'      => $items,
            'ukt_period' => '6_bulan',
        ]);
        $resNotFound->assertStatus(422);
        $this->assertStringContainsString('tidak ditemukan', $resNotFound->json('message'));

        // Expired code
        $resExpired = $this->actingAs($user)->postJson(route('pmb.akun.voucher.apply'), [
            'code'       => 'EXPIRED_VOUCHER',
            'items'      => $items,
            'ukt_period' => '6_bulan',
        ]);
        $resExpired->assertStatus(422);
        $this->assertStringContainsString('kedaluwarsa', $resExpired->json('message'));

        // Inactive code
        $resInactive = $this->actingAs($user)->postJson(route('pmb.akun.voucher.apply'), [
            'code'       => 'INACTIVE_VOUCHER',
            'items'      => $items,
            'ukt_period' => '6_bulan',
        ]);
        $resInactive->assertStatus(422);
        $this->assertStringContainsString('tidak aktif', $resInactive->json('message'));
    }

    /**
     * Scenario 7: User attempts to use a voucher with zero remaining quota -> redemption is rejected
     */
    public function test_scenario_7_voucher_with_zero_remaining_quota_is_rejected(): void
    {
        [$user, $prospek] = $this->createCamaba('User Scenario 7');

        Voucher::create([
            'code'            => 'ZERO_QUOTA',
            'discount_amount' => 300000,
            'quota'           => 5,
            'used_quota'      => 5,
            'is_active'       => true,
        ]);

        $res = $this->actingAs($user)->postJson(route('pmb.akun.voucher.apply'), [
            'code'       => 'ZERO_QUOTA',
            'items'      => ['pendaftaran', 'perlengkapan', 'dpp', 'uang_gedung', 'ukt'],
            'ukt_period' => '6_bulan',
        ]);

        $res->assertStatus(422);
        $this->assertStringContainsString('sudah habis', $res->json('message'));
    }

    /**
     * Scenario 8: An account that has already redeemed a voucher attempts to redeem another -> rejected
     */
    public function test_scenario_8_account_already_redeemed_voucher_cannot_redeem_again(): void
    {
        [$user, $prospek] = $this->createCamaba('User Scenario 8');

        $vcr1 = Voucher::create([
            'code'            => 'VCR1',
            'discount_amount' => 300000,
            'quota'           => 10,
            'is_active'       => true,
        ]);

        $vcr2 = Voucher::create([
            'code'            => 'VCR2',
            'discount_amount' => 500000,
            'quota'           => 10,
            'is_active'       => true,
        ]);

        // Simulate user already successfully verified a transaction using VCR1
        $tx = Transaksi::create([
            'prospek_id'        => $prospek->id,
            'user_id'           => $user->id,
            'jenis'             => 'Pendaftaran',
            'kategori'          => Transaksi::KATEGORI_PENDAFTARAN,
            'nominal'           => 10600000,
            'payment_status'    => Transaksi::STATUS_VERIFIED,
            'metode_pembayaran' => 'MIDTRANS',
            'tanggal'           => now(),
            'verified_at'       => now(),
        ]);

        VoucherUsage::create([
            'voucher_id'           => $vcr1->id,
            'voucher_code'         => $vcr1->code,
            'prospek_id'           => $prospek->id,
            'user_id'              => $user->id,
            'transaksi_id'         => $tx->id,
            'discount_amount'      => 300000,
            'total_before_discount'=> 10900000,
            'total_after_discount' => 10600000,
            'dpp_before_discount'  => 10900000,
            'dpp_after_discount'   => 10600000,
            'status'               => VoucherUsage::STATUS_USED,
            'used_at'              => now(),
        ]);

        // User attempts to apply another voucher (VCR2)
        $res = $this->actingAs($user)->postJson(route('pmb.akun.voucher.apply'), [
            'code'       => 'VCR2',
            'items'      => ['pendaftaran', 'perlengkapan', 'dpp', 'uang_gedung', 'ukt'],
            'ukt_period' => '6_bulan',
        ]);

        $res->assertStatus(422);
        $this->assertStringContainsString('pernah menggunakan voucher', $res->json('message'));
    }

    /**
     * Scenario 9: A voucher with a quota of 20 is redeemed by 20 different accounts -> the 21st account is rejected
     */
    public function test_scenario_9_quota_of_20_exhausted_by_20_accounts_21st_rejected(): void
    {
        $voucher = Voucher::create([
            'code'            => 'QUOTA20',
            'discount_amount' => 200000,
            'quota'           => 20,
            'used_quota'      => 0,
            'is_active'       => true,
        ]);

        // Simulate 20 verified redemptions from 20 distinct accounts
        for ($i = 1; $i <= 20; $i++) {
            [$u, $p] = $this->createCamaba("Camaba {$i}");

            $tx = Transaksi::create([
                'prospek_id'        => $p->id,
                'user_id'           => $u->id,
                'kategori'          => Transaksi::KATEGORI_PENDAFTARAN,
                'nominal'           => 10700000,
                'payment_status'    => Transaksi::STATUS_VERIFIED,
                'metode_pembayaran' => 'MIDTRANS',
                'tanggal'           => now(),
                'verified_at'       => now(),
            ]);

            VoucherUsage::create([
                'voucher_id'           => $voucher->id,
                'voucher_code'         => $voucher->code,
                'prospek_id'           => $p->id,
                'user_id'              => $u->id,
                'transaksi_id'         => $tx->id,
                'discount_amount'      => 200000,
                'total_before_discount'=> 10900000,
                'total_after_discount' => 10700000,
                'dpp_before_discount'  => 10900000,
                'dpp_after_discount'   => 10700000,
                'status'               => VoucherUsage::STATUS_USED,
                'used_at'              => now(),
            ]);

            $voucher->increment('used_quota');
        }

        $voucher->refresh();
        $this->assertEquals(20, $voucher->used_quota);
        $this->assertEquals(0, $voucher->remaining_quota);
        $this->assertFalse($voucher->hasRemainingQuota());

        // 21st account tries to apply
        [$user21, $prospek21] = $this->createCamaba('Camaba 21');
        $res = $this->actingAs($user21)->postJson(route('pmb.akun.voucher.apply'), [
            'code'       => 'QUOTA20',
            'items'      => ['pendaftaran', 'perlengkapan', 'dpp', 'uang_gedung', 'ukt'],
            'ukt_period' => '6_bulan',
        ]);

        $res->assertStatus(422);
        $this->assertStringContainsString('sudah habis', $res->json('message'));
    }

    /**
     * Scenario 10: Payment fails or is cancelled -> voucher quota is not incorrectly deducted
     */
    public function test_scenario_10_failed_or_cancelled_payment_does_not_deduct_voucher_quota(): void
    {
        [$user, $prospek] = $this->createCamaba('User Scenario 10');

        $voucher = Voucher::create([
            'code'            => 'QUOTA_SAFE',
            'discount_amount' => 300000,
            'quota'           => 10,
            'used_quota'      => 0,
            'is_active'       => true,
        ]);

        // 1. User applies voucher
        $this->actingAs($user)->postJson(route('pmb.akun.voucher.apply'), [
            'code'       => 'QUOTA_SAFE',
            'items'      => ['pendaftaran', 'perlengkapan', 'dpp', 'uang_gedung', 'ukt'],
            'ukt_period' => '6_bulan',
        ])->assertOk();

        // 2. User checks out -> transaction is 'pending'
        $checkoutRes = $this->actingAs($user)->postJson(route('pmb.checkout', $prospek->id), [
            'items'          => ['pendaftaran', 'perlengkapan', 'dpp', 'uang_gedung', 'ukt'],
            'ukt_period'     => '6_bulan',
            'payment_method' => 'online',
            'voucher_code'   => 'QUOTA_SAFE',
        ])->assertOk();

        $orderId = $checkoutRes->json('order_id');
        $transaksi = Transaksi::where('order_id', $orderId)->firstOrFail();

        // While pending, quota MUST NOT be consumed
        $voucher->refresh();
        $this->assertEquals(0, $voucher->used_quota);
        $this->assertEquals(10, $voucher->remaining_quota);

        // 3. Payment cancelled or failed
        $transaksi->update(['payment_status' => Transaksi::STATUS_REJECTED]);
        VoucherDiscountService::syncUsageForTransaksi($transaksi);

        // Quota is still untouched
        $voucher->refresh();
        $this->assertEquals(0, $voucher->used_quota);
        $this->assertEquals(10, $voucher->remaining_quota);
    }

    /**
     * Scenario 11: Admin updates the discount amount and quota -> changes are saved correctly
     */
    public function test_scenario_11_admin_updates_discount_amount_and_quota(): void
    {
        // 1. Admin creates voucher (with dots formatting like 500.000)
        $storeRes = $this->actingAs($this->admin)->post(route('pmb.pembayaran.vouchers.store'), [
            'code'            => 'UCIC500',
            'name'            => 'Diskon PMB 500K',
            'discount_amount' => '500.000',
            'quota'           => 50,
            'is_active'       => '1',
        ]);
        $storeRes->assertSessionHas('success');

        $voucher = Voucher::where('code', 'UCIC500')->firstOrFail();
        $this->assertEquals(500000, $voucher->discount_amount);
        $this->assertEquals(50, $voucher->quota);

        // 2. Admin updates discount amount and quota (with dots formatting like 1.000.000)
        $updateRes = $this->actingAs($this->admin)->put(route('pmb.pembayaran.vouchers.update', $voucher->id), [
            'name'            => 'Diskon PMB 1Juta Updated',
            'discount_amount' => '1.000.000',
            'quota'           => 30,
            'is_active'       => '1',
        ]);
        $updateRes->assertSessionHas('success');

        $voucher->refresh();
        $this->assertEquals(1000000, $voucher->discount_amount);
        $this->assertEquals(30, $voucher->quota);
        $this->assertEquals('Diskon PMB 1Juta Updated', $voucher->name);
    }

    /**
     * Scenario 12: Multiple users attempt to redeem the last available voucher quota simultaneously -> quota is never exceeded
     */
    public function test_scenario_12_concurrency_protection_on_last_voucher_quota(): void
    {
        $voucher = Voucher::create([
            'code'            => 'LAST_SEAT',
            'discount_amount' => 300000,
            'quota'           => 1,
            'used_quota'      => 0,
            'is_active'       => true,
        ]);

        [$userA, $prospekA] = $this->createCamaba('User A');
        [$userB, $prospekB] = $this->createCamaba('User B');

        // Prepare transactions
        $txA = Transaksi::create([
            'prospek_id'        => $prospekA->id,
            'user_id'           => $userA->id,
            'kategori'          => Transaksi::KATEGORI_PENDAFTARAN,
            'nominal'           => 10600000,
            'payment_status'    => Transaksi::STATUS_PENDING,
            'metode_pembayaran' => 'MIDTRANS',
            'tanggal'           => now(),
        ]);

        $usageA = VoucherUsage::create([
            'voucher_id'           => $voucher->id,
            'voucher_code'         => $voucher->code,
            'prospek_id'           => $prospekA->id,
            'user_id'              => $userA->id,
            'transaksi_id'         => $txA->id,
            'discount_amount'      => 300000,
            'total_before_discount'=> 10900000,
            'total_after_discount' => 10600000,
            'dpp_before_discount'  => 10900000,
            'dpp_after_discount'   => 10600000,
            'status'               => VoucherUsage::STATUS_APPLIED,
        ]);

        $txB = Transaksi::create([
            'prospek_id'        => $prospekB->id,
            'user_id'           => $userB->id,
            'kategori'          => Transaksi::KATEGORI_PENDAFTARAN,
            'nominal'           => 10600000,
            'payment_status'    => Transaksi::STATUS_PENDING,
            'metode_pembayaran' => 'MIDTRANS',
            'tanggal'           => now(),
        ]);

        $usageB = VoucherUsage::create([
            'voucher_id'           => $voucher->id,
            'voucher_code'         => $voucher->code,
            'prospek_id'           => $prospekB->id,
            'user_id'              => $userB->id,
            'transaksi_id'         => $txB->id,
            'discount_amount'      => 300000,
            'total_before_discount'=> 10900000,
            'total_after_discount' => 10600000,
            'dpp_before_discount'  => 10900000,
            'dpp_after_discount'   => 10600000,
            'status'               => VoucherUsage::STATUS_APPLIED,
        ]);

        // First verification succeeds and consumes the last quota (1 of 1)
        $txA->update(['payment_status' => Transaksi::STATUS_VERIFIED]);
        VoucherDiscountService::syncUsageForTransaksi($txA);

        $voucher->refresh();
        $this->assertEquals(1, $voucher->used_quota);
        $this->assertEquals(0, $voucher->remaining_quota);

        // Second verification arrives when quota is already 0
        $txB->update(['payment_status' => Transaksi::STATUS_VERIFIED]);
        VoucherDiscountService::syncUsageForTransaksi($txB);

        $voucher->refresh();
        // Quota MUST NEVER exceed 1
        $this->assertEquals(1, $voucher->used_quota);

        $usageB->refresh();
        // Second usage could not be converted to 'used' due to quota exhaustion
        $this->assertNotSame(VoucherUsage::STATUS_USED, $usageB->status);
    }

    /**
     * Scenario 13: Refreshing payment page or resubmitting request does not duplicate redemption
     */
    public function test_scenario_13_refresh_or_duplicate_submission_is_idempotent(): void
    {
        [$user, $prospek] = $this->createCamaba('User Scenario 13');

        $voucher = Voucher::create([
            'code'            => 'IDEMPOTENT_TEST',
            'discount_amount' => 300000,
            'quota'           => 10,
            'used_quota'      => 0,
            'is_active'       => true,
        ]);

        $items = ['pendaftaran', 'perlengkapan', 'dpp', 'uang_gedung', 'ukt'];

        // First application
        $res1 = $this->actingAs($user)->postJson(route('pmb.akun.voucher.apply'), [
            'code'       => 'IDEMPOTENT_TEST',
            'items'      => $items,
            'ukt_period' => '6_bulan',
        ]);
        $res1->assertOk();

        $this->assertEquals(1, VoucherUsage::where('prospek_id', $prospek->id)->count());

        // Refreshing page
        $pageRes = $this->actingAs($user)->get(route('pmb.akun.bayar'));
        $pageRes->assertOk();

        // Count of usages remains 1
        $this->assertEquals(1, VoucherUsage::where('prospek_id', $prospek->id)->count());

        // Applying again with same code re-applies cleanly without duplicating records
        $res2 = $this->actingAs($user)->postJson(route('pmb.akun.voucher.apply'), [
            'code'       => 'IDEMPOTENT_TEST',
            'items'      => $items,
            'ukt_period' => '6_bulan',
        ]);
        $res2->assertOk();

        $this->assertEquals(1, VoucherUsage::where('prospek_id', $prospek->id)->count());
    }

    /**
     * Extra verification: Sequential restriction removed; user can select any combination without lock
     * and max 2 payment transactions are allowed.
     */
    public function test_sequential_restriction_removed_and_max_two_transactions_enforced(): void
    {
        [$user, $prospek] = $this->createCamaba('User Free Selection');

        // All 5 components are unlocked initially without requiring prior payment
        $gate = MidtransService::getSequentialGateState($prospek);
        $this->assertTrue($gate['unlocked']['pendaftaran']);
        $this->assertTrue($gate['unlocked']['perlengkapan']);
        $this->assertTrue($gate['unlocked']['dpp']);
        $this->assertTrue($gate['unlocked']['uang_gedung']);
        $this->assertTrue($gate['unlocked']['ukt']);

        // First transaction (e.g. paying Pendaftaran & DPP directly)
        $tx1Res = $this->actingAs($user)->postJson(route('pmb.checkout', $prospek->id), [
            'items'          => ['pendaftaran', 'dpp'],
            'payment_method' => 'online',
        ]);
        $tx1Res->assertOk();

        $tx1 = Transaksi::where('order_id', $tx1Res->json('order_id'))->firstOrFail();
        $tx1->update(['payment_status' => Transaksi::STATUS_VERIFIED]);

        // Second transaction (paying Perlengkapan, Uang Gedung)
        $tx2Res = $this->actingAs($user)->postJson(route('pmb.checkout', $prospek->id), [
            'items'          => ['perlengkapan', 'uang_gedung'],
            'payment_method' => 'online',
        ]);
        $tx2Res->assertOk();

        $tx2 = Transaksi::where('order_id', $tx2Res->json('order_id'))->firstOrFail();
        $tx2->update(['payment_status' => Transaksi::STATUS_VERIFIED]);

        // Attempting a 3rd transaction for remaining unpaid component (UKT) should be rejected (max 2 payment transactions limit)
        $tx3Res = $this->actingAs($user)->postJson(route('pmb.checkout', $prospek->id), [
            'items'          => ['ukt'],
            'ukt_period'     => '6_bulan',
            'payment_method' => 'online',
        ]);
        $res3 = $tx3Res->json();
        $tx3Res->assertStatus(422);
        $this->assertStringContainsString('maksimal 2 kali transaksi', $tx3Res->json('message'));
    }
}
