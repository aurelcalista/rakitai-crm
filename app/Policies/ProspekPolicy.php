<?php

namespace App\Policies;

use App\Models\Prospek;
use App\Models\User;

class ProspekPolicy
{
    /**
     * Helper: check if HM has authority over the prospect's Wilayah.
     */
    private function isHmAuthorized(User $user, Prospek $prospek): bool
    {
        if (is_null($user->wilayah_id)) {
            return true; // HM without specific wilayah restriction has global view
        }

        if ($prospek->wilayah_id && $prospek->wilayah_id === $user->wilayah_id) {
            return true;
        }

        $hmMemberIds = $user->hmMemberIds();
        return in_array($prospek->sales_id, $hmMemberIds)
            || in_array($prospek->owner_id, $hmMemberIds);
    }

    /**
     * Helper: check if SPV has authority over the prospect (via team membership).
     */
    private function isSpvAuthorized(User $user, Prospek $prospek): bool
    {
        $teamIds = $user->teamMemberIds();

        return ($prospek->sales_id && in_array($prospek->sales_id, $teamIds))
            || ($prospek->owner_id && in_array($prospek->owner_id, $teamIds))
            || ($prospek->cs_id && in_array($prospek->cs_id, $teamIds))
            || $prospek->sales_id === $user->id
            || $prospek->owner_id === $user->id;
    }

    /**
     * Helper: check if a Sales is authorized (own prospect or another Sales' prospect).
     * Sesuai revisi hak akses follow-up:
     * Semua pengguna dengan role Sales dapat melihat, melakukan follow-up, dan memperbarui status
     * pada prospek milik Sales mana pun tanpa pembatasan kesamaan wilayah maupun tim SPV.
     */
    private function isSalesAuthorized(User $user, Prospek $prospek): bool
    {
        // 1. Prospek milik sendiri (sebagai sales handler atau owner)
        if ($prospek->sales_id === $user->id || $prospek->owner_id === $user->id) {
            return true;
        }

        // 2. Proteksi role non-Sales: Prospek eksklusif CS tidak dapat diintervensi oleh Sales
        if (is_null($prospek->sales_id) && !is_null($prospek->cs_id)) {
            $leadOwner = $prospek->owner ?? ($prospek->owner_id ? User::find($prospek->owner_id) : null);
            if ($leadOwner && strtolower($leadOwner->role ?? '') === 'cs') {
                return false;
            }
        }

        $salesOwner = $prospek->sales ?? ($prospek->sales_id ? User::find($prospek->sales_id) : null);
        $leadOwner  = $prospek->owner ?? ($prospek->owner_id ? User::find($prospek->owner_id) : null);

        $salesRole = strtolower(optional($salesOwner)->role ?? '');
        $ownerRole = strtolower(optional($leadOwner)->role ?? '');

        // 3. Prospek milik Sales lain (baik sebagai handler maupun creator/owner):
        // Diizinkan untuk semua Sales lintas tim SPV dan lintas wilayah.
        if ($salesRole === 'sales' || $ownerRole === 'sales' || !is_null($prospek->sales_id)) {
            return true;
        }

        return false;
    }

    /**
     * View a prospect.
     * Admin: global
     * HM: scoped to HM's Wilayah
     * SPV: scoped to team members under SPV
     * Sales: handled, owned, team members under same supervisor, or cs-assigned prospects
     * CS: only assigned prospects or unassigned FORMULIR handover prospects in same Wilayah
     */
    public function view(User $user, Prospek $prospek): bool
    {
        $role = strtolower($user->role);

        return match ($role) {
            'sales' => ($prospek->sales_id === $user->id || $prospek->owner_id === $user->id)
                    || $this->isSalesAuthorized($user, $prospek)
                    || $prospek->cs_id === $user->id,

            'cs'    => $prospek->cs_id === $user->id
                    || (in_array($prospek->status, ['FORMULIR', 'BERKAS', 'LUNAS']) && (!$user->wilayah_id || !$prospek->wilayah_id || $prospek->wilayah_id === $user->wilayah_id)),

            'spv'   => $this->isSpvAuthorized($user, $prospek),

            'hm'    => $this->isHmAuthorized($user, $prospek),

            'admin', 'data analyst' => true,

            default => false,
        };
    }

    /**
     * Create a new prospect.
     */
    public function create(User $user): bool
    {
        return in_array(strtolower($user->role), ['sales', 'cs', 'spv', 'hm', 'admin']);
    }

    /**
     * Update prospect data fields.
     */
    public function update(User $user, Prospek $prospek): bool
    {
        $role = strtolower($user->role);

        if ($role === 'admin') {
            return true;
        }

        if ($role === 'hm') {
            return $this->isHmAuthorized($user, $prospek);
        }

        if ($role === 'spv') {
            return $this->isSpvAuthorized($user, $prospek);
        }

        return $prospek->isActiveHandler($user) || $prospek->owner_id === $user->id;
    }

    /**
     * Perform a follow-up on a prospect.
     */
    public function followUp(User $user, Prospek $prospek): bool
    {
        $role = strtolower($user->role);

        if ($role === 'admin') {
            return true;
        }

        if ($role === 'hm') {
            return $this->isHmAuthorized($user, $prospek);
        }

        if ($role === 'spv') {
            return $this->isSpvAuthorized($user, $prospek);
        }

        if ($role === 'cs') {
            return $prospek->cs_id === $user->id || (is_null($prospek->cs_id) && in_array($prospek->status, ['FORMULIR', 'BERKAS', 'LUNAS']) && (!$user->wilayah_id || !$prospek->wilayah_id || $prospek->wilayah_id === $user->wilayah_id));
        }

        if ($role === 'sales') {
            // Aturan bisnis: Sales boleh membantu follow-up prospek yang dimiliki/ditangani oleh Sales lain yang diizinkan
            if ($prospek->sales_id === $user->id || $prospek->owner_id === $user->id) {
                return true;
            }
            return $this->isSalesAuthorized($user, $prospek);
        }

        return false;
    }

    /**
     * Update pipeline status.
     */
    public function updateStatus(User $user, Prospek $prospek): bool
    {
        $role = strtolower($user->role);

        if ($role === 'admin') {
            return true;
        }

        if ($role === 'hm') {
            return $this->isHmAuthorized($user, $prospek);
        }

        if ($role === 'spv') {
            return $this->isSpvAuthorized($user, $prospek);
        }

        if ($role === 'cs') {
            return $prospek->cs_id === $user->id || (is_null($prospek->cs_id) && in_array($prospek->status, ['FORMULIR', 'BERKAS', 'LUNAS']) && (!$user->wilayah_id || !$prospek->wilayah_id || $prospek->wilayah_id === $user->wilayah_id));
        }

        if ($role === 'sales') {
            // Sales cannot transition a prospect if status is already LUNAS
            if (strtoupper($prospek->status) === 'LUNAS') {
                return false;
            }

            // Can update if handled by this sales, owned by this sales, or authorized sales helper
            return ($prospek->sales_id === $user->id || $prospek->owner_id === $user->id)
                || $this->isSalesAuthorized($user, $prospek);
        }

        return false;
    }

    /**
     * Takeover / Assignment.
     */
    public function takeover(User $user, Prospek $prospek): bool
    {
        $role = strtolower($user->role);

        if ($role === 'admin') {
            return true;
        }

        if ($role === 'hm') {
            return $this->isHmAuthorized($user, $prospek);
        }

        if ($role === 'spv') {
            return $this->isSpvAuthorized($user, $prospek);
        }

        if ($role === 'sales') {
            return $prospek->isActiveHandler($user) && is_null($prospek->cs_id);
        }
        
        if ($role === 'cs') {
            return in_array($prospek->status, ['FORMULIR', 'BERKAS', 'LUNAS']) && (!$user->wilayah_id || !$prospek->wilayah_id || $prospek->wilayah_id === $user->wilayah_id);
        }

        return false;
    }

    /**
     * Input manual transaction (Beli Formulir & Pembayaran Termin 1) / Closing.
     */
    public function transaction(User $user, Prospek $prospek): bool
    {
        $role = strtolower($user->role);

        if ($role === 'admin') {
            return true;
        }

        if ($role === 'hm') {
            return $this->isHmAuthorized($user, $prospek);
        }

        if ($role === 'spv') {
            return $this->isSpvAuthorized($user, $prospek);
        }

        return $prospek->sales_id === $user->id 
            || $prospek->owner_id === $user->id 
            || $prospek->cs_id === $user->id
            || $prospek->isActiveHandler($user);
    }

    /**
     * Re-allocate prospect between sales in the team.
     */
    public function reallocate(User $user, Prospek $prospek): bool
    {
        $role = strtolower($user->role);

        if ($role === 'admin') {
            return true;
        }

        if ($role === 'hm') {
            return $this->isHmAuthorized($user, $prospek);
        }

        if ($role === 'spv') {
            return $this->isSpvAuthorized($user, $prospek);
        }

        if ($role === 'cs') {
            return $prospek->cs_id === $user->id || is_null($prospek->sales_id);
        }

        return false;
    }

    /**
     * Mark prospect as Lost.
     */
    public function markLost(User $user, Prospek $prospek): bool
    {
        $role = strtolower($user->role);

        if ($role === 'admin') {
            return true;
        }

        if ($role === 'hm') {
            return $this->isHmAuthorized($user, $prospek);
        }

        if ($role === 'spv') {
            return $this->isSpvAuthorized($user, $prospek);
        }

        return $prospek->isActiveHandler($user);
    }

    /**
     * Delete a prospect.
     */
    public function delete(User $user, Prospek $prospek): bool
    {
        $role = strtolower($user->role);

        if ($role === 'admin') {
            return true;
        }

        if ($role === 'hm') {
            return $this->isHmAuthorized($user, $prospek);
        }

        if ($role === 'spv') {
            return $this->isSpvAuthorized($user, $prospek);
        }

        return false;
    }
}
