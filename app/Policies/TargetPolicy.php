<?php

namespace App\Policies;

use App\Models\Target;
use App\Models\User;

class TargetPolicy
{
    /**
     * Helper: check if HM has authority over the target.
     */
    private function isHmAuthorized(User $user, Target $target): bool
    {
        if (strtolower($user->role) === 'admin') {
            return true; // Admin HAS GLOBAL ACCESS
        }

        $activeWilayahIds = $user->activeWilayahIds();
        if (empty($activeWilayahIds)) {
            return true;
        }

        if ($target->wilayah_id) {
            $targetWilayah = \App\Models\Wilayah::find($target->wilayah_id);
            if ($targetWilayah) {
                foreach ($activeWilayahIds as $hmWilayahId) {
                    if ($targetWilayah->id == $hmWilayahId || $targetWilayah->isDescendantOf($hmWilayahId)) {
                        return true;
                    }
                }
            }
        }

        return in_array($target->sales_id, $user->hmMemberIds());
    }

    /**
     * View target.
     */
    public function view(User $user, Target $target): bool
    {
        $role = strtolower($user->role);

        if (in_array($role, ['admin', 'data analyst'])) {
            return true; // Admin & Data Analyst HAVE GLOBAL READ ACCESS
        }

        return match ($role) {
            'sales' => $target->sales_id === $user->id,

            'spv'   => ($target->spv_id === $user->id) || in_array($target->sales_id, $user->teamMemberIds()),

            'hm'    => $this->isHmAuthorized($user, $target),

            default => false,
        };
    }

    /**
     * Set / Create Wilayah Target (HM operation).
     */
    public function createWilayahTarget(User $user, ?int $wilayahId = null): bool
    {
        $role = strtolower($user->role);

        if ($role === 'admin') {
            return true; // Admin HAS GLOBAL ACCESS
        }

        if ($role === 'hm') {
            $activeWilayahIds = $user->activeWilayahIds();
            if (empty($activeWilayahIds)) {
                return true; // Global HM without scope restriction
            }
            if (!$wilayahId) {
                return true;
            }
            
            $targetWilayah = \App\Models\Wilayah::find($wilayahId);
            if (!$targetWilayah) {
                return false;
            }
            
            foreach ($activeWilayahIds as $hmWilayahId) {
                if ($wilayahId == $hmWilayahId || $targetWilayah->isDescendantOf($hmWilayahId)) {
                    return true;
                }
            }
            return false;
        }

        return false;
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
            return ($target->spv_id === $user->id) || in_array($target->sales_id, $user->teamMemberIds());
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
