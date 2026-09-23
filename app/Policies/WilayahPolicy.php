<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Wilayah;

class WilayahPolicy
{
    /**
     * View any / index.
     * Admin: global
     * HM, SPV, Sales, CS, EO: permitted according to role access
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * View a specific Wilayah.
     * Admin: global
     * HM & SPV: only if the Wilayah is within their scope (isDescendantOf)
     */
    public function view(User $user, Wilayah $wilayah): bool
    {
        $role = strtolower($user->role);

        if ($role === 'admin') {
            return true;
        }

        if (in_array($role, ['hm', 'spv'])) {
            return $user->isWithinWilayahScope($wilayah);
        }

        return true;
    }

    /**
     * Create/Store a new Wilayah.
     * ONLY Admin is allowed. HM, SPV, Sales, CS, EO get 403.
     */
    public function create(User $user): bool
    {
        return strtolower($user->role) === 'admin';
    }

    /**
     * Update a Wilayah.
     * ONLY Admin is allowed.
     */
    public function update(User $user, Wilayah $wilayah): bool
    {
        return strtolower($user->role) === 'admin';
    }

    /**
     * Delete a Wilayah.
     * ONLY Admin is allowed.
     */
    public function delete(User $user, Wilayah $wilayah): bool
    {
        return strtolower($user->role) === 'admin';
    }
}
