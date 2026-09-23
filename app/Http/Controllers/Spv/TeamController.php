<?php

namespace App\Http\Controllers\Spv;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Wilayah;
use App\Models\Prospek;
use App\Models\Kunjungan;
use Illuminate\Http\Request;
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
                'wilayah'       => $member->wilayah ? $member->wilayah->nama : 'Belum Ditugaskan',
                'kota'          => $member->wilayah && $member->wilayah->parent ? $member->wilayah->parent->nama : '-',
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

        return view('spv.tim.index', compact('teamData', 'candidates', 'availableAreas', 'myWilayah'));
    }

    /**
     * SPV Select/Assign a Sales or CS user into their team.
     * Enforces strict backend descendant scope validation. ID tampering returns 403.
     */
    public function assignMember(\Illuminate\Http\Request $request): \Illuminate\Http\RedirectResponse
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
}
