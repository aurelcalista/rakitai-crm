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
     * Display SPV Team members list & assignments.
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
            })->whereIn('status', ['LUNAS', 'Closing', '07 LUNAS'])->count();
            $visitCount = Kunjungan::where('sales_id', $member->id)->count();

            return [
                'id'            => $member->id,
                'name'          => $member->name,
                'email'         => $member->email,
                'phone'         => $member->phone ?? '-',
                'role'          => $member->role,
                'status'        => $member->status,
                'is_cs'         => $member->role === 'CS',
                'wilayah'       => $member->role === 'CS' ? 'Centralized (Tanpa Wilayah)' : ($member->wilayah ? $member->wilayah->nama : 'Belum Ditugaskan'),
                'wilayah_id'    => $member->wilayah_id,
                'kota'          => $member->wilayah && $member->wilayah->parent ? $member->wilayah->parent->nama : '-',
                'prospects'     => $prospectCount,
                'closings'      => $closingCount,
                'visits'        => $visitCount,
                'last_login'    => $member->last_login_at ? \Carbon\Carbon::parse($member->last_login_at)->diffForHumans() : 'Belum Pernah',
            ];
        });

        // Kecamatan dalam cakupan Kota SPV untuk dropdown assignment
        $spv = auth()->user();
        $kecamatanList = collect();
        if ($spv->wilayah_id) {
            $spvKota = Wilayah::find($spv->wilayah_id);
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

        $myWilayah = $spv->wilayah ? $spv->wilayah->nama : 'Semua Wilayah';

        return view('spv.tim.index', compact('teamData', 'myWilayah', 'kecamatanList'));
    }

    /**
     * Assign Kecamatan (wilayah) ke Sales oleh SPV.
     * CS tidak boleh mendapat wilayah — ditolak.
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

        $request->validate([
            'wilayah_id' => 'required|exists:wilayahs,id',
        ]);

        $wilayah = Wilayah::findOrFail($request->wilayah_id);

        // Pastikan wilayah yang di-assign adalah Kecamatan
        if ($wilayah->level !== 'Kecamatan') {
            return redirect()->route('spv.tim.index')
                ->with('error', 'SPV hanya dapat menugaskan Kecamatan ke Sales, bukan Kota/Kabupaten.');
        }

        $user->update(['wilayah_id' => $wilayah->id]);

        return redirect()->route('spv.tim.index')
            ->with('success', "Wilayah {$user->name} berhasil diperbarui ke {$wilayah->nama}.");
    }
}

