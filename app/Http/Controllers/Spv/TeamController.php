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

        $myWilayah = $user->wilayah ? $user->wilayah->nama : 'Semua Wilayah';

        return view('spv.tim.index', compact('teamData', 'myWilayah'));
    }
}
