<?php

namespace App\Http\Controllers\Spv;

use App\Http\Controllers\Controller;
use App\Models\Prospek;
use App\Models\Kunjungan;
use App\Models\User;
use App\Models\FollowUp;
use App\Services\AkademikService;
use App\Services\SpvPerformanceService;
use App\Services\TargetAchievementService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private SpvPerformanceService $spvService,
        private TargetAchievementService $targetAchievementService
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

        // Dynamic Pipeline Stages from Master Data
        $stages = Prospek::getActiveStages();

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
            ->orderBy('id', 'desc')
            ->limit(8)
            ->get();

        // Team performance summary (Sales & CS under SPV)
        $teamPerformance = $teamMembers->map(function ($member) use ($activeTa, $activeTaId) {
            $isCs = $member->role === 'CS';

            $prospectQuery = Prospek::where(function ($q) use ($member, $isCs) {
                if ($isCs) {
                    $q->where('cs_id', $member->id);
                } else {
                    $q->where('sales_id', $member->id);
                }
            })->where(function ($q) use ($activeTa, $activeTaId) {
                if ($activeTaId) {
                    $q->where('academic_year_id', $activeTaId);
                } else {
                    $q->where('tahun_akademik', $activeTa)->orWhereNull('tahun_akademik');
                }
            });

            $prospectCount = (clone $prospectQuery)->count();
            $closing = (clone $prospectQuery)->where('status', 'LUNAS')->count();
            $visits = $isCs ? 0 : Kunjungan::where('sales_id', $member->id)->count();

            $target = $member->targets()
                ->where('status', 'Aktif')
                ->when($activeTaId, fn($q) => $q->where('academic_year_id', $activeTaId))
                ->latest()
                ->first();

            $targetNum   = $target ? (int)$target->target_lunas : 0;
            $achievedPct = $targetNum > 0 ? round(($closing / $targetNum) * 100) : 0;

            return [
                'user'         => $member,
                'role'         => $member->role,
                'prospects'    => $prospectCount,
                'closing'      => $closing,
                'visits'       => $visits,
                'target'       => $targetNum,
                'achieved_pct' => $achievedPct,
                'wilayah_nama' => $isCs ? 'Centralized (Tanpa Wilayah)' : ($member->wilayah?->nama ?? 'Belum Ditugaskan'),
            ];
        });

        // P0 Bab 8.6: Deteksi Prospek FORMULIR yang melanggar SLA 2 jam serah terima CS
        $overdueHandovers = (clone $prospekQuery)
            ->where(function ($q) {
                $q->where('status', 'FORMULIR')->orWhere('status', '05 FORMULIR');
            })
            ->whereNotNull('handover_at')
            ->where('handover_at', '<=', now()->subHours(2))
            ->with(['sales', 'cs'])
            ->get()
            ->filter(function ($p) {
                return !FollowUp::where('prospek_id', $p->id)
                    ->where('created_at', '>=', $p->handover_at)
                    ->whereHas('user', function ($q) { $q->where('role', 'CS'); })
                    ->exists();
            })
            ->values();

        // Data Dashboard Target & Pencapaian Berjenjang (PRD Bab 6.2)
        $targetAchievementData = $this->targetAchievementService->getDashboardTargetData(
            $user,
            $request->get('periode', 'bulanan'),
            $request->get('wilayah_id') ? (int)$request->get('wilayah_id') : null,
            $activeTa
        );

        // spvRollup placeholder for view compatibility
        $spvRollup = [];

        return view('spv.dashboard', compact(
            'stats',
            'targetHm',
            'pipelineStats',
            'teamMembers',
            'teamPerformance',
            'spvRollup',
            'recentFollowUps',
            'activeTa',
            'overdueHandovers',
            'targetAchievementData'
        ));
    }
}
