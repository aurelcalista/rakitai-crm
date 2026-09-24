<?php

namespace App\Http\Controllers\Hm;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Wilayah;
use App\Models\Prospek;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class HmCsController extends Controller
{
    /**
     * Display CS members list & assignment management for HM.
     */
    public function index(): View
    {
        $user = auth()->user();

        // 1. Resolve HM's Kota/Kabupaten scope
        $mainWilayah = $user->wilayah_id ? Wilayah::find($user->wilayah_id) : null;
        $hmKotaId = null;
        if ($mainWilayah) {
            if ($mainWilayah->level === 'Kota/Kabupaten') {
                $hmKotaId = $mainWilayah->id;
            } elseif ($mainWilayah->parent_id) {
                $hmKotaId = $mainWilayah->parent_id;
            }
        }

        $hmKota = $hmKotaId ? Wilayah::find($hmKotaId) : $mainWilayah;
        $myWilayah = $hmKota ? $hmKota->nama : ($user->wilayah ? $user->wilayah->nama : 'Semua Wilayah (Global HM/Admin)');
        $descendantWilayahIds = $hmKota ? $hmKota->getDescendantIds() : Wilayah::pluck('id')->toArray();

        // 2. Fetch active Kecamatan list under HM scope
        $kecamatanList = collect();
        if ($hmKotaId) {
            $kecamatanList = Wilayah::where('parent_id', $hmKotaId)
                ->where('level', 'Kecamatan')
                ->where('status', 'Aktif')
                ->orderBy('nama')
                ->get();
        } else {
            $kecamatanList = Wilayah::where('level', 'Kecamatan')
                ->where('status', 'Aktif')
                ->orderBy('nama')
                ->get();
        }

        // 3. Fetch CS users under HM scope
        $csUsers = User::whereRaw('LOWER(role) = ?', ['cs'])
            ->where(function ($q) use ($user, $descendantWilayahIds) {
                if (!empty($descendantWilayahIds) && strtolower($user->role) !== 'admin') {
                    $q->whereIn('wilayah_id', $descendantWilayahIds)
                      ->orWhereHas('activeWilayahes', function ($wq) use ($descendantWilayahIds) {
                          $wq->whereIn('wilayah_id', $descendantWilayahIds);
                      })
                      ->orWhereNull('wilayah_id');
                }
            })
            ->with(['wilayah.parent', 'activeWilayahes.parent'])
            ->orderBy('name')
            ->get();

        $csData = $csUsers->map(function ($cs) use ($hmKota, $hmKotaId, $user) {
            if (empty($cs->kode)) {
                $cs->kode = User::generateUserCode('CS');
                $cs->save();
            }

            // Auto-assign CS without wilayah_id to HM's Wilayah scope
            $targetWilayahId = $hmKotaId ?? ($user->wilayah_id ?? null);
            if (empty($cs->wilayah_id) && $targetWilayahId) {
                $cs->wilayah_id = $targetWilayahId;
                $cs->save();
                $cs->load('wilayah.parent');

                DB::table('user_wilayah')->updateOrInsert(
                    ['user_id' => $cs->id, 'wilayah_id' => $targetWilayahId, 'role' => 'CS'],
                    ['is_active' => true, 'assigned_at' => now(), 'deactivated_at' => null, 'updated_at' => now()]
                );
            }

            $prospectCount = Prospek::where('cs_id', $cs->id)->count();
            $closingCount  = Prospek::where('cs_id', $cs->id)->where('status', 'LUNAS')->count();

            $activeWilayahes = $cs->activeWilayahes;
            $wilayahNames = $activeWilayahes->pluck('nama')->toArray();

            if (empty($wilayahNames)) {
                if ($cs->wilayah) {
                    $wilayahNames[] = $cs->wilayah->nama;
                } elseif (!empty($cs->lokasi_penugasan)) {
                    $wilayahNames[] = 'Kota Lainnya (' . $cs->lokasi_penugasan . ')';
                }
            }

            $wilayahDisplay = !empty($wilayahNames)
                ? implode(', ', $wilayahNames)
                : 'Belum Ditugaskan Area';

            // Resolve Kota/Kabupaten name
            $kotaName = null;
            if ($cs->wilayah) {
                if ($cs->wilayah->parent) {
                    $kotaName = $cs->wilayah->parent->nama;
                } elseif ($cs->wilayah->level === 'Kota/Kabupaten') {
                    $kotaName = $cs->wilayah->nama;
                }
            }

            if (!$kotaName && $activeWilayahes->isNotEmpty()) {
                $firstWithParent = $activeWilayahes->first(fn($w) => $w->parent !== null);
                if ($firstWithParent && $firstWithParent->parent) {
                    $kotaName = $firstWithParent->parent->nama;
                }
            }

            if (!$kotaName && $hmKota) {
                $kotaName = $hmKota->nama;
            }

            return [
                'id'               => $cs->id,
                'kode'             => $cs->kode,
                'name'             => $cs->name,
                'email'            => $cs->email,
                'phone'            => $cs->phone ?? '-',
                'role'             => 'CS',
                'status'           => $cs->status,
                'wilayah'          => $wilayahDisplay,
                'active_wilayahs'  => $activeWilayahes,
                'wilayah_id'       => $cs->wilayah_id,
                'kota'             => $kotaName ?: '-',
                'prospects'        => $prospectCount,
                'closings'         => $closingCount,
                'last_login'       => $cs->last_login_at ? \Carbon\Carbon::parse($cs->last_login_at)->diffForHumans() : 'Belum Pernah',
            ];
        });

        return view('hm.cs.index', compact('csData', 'myWilayah', 'kecamatanList', 'csUsers'));
    }

    /**
     * HM Create a new CS user.
     * Enforces:
     * - Role strictly 'CS', Jabatan strictly 'CS'
     * - Email domain strictly '@cic.ac.id'
     * - Auto-generated unique code
     * - Multi-wilayah area assignment within HM scope
     * - Strict backend scope check (403 on ID tampering)
     */
    public function store(Request $request): RedirectResponse
    {
        $hm = auth()->user();

        Gate::authorize('createCs', User::class);

        // Auto-append @cic.ac.id if input is just username prefix or from email_username
        $rawEmail = trim((string) ($request->input('email_username') ?: $request->input('email')));
        if (!empty($rawEmail)) {
            if (!str_contains($rawEmail, '@')) {
                $rawEmail .= '@cic.ac.id';
            }
            $request->merge(['email' => strtolower($rawEmail)]);
        }

        $request->validate([
            'name'         => 'required|string|max:255',
            'email'        => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users',
                'regex:/^[a-zA-Z0-9._%+-]+@cic\.ac\.id$/i',
            ],
            'phone'        => 'nullable|string|max:20',
            'password'     => 'required|string|confirmed',
            'cs_area_ids'   => 'nullable|array',
            'cs_area_ids.*' => 'exists:wilayahs,id',
        ], [
            'email.regex' => 'Email CS wajib menggunakan domain @cic.ac.id.',
        ]);

        return DB::transaction(function () use ($request, $hm) {
            // Resolve HM Kota ID scope
            $mainWilayah = $hm->wilayah_id ? Wilayah::find($hm->wilayah_id) : null;
            $hmKotaId = null;
            if ($mainWilayah) {
                if ($mainWilayah->level === 'Kota/Kabupaten') {
                    $hmKotaId = $mainWilayah->id;
                } elseif ($mainWilayah->parent_id) {
                    $hmKotaId = $mainWilayah->parent_id;
                }
            }

            $selectedAreaIds = array_filter((array) ($request->cs_area_ids ?? []));

            // Validate HM Scope & Kota Lainnya rejection for each area ID
            if (!empty($selectedAreaIds)) {
                foreach ($selectedAreaIds as $areaId) {
                    $area = Wilayah::findOrFail($areaId);
                    if (str_contains(strtolower($area->nama), 'lainnya') || $area->kode === 'W-LAIN') {
                        abort(403, 'Kota Lainnya tidak dapat dijadikan wilayah operasional.');
                    }
                    if ($hmKotaId && strtolower($hm->role) !== 'admin' && !$area->isDescendantOf($hmKotaId) && $area->id != $hmKotaId && $area->id != $hm->wilayah_id) {
                        abort(403, 'Area penugasan berada di luar cakupan Wilayah HM.');
                    }
                }
            }

            $defaultWilayahId = $hmKotaId ?? ($hm->wilayah_id ?? null);
            $primaryAreaId = !empty($selectedAreaIds) ? $selectedAreaIds[0] : $defaultWilayahId;

            // Create CS User with automatic role = CS & jabatan = CS
            $cs = User::create([
                'name'          => $request->name,
                'email'         => strtolower(trim($request->email)),
                'phone'         => $request->phone ?? '-',
                'password'      => Hash::make($request->password),
                'role'          => 'CS',
                'jabatan'       => 'CS',
                'status'        => 'Aktif',
                'kode'          => User::generateUserCode('CS'),
                'supervisor_id' => null,
                'wilayah_id'    => $primaryAreaId,
            ]);

            // Assign Multi-Wilayah in user_wilayah
            if (!empty($selectedAreaIds)) {
                foreach ($selectedAreaIds as $areaId) {
                    DB::table('user_wilayah')->updateOrInsert(
                        ['user_id' => $cs->id, 'wilayah_id' => $areaId, 'role' => 'CS'],
                        ['is_active' => true, 'assigned_at' => now(), 'deactivated_at' => null, 'updated_at' => now()]
                    );
                }
            } elseif ($primaryAreaId) {
                DB::table('user_wilayah')->updateOrInsert(
                    ['user_id' => $cs->id, 'wilayah_id' => $primaryAreaId, 'role' => 'CS'],
                    ['is_active' => true, 'assigned_at' => now(), 'deactivated_at' => null, 'updated_at' => now()]
                );
            }

            return redirect()->route('hm.cs.index')
                ->with('success', "Akun CS baru {$cs->name} ({$cs->kode}) berhasil dibuat dan ditugaskan!");
        });
    }

    /**
     * HM Assign / Update CS Multi-Wilayah areas.
     */
    public function assignTerritory(Request $request): RedirectResponse
    {
        $hm = auth()->user();

        $request->validate([
            'cs_id'         => 'required|exists:users,id',
            'cs_area_ids'   => 'nullable|array',
            'cs_area_ids.*' => 'exists:wilayahs,id',
        ]);

        $cs = User::findOrFail($request->cs_id);

        Gate::authorize('assignCs', [$cs]);

        if (strtolower($cs->role) !== 'cs') {
            return redirect()->back()->with('error', "User {$cs->name} bukan ber-role CS.");
        }

        $selectedAreaIds = array_filter((array) ($request->cs_area_ids ?? []));

        // Resolve HM Scope
        $mainWilayah = $hm->wilayah_id ? Wilayah::find($hm->wilayah_id) : null;
        $hmKotaId = null;
        if ($mainWilayah) {
            $hmKotaId = $mainWilayah->level === 'Kota/Kabupaten' ? $mainWilayah->id : $mainWilayah->parent_id;
        }

        if (!empty($selectedAreaIds) && $hmKotaId && strtolower($hm->role) !== 'admin') {
            foreach ($selectedAreaIds as $areaId) {
                $area = Wilayah::findOrFail($areaId);
                if (!$area->isDescendantOf($hmKotaId) && $area->id != $hmKotaId && $area->id != $hm->wilayah_id) {
                    abort(403, 'Area penugasan berada di luar cakupan Wilayah HM.');
                }
            }
        }

        if (!empty($selectedAreaIds)) {
            foreach ($selectedAreaIds as $areaId) {
                $area = Wilayah::findOrFail($areaId);
                $existingActiveCs = $area->activeCsUser();
                if ($existingActiveCs && $existingActiveCs->id !== $cs->id) {
                    return redirect()->back()->with('error', "Area {$area->nama} sudah memiliki CS aktif ({$existingActiveCs->name}). Silakan nonaktifkan terlebih dahulu.");
                }
            }
        }

        return DB::transaction(function () use ($cs, $selectedAreaIds, $hmKotaId, $hm) {
            $primaryAreaId = !empty($selectedAreaIds) ? $selectedAreaIds[0] : ($cs->wilayah_id ?: $hm->wilayah_id);

            $cs->update([
                'wilayah_id' => $primaryAreaId,
            ]);

            // Deactivate areas under HM scope no longer selected
            $hmDescendantIds = $hmKotaId ? Wilayah::find($hmKotaId)->getDescendantIds() : Wilayah::pluck('id')->toArray();

            DB::table('user_wilayah')
                ->where('user_id', $cs->id)
                ->where('role', 'CS')
                ->whereIn('wilayah_id', $hmDescendantIds)
                ->whereNotIn('wilayah_id', $selectedAreaIds)
                ->where('is_active', true)
                ->update([
                    'is_active'      => false,
                    'deactivated_at' => now(),
                    'updated_at'     => now(),
                ]);

            // Activate selected areas
            foreach ($selectedAreaIds as $areaId) {
                DB::table('user_wilayah')->updateOrInsert(
                    ['user_id' => $cs->id, 'wilayah_id' => $areaId, 'role' => 'CS'],
                    ['is_active' => true, 'assigned_at' => now(), 'deactivated_at' => null, 'updated_at' => now()]
                );
            }

            return redirect()->route('hm.cs.index')
                ->with('success', "Penugasan wilayah CS {$cs->name} berhasil diperbarui!");
        });
    }

    /**
     * HM Activate / Deactivate CS user account.
     */
    public function updateStatus(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('assignCs', [$user]);

        if (strtolower($user->role) !== 'cs') {
            return redirect()->back()->with('error', "User {$user->name} bukan ber-role CS.");
        }

        $newStatus = $user->status === 'Aktif' || $user->status === 'aktif' ? 'Nonaktif' : 'Aktif';
        $user->update(['status' => $newStatus]);

        return redirect()->route('hm.cs.index')
            ->with('success', "Status CS {$user->name} berhasil diubah menjadi {$newStatus}.");
    }

    /**
     * HM Deactivate specific CS area assignment.
     */
    public function deactivateTerritory(Request $request, User $user, Wilayah $wilayah): RedirectResponse
    {
        Gate::authorize('assignCs', [$user, $wilayah]);

        DB::table('user_wilayah')
            ->where('user_id', $user->id)
            ->where('wilayah_id', $wilayah->id)
            ->where('role', 'CS')
            ->update([
                'is_active'      => false,
                'deactivated_at' => now(),
                'updated_at'     => now(),
            ]);

        return redirect()->back()->with('success', "Penugasan area {$wilayah->nama} untuk CS {$user->name} telah dinonaktifkan.");
    }
}
