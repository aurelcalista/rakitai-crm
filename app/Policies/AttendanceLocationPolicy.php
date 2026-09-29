<?php

namespace App\Policies;

use App\Models\AttendanceLocation;
use App\Models\User;

class AttendanceLocationPolicy
{
    /**
     * View locations list.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Create attendance location (Admin only).
     */
    public function create(User $user): bool
    {
        return strtolower($user->role) === 'admin';
    }

    /**
     * Update attendance location (Admin only).
     */
    public function update(User $user, AttendanceLocation $location): bool
    {
        return strtolower($user->role) === 'admin';
    }

    /**
     * Delete attendance location (Admin only).
     */
    public function delete(User $user, AttendanceLocation $location): bool
    {
        return strtolower($user->role) === 'admin';
    }
}
