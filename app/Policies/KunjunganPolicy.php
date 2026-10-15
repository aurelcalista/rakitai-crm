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

        return match ($role) {
            'sales' => $kunjungan->sales_id === $user->id,

            'spv'   => in_array($kunjungan->sales_id, $user->teamMemberIds()),

            'hm'    => $user->wilayah_id === null
                    || in_array($kunjungan->sales_id, $user->hmMemberIds())
                    || ($kunjungan->sales && $kunjungan->sales->wilayah_id === $user->wilayah_id),

            'admin', 'data analyst' => true,

            default => false,
        };
    }

    /**
     * Create a visit.
     */
    public function create(User $user): bool
    {
        return in_array(strtolower($user->role), ['sales', 'spv', 'hm', 'admin']);
    }

    /**
     * Delete a visit record.
     */
    public function delete(User $user, Kunjungan $kunjungan): bool
    {
        $role = strtolower($user->role);

        return match ($role) {
            'sales' => $kunjungan->sales_id === $user->id,
            'spv'   => in_array($kunjungan->sales_id, $user->teamMemberIds()),
            'hm'    => $user->wilayah_id === null || in_array($kunjungan->sales_id, $user->hmMemberIds()),
            'admin' => true,
            default => false,
        };
    }
}
