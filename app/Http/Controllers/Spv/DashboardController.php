<?php

namespace App\Http\Controllers\Spv;

use App\Http\Controllers\Controller;
use App\Models\Prospek;
use App\Models\Kunjungan;
use App\Models\User;
use App\Models\FollowUp;
use App\Services\AkademikService;
use App\Services\SpvPerformanceService;
use App\Services\TargetMetricsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private SpvPerformanceService $spvService,
        private TargetMetricsService $metricsService,
    ) {
    }

    /**
     * Display SPV Team Analytics Dashboard (Active Tahun Akademik).
     */
    public function index(Request $request): View
    {
        $user = auth()->user();
        $teamMemberIds = $user->teamMemberIds();

        // Team members (Sales & CS under SPV)
        $teamMembers = User::whereIn('id', $teamMemberIds)->with('wilayah')->get();
        $salesCount  = $teamMembers->where('role', 'Sales')->count();
        $csCount     = $teamMembers->where('role', 'CS')->count();

        // 8 Pipeline Stages Standar
        $stages = Prospek::PIPELINE_8_STAGES;

        // Active Tahun Akademik via central mechanism
        $activeTaId  = \App\Services\AkademikService::getAktifId();
        $activeTaNama = \App\Services\AkademikService::getAktifNama();

        // Historical override via ?ta= query param (explicit, not default)
        $taOverride = $request->get('ta');
        $activeTa   = $taOverride ?? $activeTaNama; // used for SpvPerformanceService calls

        // Query prospek tim terikat TA Aktif
        $prospekQuery = $user->teamProspeks()->where('academic_year_id', $activeTaId);

        $totalProspek = (clone $prospekQuery)->count();
        $closingCount = (clone $prospekQuery)->where('status', 'LUNAS')->count();
        $hotLeads     = (clone $prospekQuery)->whereIn('status', ['PANAS', 'FORMULIR', 'BERKAS'])->count();
        $lostCount    = (clone $prospekQuery)->where('status', 'DINGIN')->count();
        $totalFollowUp = FollowUp::whereIn('user_id', $teamMemberIds)->count();

        // Target Tim dari HM untuk TA Aktif (diubah ke academic_year_id di dalam service nantinya)
        $targetHm = $this->spvService->getTargetHmForSpv($user, $activeTa);

        $conversionRate = $totalProspek > 0 ? round(($closingCount / $totalProspek) * 100, 1) : 0;

        $stats = [
            'total_sales'        => $salesCount,
            'total_cs'           => $csCount,
            'total_team_members' => $teamMembers->count(),
            'total_prospek'      => $totalProspek,
            'total_follow_up'    => $totalFollowUp,
            'closing_count'      => $closingCount,
            'total_closing'      => $closingCount,
            'hot_leads'          => $hotLeads,
            'lost_count'         => $lostCount,
            'conversion_rate'    => $conversionRate,
            'total_visits'       => $user->teamKunjungans()->count(),
            'target_tim'         => $targetHm['target_lunas'],
            'realisasi_tim'      => $targetHm['realisasi_lunas'],
            'persentase_tim'     => $targetHm['achieve_pct'],
            'sisa_target'        => $targetHm['sisa_lunas'],
        ];

        // Pipeline stage distribution (8 Status)
        $pipelineStats = [];
        foreach ($stages as $stageIndex => $stageName) {
            $count = (clone $prospekQuery)->where('status', $stageName)->count();
            if ($count === 0) {
                // Di P0, kita tidak pakai mapping legacy lagi
                $count = 0;
            }

            $pipelineStats[] = [
                'name'   => $stageName,
                'number' => $stageIndex + 1,
                'count'  => $count,
                'pct'    => $totalProspek > 0 ? round(($count / $totalProspek) * 100) : 0,
            ];
        }

        // Recent team activities / follow ups
        $recentFollowUps = FollowUp::whereIn('user_id', $teamMemberIds)
            ->with(['user', 'prospek'])
            ->orderBy('tanggal', 'desc')
            ->limit(8)
            ->get();

        // Sales Leaderboard / Team performance summary
        // Each Sales scoped to active TA — no double-count (each prospek attributed to one sales_id)
        $spvRollup = $metricsService->rollUpForSpv($user, null, $activeTaId);

        $teamPerformance = $teamMembers->where('role', 'Sales')->map(function ($sales) use ($activeTaId) {
            $prospectCount = Prospek::where('sales_id', $sales->id)
                ->where('academic_year_id', $activeTaId)
                ->count();

            $closing = Prospek::where('sales_id', $sales->id)
                ->where('status', 'LUNAS')
                ->where('academic_year_id', $activeTaId)
                ->count();
            $visits = Kunjungan::where('sales_id', $sales->id)->count();

            // Use target_lunas (target_closing does not exist in DB)
            $target = $sales->targets()
                ->where('status', 'Aktif')
                ->when($activeTaId, fn($q) => $q->where('academic_year_id', $activeTaId))
                ->latest()
                ->first();

            $targetNum   = $target ? (int)$target->target_lunas : 0;
            $achievedPct = $targetNum > 0 ? round(($closing / $targetNum) * 100) : 0;

            return [
                'user'         => $sales,
                'prospects'    => $prospectCount,
                'closing'      => $closing,
                'visits'       => $visits,
                'target'       => $targetNum,
                'achieved_pct' => $achievedPct,
                'color_status' => \App\Services\TargetMetricsService::YELLOW_THRESHOLD <= $achievedPct
                    ? ($achievedPct >= 100 ? 'green' : 'yellow')
                    : 'red',
            ];
        });


        return view('spv.dashboard', compact(
            'stats',
            'targetHm',
            'pipelineStats',
            'teamMembers',
            'teamPerformance',
            'spvRollup',
            'recentFollowUps',
            'activeTa'
        ));
    }
}
