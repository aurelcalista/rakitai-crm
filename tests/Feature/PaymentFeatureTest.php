<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $cs;
    protected User $sales;
    protected User $customerA;
    protected User $customerB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name'     => 'Admin Finance',
            'email'    => 'admin@test.com',
            'password' => bcrypt('password'),
            'role'     => 'Admin',
            'status'   => 'Aktif',
        ]);

        $this->cs = User::create([
            'name'     => 'CS Officer',
            'email'    => 'cs@test.com',
            'password' => bcrypt('password'),
            'role'     => 'CS',
            'status'   => 'Aktif',
        ]);

        $this->sales = User::create([
            'name'     => 'Sales Rep',
            'email'    => 'sales@test.com',
            'password' => bcrypt('password'),
            'role'     => 'Sales',
            'status'   => 'Aktif',
        ]);

        $this->customerA = User::create([
            'name'     => 'Customer A',
            'email'    => 'customera@test.com',
            'password' => bcrypt('password'),
            'role'     => 'Sales', // Example regular user
            'status'   => 'Aktif',
        ]);

        $this->customerB = User::create([
            'name'     => 'Customer B',
            'email'    => 'customerb@test.com',
            'password' => bcrypt('password'),
            'role'     => 'Sales',
            'status'   => 'Aktif',
        ]);
    }

    public function test_cs_and_admin_can_access_payment_management()
    {
        $this->actingAs($this->cs)->get(route('payments.index'))->assertStatus(200);
        $this->actingAs($this->admin)->get(route('payments.index'))->assertStatus(200);
        $this->actingAs($this->cs)->get(route('payments.history'))->assertStatus(200);

        // Sales cannot create invoice
        $this->actingAs($this->sales)->get(route('payments.create'))->assertStatus(403);
    }

    public function test_cs_can_create_invoice_with_unique_number()
    {
        $response = $this->actingAs($this->cs)->post(route('payments.store'), [
            'customer_name'  => 'Agus Pratama',
            'customer_phone' => '081234567890',
            'amount'         => 750000,
            'due_date'       => now()->addDays(7)->toDateString(),
            'notes'          => 'Biaya Pendaftaran Ujian Masuk',
        ]);

        $invoice = Invoice::first();
        $this->assertNotNull($invoice);
        $this->assertEquals('Agus Pratama', $invoice->customer_name);
        $this->assertEquals(750000, $invoice->amount);
        $this->assertEquals('pending', $invoice->status);
        $this->assertStringStartsWith('INV-', $invoice->invoice_number);
        $response->assertRedirect(route('payments.show', $invoice->id));
    }

    public function test_qris_payment_creation_has_exact_5_minutes_expiration()
    {
        $invoice = Invoice::create([
            'customer_name'  => 'Budi Setiawan',
            'amount'         => 500000,
            'due_date'       => now()->addDays(3)->toDateString(),
            'invoice_number' => 'INV-202609-0001',
            'status'         => 'pending',
            'created_by_id'  => $this->cs->id,
        ]);

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $response = $this->actingAs($this->cs)->post(route('payments.create-payment', $invoice->id), [
            'payment_method' => 'qris',
        ]);

        $payment = Payment::first();
        $this->assertNotNull($payment);
        $this->assertEquals(Payment::STATUS_PENDING, $payment->status);
        $this->assertEquals('qris', $payment->payment_method);
        $this->assertStringStartsWith('SIM-', $payment->transaction_id);

        // Expired at must be exactly 5 minutes after created_at
        $this->assertEquals(
            $now->addMinutes(5)->toDateTimeString(),
            $payment->expired_at->toDateTimeString()
        );
        $this->assertGreaterThanOrEqual(298, $payment->remaining_seconds);
        $this->assertLessThanOrEqual(300, $payment->remaining_seconds);

        $response->assertRedirect(route('payments.simulate', $payment->transaction_id));
    }

    public function test_simulation_success_marks_payment_and_invoice_as_paid()
    {
        $invoice = Invoice::create([
            'customer_name'  => 'Siti Rahma',
            'amount'         => 1200000,
            'due_date'       => now()->addDays(5)->toDateString(),
            'invoice_number' => 'INV-202609-0002',
            'status'         => 'pending',
        ]);

        $payment = Payment::create([
            'invoice_id'      => $invoice->id,
            'transaction_id'  => 'SIM-20260929-TEST01',
            'payment_gateway' => 'simulation',
            'payment_method'  => 'qris',
            'amount'          => 1200000,
            'status'          => Payment::STATUS_PENDING,
            'expired_at'      => now()->addMinutes(5),
        ]);

        // Simulasikan pembayaran berhasil sebelum 5 menit
        $response = $this->actingAs($this->cs)->post(
            route('payments.simulate.action', $payment->transaction_id),
            ['action' => 'success']
        );

        $payment->refresh();
        $invoice->refresh();

        $this->assertEquals(Payment::STATUS_PAID, $payment->status);
        $this->assertNotNull($payment->paid_at);
        $this->assertEquals('paid', $invoice->status);

        // Halaman simulasi menampilkan status PAID
        $pageResponse = $this->actingAs($this->cs)->get(route('payments.simulate', $payment->transaction_id));
        $pageResponse->assertSee('Pembayaran Berhasil');
        $pageResponse->assertSee('PAID');
    }

    public function test_paid_payment_cannot_be_paid_again_or_reset()
    {
        $invoice = Invoice::create([
            'customer_name'  => 'Paid User',
            'amount'         => 300000,
            'due_date'       => now()->addDays(1)->toDateString(),
            'invoice_number' => 'INV-202609-0003',
            'status'         => 'paid',
        ]);

        $payment = Payment::create([
            'invoice_id'      => $invoice->id,
            'transaction_id'  => 'SIM-20260929-ALREADYPAID',
            'payment_gateway' => 'simulation',
            'payment_method'  => 'qris',
            'amount'          => 300000,
            'status'          => Payment::STATUS_PAID,
            'paid_at'         => now()->subMinute(),
            'expired_at'      => now()->addMinutes(4),
        ]);

        // Mencoba simulasi ulang harus ditolak
        $response = $this->actingAs($this->cs)->post(
            route('payments.simulate.action', $payment->transaction_id),
            ['action' => 'success']
        );

        $response->assertSessionHasErrors(['payment']);
        $this->assertEquals(Payment::STATUS_PAID, $payment->fresh()->status);
    }

    public function test_payment_auto_expires_when_current_time_exceeds_expired_at()
    {
        $invoice = Invoice::create([
            'customer_name'  => 'Late Payer',
            'amount'         => 450000,
            'due_date'       => now()->addDays(2)->toDateString(),
            'invoice_number' => 'INV-202609-0004',
            'status'         => 'pending',
        ]);

        $createdAt = Carbon::now();
        $expiredAt = (clone $createdAt)->addMinutes(5);

        $payment = Payment::create([
            'invoice_id'      => $invoice->id,
            'transaction_id'  => 'SIM-20260929-EXPIRING',
            'payment_gateway' => 'simulation',
            'payment_method'  => 'qris',
            'amount'          => 450000,
            'status'          => Payment::STATUS_PENDING,
            'created_at'      => $createdAt,
            'expired_at'      => $expiredAt,
        ]);

        // Simulasikan waktu melampaui 5 menit (misal 5 menit 10 detik kemudian)
        Carbon::setTestNow((clone $expiredAt)->addSeconds(10));

        // Akses halaman simulasi: backend harus otomatis mengubah status menjadi expired
        $this->actingAs($this->cs)->get(route('payments.simulate', $payment->transaction_id));

        $payment->refresh();
        $this->assertEquals(Payment::STATUS_EXPIRED, $payment->status);

        // Mencoba bayar setelah expired harus ditolak backend
        $response = $this->actingAs($this->cs)->post(
            route('payments.simulate.action', $payment->transaction_id),
            ['action' => 'success']
        );

        $response->assertSessionHasErrors(['payment']);
        $this->assertNotEquals(Payment::STATUS_PAID, $payment->fresh()->status);
    }

    public function test_new_payment_attempt_creates_distinct_transaction_id()
    {
        $invoice = Invoice::create([
            'customer_name'  => 'Retry Payer',
            'amount'         => 600000,
            'due_date'       => now()->addDays(2)->toDateString(),
            'invoice_number' => 'INV-202609-0005',
            'status'         => 'pending',
        ]);

        $payment1 = Payment::create([
            'invoice_id'      => $invoice->id,
            'transaction_id'  => 'SIM-20260929-FIRST',
            'payment_gateway' => 'simulation',
            'payment_method'  => 'qris',
            'amount'          => 600000,
            'status'          => Payment::STATUS_EXPIRED,
            'expired_at'      => now()->subMinute(),
        ]);

        // User membuat transaksi baru
        $this->actingAs($this->cs)->post(route('payments.create-payment', $invoice->id), [
            'payment_method' => 'qris',
        ]);

        $payment2 = Payment::latest('id')->first();
        $this->assertNotEquals($payment1->transaction_id, $payment2->transaction_id);
        $this->assertEquals(Payment::STATUS_PENDING, $payment2->status);
    }

    public function test_customer_cannot_view_or_simulate_other_customers_invoice()
    {
        $invoiceA = Invoice::create([
            'user_id'        => $this->customerA->id,
            'customer_name'  => 'Customer A',
            'amount'         => 250000,
            'due_date'       => now()->addDays(5)->toDateString(),
            'invoice_number' => 'INV-202609-0006',
            'status'         => 'pending',
        ]);

        $paymentA = Payment::create([
            'invoice_id'      => $invoiceA->id,
            'transaction_id'  => 'SIM-20260929-CUSTOMER-A',
            'payment_gateway' => 'simulation',
            'payment_method'  => 'qris',
            'amount'          => 250000,
            'status'          => Payment::STATUS_PENDING,
            'expired_at'      => now()->addMinutes(5),
        ]);

        // Customer A dapat melihat miliknya
        $this->actingAs($this->customerA)->get(route('payments.show', $invoiceA->id))->assertStatus(200);
        $this->actingAs($this->customerA)->get(route('payments.simulate', $paymentA->transaction_id))->assertStatus(200);

        // Customer B mencoba membuka milik Customer A -> Ditolak (403)
        $this->actingAs($this->customerB)->get(route('payments.show', $invoiceA->id))->assertStatus(403);
        $this->actingAs($this->customerB)->get(route('payments.simulate', $paymentA->transaction_id))->assertStatus(403);
    }
}
