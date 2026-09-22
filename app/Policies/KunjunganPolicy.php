<?php

namespace App\Policies;

use App\Models\Kunjungan;
use App\Models\User;

class KunjunganPolicy
{
    /**
     * Sales hanya boleh menghapus kunjungan miliknya sendiri.
     * Admin boleh menghapus semua.
     * Role lain tidak diizinkan.
     */
    public function delete(User $user, Kunjungan $kunjungan): bool
    {
        $role = strtolower($user->role);

        return match ($role) {
            'sales' => $kunjungan->sales_id === $user->id,
            'admin' => true,
            default => false,
        };
    }
}
