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
     * Only allowed for the Sales who is the active handler (sales_id matches).
     */
    public function update(User $user, Prospek $prospek): bool
    {
        if (in_array(strtolower($user->role), ['spv', 'hm', 'admin'])) {
            return true;
        }

        return $prospek->isHandledBySales($user);
    }

    /**
     * Perform a follow-up on a prospect.
     * CRITICAL: Only allowed when the Sales is the ACTIVE HANDLER (sales_id = user->id).
     * If handler is CS, Sales CANNOT follow-up.
     */
    public function followUp(User $user, Prospek $prospek): bool
    {
        if (in_array(strtolower($user->role), ['spv', 'hm', 'admin'])) {
            return true;
        }

        // For CS role: only if they are the cs handler
        if (strtolower($user->role) === 'cs') {
            return $prospek->isHandledByCs($user);
        }

        // For Sales role: only if they are the sales handler AND prospect is not fully handled by CS only
        if (strtolower($user->role) === 'sales') {
            return $prospek->isHandledBySales($user);
        }

        return false;
    }

    /**
     * Update pipeline status.
     * Same as follow-up: only active handler can change status.
     */
    public function updateStatus(User $user, Prospek $prospek): bool
    {
        return $this->followUp($user, $prospek);
    }

    /**
     * Mark prospect as Lost.
     * Only the active Sales handler can mark as Lost.
     */
    public function markLost(User $user, Prospek $prospek): bool
    {
        if (in_array(strtolower($user->role), ['spv', 'hm', 'admin'])) {
            return true;
        }

        return $prospek->isHandledBySales($user);
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
