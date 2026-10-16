<?php

namespace App\Policies;

use App\Models\Kunjungan;
use App\Models\User;

class KunjunganPolicy
{
    /**
     * View a visit record.
     */
    public function view(User $user, Kunjungan $kunjungan): bool
    {
        $role = strtolower($user->role);

        return in_array($role, ['sales', 'spv', 'hm', 'admin', 'cs', 'eo', 'data analyst']);
    }

    /**
     * Create a visit.
     */
    public function create(User $user): bool
    {
        return in_array(strtolower($user->role), ['sales', 'spv', 'hm', 'admin', 'cs', 'eo']);
    }

    /**
     * Update a visit record.
     */
    public function update(User $user, Kunjungan $kunjungan): bool
    {
        return in_array(strtolower($user->role), ['sales', 'spv', 'hm', 'admin', 'cs', 'eo']);
    }

    /**
     * Delete a visit record.
     */
    public function delete(User $user, Kunjungan $kunjungan): bool
    {
        return in_array(strtolower($user->role), ['sales', 'spv', 'hm', 'admin', 'cs', 'eo']);
    }
}
