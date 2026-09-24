<?php

namespace App\Http\Controllers\Spv;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Wilayah;
use App\Models\Prospek;
use App\Models\Kunjungan;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TeamController extends Controller
{
    /**
     * Display SPV Team members list & candidate Sales/CS pool.
     */
    public function index(): View
    {
        $user = auth()->user();
        $teamMemberIds = $user->teamMemberIds();

        $subordinates = User::whereIn('id', $teamMemberIds)
            ->with(['wilayah.parent'])
            ->get();

        $teamData = $subordinates->map(function ($member) {
            $prospectCount = Prospek::where('sales_id', $member->id)
                ->orWhere('cs_id', $member->id)
                ->count();
            $closingCount = Prospek::where(function ($q) use ($member) {
                $q->where('sales_id', $member->id)->orWhere('cs_id', $member->id);
            })->where('status', 'LUNAS')->count();
            $visitCount = Kunjungan::where('sales_id', $member->id)->count();

            // Fetch active multi-wilayah names
            $activeWilayahes = $member->activeWilayahes;
            $wilayahNames = $activeWilayahes->pluck('nama')->toArray();

            if (empty($wilayahNames)) {
                if ($member->wilayah) {
                    $wilayahNames[] = $member->wilayah->nama;
                } elseif (!empty($member->lokasi_penugasan)) {
                    $wilayahNames[] = 'Kota Lainnya (' . $member->lokasi_penugasan . ')';
                }
            }

            $wilayahDisplay = !empty($wilayahNames)
                ? implode(', ', $wilayahNames)
                : ($member->role === 'CS' ? 'Centralized (Belum Ada Area)' : 'Belum Ditugaskan');

            return [
                'id'               => $member->id,
                'name'             => $member->name,
                'email'            => $member->email,
                'phone'            => $member->phone ?? '-',
                'role'             => $member->role,
                'status'           => $member->status,
                'is_cs'            => $member->role === 'CS',
                'wilayah'          => $wilayahDisplay,
                'active_wilayahs'  => $activeWilayahes,
                'wilayah_id'       => $member->wilayah_id,
                'lokasi_penugasan' => $member->lokasi_penugasan,
                'is_other_city'    => !empty($member->lokasi_penugasan) && empty($member->wilayah_id),
                'kota'             => $member->wilayah && $member->wilayah->parent ? $member->wilayah->parent->nama : ($member->lokasi_penugasan ?: '-'),
                'prospects'        => $prospectCount,
                'closings'         => $closingCount,
                'visits'           => $visitCount,
                'last_login'       => $member->last_login_at ? \Carbon\Carbon::parse($member->last_login_at)->diffForHumans() : 'Belum Pernah',
            ];
        });

        // 1. Resolve SPV's Kota/Kabupaten
        $mainWilayah = $user->wilayah_id ? Wilayah::find($user->wilayah_id) : null;
        $spvKotaId = null;
        if ($mainWilayah) {
            if ($mainWilayah->level === 'Kota/Kabupaten') {
                $spvKotaId = $mainWilayah->id;
            } elseif ($mainWilayah->parent_id) {
                $spvKotaId = $mainWilayah->parent_id;
            }
        }

        $spvKota = $spvKotaId ? Wilayah::find($spvKotaId) : $mainWilayah;
        $myWilayah = $spvKota ? $spvKota->nama : ($user->wilayah ? $user->wilayah->nama : 'Belum Ada Scope Wilayah');

        // 2. Fetch Kecamatan strictly under SPV's Kota/Kabupaten (from Admin Wilayah master data)
        $kecamatanList = collect();
        if ($spvKotaId) {
            $kecamatanList = Wilayah::where('parent_id', $spvKotaId)
                ->where('level', 'Kecamatan')
                ->where('status', 'Aktif')
                ->orderBy('nama')
                ->get();
        }

        $descendantWilayahIds = $spvKota ? $spvKota->getDescendantIds() : [];
        $spvTeamMemberIds = $user->teamMemberIds();

        // 3. Candidates filtered strictly within SPV Kota scope
        $salesCandidates = User::whereRaw('LOWER(role) = ?', ['sales'])
            ->where(function($q) use ($user, $descendantWilayahIds, $spvTeamMemberIds) {
                if (!empty($descendantWilayahIds)) {
                    $q->where('supervisor_id', $user->id)
                      ->orWhereIn('id', $spvTeamMemberIds)
                      ->orWhereNull('wilayah_id')
                      ->orWhereIn('wilayah_id', $descendantWilayahIds)
                      ->orWhereHas('activeWilayahes', function($wq) use ($descendantWilayahIds) {
                          $wq->whereIn('wilayah_id', $descendantWilayahIds);
                      });
                }
            })
            ->with(['activeWilayahes'])
            ->orderBy('name')
            ->get();

        $csCandidates = User::whereRaw('LOWER(role) = ?', ['cs'])
            ->where(function($q) use ($user, $descendantWilayahIds, $spvTeamMemberIds) {
                if (!empty($descendantWilayahIds)) {
                    $q->where('supervisor_id', $user->id)
                      ->orWhereIn('id', $spvTeamMemberIds)
                      ->orWhereNull('wilayah_id')
                      ->orWhereIn('wilayah_id', $descendantWilayahIds)
                      ->orWhereHas('activeWilayahes', function($wq) use ($descendantWilayahIds) {
                          $wq->whereIn('wilayah_id', $descendantWilayahIds);
                      });
                }
            })
            ->with(['activeWilayahes'])
            ->orderBy('name')
            ->get();

        $candidates = User::whereIn('role', ['Sales', 'CS'])
            ->where(function($q) use ($descendantWilayahIds) {
                if (!empty($descendantWilayahIds)) {
                    $q->whereIn('wilayah_id', $descendantWilayahIds)->orWhereNull('wilayah_id');
                }
            })
            ->orderBy('name')
            ->get();

        $availableAreas = $kecamatanList;

        return view('spv.tim.index', compact('teamData', 'candidates', 'salesCandidates', 'csCandidates', 'availableAreas', 'myWilayah', 'kecamatanList'));
    }

    /**
     * SPV Select/Assign a Sales or CS user into their team.
     * Enforces strict backend descendant scope validation. ID tampering returns 403.
     */
    public function assignMember(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'area_id' => 'nullable|exists:wilayahs,id',
        ]);

        $candidate = User::findOrFail($request->user_id);

        \Illuminate\Support\Facades\Gate::authorize('assignTeamMember', $candidate);

        $areaId = $request->area_id ?? $candidate->wilayah_id ?? $user->wilayah_id;

        if ($areaId) {
            $area = Wilayah::find($areaId);
            if ($area && !$candidate->isWithinWilayahScope($user->wilayah_id) && !$area->isDescendantOf($user->wilayah_id)) {
                abort(403, 'Area detail pilihan berada di luar cakupan Wilayah SPV.');
            }
        }

        $candidate->update([
            'supervisor_id' => $user->id,
            'wilayah_id'    => $areaId,
        ]);

        if ($areaId) {
            $role = $candidate->role;
            if ($role === 'Sales') {
                \Illuminate\Support\Facades\DB::table('user_wilayah')
                    ->where('user_id', $candidate->id)
                    ->where('role', 'Sales')
                    ->where('wilayah_id', '!=', $areaId)
                    ->where('is_active', true)
                    ->update([
                        'is_active'      => false,
                        'deactivated_at' => now(),
                        'updated_at'     => now(),
                    ]);
            }

            \Illuminate\Support\Facades\DB::table('user_wilayah')->updateOrInsert(
                ['user_id' => $candidate->id, 'wilayah_id' => $areaId, 'role' => $role],
                ['is_active' => true, 'assigned_at' => now(), 'deactivated_at' => null, 'updated_at' => now()]
            );
        }

        return redirect()->back()->with('success', "Anggota tim {$candidate->name} ({$candidate->role}) berhasil ditambahkan ke tim SPV!");
    }

    /**
     * SPV Form: Assign Sales & CS to an Area/Kecamatan under SPV scope.
     * Enforces unique 1 active Sales & unique 1 active CS per area rule.
     */
    public function assignTeamTerritory(Request $request): RedirectResponse
    {
        $spv = auth()->user();

        $request->validate([
            'sales_id'      => 'nullable|exists:users,id',
            'sales_area_id' => 'nullable|exists:wilayahs,id',
            'area_id'       => 'nullable|exists:wilayahs,id',
            'cs_id'         => 'nullable|exists:users,id',
            'cs_area_ids'   => 'nullable|array',
            'cs_area_ids.*' => 'exists:wilayahs,id',
        ]);

        $spvWilayahId = $spv->wilayah_id;
        $salesAreaId = $request->sales_area_id ?? $request->area_id;

        // 1. Validate Sales Assignment
        if ($request->filled('sales_id')) {
            $sales = User::findOrFail($request->sales_id);

            $salesInScope = !$spvWilayahId 
                || !$sales->wilayah_id
                || $sales->isWithinWilayahScope($spvWilayahId) 
                || $spv->isSupervisorOf($sales) 
                || in_array($sales->id, $spv->teamMemberIds());

            if (!$salesInScope) {
                abort(403, 'Sales yang dipilih berada di luar cakupan Wilayah SPV.');
            }

            if (strtolower($sales->role) !== 'sales') {
                return redirect()->back()->with('error', "User {$sales->name} bukan ber-role Sales.");
            }

            if ($salesAreaId) {
                $salesArea = Wilayah::findOrFail($salesAreaId);

                // Strict SPV Scope check
                if ($spvWilayahId && !$salesArea->isDescendantOf($spvWilayahId) && $salesArea->id != $spvWilayahId) {
                    abort(403, 'Area penugasan berada di luar cakupan Wilayah SPV.');
                }

                // Unique Sales per Area Rule
                $existingActiveSales = \Illuminate\Support\Facades\DB::table('user_wilayah')
                    ->where('wilayah_id', $salesArea->id)
                    ->where('role', 'Sales')
                    ->where('is_active', true)
                    ->where('user_id', '!=', $sales->id)
                    ->first();

                if ($existingActiveSales) {
                    $otherSales = User::find($existingActiveSales->user_id);
                    $otherName = $otherSales ? $otherSales->name : 'Sales lain';
                    return redirect()->back()->with('error', "Area {$salesArea->nama} sudah memiliki Sales aktif. Silakan nonaktifkan atau ubah assignment sebelumnya.");
                }

                $sales->update([
                    'supervisor_id' => $spv->id,
                    'wilayah_id'    => $salesAreaId,
                ]);

                // Deactivate previous active area assignments for this Sales user
                \Illuminate\Support\Facades\DB::table('user_wilayah')
                    ->where('user_id', $sales->id)
                    ->where('role', 'Sales')
                    ->where('wilayah_id', '!=', $salesAreaId)
                    ->where('is_active', true)
                    ->update([
                        'is_active'      => false,
                        'deactivated_at' => now(),
                        'updated_at'     => now(),
                    ]);

                \Illuminate\Support\Facades\DB::table('user_wilayah')->updateOrInsert(
                    ['user_id' => $sales->id, 'wilayah_id' => $salesAreaId, 'role' => 'Sales'],
                    ['is_active' => true, 'assigned_at' => now(), 'deactivated_at' => null, 'updated_at' => now()]
                );
            }
        }

        // 2. Validate CS Assignment
        if ($request->filled('cs_id')) {
            $cs = User::findOrFail($request->cs_id);

            $csInScope = !$spvWilayahId 
                || !$cs->wilayah_id
                || $cs->isWithinWilayahScope($spvWilayahId) 
                || $spv->isSupervisorOf($cs) 
                || in_array($cs->id, $spv->teamMemberIds());

            if (!$csInScope) {
                abort(403, 'CS yang dipilih berada di luar cakupan Wilayah SPV.');
            }

            if (strtolower($cs->role) !== 'cs') {
                return redirect()->back()->with('error', "User {$cs->name} bukan ber-role CS.");
            }

            $hasMultiSelect = $request->has('cs_area_ids');
            $csAreaIds = array_filter((array) ($request->cs_area_ids ?? ($request->area_id ? [$request->area_id] : [])));

            if (!empty($csAreaIds)) {
                // Strict SPV Scope check for each requested area
                foreach ($csAreaIds as $cAreaId) {
                    $cArea = Wilayah::findOrFail($cAreaId);
                    if ($spvWilayahId && !$cArea->isDescendantOf($spvWilayahId) && $cArea->id != $spvWilayahId) {
                        abort(403, 'Area penugasan berada di luar cakupan Wilayah SPV.');
                    }
                }

                // Check unique active CS per area rule for all requested areas
                foreach ($csAreaIds as $cAreaId) {
                    $existingActiveCs = \Illuminate\Support\Facades\DB::table('user_wilayah')
                        ->where('wilayah_id', $cAreaId)
                        ->where('role', 'CS')
                        ->where('is_active', true)
                        ->where('user_id', '!=', $cs->id)
                        ->first();

                    if ($existingActiveCs) {
                        $cArea = Wilayah::find($cAreaId);
                        $areaName = $cArea ? $cArea->nama : "ID {$cAreaId}";
                        return redirect()->back()->with('error', "Area {$areaName} sudah memiliki CS aktif. Silakan nonaktifkan atau ubah assignment sebelumnya.");
                    }
                }

                $cs->update([
                    'supervisor_id' => $spv->id,
                    'wilayah_id'    => $cs->wilayah_id ?: $spvWilayahId,
                ]);

                // Deactivate CS active areas under SPV scope that are no longer selected (only when multi-select input is explicitly passed)
                if ($hasMultiSelect) {
                    $spvMainWilayah = $spvWilayahId ? Wilayah::find($spvWilayahId) : null;
                    $spvScopeAreaIds = $spvMainWilayah ? $spvMainWilayah->getDescendantIds() : Wilayah::pluck('id')->toArray();

                    \Illuminate\Support\Facades\DB::table('user_wilayah')
                        ->where('user_id', $cs->id)
                        ->where('role', 'CS')
                        ->whereIn('wilayah_id', $spvScopeAreaIds)
                        ->whereNotIn('wilayah_id', $csAreaIds)
                        ->where('is_active', true)
                        ->update([
                            'is_active'      => false,
                            'deactivated_at' => now(),
                            'updated_at'     => now(),
                        ]);
                }

                // Activate selected CS areas
                foreach ($csAreaIds as $cAreaId) {
                    \Illuminate\Support\Facades\DB::table('user_wilayah')->updateOrInsert(
                        ['user_id' => $cs->id, 'wilayah_id' => $cAreaId, 'role' => 'CS'],
                        ['is_active' => true, 'assigned_at' => now(), 'deactivated_at' => null, 'updated_at' => now()]
                    );
                }
            }
        }

        if (!$request->filled('sales_id') && !$request->filled('cs_id')) {
            return redirect()->back()->with('error', 'Silakan pilih Sales atau CS untuk ditugaskan.');
        }

        return redirect()->route('spv.tim.index')
            ->with('success', 'Penugasan tim berhasil diperbarui!');
    }

    /**
     * Assign Kecamatan (wilayah) ke Sales/CS oleh SPV.
     */
    public function assignWilayah(Request $request, User $user): RedirectResponse
    {
        $spv = auth()->user();

        if (!$spv->isSupervisorOf($user) && !in_array($user->id, $spv->teamMemberIds())) {
            abort(403, 'Anda tidak berwenang mengatur anggota tim ini.');
        }

        if ($request->boolean('is_other_city')) {
            $request->validate([
                'custom_city' => 'required|string|max:255',
            ]);

            $user->update([
                'wilayah_id'       => null,
                'lokasi_penugasan' => $request->custom_city,
            ]);

            return redirect()->route('spv.tim.index')
                ->with('success', "Wilayah {$user->name} berhasil ditugaskan di Kota Lainnya: {$request->custom_city}.");
        }

        $request->validate([
            'wilayah_id' => 'required|exists:wilayahs,id',
        ]);

        $wilayah = Wilayah::findOrFail($request->wilayah_id);

        if ($wilayah->level !== 'Kecamatan') {
            return redirect()->route('spv.tim.index')
                ->with('error', 'SPV hanya dapat menugaskan Kecamatan, bukan Kota/Kabupaten.');
        }

        $role = $user->role;

        // Check unique active assignment for this area & role
        $existingActive = \Illuminate\Support\Facades\DB::table('user_wilayah')
            ->where('wilayah_id', $wilayah->id)
            ->where('role', $role)
            ->where('is_active', true)
            ->where('user_id', '!=', $user->id)
            ->first();

        if ($existingActive) {
            $otherUser = User::find($existingActive->user_id);
            $otherName = $otherUser ? $otherUser->name : "{$role} lain";
            return redirect()->route('spv.tim.index')
                ->with('error', "Area {$wilayah->nama} sudah memiliki {$role} aktif ({$otherName}). Silakan nonaktifkan atau ubah assignment sebelumnya.");
        }

        $user->update([
            'wilayah_id'       => $wilayah->id,
            'lokasi_penugasan' => null,
        ]);

        if ($role === 'Sales') {
            // Deactivate previous active area assignments for this Sales user
            \Illuminate\Support\Facades\DB::table('user_wilayah')
                ->where('user_id', $user->id)
                ->where('role', 'Sales')
                ->where('wilayah_id', '!=', $wilayah->id)
                ->where('is_active', true)
                ->update([
                    'is_active'      => false,
                    'deactivated_at' => now(),
                    'updated_at'     => now(),
                ]);
        }

        \Illuminate\Support\Facades\DB::table('user_wilayah')->updateOrInsert(
            ['user_id' => $user->id, 'wilayah_id' => $wilayah->id, 'role' => $role],
            ['is_active' => true, 'assigned_at' => now(), 'deactivated_at' => null, 'updated_at' => now()]
        );

        return redirect()->route('spv.tim.index')
            ->with('success', "Wilayah {$user->name} berhasil diperbarui ke {$wilayah->nama}.");
    }

    /**
     * Deactivate user_wilayah assignment history.
     */
    public function deactivateTerritory(Request $request, User $user, Wilayah $wilayah): RedirectResponse
    {
        $spv = auth()->user();

        if (!$spv->isSupervisorOf($user) && !in_array($user->id, $spv->teamMemberIds()) && strtolower($spv->role) !== 'admin') {
            abort(403, 'Anda tidak berwenang mengelola wilayah user ini.');
        }

        \Illuminate\Support\Facades\DB::table('user_wilayah')
            ->where('user_id', $user->id)
            ->where('wilayah_id', $wilayah->id)
            ->update([
                'is_active'      => false,
                'deactivated_at' => now(),
                'updated_at'     => now(),
            ]);

        return redirect()->back()->with('success', "Penugasan wilayah {$wilayah->nama} untuk {$user->name} telah dinonaktifkan.");
    }
}

