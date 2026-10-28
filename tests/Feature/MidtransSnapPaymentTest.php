<?php

namespace Tests\Feature;

use App\Models\Prospek;
use App\Models\Transaksi;
use App\Models\User;
use App\Services\MidtransService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MidtransSnapPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $csUser;
    protected string $serverKey = 'SB-Mid-server-TESTKEY123';
    protected string $clientKey = 'SB-Mid-client-TESTKEY123';

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create(['role' => 'Admin']);
        $this->csUser = User::factory()->create(['role' => 'CS']);

        config([
            'services.midtrans.server_key'    => $this->serverKey,
            'services.midtrans.client_key'    => $this->clientKey,
            'services.midtrans.is_production' => false,
            'services.midtrans.biaya_pendaftaran' => 300000,
        ]);
    }

    /**
     * Hitung signature resmi Midtrans untuk pengujian:
     * SHA512(order_id + status_code + gross_amount + serverKey)
     */
    protected function generateSignature(string $orderId, string $statusCode, string $grossAmount): string
    {
        return hash('sha512', $orderId . $statusCode . $grossAmount . $this->serverKey);
    }

    public function test_signature_verification_logic()
    {
        $service = new MidtransService();
        $orderId = 'ORDER-12345';
        $statusCode = '200';
        $grossAmount = '300000.00';
        $signature = $this->generateSignature($orderId, $statusCode, $grossAmount);

        $this->assertTrue($service->verifySignature($orderId, $statusCode, $grossAmount, $signature));
        $this->assertFalse($service->verifySignature($orderId, $statusCode, $grossAmount, 'invalidsignature123'));
    }

    public function test_admin_can_checkout_registration_fee_for_prospek()
    {
        $this->actingAs($this->adminUser);

        $prospek = Prospek::create([
            'name'     => 'Budi Pratama',
            'email'    => 'budi@gmail.com',
            'whatsapp' => '081234567890',
            'status'   => 'BARU',
        ]);

        // Simulasikan transaksi lokal yang sudah dibuat / re-use
        $trx = Transaksi::create([
            'order_id'          => 'PMB-UCIC-' . $prospek->id . '-123456',
            'prospek_id'        => $prospek->id,
            'user_id'           => $this->adminUser->id,
            'nominal'           => 300000,
            'jenis'             => 'Beli Formulir',
            'kategori'          => Transaksi::KATEGORI_DP_DAFTAR,
            'metode_pembayaran' => 'MIDTRANS',
            'payment_status'    => Transaksi::STATUS_PENDING,
            'snap_token'        => 'dummy-snap-token-123',
            'tanggal'           => now(),
        ]);

        $response = $this->postJson(route('pmb.checkout', $prospek->id));

        $response->assertOk()
            ->assertJson([
                'success'    => true,
                'order_id'   => $trx->order_id,
                'snap_token' => 'dummy-snap-token-123',
                'nominal'    => 300000,
            ]);
    }

    public function test_public_camaba_can_access_payment_page_with_valid_order_id()
    {
        $prospek = Prospek::create([
            'name'     => 'Siti Aisyah',
            'whatsapp' => '089876543210',
            'status'   => 'BARU',
        ]);

        $trx = Transaksi::create([
            'order_id'          => 'PMB-UCIC-SITI-001',
            'prospek_id'        => $prospek->id,
            'nominal'           => 300000,
            'jenis'             => 'Beli Formulir',
            'metode_pembayaran' => 'MIDTRANS',
            'payment_status'    => Transaksi::STATUS_PENDING,
            'tanggal'           => now(),
        ]);

        $response = $this->get(route('pmb.bayar', $trx->order_id));

        $response->assertOk();
        $response->assertSee('Siti Aisyah');
        $response->assertSee('PMB-UCIC-SITI-001');
        $response->assertSee('300.000');
    }

    public function test_unauthenticated_guest_cannot_checkout_arbitrary_applicant_without_valid_order()
    {
        $prospek = Prospek::create([
            'name'     => 'Target Mahasiswa',
            'whatsapp' => '081299998888',
            'status'   => 'BARU',
        ]);

        $response = $this->postJson(route('pmb.checkout', $prospek->id), [
            'order_id' => 'NON_EXISTENT_ORDER',
        ]);

        $response->assertForbidden();
    }

    public function test_cannot_checkout_if_already_verified()
    {
        $this->actingAs($this->adminUser);

        $prospek = Prospek::create([
            'name'     => 'Mahasiswa Lunas',
            'whatsapp' => '081233334444',
            'status'   => 'FORMULIR',
            'stage_number' => 5,
        ]);

        Transaksi::create([
            'order_id'          => 'PMB-UCIC-PAID-001',
            'prospek_id'        => $prospek->id,
            'nominal'           => 300000,
            'jenis'             => 'Beli Formulir',
            'kategori'          => Transaksi::KATEGORI_DP_DAFTAR,
            'metode_pembayaran' => 'MIDTRANS',
            'payment_status'    => Transaksi::STATUS_VERIFIED,
            'tanggal'           => now(),
            'verified_at'       => now(),
        ]);

        $response = $this->postJson(route('pmb.checkout', $prospek->id));

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_webhook_settlement_marks_transaction_verified_and_updates_prospek_stage()
    {
        $prospek = Prospek::create([
            'name'         => 'Rina Wati',
            'email'        => 'rina@gmail.com',
            'whatsapp'     => '085512345678',
            'status'       => 'BARU',
            'stage_number' => 1,
        ]);

        $trx = Transaksi::create([
            'order_id'          => 'PMB-UCIC-WEBHOOK-SETTLEMENT',
            'prospek_id'        => $prospek->id,
            'nominal'           => 300000,
            'jenis'             => 'Beli Formulir',
            'metode_pembayaran' => 'MIDTRANS',
            'payment_status'    => Transaksi::STATUS_PENDING,
            'tanggal'           => now(),
        ]);

        $signature = $this->generateSignature($trx->order_id, '200', '300000.00');

        $payload = [
            'order_id'           => $trx->order_id,
            'status_code'        => '200',
            'gross_amount'       => '300000.00',
            'signature_key'      => $signature,
            'transaction_status' => 'settlement',
            'payment_type'       => 'qris',
            'fraud_status'       => 'accept',
            'transaction_time'   => now()->toDateTimeString(),
        ];

        $response = $this->postJson(route('pmb.midtrans.notification'), $payload);

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
            ]);

        $freshTrx = $trx->fresh();
        $this->assertEquals(Transaksi::STATUS_VERIFIED, $freshTrx->payment_status);
        $this->assertEquals('qris', $freshTrx->payment_type);
        $this->assertNotNull($freshTrx->verified_at);

        $freshProspek = $prospek->fresh();
        $this->assertEquals('FORMULIR', $freshProspek->status);
        $this->assertEquals(5, $freshProspek->stage_number);

        $this->assertDatabaseHas('prospek_timelines', [
            'prospek_id' => $prospek->id,
            'title'      => 'Pembayaran PMB Online Sukses (Midtrans)',
        ]);
    }

    public function test_webhook_expire_marks_transaction_rejected()
    {
        $prospek = Prospek::create([
            'name'     => 'Kurniawan',
            'whatsapp' => '087712345678',
            'status'   => 'BARU',
        ]);

        $trx = Transaksi::create([
            'order_id'          => 'PMB-UCIC-EXPIRE-TEST',
            'prospek_id'        => $prospek->id,
            'nominal'           => 300000,
            'jenis'             => 'Beli Formulir',
            'metode_pembayaran' => 'MIDTRANS',
            'payment_status'    => Transaksi::STATUS_PENDING,
            'tanggal'           => now(),
        ]);

        $signature = $this->generateSignature($trx->order_id, '200', '300000');

        $payload = [
            'order_id'           => $trx->order_id,
            'status_code'        => '200',
            'gross_amount'       => '300000',
            'signature_key'      => $signature,
            'transaction_status' => 'expire',
            'payment_type'       => 'bank_transfer',
        ];

        $response = $this->postJson(route('pmb.midtrans.notification'), $payload);

        $response->assertOk();
        $freshTrx = $trx->fresh();
        $this->assertEquals(Transaksi::STATUS_REJECTED, $freshTrx->payment_status);
        $this->assertNotNull($freshTrx->rejected_at);
        $this->assertStringContainsString('Expired', $freshTrx->rejection_reason);
    }

    public function test_webhook_rejects_invalid_signature()
    {
        $prospek = Prospek::create([
            'name'     => 'Bad Sig User',
            'whatsapp' => '081299990001',
            'status'   => 'BARU',
        ]);

        $trx = Transaksi::create([
            'order_id'          => 'PMB-UCIC-BAD-SIG',
            'prospek_id'        => $prospek->id,
            'nominal'           => 300000,
            'jenis'             => 'Beli Formulir',
            'metode_pembayaran' => 'MIDTRANS',
            'payment_status'    => Transaksi::STATUS_PENDING,
            'tanggal'           => now(),
        ]);

        $payload = [
            'order_id'           => $trx->order_id,
            'status_code'        => '200',
            'gross_amount'       => '300000',
            'signature_key'      => 'fake-signature-key-12345',
            'transaction_status' => 'settlement',
        ];

        $response = $this->postJson(route('pmb.midtrans.notification'), $payload);

        $response->assertStatus(400);
        $this->assertEquals(Transaksi::STATUS_PENDING, $trx->fresh()->payment_status);
    }

    public function test_webhook_rejects_nominal_mismatch()
    {
        $prospek = Prospek::create([
            'name'     => 'Mismatch User',
            'whatsapp' => '081299990002',
            'status'   => 'BARU',
        ]);

        $trx = Transaksi::create([
            'order_id'          => 'PMB-UCIC-NOMINAL-MISMATCH',
            'prospek_id'        => $prospek->id,
            'nominal'           => 300000,
            'jenis'             => 'Beli Formulir',
            'metode_pembayaran' => 'MIDTRANS',
            'payment_status'    => Transaksi::STATUS_PENDING,
            'tanggal'           => now(),
        ]);

        // Attacker attempts to pay 1000 instead of 300000
        $tamperedAmount = '1000.00';
        $signature = $this->generateSignature($trx->order_id, '200', $tamperedAmount);

        $payload = [
            'order_id'           => $trx->order_id,
            'status_code'        => '200',
            'gross_amount'       => $tamperedAmount,
            'signature_key'      => $signature,
            'transaction_status' => 'settlement',
        ];

        $response = $this->postJson(route('pmb.midtrans.notification'), $payload);

        $response->assertStatus(400);
        $this->assertEquals(Transaksi::STATUS_PENDING, $trx->fresh()->payment_status);
    }

    public function test_webhook_is_idempotent_and_does_not_overwrite_verified_status_with_late_expire()
    {
        $prospek = Prospek::create([
            'name'     => 'Idempotent User',
            'whatsapp' => '081299990003',
            'status'   => 'FORMULIR',
            'stage_number' => 5,
        ]);

        $trx = Transaksi::create([
            'order_id'          => 'PMB-UCIC-IDEMPOTENT-TEST',
            'prospek_id'        => $prospek->id,
            'nominal'           => 300000,
            'jenis'             => 'Beli Formulir',
            'metode_pembayaran' => 'MIDTRANS',
            'payment_status'    => Transaksi::STATUS_VERIFIED,
            'verified_at'       => now()->subHour(),
            'tanggal'           => now()->subHour(),
        ]);

        // Duplicate settlement notification
        $signature = $this->generateSignature($trx->order_id, '200', '300000.00');
        $payloadSettlement = [
            'order_id'           => $trx->order_id,
            'status_code'        => '200',
            'gross_amount'       => '300000.00',
            'signature_key'      => $signature,
            'transaction_status' => 'settlement',
            'payment_type'       => 'gopay',
        ];

        $response1 = $this->postJson(route('pmb.midtrans.notification'), $payloadSettlement);
        $response1->assertOk()
            ->assertJsonPath('data.status', 'already_verified');

        $this->assertEquals(Transaksi::STATUS_VERIFIED, $trx->fresh()->payment_status);

        // Late expire notification arriving after verified
        $payloadExpire = [
            'order_id'           => $trx->order_id,
            'status_code'        => '200',
            'gross_amount'       => '300000.00',
            'signature_key'      => $signature,
            'transaction_status' => 'expire',
            'payment_type'       => 'gopay',
        ];

        $response2 = $this->postJson(route('pmb.midtrans.notification'), $payloadExpire);
        $response2->assertOk()
            ->assertJsonPath('data.status', 'ignored');

        $this->assertEquals(Transaksi::STATUS_VERIFIED, $trx->fresh()->payment_status);
    }

    public function test_snap_payload_includes_valid_pmb_callbacks_and_no_placeholder_domains()
    {
        $camaba = User::factory()->create(['role' => 'Camaba']);
        $prospek = Prospek::create([
            'user_id'  => $camaba->id,
            'name'     => 'Callback Test User',
            'whatsapp' => '081298765432',
            'status'   => 'BARU',
        ]);

        $trx = Transaksi::create([
            'order_id'          => 'PMB-UCIC-CALLBACK-001',
            'prospek_id'        => $prospek->id,
            'user_id'           => $camaba->id,
            'nominal'           => 300000,
            'jenis'             => 'Beli Formulir',
            'metode_pembayaran' => 'MIDTRANS',
            'payment_status'    => Transaksi::STATUS_PENDING,
            'tanggal'           => now(),
        ]);

        $service = new MidtransService();
        $payload = $service->buildSnapPayload($trx);

        $this->assertArrayHasKey('callbacks', $payload);
        $this->assertArrayHasKey('finish', $payload['callbacks']);
        $this->assertArrayHasKey('unfinish', $payload['callbacks']);
        $this->assertArrayHasKey('error', $payload['callbacks']);

        // Default callback URL harus persis http://127.0.0.1:8000/pmb/akun/bayar
        $this->assertEquals('http://127.0.0.1:8000/pmb/akun/bayar', $payload['callbacks']['finish']);
        $this->assertEquals('http://127.0.0.1:8000/pmb/akun/bayar', $payload['callbacks']['unfinish']);
        $this->assertEquals('http://127.0.0.1:8000/pmb/akun/bayar', $payload['callbacks']['error']);

        // Pastikan tidak ada penggunaan placeholder domain (example.com/example.org/example.net)
        foreach ($payload['callbacks'] as $type => $url) {
            $this->assertStringNotContainsString('example.com', $url, "Callback {$type} must not contain example.com");
            $this->assertStringNotContainsString('example.org', $url, "Callback {$type} must not contain example.org");
            $this->assertStringNotContainsString('example.net', $url, "Callback {$type} must not contain example.net");
        }

        // Jika konfigurasi tidak sengaja diset ke example.com, sistem harus menolak dan tetap fallback ke 127.0.0.1
        config([
            'services.midtrans.finish_redirect_url' => 'https://example.com/finish',
        ]);
        $fallbackPayload = $service->buildSnapPayload($trx);
        $this->assertEquals('http://127.0.0.1:8000/pmb/akun/bayar', $fallbackPayload['callbacks']['finish']);

        // Uji override valid via config services.php
        config([
            'services.midtrans.finish_redirect_url' => 'http://127.0.0.1:8000/pmb/akun/bayar?custom=finish',
        ]);

        $customPayload = $service->buildSnapPayload($trx);
        $this->assertEquals('http://127.0.0.1:8000/pmb/akun/bayar?custom=finish', $customPayload['callbacks']['finish']);
    }

    public function test_browser_redirect_with_settlement_query_params_does_not_falsely_verify_payment()
    {
        $camaba = User::factory()->create(['role' => 'Camaba']);
        $prospek = Prospek::create([
            'user_id'  => $camaba->id,
            'name'     => 'Browser Redirect Test User',
            'whatsapp' => '081211112222',
            'status'   => 'BARU',
        ]);

        $trx = Transaksi::create([
            'order_id'          => 'PMB-UCIC-SPOOF-001',
            'prospek_id'        => $prospek->id,
            'user_id'           => $camaba->id,
            'nominal'           => 300000,
            'jenis'             => 'Beli Formulir',
            'metode_pembayaran' => 'MIDTRANS',
            'payment_status'    => Transaksi::STATUS_PENDING,
            'tanggal'           => now(),
        ]);

        // Simulasikan browser redirect dari Midtrans dengan parameter settlement palsu
        $response = $this->actingAs($camaba)->get(route('pmb.akun.bayar', [
            'order_id'           => $trx->order_id,
            'status_code'        => '200',
            'transaction_status' => 'settlement',
        ]));

        $response->assertOk();

        // Pastikan transaksi lokal TETAP pending dan TIDAK otomatis ditandai verified hanya karena redirect browser
        $freshTrx = $trx->fresh();
        $this->assertEquals(Transaksi::STATUS_PENDING, $freshTrx->payment_status);
        $this->assertNull($freshTrx->verified_at);
        $this->assertNotEquals(5, $prospek->fresh()->stage_number);
    }

    /**
     * Uji kalkulasi komponen biaya PMB pada MidtransService:
     * - Perlengkapan: Rp 600.000
     * - Pendaftaran: Rp 300.000
     * - DPP: Rp 500.000
     */
    public function test_midtrans_service_calculate_items_scenarios()
    {
        // 1. Hanya DPP: 500.000
        $calcDpp = MidtransService::calculateItems(['dpp']);
        $this->assertEquals(500000, $calcDpp['total']);
        $this->assertEquals(['dpp'], $calcDpp['item_keys']);
        $this->assertCount(1, $calcDpp['items']);
        $this->assertEquals(500000, $calcDpp['items'][0]['price']);

        // 2. Hanya Pendaftaran: 300.000
        $calcDaftar = MidtransService::calculateItems(['pendaftaran']);
        $this->assertEquals(300000, $calcDaftar['total']);
        $this->assertEquals(['pendaftaran'], $calcDaftar['item_keys']);
        $this->assertCount(1, $calcDaftar['items']);
        $this->assertEquals(300000, $calcDaftar['items'][0]['price']);

        // 3. Pendaftaran + DPP: 800.000
        $calcBoth = MidtransService::calculateItems(['pendaftaran', 'dpp']);
        $this->assertEquals(800000, $calcBoth['total']);
        $this->assertEquals(['pendaftaran', 'dpp'], $calcBoth['item_keys']);
        $this->assertCount(2, $calcBoth['items']);

        // 4. Perlengkapan + Pendaftaran + DPP: 1.400.000
        $calcAll = MidtransService::calculateItems(['perlengkapan', 'pendaftaran', 'dpp']);
        $this->assertEquals(1400000, $calcAll['total']);
        $this->assertCount(3, $calcAll['items']);

        // 5. Associative format dari form: ['pendaftaran' => true, 'dpp' => true, 'perlengkapan' => false]
        $calcAssoc = MidtransService::calculateItems([
            'pendaftaran'  => true,
            'dpp'          => true,
            'perlengkapan' => false,
        ]);
        $this->assertEquals(800000, $calcAssoc['total']);
        $this->assertEquals(['pendaftaran', 'dpp'], $calcAssoc['item_keys']);

        // 6. Kosong / tidak ada biaya dipilih: 0
        $calcEmpty = MidtransService::calculateItems([]);
        $this->assertEquals(0, $calcEmpty['total']);
        $this->assertEmpty($calcEmpty['items']);
    }

    /**
     * Skenario 1: Hanya DPP Rp 500.000 yang dipilih.
     * Total tampilan, database, dan Midtrans snap payload harus tepat Rp 500.000.
     */
    public function test_checkout_only_dpp_calculates_500k_and_syncs_to_db_and_snap_payload()
    {
        $camaba = User::factory()->create(['role' => 'Camaba']);
        $prospek = Prospek::create([
            'user_id'  => $camaba->id,
            'name'     => 'DPP Only User',
            'whatsapp' => '081233445566',
            'status'   => 'BARU',
        ]);

        $response = $this->actingAs($camaba)->postJson(route('pmb.checkout', $prospek->id), [
            'items' => ['dpp'],
            'fresh' => true,
        ]);

        $response->assertOk()
            ->assertJson([
                'success'           => true,
                'nominal'           => 500000,
                'nominal_formatted' => 'Rp 500.000',
            ]);

        $orderId = $response->json('order_id');
        $transaksi = Transaksi::where('order_id', $orderId)->first();
        $this->assertNotNull($transaksi);
        $this->assertEquals(500000, (int) $transaksi->nominal);
        $this->assertStringContainsString('DPP', $transaksi->detail_pembayaran);

        // Periksa payload Midtrans Snap: gross_amount 500.000 & item_details berisi DPP seharga 500.000
        $service = new MidtransService();
        $snapPayload = $service->buildSnapPayload($transaksi);
        $this->assertEquals(500000, $snapPayload['transaction_details']['gross_amount']);
        $this->assertEquals(500000, array_sum(array_column($snapPayload['item_details'], 'price')));
        $this->assertEquals('PMB-DPP', $snapPayload['item_details'][0]['id']);
    }

    /**
     * Skenario 2: Hanya Pendaftaran Rp 300.000 yang dipilih.
     * Total tampilan, database, dan Midtrans snap payload harus tepat Rp 300.000.
     */
    public function test_checkout_only_pendaftaran_calculates_300k()
    {
        $camaba = User::factory()->create(['role' => 'Camaba']);
        $prospek = Prospek::create([
            'user_id'  => $camaba->id,
            'name'     => 'Pendaftaran Only User',
            'whatsapp' => '081244556677',
            'status'   => 'BARU',
        ]);

        $response = $this->actingAs($camaba)->postJson(route('pmb.checkout', $prospek->id), [
            'items' => ['pendaftaran'],
            'fresh' => true,
        ]);

        $response->assertOk()
            ->assertJson([
                'success'           => true,
                'nominal'           => 300000,
                'nominal_formatted' => 'Rp 300.000',
            ]);

        $orderId = $response->json('order_id');
        $transaksi = Transaksi::where('order_id', $orderId)->first();
        $this->assertEquals(300000, (int) $transaksi->nominal);

        $service = new MidtransService();
        $snapPayload = $service->buildSnapPayload($transaksi);
        $this->assertEquals(300000, $snapPayload['transaction_details']['gross_amount']);
        $this->assertEquals(300000, array_sum(array_column($snapPayload['item_details'], 'price')));
    }

    /**
     * Skenario 3: Pendaftaran dan DPP dipilih bersamaan.
     * Total tampilan, database, dan Midtrans snap payload harus tepat Rp 800.000.
     */
    public function test_checkout_pendaftaran_and_dpp_calculates_800k()
    {
        $camaba = User::factory()->create(['role' => 'Camaba']);
        $prospek = Prospek::create([
            'user_id'  => $camaba->id,
            'name'     => 'Pendaftaran Dan DPP User',
            'whatsapp' => '081255667788',
            'status'   => 'BARU',
        ]);

        $response = $this->actingAs($camaba)->postJson(route('pmb.checkout', $prospek->id), [
            'items' => ['pendaftaran', 'dpp'],
            'fresh' => true,
        ]);

        $response->assertOk()
            ->assertJson([
                'success'           => true,
                'nominal'           => 800000,
                'nominal_formatted' => 'Rp 800.000',
            ]);

        $orderId = $response->json('order_id');
        $transaksi = Transaksi::where('order_id', $orderId)->first();
        $this->assertEquals(800000, (int) $transaksi->nominal);

        $service = new MidtransService();
        $snapPayload = $service->buildSnapPayload($transaksi);
        $this->assertEquals(800000, $snapPayload['transaction_details']['gross_amount']);
        $this->assertCount(2, $snapPayload['item_details']);
        $this->assertEquals(800000, array_sum(array_column($snapPayload['item_details'], 'price')));
    }

    /**
     * Skenario 4: Tidak ada biaya yang dipilih (kosong).
     * Checkout dicegah dan backend mengembalikan error validasi 422.
     */
    public function test_checkout_with_no_fee_selected_fails_validation_and_prevents_checkout()
    {
        $camaba = User::factory()->create(['role' => 'Camaba']);
        $prospek = Prospek::create([
            'user_id'  => $camaba->id,
            'name'     => 'No Fee User',
            'whatsapp' => '081266778899',
            'status'   => 'BARU',
        ]);

        // Kirim array kosong
        $response = $this->actingAs($camaba)->postJson(route('pmb.checkout', $prospek->id), [
            'items' => [],
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Silakan pilih minimal satu komponen biaya pembayaran.',
            ]);

        // Kirim semua item false
        $response2 = $this->actingAs($camaba)->postJson(route('pmb.checkout', $prospek->id), [
            'items' => [
                'perlengkapan' => false,
                'pendaftaran'  => false,
                'dpp'          => false,
            ],
        ]);

        $response2->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Silakan pilih minimal satu komponen biaya pembayaran.',
            ]);
    }

    /**
     * Skenario 5: Backend menghitung total otoritatif dan mengabaikan nilai tampered dari client/browser.
     */
    public function test_backend_calculates_authoritative_total_and_ignores_tampered_browser_amount()
    {
        $camaba = User::factory()->create(['role' => 'Camaba']);
        $prospek = Prospek::create([
            'user_id'  => $camaba->id,
            'name'     => 'Hacker User',
            'whatsapp' => '081277889900',
            'status'   => 'BARU',
        ]);

        // Client mencoba mengirim nominal palsu Rp 1.000 untuk pendaftaran
        $response = $this->actingAs($camaba)->postJson(route('pmb.checkout', $prospek->id), [
            'items'   => ['pendaftaran'],
            'nominal' => 1000,
            'total'   => 1000,
            'fresh'   => true,
        ]);

        $response->assertOk()
            ->assertJson([
                'success'           => true,
                'nominal'           => 300000, // Harus tetap 300.000 sesuai katalog resmi backend
                'nominal_formatted' => 'Rp 300.000',
            ]);

        $orderId = $response->json('order_id');
        $transaksi = Transaksi::where('order_id', $orderId)->first();
        $this->assertEquals(300000, (int) $transaksi->nominal);
    }

    /**
     * Skenario 6: Halaman view pembayaran CAMABA merender checkbox dan katalog harga dengan benar.
     */
    public function test_pmb_bayar_view_renders_checkbox_and_authoritative_catalog_prices()
    {
        $camaba = User::factory()->create(['role' => 'Camaba']);
        $prospek = Prospek::create([
            'user_id'  => $camaba->id,
            'name'     => 'View Render User',
            'whatsapp' => '081288990011',
            'status'   => 'BARU',
        ]);

        $response = $this->actingAs($camaba)->get(route('pmb.akun.bayar'));
        $response->assertOk();

        // Verifikasi teks rincian biaya resmi UCIC
        $response->assertSee('Perlengkapan');
        $response->assertSee('Rp 600.000');
        $response->assertSee('Pendaftaran');
        $response->assertSee('Rp 300.000');
        $response->assertSee('DPP');
        $response->assertSee('Rp 500.000');
        $response->assertSee('Total Pembayaran');
    }
}
