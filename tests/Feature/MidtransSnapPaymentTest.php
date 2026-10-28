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
}
