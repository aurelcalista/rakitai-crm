<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Target;
use App\Models\User;
use App\Models\Wilayah;
use App\Services\AkademikService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminHmWilayahController extends Controller
{
    /**
     * Display list of Kota/Kabupaten and HM Wilayah assignments.
     */
    public function index(): View
    {
        $activeTA = AkademikService::getAktif();

        // All Kota/Kabupaten (parent_id is null)
        $wilayahs = Wilayah::whereNull('parent_id')
            ->with(['children', 'users'])
            ->get()
            ->map(function ($kota) use ($activeTA) {
                $descendantIds = $kota->getDescendantIds();

                // HM currently assigned to this Kota (check both wilayah_id and pivot)
                $assignedHm = User::where('role', 'HM')->where(function($q) use ($kota) {
                    $q->where('wilayah_id', $kota->id)
                      ->orWhereHas('activeWilayahes', function($wq) use ($kota) {
                          $wq->where('wilayah_id', $kota->id);
                      });
                })->first();

                // SPV currently assigned to this Kota
                $assignedSpv = $kota->users->where('role', 'SPV')->first();

                // Active Target Wilayah
                $targetWil = Target::where('target_type', 'Wilayah')
                    ->where('wilayah_id', $kota->id)
                    ->where('status', 'Aktif')
                    ->when($activeTA, fn($q) => $q->where('academic_year_id', $activeTA->id))
                    ->latest()
                    ->first();

                if (!$assignedSpv && $targetWil && $targetWil->spv_id) {
                    $assignedSpv = User::find($targetWil->spv_id);
                }

                // Team count under this Kota and its kecamatans
                $salesCount = User::where('role', 'Sales')
                    ->whereIn('wilayah_id', $descendantIds)
                    ->count();

                $csCount = User::where('role', 'CS')
                    ->whereIn('wilayah_id', $descendantIds)
                    ->count();

                $kota->assigned_hm = $assignedHm;
                $kota->assigned_spv = $assignedSpv;
                $kota->target_wilayah = $targetWil;
                $kota->sales_count = $salesCount;
                $kota->cs_count = $csCount;

                return $kota;
            });

        // List of all active HM users
        $hmList = User::where('role', 'HM')
            ->where('status', 'Aktif')
            ->with('wilayah')
            ->orderBy('name')
            ->get();

        // HMs without any assigned wilayah (neither main nor pivot)
        $unassignedHms = $hmList->filter(function($hm) {
            return empty($hm->wilayah_id) && $hm->activeWilayahes->isEmpty();
        });

        return view('admin.hm-wilayah.index', compact('wilayahs', 'hmList', 'unassignedHms', 'activeTA'));
    }

    /**
     * Admin assign HM to a Kota/Kabupaten.
     */
    public function assign(Request $request): RedirectResponse
    {
        $request->validate([
            'hm_id'      => 'required|exists:users,id',
            'wilayah_id' => 'required|exists:wilayahs,id',
        ]);

        $hm = User::findOrFail($request->hm_id);
        $wilayah = Wilayah::findOrFail($request->wilayah_id);

        if (strtolower($hm->role) !== 'hm') {
            return redirect()->back()->with('error', 'User yang dipilih bukan Head Marketing (HM).');
        }

        // Reassign previous HMs if any
        $previousHms = User::where('role', 'HM')
            ->where(function($q) use ($wilayah) {
                $q->where('wilayah_id', $wilayah->id)
                  ->orWhereHas('activeWilayahes', function($wq) use ($wilayah) {
                      $wq->where('wilayah_id', $wilayah->id);
                  });
            })
            ->where('id', '!=', $hm->id)
            ->get();

        foreach ($previousHms as $prevHm) {
            if ($prevHm->wilayah_id == $wilayah->id) {
                $prevHm->update(['wilayah_id' => null]);
            }
            // Deactivate pivot
            if ($prevHm->activeWilayahes()->where('wilayah_id', $wilayah->id)->exists()) {
                $prevHm->activeWilayahes()->updateExistingPivot($wilayah->id, ['is_active' => false, 'deactivated_at' => now()]);
            }
        }

        if (empty($hm->wilayah_id)) {
            $hm->update(['wilayah_id' => $wilayah->id]);
        }

        // Attach or update pivot for this new HM
        if (!$hm->activeWilayahes()->where('wilayah_id', $wilayah->id)->exists()) {
            $hm->activeWilayahes()->attach($wilayah->id, [
                'role' => 'HM', 
                'is_active' => true, 
                'assigned_at' => now()
            ]);
        } else {
            $hm->activeWilayahes()->updateExistingPivot($wilayah->id, [
                'is_active' => true, 
                'deactivated_at' => null
            ]);
        }

        return redirect()->back()->with('success', "Berhasil menugaskan HM {$hm->name} ke Wilayah {$wilayah->nama}!");
    }

    /**
     * Admin unassign HM from a Wilayah.
     */
    public function unassign(User $user): RedirectResponse
    {
        if (strtolower($user->role) !== 'hm') {
            return redirect()->back()->with('error', 'User bukan HM.');
        }

        $wilayahNama = 'wilayah yang dipilih';
        
        $wilayahId = request('wilayah_id');
        if ($wilayahId) {
            if ($user->wilayah_id == $wilayahId) {
                $user->update(['wilayah_id' => null]);
            }
            if ($user->activeWilayahes()->where('wilayah_id', $wilayahId)->exists()) {
                $user->activeWilayahes()->updateExistingPivot($wilayahId, ['is_active' => false, 'deactivated_at' => now()]);
            }
            $wilayah = Wilayah::find($wilayahId);
            if ($wilayah) {
                $wilayahNama = $wilayah->nama;
            }
        } else {
            // Unassign all if no specific wilayah_id provided
            $user->update(['wilayah_id' => null]);
            foreach ($user->activeWilayahes as $w) {
                $user->activeWilayahes()->updateExistingPivot($w->id, ['is_active' => false, 'deactivated_at' => now()]);
            }
            $wilayahNama = 'seluruh wilayah';
        }

        return redirect()->back()->with('success', "Penugasan HM {$user->name} dari {$wilayahNama} berhasil dilepas.");
    }
}
