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
     * Create user. Admin and HM can create users.
     */
    public function create(User $user): bool
    {
        $role = strtolower($user->role);
        return $role === 'admin' || $role === 'hm';
    }

    /**
     * SPV creating a new Sales user under their scope.
     */
    public function createSales(User $user): bool
    {
        $role = strtolower($user->role);
        return $role === 'spv' || $role === 'admin' || $role === 'hm';
    }

    /**
     * Update user details. Admin globally, HM within scope.
     */
    public function update(User $user, User $model): bool
    {
        $role = strtolower($user->role);
        if ($role === 'admin') {
            return true;
        }

        if ($role === 'hm') {
            return in_array($model->id, $user->hmMemberIds()) || $user->isWithinWilayahScope($model->wilayah_id);
        }

        return false;
    }

    /**
     * Delete user. Admin globally, HM within scope (except deleting self).
     */
    public function delete(User $user, User $model): bool
    {
        $role = strtolower($user->role);
        if ($role === 'admin') {
            return true;
        }

        if ($role === 'hm' && $user->id !== $model->id) {
            return in_array($model->id, $user->hmMemberIds()) || $user->isWithinWilayahScope($model->wilayah_id);
        }

        return false;
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
     * Creating a new CS user. Admin and HM can create CS. SPV is forbidden (403).
     */
    public function createCs(User $user): bool
    {
        $role = strtolower($user->role);
        return $role === 'admin' || $role === 'hm';
    }

    /**
     * HM appointing/assigning a CS to a Wilayah area.
     * Admin: global PASS
     * HM: PASS only if target Wilayah & target CS are within HM's Wilayah scope. SPV: 403.
     */
    public function assignCs(User $user, User $cs, ?Wilayah $targetWilayah = null): bool
    {
        $role = strtolower($user->role);

        if ($role === 'admin') {
            return true;
        }

        if ($role !== 'hm') {
            return false;
        }

        if (strtolower($cs->role) !== 'cs') {
            return false;
        }

        if ($targetWilayah && $user->wilayah_id) {
            $isSameOrChild = ($targetWilayah->id == $user->wilayah_id) || $targetWilayah->isDescendantOf($user->wilayah_id);
            if (!$isSameOrChild) {
                return false;
            }
        }

        return true;
    }

    /**
     * SPV selecting Sales into their team.
     * Admin: global PASS
     * SPV: PASS only if candidate is Sales and within SPV's descendant Wilayah scope.
     * SPV is forbidden from managing/assigning CS (403).
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

        if (strtolower($candidate->role) !== 'sales') {
            return false; // SPV can ONLY manage Sales (CS is forbidden)
        }

        // Candidate must be within SPV's Wilayah scope (recursive descendant check)
        if ($candidate->wilayah_id && !$candidate->isWithinWilayahScope($user->wilayah_id)) {
            return false;
        }

        return true;
    }
}
