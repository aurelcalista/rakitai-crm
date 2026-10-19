<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    /**
     * View an event.
     * EO: only events created/managed by this EO (eo_id === user->id)
     * Sales: assigned in event_sales
     * SPV: assigned in event_spv OR sales in team assigned in event_sales
     * HM: events created by EO in HM's Wilayah OR assigned to SPV/Sales in HM's Wilayah
     * Admin: global
     */
    public function view(User $user, Event $event): bool
    {
        $role = strtolower($user->role);

        return match ($role) {
            'eo'    => true,

            'sales' => $event->sales()->where('users.id', $user->id)->exists(),

            'spv'   => $event->spvs()->where('users.id', $user->id)->exists()
                    || $event->sales()->whereIn('users.id', $user->teamMemberIds())->exists(),

            'hm'    => $user->wilayah_id === null
                    || ($event->eo && $event->eo->wilayah_id === $user->wilayah_id)
                    || $event->spvs()->where('users.wilayah_id', $user->wilayah_id)->exists()
                    || $event->sales()->where('users.wilayah_id', $user->wilayah_id)->exists(),

            'admin', 'data analyst' => true,

            default => false,
        };
    }

    /**
     * Create an event.
     * Only EO role can create new EO events.
     */
    public function create(User $user): bool
    {
        return strtolower($user->role) === 'eo';
    }

    /**
     * Update an event.
     * All roles except Sales can update existing EO events.
     */
    public function update(User $user, Event $event): bool
    {
        return strtolower($user->role) !== 'sales';
    }

    /**
     * Delete an event.
     * All roles except Sales can delete existing EO events.
     */
    public function delete(User $user, Event $event): bool
    {
        return strtolower($user->role) !== 'sales';
    }

    /**
     * Take over an event.
     * All roles with calendar access can take over EXCEPT Admin and Data Analyst.
     * Event must not have already been taken over.
     */
    public function takeover(User $user, Event $event): bool
    {
        $role = strtolower($user->role ?? '');
        $disallowedRoles = ['admin', 'data analyst', 'data-analyst', 'analyst'];

        if (in_array($role, $disallowedRoles)) {
            return false;
        }

        return is_null($event->takeover_by_id);
    }
}
