<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Prospek;
use App\Services\SalesTargetService;
use App\Services\TargetAchievementService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PerformanceController extends Controller
{
    public function __construct(
        private SalesTargetService $targetService,
        private TargetAchievementService $targetAchievementService
    ) {
    }

    /**
     * Halaman Target & Performa untuk Sales.
     */
    public function index(Request $request): View
    {
        $user = auth()->user();

        // Ambil list sales dalam tim
        // Dalam konteks Sales, mungkin hanya bisa melihat dirinya sendiri atau timnya.
        // Asumsi: Semua sales ditampilkan untuk leaderboard, atau hanya data pribadi + agregat tim.
        // Kita tampilkan semua sales untuk leaderboard seperti di mockup.
        
        $salesUsers = \App\Models\User::where('role', 'Sales')->get();
        $team = [];

        $totalTarget = 0;
        $totalRealisasi = 0;

        foreach ($salesUsers as $s) {
            $stats = $this->targetService->getStats($s);
            $activeTarget = $this->targetService->getActiveTarget($s);
            // Use target_lunas as the closing target metric (target_closing does not exist in DB)
            $targetLunas = $activeTarget ? (int)$activeTarget->target_lunas : 0;
            if ($targetLunas <= 0 && $activeTarget) {
                $targetLunas = (int)$activeTarget->target_kontak;
            }

            $achievement = $targetLunas > 0 ? min(100, round(($stats['realisasi_closing'] / $targetLunas) * 100)) : 0;

            $team[] = [
                'name'        => $s->name,
                'role'        => $s->role,
                'target'      => $targetLunas,
                'prospects'   => $stats['total_prospek'],
                'follow_up'   => $stats['follow_up'],
                'closing'     => $stats['realisasi_closing'],
                'lost'        => $stats['lost'],
                'achievement' => $achievement,
                'avatar'      => strtoupper(substr($s->name, 0, 2)),
                'status'      => ($targetLunas > 0 && $stats['realisasi_closing'] >= $targetLunas) ? 'Target Achieved' : 'On Progress'
            ];

            $totalTarget    += $targetLunas;
            $totalRealisasi += $stats['realisasi_closing'];
        }


        $breakdown = [
            'Sekolah' => \App\Models\Prospek::where('type', 'Sekolah')->where('status', 'LUNAS')->count(),
            'Corporate' => \App\Models\Prospek::where('type', 'Corporate')->where('status', 'LUNAS')->count(),
            'Individu' => \App\Models\Prospek::where('type', 'Individu')->where('status', 'LUNAS')->count(),
        ];

        $summary = [
            'target'      => $totalTarget,
            'realisasi'   => $totalRealisasi,
            'achievement' => $totalTarget > 0 ? round(($totalRealisasi / $totalTarget) * 100) : 0,
            'sisa_target' => max(0, $totalTarget - $totalRealisasi),
            'breakdown'   => $breakdown,
            'periode_label' => \Carbon\Carbon::now()->locale('id')->isoFormat('MMMM YYYY'),
        ];

        // Data Dashboard Target & Pencapaian Berjenjang (PRD Bab 6.2)
        $targetAchievementData = $this->targetAchievementService->getDashboardTargetData(
            $user,
            $request->get('periode', 'bulanan'),
            null,
            $request->get('ta')
        );

        return view('performa.index', compact('team', 'summary', 'targetAchievementData'));
    }
}
