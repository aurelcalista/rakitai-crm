<?php

namespace App\Policies;

use App\Models\Target;
use App\Models\User;

class TargetPolicy
{
    /**
     * Helper: check if HM has authority over the target (via Sales' Wilayah).
     */
    private function isHmAuthorized(User $user, Target $target): bool
    {
        if (is_null($user->wilayah_id)) {
            return true;
        }

        if ($target->sales && $target->sales->wilayah_id === $user->wilayah_id) {
            return true;
        }

        return in_array($target->sales_id, $user->hmMemberIds());
    }

    /**
     * View target.
     */
    public function view(User $user, Target $target): bool
    {
        $role = strtolower($user->role);

        return match ($role) {
            'sales' => $target->sales_id === $user->id,

            'spv'   => in_array($target->sales_id, $user->teamMemberIds()),

            'hm'    => $this->isHmAuthorized($user, $target),

            'admin' => true,

            default => false,
        };
    }

    /**
     * Lock target. Allowed only for Admin and HM for their Wilayah.
     */
    public function lock(User $user, Target $target): bool
    {
        $role = strtolower($user->role);

        if ($role === 'admin') {
            return true;
        }

        if ($role === 'hm') {
            return $this->isHmAuthorized($user, $target);
        }

        return false;
    }

    /**
     * Unlock target. Allowed only for Admin and HM for their Wilayah.
     */
    public function unlock(User $user, Target $target): bool
    {
        return $this->lock($user, $target);
    }

    /**
     * Update target fields.
     * If locked: only Admin or authorized HM can update. SPV and Sales CANNOT update locked target.
     * If unlocked: Admin, HM, and SPV (for team members) can update. Sales CANNOT update targets.
     */
    public function update(User $user, Target $target): bool
    {
        $role = strtolower($user->role);

        if ($role === 'sales') {
            return false;
        }

        if ($target->isLocked()) {
            if ($role === 'admin') {
                return true;
            }
            if ($role === 'hm') {
                return $this->isHmAuthorized($user, $target);
            }
            return false;
        }

        if ($role === 'admin') {
            return true;
        }

        if ($role === 'hm') {
            return $this->isHmAuthorized($user, $target);
        }

        if ($role === 'spv') {
            return in_array($target->sales_id, $user->teamMemberIds());
        }

        return false;
    }

    /**
     * Delete target.
     */
    public function delete(User $user, Target $target): bool
    {
        return $this->update($user, $target);
    }
}
