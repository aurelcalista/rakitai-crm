<?php

namespace App\Policies;

use App\Models\Prospek;
use App\Models\User;

class ProspekPolicy
{
    /**
     * View a prospect.
     * Sales can view if they are the sales handler, cs handler, or owner.
     * CS can view if they are the cs handler or sales handler.
     */
    public function view(User $user, Prospek $prospek): bool
    {
        $role = strtolower($user->role);

        return match ($role) {
            'sales' => $prospek->sales_id === $user->id
                    || $prospek->owner_id === $user->id
                    || $prospek->cs_id === $user->id,

            'cs'    => $prospek->cs_id === $user->id
                    || $prospek->sales_id !== null, // CS can see any that has a sales assigned

            'spv', 'hm', 'admin' => true, // Management can see all

            default => false,
        };
    }

    /**
     * Create a new prospect.
     * Both Sales and CS can create prospects.
     */
    public function create(User $user): bool
    {
        return in_array(strtolower($user->role), ['sales', 'cs', 'spv', 'hm', 'admin']);
    }

    /**
     * Update prospect data fields.
     */
    public function update(User $user, Prospek $prospek): bool
    {
        if (in_array(strtolower($user->role), ['spv', 'hm', 'admin'])) {
            return true;
        }

        return $prospek->isActiveHandler($user);
    }

    /**
     * Perform a follow-up on a prospect.
     */
    public function followUp(User $user, Prospek $prospek): bool
    {
        $role = strtolower($user->role);

        if (in_array($role, ['spv', 'hm', 'admin'])) {
            return true;
        }

        if ($role === 'cs') {
            // CS scope: either explicitly assigned to them, or has a sales assigned
            return $prospek->cs_id === $user->id || !is_null($prospek->sales_id);
        }

        if ($role === 'sales') {
            // Sales scope: either they are the active sales or they are the owner
            return $prospek->sales_id === $user->id || $prospek->owner_id === $user->id;
        }

        return false;
    }

    /**
     * Update pipeline status.
     */
    public function updateStatus(User $user, Prospek $prospek): bool
    {
        return $this->followUp($user, $prospek);
    }

    /**
     * Takeover / Assignment.
     * Allowed if user is SPV/HM/Admin OR if user is the current active Sales handler handing over to CS.
     * Also allowed for CS to proactively takeover if it has a sales_id but no cs_id yet.
     */
    public function takeover(User $user, Prospek $prospek): bool
    {
        if (in_array(strtolower($user->role), ['spv', 'hm', 'admin'])) {
            return true;
        }

        if (strtolower($user->role) === 'sales') {
            return $prospek->isActiveHandler($user) && is_null($prospek->cs_id);
        }
        
        if (strtolower($user->role) === 'cs') {
            return !is_null($prospek->sales_id) && is_null($prospek->cs_id);
        }

        return false;
    }

    /**
     * Input manual transaction (Beli Formulir & Pembayaran Termin 1).
     * Sales and CS can input transaction if they are the active handler.
     */
    public function transaction(User $user, Prospek $prospek): bool
    {
        if (in_array(strtolower($user->role), ['spv', 'hm', 'admin'])) {
            return true;
        }

        return $prospek->isActiveHandler($user);
    }

    /**
     * Mark prospect as Lost.
     */
    public function markLost(User $user, Prospek $prospek): bool
    {
        if (in_array(strtolower($user->role), ['spv', 'hm', 'admin'])) {
            return true;
        }

        return $prospek->isActiveHandler($user);
    }

    /**
     * Delete a prospect.
     * Only supervisors, HM, and admin can delete.
     */
    public function delete(User $user, Prospek $prospek): bool
    {
        return in_array(strtolower($user->role), ['spv', 'hm', 'admin']);
    }
}
