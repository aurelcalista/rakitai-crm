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

        $selectedPeriode = $request->get('periode', 'bulan-ini');
        $periodInfo = $this->targetAchievementService->resolvePeriod($selectedPeriode, $request->get('ta'));
        $start = $periodInfo['start'];
        $end = $periodInfo['end'];
        $isSemua = $selectedPeriode === 'semua';

        $salesUsers = \App\Models\User::where('role', 'Sales')->get();
        $team = [];

        $totalTarget = 0;
        $totalRealisasi = 0;

        foreach ($salesUsers as $s) {
            $activeTarget = $this->targetService->getActiveTarget($s);
            $rawTarget = $activeTarget ? (int)$activeTarget->target_lunas : 0;
            if ($rawTarget <= 0 && $activeTarget) {
                $rawTarget = (int)$activeTarget->target_kontak;
            }
            $targetLunas = $this->targetAchievementService->prorateTarget($rawTarget, $periodInfo, $activeTarget?->tipe_periode);

            $prospekQuery = Prospek::where('sales_id', $s->id);
            if (!$isSemua) {
                $totalProspek = (clone $prospekQuery)->whereBetween('created_at', [$start, $end])->count();
                $closing = (clone $prospekQuery)->where('status', 'LUNAS')->whereBetween('updated_at', [$start, $end])->count();
                $lost = (clone $prospekQuery)->where('status', 'DINGIN')->whereBetween('updated_at', [$start, $end])->count();
                $followUp = \App\Models\FollowUp::where('user_id', $s->id)->whereBetween('tanggal', [$start, $end])->count();
            } else {
                $totalProspek = (clone $prospekQuery)->count();
                $closing = (clone $prospekQuery)->where('status', 'LUNAS')->count();
                $lost = (clone $prospekQuery)->where('status', 'DINGIN')->count();
                $followUp = \App\Models\FollowUp::where('user_id', $s->id)->count();
            }

            $achievement = $targetLunas > 0 ? min(100, round(($closing / $targetLunas) * 100)) : ($closing > 0 ? 100 : 0);

            $team[] = [
                'name'        => $s->name,
                'role'        => $s->role,
                'target'      => $targetLunas,
                'prospects'   => $totalProspek,
                'follow_up'   => $followUp,
                'closing'     => $closing,
                'lost'        => $lost,
                'achievement' => $achievement,
                'avatar'      => strtoupper(substr($s->name, 0, 2)),
                'status'      => ($targetLunas > 0 && $closing >= $targetLunas) ? 'Target Achieved' : ($achievement >= 40 ? 'On Track' : 'Progres Berjalan')
            ];

            $totalTarget    += $targetLunas;
            $totalRealisasi += $closing;
        }

        $breakdownQuery = \App\Models\Prospek::where('status', 'LUNAS');
        if (!$isSemua) {
            $breakdownQuery->whereBetween('updated_at', [$start, $end]);
        }
        $breakdown = [
            'Sekolah'   => (clone $breakdownQuery)->where('type', 'Sekolah')->count(),
            'Corporate' => (clone $breakdownQuery)->where('type', 'Corporate')->count(),
            'Individu'  => (clone $breakdownQuery)->where('type', 'Individu')->count(),
        ];

        $summary = [
            'target'        => $totalTarget,
            'realisasi'     => $totalRealisasi,
            'achievement'   => $totalTarget > 0 ? round(($totalRealisasi / $totalTarget) * 100) : ($totalRealisasi > 0 ? 100 : 0),
            'sisa_target'   => max(0, $totalTarget - $totalRealisasi),
            'breakdown'     => $breakdown,
            'periode_label' => $periodInfo['label'],
        ];

        // Data Dashboard Target & Pencapaian Berjenjang (PRD Bab 6.2)
        $targetAchievementData = $this->targetAchievementService->getDashboardTargetData(
            $user,
            $selectedPeriode,
            null,
            $request->get('ta')
        );
        $ta = $this->targetAchievementService->getActiveTa();

        return view('performa.index', compact('team', 'summary', 'targetAchievementData', 'selectedPeriode', 'ta'));
    }
}
