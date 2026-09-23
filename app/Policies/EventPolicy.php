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
            'eo'    => $event->eo_id === $user->id,

            'sales' => $event->sales()->where('users.id', $user->id)->exists(),

            'spv'   => $event->spvs()->where('users.id', $user->id)->exists()
                    || $event->sales()->whereIn('users.id', $user->teamMemberIds())->exists(),

            'hm'    => $user->wilayah_id === null
                    || ($event->eo && $event->eo->wilayah_id === $user->wilayah_id)
                    || $event->spvs()->where('users.wilayah_id', $user->wilayah_id)->exists()
                    || $event->sales()->where('users.wilayah_id', $user->wilayah_id)->exists(),

            'admin' => true,

            default => false,
        };
    }

    /**
     * Create an event.
     */
    public function create(User $user): bool
    {
        return in_array(strtolower($user->role), ['eo', 'spv', 'hm', 'admin']);
    }

    /**
     * Update an event.
     */
    public function update(User $user, Event $event): bool
    {
        $role = strtolower($user->role);

        if ($role === 'admin') return true;

        if ($role === 'eo') {
            return $event->eo_id === $user->id;
        }

        if ($role === 'hm') {
            return $user->wilayah_id === null || ($event->eo && $event->eo->wilayah_id === $user->wilayah_id);
        }

        if ($role === 'spv') {
            return $event->spvs()->where('users.id', $user->id)->exists();
        }

        return false;
    }

    /**
     * Delete an event.
     */
    public function delete(User $user, Event $event): bool
    {
        return $this->update($user, $event);
    }
}
