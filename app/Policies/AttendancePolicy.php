<?php

namespace App\Policies;

use App\Models\Attendance;
use App\Models\User;

class AttendancePolicy
{
    /**
     * Check if user can perform attendance check-in.
     */
    public function checkIn(User $user): bool
    {
        return in_array(strtolower($user->role), ['sales', 'spv', 'eo']);
    }

    /**
     * Check if user can monitor attendance records.
     */
    public function monitor(User $user): bool
    {
        return in_array(strtolower($user->role), ['admin', 'hm', 'spv']);
    }

    /**
     * View a single attendance record.
     */
    public function view(User $user, Attendance $attendance): bool
    {
        $role = strtolower($user->role);

        // Owner can always view their own record
        if ($attendance->user_id === $user->id) {
            return true;
        }

        return match ($role) {
            'admin' => true,
            'hm'    => in_array($attendance->user_id, $user->getAttendanceMonitoredUserIds()),
            'spv'   => in_array($attendance->user_id, $user->getAttendanceMonitoredUserIds()),
            default => false,
        };
    }

    /**
     * View/download private selfie photo.
     */
    public function viewPhoto(User $user, Attendance $attendance): bool
    {
        return $this->view($user, $attendance);
    }
}
