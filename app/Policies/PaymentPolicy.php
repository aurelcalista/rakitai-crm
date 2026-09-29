<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    /**
     * View any payments / history (CS, Admin).
     */
    public function viewAny(User $user): bool
    {
        return in_array(strtolower($user->role), ['cs', 'admin']);
    }

    /**
     * View a single payment: CS, Admin, or the invoice owner.
     */
    public function view(User $user, Payment $payment): bool
    {
        $role = strtolower($user->role);
        if (in_array($role, ['cs', 'admin'])) {
            return true;
        }

        $invoice = $payment->invoice;
        return $invoice && $invoice->user_id !== null && $invoice->user_id === $user->id;
    }

    /**
     * Simulate or trigger payment actions: CS, Admin, or the invoice owner.
     */
    public function simulate(User $user, Payment $payment): bool
    {
        return $this->view($user, $payment);
    }
}
