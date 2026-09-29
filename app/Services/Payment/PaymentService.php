<?php

namespace App\Services\Payment;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\CrmActivityNotification;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(
        protected PaymentGatewayInterface $gateway
    ) {}

    /**
     * Create a new invoice.
     */
    public function createInvoice(array $data, ?User $creator = null): Invoice
    {
        $invoiceNumber = $data['invoice_number'] ?? Invoice::generateInvoiceNumber();

        return Invoice::create([
            'user_id'         => $data['user_id'] ?? null,
            'prospek_id'      => $data['prospek_id'] ?? null,
            'invoice_number'  => $invoiceNumber,
            'customer_name'   => $data['customer_name'],
            'customer_phone'  => $data['customer_phone'] ?? null,
            'amount'          => $data['amount'],
            'due_date'        => $data['due_date'],
            'status'          => 'pending',
            'notes'           => $data['notes'] ?? null,
            'created_by_id'   => $creator?->id,
        ]);
    }

    /**
     * Initiate payment transaction for an invoice.
     */
    public function initiatePayment(Invoice $invoice, string $paymentMethod): Payment
    {
        if ($invoice->status === 'paid') {
            throw ValidationException::withMessages([
                'invoice' => 'Tagihan ini sudah lunas (PAID).',
            ]);
        }

        // Create new charge via gateway
        return $this->gateway->createCharge($invoice, $paymentMethod);
    }

    /**
     * Fetch payment by transaction_id and auto-sync expiration.
     */
    public function getPaymentWithStatusSync(string $transactionId): Payment
    {
        $payment = Payment::with('invoice')->where('transaction_id', $transactionId)->firstOrFail();
        return $this->gateway->checkStatus($payment);
    }

    /**
     * Process simulated payment action.
     */
    public function simulatePaymentAction(string $transactionId, string $action): Payment
    {
        $payment = $this->gateway->handleCallback([
            'transaction_id' => $transactionId,
            'action'         => $action,
        ]);

        // Send notifications
        $this->sendPaymentNotification($payment, $action);

        return $payment;
    }

    /**
     * Send CrmActivityNotification on payment state change.
     */
    protected function sendPaymentNotification(Payment $payment, string $action): void
    {
        try {
            $invoice = $payment->invoice;
            $nominalFormatted = 'Rp ' . number_format($payment->amount, 0, ',', '.');

            $recipients = collect();

            // 1. If invoice has assigned user
            if ($invoice->user) {
                $recipients->push($invoice->user);
            }

            // 2. Creator (CS or Admin)
            if ($invoice->creator && (!$invoice->user || $invoice->user->id !== $invoice->creator->id)) {
                $recipients->push($invoice->creator);
            }

            // 3. All CS users for monitoring
            $csUsers = User::where('role', 'CS')->where('status', 'aktif')->take(5)->get();
            $recipients = $recipients->merge($csUsers)->unique('id');

            $title = match ($payment->status) {
                Payment::STATUS_PAID    => '💳 Pembayaran Berhasil',
                Payment::STATUS_FAILED  => '❌ Pembayaran Gagal',
                Payment::STATUS_EXPIRED => '⏱️ Pembayaran Kedaluwarsa',
                default                 => 'Pembayaran Diperbarui',
            };

            $type = match ($payment->status) {
                Payment::STATUS_PAID    => 'success',
                Payment::STATUS_FAILED  => 'danger',
                Payment::STATUS_EXPIRED => 'warning',
                default                 => 'info',
            };

            $message = match ($payment->status) {
                Payment::STATUS_PAID    => "Pembayaran {$nominalFormatted} untuk tagihan {$invoice->invoice_number} ({$invoice->customer_name}) telah berhasil tervalidasi.",
                Payment::STATUS_FAILED  => "Pembayaran {$nominalFormatted} ({$payment->transaction_id}) ditandai gagal.",
                Payment::STATUS_EXPIRED => "Pembayaran {$nominalFormatted} ({$payment->transaction_id}) telah kedaluwarsa setelah 5 menit.",
                default                 => "Status transaksi {$payment->transaction_id} diperbarui menjadi {$payment->status}.",
            };

            foreach ($recipients as $recipient) {
                $recipient->notify(new CrmActivityNotification(
                    title: $title,
                    message: $message,
                    type: $type,
                    link: route('payments.show', $invoice->id),
                    icon: '💳',
                    senderName: 'Payment System',
                    senderRole: 'System',
                    action: 'payment_' . $payment->status
                ));
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Error sending payment notification: ' . $e->getMessage());
        }
    }
}
