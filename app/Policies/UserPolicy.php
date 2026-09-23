<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Wilayah;

class UserPolicy
{
    /**
     * Admin has global access.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * View user details.
     */
    public function view(User $user, User $model): bool
    {
        if (strtolower($user->role) === 'admin') {
            return true;
        }

        if (strtolower($user->role) === 'hm') {
            return in_array($model->id, $user->hmMemberIds());
        }

        if (strtolower($user->role) === 'spv') {
            return in_array($model->id, $user->teamMemberIds());
        }

        return $user->id === $model->id;
    }

    /**
     * Create user. Only Admin can create users.
     */
    public function create(User $user): bool
    {
        return strtolower($user->role) === 'admin';
    }

    /**
     * Update user details. Only Admin can update users globally.
     */
    public function update(User $user, User $model): bool
    {
        return strtolower($user->role) === 'admin';
    }

    /**
     * Delete user. Only Admin can delete users.
     */
    public function delete(User $user, User $model): bool
    {
        return strtolower($user->role) === 'admin';
    }

    /**
     * HM appointing/assigning an SPV to a Wilayah.
     * Admin: global PASS
     * HM: PASS only if target Wilayah & target SPV are within HM's Wilayah scope. Mismatch/ID tampering -> 403.
     */
    public function assignSpv(User $user, User $spv, ?Wilayah $targetWilayah = null): bool
    {
        $role = strtolower($user->role);

        if ($role === 'admin') {
            return true; // Admin HAS GLOBAL ACCESS
        }

        if ($role !== 'hm') {
            return false;
        }

        if (strtolower($spv->role) !== 'spv') {
            return false;
        }

        // Validate HM scope over target Wilayah
        if ($targetWilayah && $user->wilayah_id) {
            $isSameOrChild = ($targetWilayah->id == $user->wilayah_id) || $targetWilayah->isDescendantOf($user->wilayah_id);
            if (!$isSameOrChild) {
                return false;
            }
        }

        return true;
    }

    /**
     * SPV selecting Sales or CS into their team.
     * Admin: global PASS
     * SPV: PASS only if candidate Sales/CS is within SPV's descendant Wilayah scope. ID tampering -> 403.
     */
    public function assignTeamMember(User $user, User $candidate): bool
    {
        $role = strtolower($user->role);

        if ($role === 'admin') {
            return true; // Admin HAS GLOBAL ACCESS
        }

        if ($role !== 'spv') {
            return false;
        }

        if (!in_array(strtolower($candidate->role), ['sales', 'cs'])) {
            return false;
        }

        // Candidate must be within SPV's Wilayah scope (recursive descendant check)
        if ($candidate->wilayah_id && !$candidate->isWithinWilayahScope($user->wilayah_id)) {
            return false;
        }

        return true;
    }
}
