<?php

namespace App\Services\Payment;

use App\Models\Invoice;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class SimulationPaymentGateway implements PaymentGatewayInterface
{
    /**
     * Create simulated payment charge.
     */
    public function createCharge(Invoice $invoice, string $paymentMethod, array $extraData = []): Payment
    {
        $now = Carbon::now();
        // QRIS and simulation payments have strict 5 minutes validity
        $expiredAt = (clone $now)->addMinutes(5);

        $txId = Payment::generateTransactionId();

        $payload = [
            'mode'           => 'simulation',
            'qr_data'        => 'QRIS-SIMULASI-' . $invoice->invoice_number . '-' . $txId,
            'qr_label'       => 'QRIS SIMULASI',
            'bank_target'    => $paymentMethod === 'bank_transfer' ? 'BCA Simulation VA 8800' . rand(100000, 999999) : null,
            'ewallet_target' => $paymentMethod === 'e_wallet' ? 'GOPAY/OVO Simulation' : null,
            'created_at'     => $now->toIso8601String(),
            'expired_at'     => $expiredAt->toIso8601String(),
        ];

        return Payment::create([
            'invoice_id'      => $invoice->id,
            'transaction_id'  => $txId,
            'payment_gateway' => 'simulation',
            'payment_method'  => $paymentMethod,
            'amount'          => $invoice->amount,
            'status'          => Payment::STATUS_PENDING,
            'paid_at'         => null,
            'expired_at'      => $expiredAt,
            'payload'         => $payload,
        ]);
    }

    /**
     * Check / sync status from gateway.
     */
    public function checkStatus(Payment $payment): Payment
    {
        if ($payment->status === Payment::STATUS_PENDING) {
            if ($payment->expired_at && Carbon::now()->greaterThanOrEqualTo($payment->expired_at)) {
                $payment->update(['status' => Payment::STATUS_EXPIRED]);
            }
        }

        return $payment;
    }

    /**
     * Handle simulated callback with strict state machine.
     * Allowed:
     * pending -> paid (only if now < expired_at)
     * pending -> failed (only if now < expired_at)
     * pending -> expired (if now >= expired_at or simulation trigger)
     */
    public function handleCallback(array $payload): Payment
    {
        $transactionId = $payload['transaction_id'] ?? null;
        $simulatedAction = $payload['action'] ?? null; // 'success', 'failed', 'expired'

        $payment = Payment::where('transaction_id', $transactionId)->firstOrFail();

        // 1. Sync expiration first
        $this->checkStatus($payment);

        if ($payment->status === Payment::STATUS_PAID) {
            throw ValidationException::withMessages([
                'payment' => 'Pembayaran sudah lunas (PAID) dan tidak dapat diubah.',
            ]);
        }

        if ($payment->status === Payment::STATUS_EXPIRED) {
            throw ValidationException::withMessages([
                'payment' => 'Pembayaran sudah kedaluwarsa (EXPIRED). Silakan buat pembayaran baru.',
            ]);
        }

        if ($payment->status === Payment::STATUS_FAILED && $simulatedAction === 'success') {
            throw ValidationException::withMessages([
                'payment' => 'Pembayaran yang gagal tidak dapat langsung dibayar. Silakan buat transaksi baru.',
            ]);
        }

        $now = Carbon::now();

        // 2. Action: SUCCESS
        if ($simulatedAction === 'success') {
            if ($payment->expired_at && $now->greaterThanOrEqualTo($payment->expired_at)) {
                $payment->update(['status' => Payment::STATUS_EXPIRED]);
                throw ValidationException::withMessages([
                    'payment' => 'Waktu pembayaran telah habis (EXPIRED). Transaksi tidak dapat diselesaikan.',
                ]);
            }

            $payment->update([
                'status'  => Payment::STATUS_PAID,
                'paid_at' => $now,
            ]);

            // Update associated invoice to paid
            $payment->invoice->update([
                'status' => 'paid',
            ]);

            return $payment;
        }

        // 3. Action: FAILED
        if ($simulatedAction === 'failed') {
            if ($payment->expired_at && $now->greaterThanOrEqualTo($payment->expired_at)) {
                $payment->update(['status' => Payment::STATUS_EXPIRED]);
            } else {
                $payment->update(['status' => Payment::STATUS_FAILED]);
            }
            return $payment;
        }

        // 4. Action: EXPIRED
        if ($simulatedAction === 'expired') {
            $payment->update(['status' => Payment::STATUS_EXPIRED]);
            return $payment;
        }

        throw ValidationException::withMessages([
            'action' => 'Aksi simulasi tidak valid.',
        ]);
    }
}
