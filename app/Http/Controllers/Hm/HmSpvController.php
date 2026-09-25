<?php

namespace App\Http\Controllers\Hm;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Wilayah;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class HmSpvController extends Controller
{
    /**
     * Display SPV list & territory assignment for HM.
     */
    public function index(): View
    {
        $user = auth()->user();

        // 1. Resolve HM's Kota/Kabupaten scope
        $activeWilayahIds = $user->activeWilayahIds();
        $hmKotas = Wilayah::whereIn('id', $activeWilayahIds)->get();
        
        $myWilayah = $hmKotas->isNotEmpty() ? $hmKotas->pluck('nama')->join(', ') : 'Semua Wilayah (Global HM/Admin)';
        
        $descendantWilayahIds = [];
        if ($hmKotas->isNotEmpty()) {
            foreach ($hmKotas as $kota) {
                $descendantWilayahIds = array_merge($descendantWilayahIds, $kota->getDescendantIds());
            }
            $descendantWilayahIds = array_unique($descendantWilayahIds);
        } else {
            $descendantWilayahIds = Wilayah::pluck('id')->toArray();
        }

        // 2. Fetch active Kecamatan list under HM scope
        $kecamatanList = collect();
        if ($hmKotas->isNotEmpty()) {
            $hmKotaIds = $hmKotas->pluck('id')->toArray();
            $kecamatanList = Wilayah::whereIn('parent_id', $hmKotaIds)
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

        // 3. Fetch SPV users under HM scope
        $spvUsers = User::whereRaw('LOWER(role) = ?', ['spv'])
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

        $spvData = $spvUsers->map(function ($spv) use ($hmKotas) {
            if (empty($spv->kode)) {
                $spv->kode = User::generateUserCode('SPV');
                $spv->save();
            }

            // Auto-assign SPV without wilayah_id to HM's Wilayah scope
            $targetWilayahId = $hmKotas->first()?->id ?? null;
            if (empty($spv->wilayah_id) && $targetWilayahId) {
                $spv->wilayah_id = $targetWilayahId;
                $spv->save();
                $spv->load('wilayah.parent');

                DB::table('user_wilayah')->updateOrInsert(
                    ['user_id' => $spv->id, 'wilayah_id' => $targetWilayahId, 'role' => 'SPV'],
                    ['is_active' => true, 'assigned_at' => now(), 'deactivated_at' => null, 'updated_at' => now()]
                );
            }

            $activeWilayahes = $spv->activeWilayahes;
            $wilayahNames = $activeWilayahes->pluck('nama')->toArray();

            if (empty($wilayahNames)) {
                if ($spv->wilayah) {
                    $wilayahNames[] = $spv->wilayah->nama . ($spv->wilayah->parent ? ', ' . $spv->wilayah->parent->nama : '');
                } else {
                    $wilayahNames[] = 'Belum Ditugaskan';
                }
            }

            return [
                'id'            => $spv->id,
                'kode'          => $spv->kode,
                'name'          => $spv->name,
                'email'         => $spv->email,
                'phone'         => $spv->phone ?? '-',
                'status'        => $spv->status,
                'wilayah_names' => implode(' | ', $wilayahNames),
                'active_areas'  => $activeWilayahes->pluck('id')->toArray(),
                'primary_area'  => $spv->wilayah_id,
                'last_login'    => $spv->last_login_at ? \Carbon\Carbon::parse($spv->last_login_at)->diffForHumans() : 'Belum pernah',
            ];
        });

        return view('hm.spv.index', compact('spvData', 'spvUsers', 'myWilayah', 'kecamatanList'));
    }

    /**
     * HM Create new SPV user account.
     */
    public function store(Request $request): RedirectResponse
    {
        $hm = auth()->user();

        $rawEmail = trim((string) ($request->input('email_username') ?: $request->input('email')));
        if (!empty($rawEmail)) {
            if (!str_contains($rawEmail, '@')) {
                $rawEmail .= '@cic.ac.id';
            }
            $request->merge(['email' => strtolower($rawEmail)]);
        }

        $request->validate([
            'name'             => 'required|string|max:255',
            'email'            => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users',
                'regex:/^[a-zA-Z0-9._%+-]+@cic\.ac\.id$/i',
            ],
            'phone'            => 'nullable|string|max:20',
            'password'         => 'required|string|min:6|confirmed',
            'spv_area_ids'     => 'nullable|array',
            'spv_area_ids.*'   => 'exists:wilayahs,id',
        ], [
            'email.regex' => 'Email SPV wajib menggunakan domain @cic.ac.id.',
        ]);

        $selectedAreaIds = array_filter((array) ($request->spv_area_ids ?? []));
        $primaryAreaId = !empty($selectedAreaIds) ? $selectedAreaIds[0] : $hm->wilayah_id;

        // Validation for Area Scope
        $activeWilayahIds = $hm->activeWilayahIds();
        $hmKotaIds = Wilayah::whereIn('id', $activeWilayahIds)->pluck('id')->toArray();
        if (!empty($selectedAreaIds) && !empty($hmKotaIds) && strtolower($hm->role) !== 'admin') {
            foreach ($selectedAreaIds as $areaId) {
                $area = Wilayah::findOrFail($areaId);
                $isWithin = false;
                foreach ($hmKotaIds as $hmKotaId) {
                    if ($area->isDescendantOf($hmKotaId) || $area->id == $hmKotaId) {
                        $isWithin = true;
                        break;
                    }
                }
                if (!$isWithin) {
                    abort(403, 'Area penugasan SPV berada di luar cakupan Wilayah Anda.');
                }
            }
        }

        return DB::transaction(function () use ($request, $primaryAreaId, $selectedAreaIds) {
            // Create SPV User
            $spv = User::create([
                'name'          => $request->name,
                'email'         => strtolower(trim($request->email)),
                'phone'         => $request->phone ?? '-',
                'password'      => Hash::make($request->password),
                'role'          => 'SPV',
                'jabatan'       => 'SPV',
                'status'        => 'Aktif',
                'kode'          => User::generateUserCode('SPV'),
                'supervisor_id' => null,
                'wilayah_id'    => $primaryAreaId,
            ]);

            // Assign Multi-Wilayah in user_wilayah
            if (!empty($selectedAreaIds)) {
                foreach ($selectedAreaIds as $areaId) {
                    DB::table('user_wilayah')->updateOrInsert(
                        ['user_id' => $spv->id, 'wilayah_id' => $areaId, 'role' => 'SPV'],
                        ['is_active' => true, 'assigned_at' => now(), 'deactivated_at' => null, 'updated_at' => now()]
                    );
                }
            } elseif ($primaryAreaId) {
                DB::table('user_wilayah')->updateOrInsert(
                    ['user_id' => $spv->id, 'wilayah_id' => $primaryAreaId, 'role' => 'SPV'],
                    ['is_active' => true, 'assigned_at' => now(), 'deactivated_at' => null, 'updated_at' => now()]
                );
            }

            return redirect()->route('hm.spv.index')
                ->with('success', "Akun SPV baru {$spv->name} ({$spv->kode}) berhasil dibuat dan ditugaskan!");
        });
    }

    /**
     * HM Assign / Update SPV Multi-Wilayah areas.
     */
    public function assignTerritory(Request $request): RedirectResponse
    {
        $hm = auth()->user();

        $request->validate([
            'spv_id'         => 'required|exists:users,id',
            'spv_area_ids'   => 'nullable|array',
            'spv_area_ids.*' => 'exists:wilayahs,id',
        ]);

        $spv = User::findOrFail($request->spv_id);

        if (strtolower($spv->role) !== 'spv') {
            return redirect()->back()->with('error', "User {$spv->name} bukan ber-role SPV.");
        }

        // Scope check
        if ($hm->wilayah_id && strtolower($hm->role) !== 'admin') {
            if ($spv->wilayah_id && !$spv->isWithinWilayahScope($hm->wilayah_id)) {
                abort(403, 'SPV yang dipilih berada di luar cakupan Wilayah HM.');
            }
        }

        $selectedAreaIds = array_filter((array) ($request->spv_area_ids ?? []));

        // Resolve HM Scope
        $activeWilayahIds = $hm->activeWilayahIds();
        $hmKotaIds = Wilayah::whereIn('id', $activeWilayahIds)->pluck('id')->toArray();

        if (!empty($selectedAreaIds) && !empty($hmKotaIds) && strtolower($hm->role) !== 'admin') {
            foreach ($selectedAreaIds as $areaId) {
                $area = Wilayah::findOrFail($areaId);
                $isWithin = false;
                foreach ($hmKotaIds as $hmKotaId) {
                    if ($area->isDescendantOf($hmKotaId) || $area->id == $hmKotaId) {
                        $isWithin = true;
                        break;
                    }
                }
                if (!$isWithin) {
                    abort(403, 'Area penugasan berada di luar cakupan Wilayah HM.');
                }
            }
        }

        return DB::transaction(function () use ($spv, $selectedAreaIds, $hmKotaIds, $hm) {
            $primaryAreaId = !empty($selectedAreaIds) ? $selectedAreaIds[0] : ($spv->wilayah_id ?: $hm->wilayah_id);

            $spv->update([
                'wilayah_id' => $primaryAreaId,
            ]);

            // Deactivate areas under HM scope no longer selected
            $hmDescendantIds = [];
            foreach ($hmKotaIds as $hmKotaId) {
                $hmDescendantIds = array_merge($hmDescendantIds, Wilayah::find($hmKotaId)->getDescendantIds());
            }
            if (empty($hmDescendantIds)) {
                $hmDescendantIds = Wilayah::pluck('id')->toArray();
            }

            DB::table('user_wilayah')
                ->where('user_id', $spv->id)
                ->where('role', 'SPV')
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
                    ['user_id' => $spv->id, 'wilayah_id' => $areaId, 'role' => 'SPV'],
                    ['is_active' => true, 'assigned_at' => now(), 'deactivated_at' => null, 'updated_at' => now()]
                );
            }

            return redirect()->route('hm.spv.index')
                ->with('success', "Penugasan wilayah SPV {$spv->name} berhasil diperbarui!");
        });
    }

    /**
     * HM Activate / Deactivate SPV user account.
     */
    public function updateStatus(Request $request, User $user): RedirectResponse
    {
        $hm = auth()->user();

        if (strtolower($user->role) !== 'spv') {
            return redirect()->back()->with('error', "User {$user->name} bukan ber-role SPV.");
        }

        if ($hm->wilayah_id && strtolower($hm->role) !== 'admin') {
            if ($user->wilayah_id && !$user->isWithinWilayahScope($hm->wilayah_id)) {
                abort(403, 'SPV berada di luar cakupan Wilayah HM.');
            }
        }

        $newStatus = $user->status === 'Aktif' || $user->status === 'aktif' ? 'Nonaktif' : 'Aktif';
        $user->update(['status' => $newStatus]);

        return redirect()->route('hm.spv.index')
            ->with('success', "Status SPV {$user->name} berhasil diubah menjadi {$newStatus}.");
    }

    /**
     * HM Deactivate specific SPV area assignment.
     */
    public function deactivateTerritory(Request $request, User $user, Wilayah $wilayah): RedirectResponse
    {
        $hm = auth()->user();

        if (strtolower($user->role) !== 'spv') {
            return redirect()->back()->with('error', "User {$user->name} bukan ber-role SPV.");
        }

        if ($hm->wilayah_id && strtolower($hm->role) !== 'admin') {
            if (!$wilayah->isDescendantOf($hm->wilayah_id) && $wilayah->id != $hm->wilayah_id) {
                abort(403, 'Wilayah berada di luar cakupan HM.');
            }
        }

        DB::table('user_wilayah')
            ->where('user_id', $user->id)
            ->where('wilayah_id', $wilayah->id)
            ->where('role', 'SPV')
            ->update([
                'is_active'      => false,
                'deactivated_at' => now(),
                'updated_at'     => now(),
            ]);

        return redirect()->back()->with('success', "Penugasan area {$wilayah->nama} untuk SPV {$user->name} telah dinonaktifkan.");
    }
}
