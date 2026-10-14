<?php

namespace App\Policies;

use App\Models\Prospek;
use App\Models\User;

class ProspekPolicy
{
    /**
     * View a prospect.
     * Semua pengguna internal yang login berwenang melihat detail prospek atau kontak user lain
     * tanpa pembatasan akses (tidak ada 403 Forbidden).
     */
    public function view(User $user, Prospek $prospek): bool
    {
        return in_array(strtolower($user->role), ['admin', 'cs', 'sales', 'spv', 'hm', 'eo', 'data analyst']);
    }

    /**
     * Create a new prospect.
     */
    public function create(User $user): bool
    {
        return in_array(strtolower($user->role), ['sales', 'cs', 'spv', 'hm', 'admin', 'eo']);
    }

    /**
     * Update prospect data fields.
     * Pengguna dapat mengedit data prospek tanpa pembatasan akses.
     */
    public function update(User $user, Prospek $prospek): bool
    {
        return in_array(strtolower($user->role), ['admin', 'cs', 'sales', 'spv', 'hm', 'eo']);
    }

    /**
     * Perform a follow-up on a prospect.
     * Pengguna dapat mencatat follow-up pada prospek milik user lain tanpa pembatasan akses.
     */
    public function followUp(User $user, Prospek $prospek): bool
    {
        return in_array(strtolower($user->role), ['admin', 'cs', 'sales', 'spv', 'hm', 'eo']);
    }

    /**
     * Update pipeline status.
     * Pengguna dapat memperbarui status pipeline prospek tanpa pembatasan akses.
     */
    public function updateStatus(User $user, Prospek $prospek): bool
    {
        return in_array(strtolower($user->role), ['admin', 'cs', 'sales', 'spv', 'hm', 'eo']);
    }

    /**
     * Takeover / Assignment.
     * Prospek yang belum diambil alih dapat di-takeover oleh Sales, CS, SPV, HM, atau Admin.
     */
    public function takeover(User $user, Prospek $prospek): bool
    {
        // Prospek yang sudah diambil alih tidak dapat di-takeover ulang
        if ($prospek->status_takeover === 'Sudah Di-takeover' || !is_null($prospek->takeover_by_id)) {
            return false;
        }

        $role = strtolower($user->role);

        if (in_array($role, ['admin', 'sales', 'spv', 'hm'])) {
            return true;
        }

        if ($role === 'cs') {
            // CS wilayah scope untuk presensi siswa jika wilayah sudah ditentukan
            if (($prospek->source === Prospek::SOURCE_PRESENSI_SISWA || $prospek->source === 'Presensi Siswa')
                && $user->wilayah_id && $prospek->wilayah_id && $prospek->wilayah_id !== $user->wilayah_id) {
                return false;
            }
            return true;
        }

        return false;
    }

    /**
     * Input manual transaction (Beli Formulir & Pembayaran Termin 1 / Closing).
     * Semua pengguna internal yang login berwenang menginput transaksi pada prospek atau kontak user lain
     * tanpa pembatasan akses (bebas input transaksi).
     */
    public function transaction(User $user, Prospek $prospek): bool
    {
        return in_array(strtolower($user->role), ['admin', 'cs', 'sales', 'spv', 'hm', 'eo']);
    }

    /**
     * Re-allocate prospect between team members.
     */
    public function reallocate(User $user, Prospek $prospek): bool
    {
        return in_array(strtolower($user->role), ['admin', 'spv', 'hm', 'cs', 'sales']);
    }

    /**
     * Mark prospect as Lost.
     */
    public function markLost(User $user, Prospek $prospek): bool
    {
        return in_array(strtolower($user->role), ['admin', 'cs', 'sales', 'spv', 'hm']);
    }

    /**
     * Delete a prospect.
     */
    public function delete(User $user, Prospek $prospek): bool
    {
        return in_array(strtolower($user->role), ['admin', 'spv', 'hm']);
    }
}
