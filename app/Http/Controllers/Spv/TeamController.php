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

            return [
                'id'            => $member->id,
                'name'          => $member->name,
                'email'         => $member->email,
                'phone'         => $member->phone ?? '-',
                'role'          => $member->role,
                'status'        => $member->status,
                'is_cs'         => $member->role === 'CS',
                'wilayah'       => $member->role === 'CS' ? 'Centralized (Tanpa Wilayah)' : ($member->wilayah ? $member->wilayah->nama : ($member->lokasi_penugasan ? 'Kota Lainnya (' . $member->lokasi_penugasan . ')' : 'Belum Ditugaskan')),
                'wilayah_id'    => $member->wilayah_id,
                'lokasi_penugasan' => $member->lokasi_penugasan,
                'is_other_city' => !empty($member->lokasi_penugasan) && empty($member->wilayah_id),
                'kota'          => $member->wilayah && $member->wilayah->parent ? $member->wilayah->parent->nama : ($member->lokasi_penugasan ?: '-'),
                'prospects'     => $prospectCount,
                'closings'      => $closingCount,
                'visits'        => $visitCount,
                'last_login'    => $member->last_login_at ? \Carbon\Carbon::parse($member->last_login_at)->diffForHumans() : 'Belum Pernah',
            ];
        });

        // Candidates: Sales or CS in SPV's descendant Wilayah scope
        $mainWilayah = $user->wilayah_id ? Wilayah::find($user->wilayah_id) : null;
        $descendantWilayahIds = $mainWilayah ? $mainWilayah->getDescendantIds() : Wilayah::pluck('id')->toArray();

        $candidates = User::whereIn('role', ['Sales', 'CS'])
            ->where(function($q) use ($descendantWilayahIds) {
                $q->whereIn('wilayah_id', $descendantWilayahIds)->orWhereNull('wilayah_id');
            })
            ->get();

        $availableAreas = $mainWilayah ? Wilayah::where('parent_id', $mainWilayah->id)->orWhere('id', $mainWilayah->id)->get() : Wilayah::all();
        $myWilayah = $user->wilayah ? $user->wilayah->nama : 'Semua Wilayah';

        // Kecamatan dalam cakupan Kota SPV untuk dropdown assignment
        $kecamatanList = collect();
        if ($user->wilayah_id) {
            $spvKota = Wilayah::find($user->wilayah_id);
            if ($spvKota) {
                // Jika SPV wilayah = Kota, ambil semua Kecamatan di bawahnya
                if ($spvKota->level === 'Kota/Kabupaten') {
                    $kecamatanList = Wilayah::where('parent_id', $spvKota->id)
                        ->where('level', 'Kecamatan')
                        ->where('status', 'Aktif')
                        ->get();
                }
            }
        }
        // Fallback: ambil semua kecamatan jika SPV belum punya wilayah
        if ($kecamatanList->isEmpty()) {
            $kecamatanList = Wilayah::where('level', 'Kecamatan')->where('status', 'Aktif')->get();
        }

        return view('spv.tim.index', compact('teamData', 'candidates', 'availableAreas', 'myWilayah', 'kecamatanList'));
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

        return redirect()->back()->with('success', "Anggota tim {$candidate->name} ({$candidate->role}) berhasil ditambahkan ke tim SPV!");
    }

    /**
     * Assign Kecamatan (wilayah) ke Sales oleh SPV.
     * CS tidak boleh mendapat wilayah — ditolak.
     * Mendukung opsi "Di Kota Lainnya" (P0 Bab 3 & Bab 9).
     */
    public function assignWilayah(Request $request, User $user): RedirectResponse
    {
        $spv = auth()->user();

        // Pastikan user ini adalah subordinate dari SPV yang login
        if (!$spv->isSupervisorOf($user) && !in_array($user->id, $spv->teamMemberIds())) {
            abort(403, 'Anda tidak berwenang mengatur anggota tim ini.');
        }

        // CS tidak boleh mendapat wilayah
        if ($user->role === 'CS') {
            return redirect()->route('spv.tim.index')
                ->with('error', 'CS bekerja secara Centralized dan tidak memiliki wilayah kecamatan.');
        }

        // Opsi "Di Kota Lainnya" (P0 Bab 3 & Bab 9)
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

        // Pastikan wilayah yang di-assign adalah Kecamatan
        if ($wilayah->level !== 'Kecamatan') {
            return redirect()->route('spv.tim.index')
                ->with('error', 'SPV hanya dapat menugaskan Kecamatan ke Sales, bukan Kota/Kabupaten.');
        }

        $user->update([
            'wilayah_id'       => $wilayah->id,
            'lokasi_penugasan' => null,
        ]);

        return redirect()->route('spv.tim.index')
            ->with('success', "Wilayah {$user->name} berhasil diperbarui ke {$wilayah->nama}.");
    }
}

