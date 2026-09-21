<?php

namespace App\Http\Controllers\Spv;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SalesTargetService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PerformanceController extends Controller
{
    public function __construct(private SalesTargetService $targetService)
    {
    }

    /**
     * Target & Performa Tim SPV.
     */
    public function index(Request $request): View
    {
        $user = auth()->user();
        $teamMemberIds = $user->teamMemberIds();

        $salesUsers = User::whereIn('id', $teamMemberIds)->where('role', 'Sales')->get();
        if ($salesUsers->isEmpty()) {
            $salesUsers = User::where('role', 'Sales')->get();
        }

        $team = [];
        $totalTarget = 0;
        $totalRealisasi = 0;

        foreach ($salesUsers as $s) {
            $stats = $this->targetService->getStats($s);
            $targetBulanan = 50;

            $achievement = $targetBulanan > 0 ? round(($stats['realisasi_closing'] / $targetBulanan) * 100) : 0;

            $team[] = [
                'name'        => $s->name,
                'role'        => $s->role,
                'target'      => $targetBulanan,
                'prospects'   => $stats['total_prospek'],
                'follow_up'   => $stats['follow_up'],
                'closing'     => $stats['realisasi_closing'],
                'lost'        => $stats['lost'],
                'achievement' => $achievement,
                'avatar'      => strtoupper(substr($s->name, 0, 2)),
                'status'      => $stats['realisasi_closing'] >= $targetBulanan ? 'Target Achieved' : 'On Progress',
            ];

            $totalTarget += $targetBulanan;
            $totalRealisasi += $stats['realisasi_closing'];
        }

        $summary = [
            'target'      => $totalTarget,
            'realisasi'   => $totalRealisasi,
            'achievement' => $totalTarget > 0 ? round(($totalRealisasi / $totalTarget) * 100) : 0,
            'sisa_target' => max(0, $totalTarget - $totalRealisasi),
        ];

        return view('spv.performa.index', compact('team', 'summary'));
    }
}
