<?php

namespace App\Services\Payment;

use App\Models\Invoice;
use App\Models\Payment;

interface PaymentGatewayInterface
{
    /**
     * Create payment charge / attempt.
     */
    public function createCharge(Invoice $invoice, string $paymentMethod, array $extraData = []): Payment;

    /**
     * Check / sync status from gateway.
     */
    public function checkStatus(Payment $payment): Payment;

    /**
     * Handle incoming webhook or simulation callback.
     */
    public function handleCallback(array $payload): Payment;
}
