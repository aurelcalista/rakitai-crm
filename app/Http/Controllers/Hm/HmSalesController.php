<?php

namespace App\Http\Controllers\Hm;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Wilayah;
use App\Models\Prospek;
use App\Models\Kunjungan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class HmSalesController extends Controller
{
    /**
     * Display Sales members list & territory assignment for HM.
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

        // 3. Fetch Sales users under HM scope
        $salesUsers = User::whereRaw('LOWER(role) = ?', ['sales'])
            ->where(function ($q) use ($user, $descendantWilayahIds) {
                if (!empty($descendantWilayahIds) && strtolower($user->role) !== 'admin') {
                    $q->whereIn('wilayah_id', $descendantWilayahIds)
                      ->orWhereHas('activeWilayahes', function ($wq) use ($descendantWilayahIds) {
                          $wq->whereIn('wilayah_id', $descendantWilayahIds);
                      })
                      ->orWhereNull('wilayah_id');
                }
            })
            ->with(['wilayah.parent', 'activeWilayahes.parent', 'supervisor'])
            ->orderBy('name')
            ->get();

        $salesData = $salesUsers->map(function ($sales) use ($hmKota, $hmKotaId, $user) {
            if (empty($sales->kode)) {
                $sales->kode = User::generateUserCode('Sales');
                $sales->save();
            }

            // Auto-assign sales without wilayah_id to HM's Wilayah scope
            $targetWilayahId = $hmKotaId ?? ($user->wilayah_id ?? null);
            if (empty($sales->wilayah_id) && $targetWilayahId) {
                $sales->wilayah_id = $targetWilayahId;
                $sales->save();
                $sales->load('wilayah.parent');

                DB::table('user_wilayah')->updateOrInsert(
                    ['user_id' => $sales->id, 'wilayah_id' => $targetWilayahId, 'role' => 'Sales'],
                    ['is_active' => true, 'assigned_at' => now(), 'deactivated_at' => null, 'updated_at' => now()]
                );
            }

            $prospectCount = Prospek::where('sales_id', $sales->id)->count();
            $closingCount  = Prospek::where('sales_id', $sales->id)->where('status', 'LUNAS')->count();
            $visitCount    = Kunjungan::where('sales_id', $sales->id)->count();

            $activeWilayahes = $sales->activeWilayahes;
            $wilayahNames = $activeWilayahes->pluck('nama')->toArray();

            if (empty($wilayahNames)) {
                if ($sales->wilayah) {
                    $wilayahNames[] = $sales->wilayah->nama;
                } elseif (!empty($sales->lokasi_penugasan)) {
                    $wilayahNames[] = 'Kota Lainnya (' . $sales->lokasi_penugasan . ')';
                }
            }

            $wilayahDisplay = !empty($wilayahNames)
                ? implode(', ', $wilayahNames)
                : 'Belum Ditugaskan Area';

            // Resolve Kota/Kabupaten name
            $kotaName = null;
            if ($sales->wilayah) {
                if ($sales->wilayah->parent) {
                    $kotaName = $sales->wilayah->parent->nama;
                } elseif ($sales->wilayah->level === 'Kota/Kabupaten') {
                    $kotaName = $sales->wilayah->nama;
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
                'id'               => $sales->id,
                'kode'             => $sales->kode,
                'name'             => $sales->name,
                'email'            => $sales->email,
                'phone'            => $sales->phone ?? '-',
                'role'             => 'Sales',
                'status'           => $sales->status,
                'spv_name'         => $sales->supervisor ? $sales->supervisor->name : '-',
                'wilayah'          => $wilayahDisplay,
                'active_wilayahs'  => $activeWilayahes,
                'wilayah_id'       => $sales->wilayah_id,
                'kota'             => $kotaName ?: '-',
                'prospects'        => $prospectCount,
                'closings'         => $closingCount,
                'visits'           => $visitCount,
                'last_login'       => $sales->last_login_at ? \Carbon\Carbon::parse($sales->last_login_at)->diffForHumans() : 'Belum Pernah',
            ];
        });

        return view('hm.sales.index', compact('salesData', 'myWilayah', 'kecamatanList', 'salesUsers'));
    }

    /**
     * HM Create a new Sales user.
     * Enforces:
     * - Role strictly 'Sales', Jabatan strictly 'Sales'
     * - Email domain strictly '@cic.ac.id'
     * - Auto-generated unique code (format YYMMSXXX)
     * - Multi-wilayah assignment within HM scope
     * - Backend authorization and scope check (403 on ID tampering)
     * - Global 1 active Sales per Wilayah rule
     */
    public function store(Request $request): RedirectResponse
    {
        $hm = auth()->user();

        Gate::authorize('createSales', User::class);

        // Auto-append @cic.ac.id if input is just username prefix or from email_username
        $rawEmail = trim((string) ($request->input('email_username') ?: $request->input('email')));
        if (!empty($rawEmail)) {
            if (!str_contains($rawEmail, '@')) {
                $rawEmail .= '@cic.ac.id';
            }
            $request->merge(['email' => strtolower($rawEmail)]);
        }

        $request->validate([
            'name'           => 'required|string|max:255',
            'email'          => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users',
                'regex:/^[a-zA-Z0-9._%+-]+@cic\.ac\.id$/i',
            ],
            'phone'          => 'nullable|string|max:20',
            'password'       => 'required|string|confirmed',
            'sales_area_ids'   => 'nullable|array',
            'sales_area_ids.*' => 'exists:wilayahs,id',
        ], [
            'email.regex' => 'Email Sales wajib menggunakan domain @cic.ac.id.',
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

            $selectedAreaIds = array_filter((array) ($request->sales_area_ids ?? []));

            // Validate HM Scope for each area ID (ID tampering check)
            if (!empty($selectedAreaIds) && $hmKotaId && strtolower($hm->role) !== 'admin') {
                foreach ($selectedAreaIds as $areaId) {
                    $area = Wilayah::findOrFail($areaId);
                    if (!$area->isDescendantOf($hmKotaId) && $area->id != $hmKotaId && $area->id != $hm->wilayah_id) {
                        abort(403, 'Area penugasan berada di luar cakupan Wilayah HM.');
                    }
                }
            }

            // Global 1 Active Sales per Wilayah Rule Check
            foreach ($selectedAreaIds as $areaId) {
                $existingActiveSales = DB::table('user_wilayah')
                    ->where('wilayah_id', $areaId)
                    ->where('role', 'Sales')
                    ->where('is_active', true)
                    ->first();

                if ($existingActiveSales) {
                    $area = Wilayah::find($areaId);
                    $areaName = $area ? $area->nama : "ID {$areaId}";
                    return redirect()->back()
                        ->withInput()
                        ->with('error', "Wilayah {$areaName} sudah memiliki Sales aktif. Tidak dapat menugaskan Sales lain ke wilayah ini.");
                }
            }

            $defaultWilayahId = $hmKotaId ?? ($hm->wilayah_id ?? null);
            $primaryAreaId = !empty($selectedAreaIds) ? $selectedAreaIds[0] : $defaultWilayahId;

            // Create Sales User
            $sales = User::create([
                'name'          => $request->name,
                'email'         => strtolower(trim($request->email)),
                'phone'         => $request->phone ?? '-',
                'password'      => Hash::make($request->password),
                'role'          => 'Sales',
                'jabatan'       => 'Sales',
                'status'        => 'Aktif',
                'kode'          => User::generateUserCode('Sales'),
                'supervisor_id' => null,
                'wilayah_id'    => $primaryAreaId,
            ]);

            // Assign Multi-Wilayah in user_wilayah
            if (!empty($selectedAreaIds)) {
                foreach ($selectedAreaIds as $areaId) {
                    DB::table('user_wilayah')->updateOrInsert(
                        ['user_id' => $sales->id, 'wilayah_id' => $areaId, 'role' => 'Sales'],
                        ['is_active' => true, 'assigned_at' => now(), 'deactivated_at' => null, 'updated_at' => now()]
                    );
                }
            } elseif ($primaryAreaId) {
                DB::table('user_wilayah')->updateOrInsert(
                    ['user_id' => $sales->id, 'wilayah_id' => $primaryAreaId, 'role' => 'Sales'],
                    ['is_active' => true, 'assigned_at' => now(), 'deactivated_at' => null, 'updated_at' => now()]
                );
            }

            return redirect()->route('hm.sales.index')
                ->with('success', "Akun Sales baru {$sales->name} ({$sales->kode}) berhasil dibuat dan ditugaskan!");
        });
    }

    /**
     * HM Assign / Update Sales Multi-Wilayah areas.
     */
    public function assignTerritory(Request $request): RedirectResponse
    {
        $hm = auth()->user();

        $request->validate([
            'sales_id'         => 'required|exists:users,id',
            'sales_area_ids'   => 'nullable|array',
            'sales_area_ids.*' => 'exists:wilayahs,id',
        ]);

        $sales = User::findOrFail($request->sales_id);

        if (strtolower($sales->role) !== 'sales') {
            return redirect()->back()->with('error', "User {$sales->name} bukan ber-role Sales.");
        }

        // Scope check
        if ($hm->wilayah_id && strtolower($hm->role) !== 'admin') {
            if ($sales->wilayah_id && !$sales->isWithinWilayahScope($hm->wilayah_id)) {
                abort(403, 'Sales yang dipilih berada di luar cakupan Wilayah HM.');
            }
        }

        $selectedAreaIds = array_filter((array) ($request->sales_area_ids ?? []));

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

        // Global 1 Active Sales per Wilayah Rule Check
        if (!empty($selectedAreaIds)) {
            foreach ($selectedAreaIds as $areaId) {
                $existingActiveSales = DB::table('user_wilayah')
                    ->where('wilayah_id', $areaId)
                    ->where('role', 'Sales')
                    ->where('is_active', true)
                    ->where('user_id', '!=', $sales->id)
                    ->first();

                if ($existingActiveSales) {
                    $area = Wilayah::find($areaId);
                    $areaName = $area ? $area->nama : "ID {$areaId}";
                    return redirect()->back()->with('error', "Wilayah {$areaName} sudah memiliki Sales aktif. Tidak dapat menugaskan Sales lain ke wilayah ini.");
                }
            }
        }

        return DB::transaction(function () use ($sales, $selectedAreaIds, $hmKotaId, $hm) {
            $primaryAreaId = !empty($selectedAreaIds) ? $selectedAreaIds[0] : ($sales->wilayah_id ?: $hm->wilayah_id);

            $sales->update([
                'wilayah_id' => $primaryAreaId,
            ]);

            // Deactivate areas under HM scope no longer selected
            $hmDescendantIds = $hmKotaId ? Wilayah::find($hmKotaId)->getDescendantIds() : Wilayah::pluck('id')->toArray();

            DB::table('user_wilayah')
                ->where('user_id', $sales->id)
                ->where('role', 'Sales')
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
                    ['user_id' => $sales->id, 'wilayah_id' => $areaId, 'role' => 'Sales'],
                    ['is_active' => true, 'assigned_at' => now(), 'deactivated_at' => null, 'updated_at' => now()]
                );
            }

            return redirect()->route('hm.sales.index')
                ->with('success', "Penugasan wilayah Sales {$sales->name} berhasil diperbarui!");
        });
    }

    /**
     * HM Activate / Deactivate Sales user account.
     */
    public function updateStatus(Request $request, User $user): RedirectResponse
    {
        $hm = auth()->user();

        if (strtolower($user->role) !== 'sales') {
            return redirect()->back()->with('error', "User {$user->name} bukan ber-role Sales.");
        }

        if ($hm->wilayah_id && strtolower($hm->role) !== 'admin') {
            if ($user->wilayah_id && !$user->isWithinWilayahScope($hm->wilayah_id)) {
                abort(403, 'Sales berada di luar cakupan Wilayah HM.');
            }
        }

        $newStatus = $user->status === 'Aktif' || $user->status === 'aktif' ? 'Nonaktif' : 'Aktif';
        $user->update(['status' => $newStatus]);

        return redirect()->route('hm.sales.index')
            ->with('success', "Status Sales {$user->name} berhasil diubah menjadi {$newStatus}.");
    }

    /**
     * HM Deactivate specific Sales area assignment.
     */
    public function deactivateTerritory(Request $request, User $user, Wilayah $wilayah): RedirectResponse
    {
        $hm = auth()->user();

        if (strtolower($user->role) !== 'sales') {
            return redirect()->back()->with('error', "User {$user->name} bukan ber-role Sales.");
        }

        if ($hm->wilayah_id && strtolower($hm->role) !== 'admin') {
            if (!$wilayah->isDescendantOf($hm->wilayah_id) && $wilayah->id != $hm->wilayah_id) {
                abort(403, 'Wilayah berada di luar cakupan HM.');
            }
        }

        DB::table('user_wilayah')
            ->where('user_id', $user->id)
            ->where('wilayah_id', $wilayah->id)
            ->where('role', 'Sales')
            ->update([
                'is_active'      => false,
                'deactivated_at' => now(),
                'updated_at'     => now(),
            ]);

        return redirect()->back()->with('success', "Penugasan area {$wilayah->nama} untuk Sales {$user->name} telah dinonaktifkan.");
    }
}
