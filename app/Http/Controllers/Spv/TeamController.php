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
            ->with(['wilayah.parent', 'activeWilayahes.parent'])
            ->get();

        $teamData = $subordinates->map(function ($member) use ($user) {
            if (empty($member->kode)) {
                $member->kode = User::generateUserCode($member->role ?? 'Sales');
                $member->save();
            }

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

            // Find parent Kota/Kabupaten name
            $kotaName = null;
            if ($member->wilayah) {
                if ($member->wilayah->parent) {
                    $kotaName = $member->wilayah->parent->nama;
                } elseif ($member->wilayah->level === 'Kota/Kabupaten') {
                    $kotaName = $member->wilayah->nama;
                }
            }

            if (!$kotaName && $activeWilayahes->isNotEmpty()) {
                $firstWithParent = $activeWilayahes->first(fn($w) => $w->parent !== null);
                if ($firstWithParent && $firstWithParent->parent) {
                    $kotaName = $firstWithParent->parent->nama;
                }
            }

            if (!$kotaName && $user->wilayah) {
                $spvW = $user->wilayah;
                $kotaName = $spvW->level === 'Kota/Kabupaten' ? $spvW->nama : ($spvW->parent ? $spvW->parent->nama : null);
            }

            if (!$kotaName) {
                $kotaName = $member->lokasi_penugasan ?: '-';
            }

            return [
                'id'               => $member->id,
                'kode'             => $member->kode,
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
                'kota'             => $kotaName,
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
                ->get()
                ->map(function ($kec) {
                    $activeSalesPivot = \Illuminate\Support\Facades\DB::table('user_wilayah')
                        ->where('wilayah_id', $kec->id)
                        ->where('role', 'Sales')
                        ->where('is_active', true)
                        ->first();

                    $kec->has_active_sales = !empty($activeSalesPivot);
                    if ($activeSalesPivot) {
                        $sUser = User::find($activeSalesPivot->user_id);
                        $kec->active_sales_name = $sUser ? $sUser->name : 'Sales lain';
                    }
                    return $kec;
                });
        }

        $descendantWilayahIds = $spvKota ? $spvKota->getDescendantIds() : [];
        $spvTeamMemberIds = $user->teamMemberIds();

        // 3. Sales Candidates filtered strictly within SPV Kota scope
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

        $candidates = $salesCandidates;
        $csCandidates = collect();

        $availableAreas = $kecamatanList;

        return view('spv.tim.index', compact('teamData', 'candidates', 'salesCandidates', 'csCandidates', 'availableAreas', 'myWilayah', 'kecamatanList'));
    }

    /**
     * SPV Select/Assign a Sales user into their team.
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

        if (strtolower($candidate->role) !== 'sales') {
            abort(403, 'SPV hanya berwenang mengelola Sales.');
        }

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

            \Illuminate\Support\Facades\DB::table('user_wilayah')->updateOrInsert(
                ['user_id' => $candidate->id, 'wilayah_id' => $areaId, 'role' => 'Sales'],
                ['is_active' => true, 'assigned_at' => now(), 'deactivated_at' => null, 'updated_at' => now()]
            );
        }

        return redirect()->back()->with('success', "Anggota tim Sales {$candidate->name} berhasil ditambahkan ke tim SPV!");
    }

    /**
     * SPV Form: Assign Sales to an Area/Kecamatan under SPV scope.
     * Enforces unique 1 active Sales per area rule. CS assignment is forbidden (403).
     */
    public function assignTeamTerritory(Request $request): RedirectResponse
    {
        $spv = auth()->user();

        if ($request->filled('cs_id') || $request->has('cs_area_ids')) {
            abort(403, 'SPV hanya berwenang menugaskan wilayah Sales. Penugasan CS dikelola oleh HM.');
        }

        $request->validate([
            'sales_id'      => 'required|exists:users,id',
            'sales_area_id' => 'nullable|exists:wilayahs,id',
            'area_id'       => 'nullable|exists:wilayahs,id',
        ]);

        return \Illuminate\Support\Facades\DB::transaction(function() use ($request, $spv) {
            $spvWilayahId = $spv->wilayah_id;
            $salesAreaId = $request->sales_area_id ?? $request->area_id;

            $sales = User::findOrFail($request->sales_id);

            if (strtolower($sales->role) !== 'sales') {
                abort(403, 'SPV hanya berwenang menugaskan Sales.');
            }

            $salesInScope = !$spvWilayahId 
                || !$sales->wilayah_id
                || $sales->isWithinWilayahScope($spvWilayahId) 
                || $spv->isSupervisorOf($sales) 
                || in_array($sales->id, $spv->teamMemberIds());

            if (!$salesInScope) {
                abort(403, 'Sales yang dipilih berada di luar cakupan Wilayah SPV.');
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

            return redirect()->route('spv.tim.index')
                ->with('success', "Penugasan wilayah Sales {$sales->name} berhasil diperbarui!");
        });
    }

    /**
     * Assign Kecamatan (wilayah) ke Sales oleh SPV.
     */
    public function assignWilayah(Request $request, User $user): RedirectResponse
    {
        $spv = auth()->user();

        if (strtolower($user->role) !== 'sales') {
            abort(403, 'SPV hanya berwenang mengelola Sales.');
        }

        if (!$spv->isSupervisorOf($user) && !in_array($user->id, $spv->teamMemberIds())) {
            abort(403, 'Anda tidak berwenang mengatur anggota tim ini.');
        }

        if ($request->boolean('is_other_city')) {
            abort(403, 'Kota Lainnya tidak dapat dijadikan wilayah operasional Sales/Field Team.');
        }

        $request->validate([
            'wilayah_id' => 'required|exists:wilayahs,id',
        ]);

        $wilayah = Wilayah::findOrFail($request->wilayah_id);

        if (str_contains(strtolower($wilayah->nama), 'lainnya') || $wilayah->kode === 'W-LAIN') {
            abort(403, 'Kota Lainnya tidak dapat dijadikan wilayah operasional.');
        }

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

        if (strtolower($user->role) !== 'sales') {
            abort(403, 'SPV hanya berwenang mengelola Sales.');
        }

        if (!$spv->isSupervisorOf($user) && !in_array($user->id, $spv->teamMemberIds()) && strtolower($spv->role) !== 'admin') {
            abort(403, 'Anda tidak berwenang mengelola wilayah user ini.');
        }

        \Illuminate\Support\Facades\DB::table('user_wilayah')
            ->where('user_id', $user->id)
            ->where('wilayah_id', $wilayah->id)
            ->where('role', 'Sales')
            ->update([
                'is_active'      => false,
                'deactivated_at' => now(),
                'updated_at'     => now(),
            ]);

        return redirect()->back()->with('success', "Penugasan wilayah {$wilayah->nama} untuk Sales {$user->name} telah dinonaktifkan.");
    }

    /**
     * SPV Create new Sales user under SPV scope.
     * Enforces:
     * - Role strictly 'Sales', Jabatan strictly 'Sales'
     * - Email domain strictly '@cic.ac.id'
     * - Auto-generated unique code (YYMM-XXX, sequence resets yearly)
     * - Multi-wilayah checkbox assignment within SPV Kota/Kabupaten scope
     * - Strict SPV Scope backend check (ID tampering -> 403)
     * - Max 1 active Sales per area constraint
     * - Wrapped in DB transaction
     */
    public function storeSales(Request $request): RedirectResponse
    {
        $spv = auth()->user();

        \Illuminate\Support\Facades\Gate::authorize('createSales', User::class);

        // Auto-append @cic.ac.id if input is just username prefix or from email_username
        $rawEmail = trim((string) ($request->input('email_username') ?: $request->input('email')));
        if (!empty($rawEmail)) {
            if (!str_contains($rawEmail, '@')) {
                $rawEmail .= '@cic.ac.id';
            }
            $request->merge(['email' => strtolower($rawEmail)]);
        }

        $request->validate([
            'name'       => 'required|string|max:255',
            'email'      => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users',
                'regex:/^[a-zA-Z0-9._%+-]+@cic\.ac\.id$/i',
            ],
            'phone'      => 'nullable|string|max:20',
            'password'   => 'nullable|string',
            'area_id'    => 'nullable|exists:wilayahs,id',
            'area_ids'   => 'nullable|array',
            'area_ids.*' => 'exists:wilayahs,id',
        ], [
            'email.regex' => 'Email Sales wajib menggunakan domain @cic.ac.id.',
        ]);

        return \Illuminate\Support\Facades\DB::transaction(function() use ($request, $spv) {
            // Resolve SPV Kota ID
            $mainWilayah = $spv->wilayah_id ? Wilayah::find($spv->wilayah_id) : null;
            $spvKotaId = null;
            if ($mainWilayah) {
                if ($mainWilayah->level === 'Kota/Kabupaten') {
                    $spvKotaId = $mainWilayah->id;
                } elseif ($mainWilayah->parent_id) {
                    $spvKotaId = $mainWilayah->parent_id;
                }
            }

            // Accept area_id (single area) or area_ids array
            $selectedAreaIds = [];
            if ($request->filled('area_id')) {
                $selectedAreaIds[] = (int) $request->area_id;
            } elseif ($request->filled('area_ids')) {
                $selectedAreaIds = array_filter((array) $request->area_ids);
            }

            // Validate SPV Scope for each area ID (ID tampering check & Kota Lainnya rejection)
            if (!empty($selectedAreaIds)) {
                foreach ($selectedAreaIds as $areaId) {
                    $area = Wilayah::findOrFail($areaId);
                    if (str_contains(strtolower($area->nama), 'lainnya') || $area->kode === 'W-LAIN') {
                        abort(403, 'Kota Lainnya tidak dapat dijadikan wilayah operasional.');
                    }
                    if ($spvKotaId && !$area->isDescendantOf($spvKotaId) && $area->id != $spvKotaId && $area->id != $spv->wilayah_id) {
                        abort(403, 'Area penugasan berada di luar cakupan Wilayah SPV.');
                    }
                }
            }

            // Validate 1 Area = Max 1 Active Sales
            foreach ($selectedAreaIds as $areaId) {
                $existingActiveSales = \Illuminate\Support\Facades\DB::table('user_wilayah')
                    ->where('wilayah_id', $areaId)
                    ->where('role', 'Sales')
                    ->where('is_active', true)
                    ->first();

                if ($existingActiveSales) {
                    $area = Wilayah::find($areaId);
                    $areaName = $area ? $area->nama : "ID {$areaId}";
                    return redirect()->back()
                        ->withInput()
                        ->with('error', "Area {$areaName} sudah memiliki Sales aktif. Silakan nonaktifkan atau ubah assignment sebelumnya.");
                }
            }

            $primaryAreaId = !empty($selectedAreaIds) ? $selectedAreaIds[0] : ($spv->wilayah_id ?? null);
            $rawPassword = $request->filled('password') ? $request->input('password') : '123';

            // Create Sales User
            $sales = User::create([
                'name'          => $request->name,
                'email'         => strtolower(trim($request->email)),
                'phone'         => $request->input('phone', '-'),
                'password'      => \Illuminate\Support\Facades\Hash::make($rawPassword),
                'role'          => 'Sales',
                'jabatan'       => 'Sales',
                'status'        => 'Aktif',
                'kode'          => User::generateUserCode('Sales'),
                'supervisor_id' => $spv->id,
                'wilayah_id'    => $primaryAreaId,
            ]);

            // Assign Multi-Wilayah
            foreach ($selectedAreaIds as $areaId) {
                \Illuminate\Support\Facades\DB::table('user_wilayah')->updateOrInsert(
                    ['user_id' => $sales->id, 'wilayah_id' => $areaId, 'role' => 'Sales'],
                    ['is_active' => true, 'assigned_at' => now(), 'deactivated_at' => null, 'updated_at' => now()]
                );
            }

            return redirect()->route('spv.tim.index')
                ->with('success', "Sales baru {$sales->name} ({$sales->kode}) berhasil dibuat dengan password awal '123'!");
        });
    }
}

