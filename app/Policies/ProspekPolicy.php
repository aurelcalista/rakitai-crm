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
        if (in_array(strtolower($user->role), ['spv', 'hm', 'admin'])) {
            return true;
        }

        return $prospek->isActiveHandler($user);
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
     */
    public function takeover(User $user, Prospek $prospek): bool
    {
        if (in_array(strtolower($user->role), ['spv', 'hm', 'admin'])) {
            return true;
        }

        // Only sales can hand over to CS, and only if they are the current active handler (i.e. not already handed over).
        return strtolower($user->role) === 'sales' && $prospek->isActiveHandler($user) && is_null($prospek->cs_id);
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
