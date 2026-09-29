<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    /**
     * View any invoices list (CS, Admin).
     */
    public function viewAny(User $user): bool
    {
        return in_array(strtolower($user->role), ['cs', 'admin']);
    }

    /**
     * View a single invoice: CS, Admin, or the invoice owner.
     */
    public function view(User $user, Invoice $invoice): bool
    {
        $role = strtolower($user->role);
        if (in_array($role, ['cs', 'admin'])) {
            return true;
        }

        return $invoice->user_id !== null && $invoice->user_id === $user->id;
    }

    /**
     * Create an invoice (CS, Admin).
     */
    public function create(User $user): bool
    {
        return in_array(strtolower($user->role), ['cs', 'admin']);
    }

    /**
     * Update an invoice (CS, Admin).
     */
    public function update(User $user, Invoice $invoice): bool
    {
        return in_array(strtolower($user->role), ['cs', 'admin']);
    }

    /**
     * Delete an invoice (Admin only).
     */
    public function delete(User $user, Invoice $invoice): bool
    {
        return strtolower($user->role) === 'admin';
    }
}
