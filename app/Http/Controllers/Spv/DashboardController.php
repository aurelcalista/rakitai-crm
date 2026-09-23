<?php

namespace App\Http\Controllers\Spv;

use App\Http\Controllers\Controller;
use App\Models\Prospek;
use App\Models\Kunjungan;
use App\Models\User;
use App\Models\FollowUp;
use App\Services\SpvPerformanceService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private SpvPerformanceService $spvService)
    {
    }

    /**
     * Display SPV Team Analytics Dashboard (Tahun Akademik Aktif TA 2027/2028).
     */
    public function index(Request $request): View
    {
        $user = auth()->user();
        $teamMemberIds = $user->teamMemberIds();
        $activeTa = $request->get('ta', SpvPerformanceService::DEFAULT_TA);

        // Team members (Sales & CS under SPV)
        $teamMembers = User::whereIn('id', $teamMemberIds)->with('wilayah')->get();
        $salesCount  = $teamMembers->where('role', 'Sales')->count();
        $csCount     = $teamMembers->where('role', 'CS')->count();

        // 8 Pipeline Stages Standar
        $stages = Prospek::PIPELINE_8_STAGES;

        // Ambil ID Tahun Akademik Aktif
        $activeTaObj = \App\Models\TahunAkademik::getAktif();
        $activeTaId = $activeTaObj ? $activeTaObj->id : null;

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

        // Team performance summary (Sales & CS under SPV)
        $teamPerformance = $teamMembers->map(function ($member) use ($activeTa) {
            $isCs = $member->role === 'CS';

            $prospectQuery = Prospek::where(function ($q) use ($member, $isCs) {
                if ($isCs) {
                    $q->where('cs_id', $member->id);
                } else {
                    $q->where('sales_id', $member->id);
                }
            })->where(function ($q) use ($activeTa) {
                $q->where('tahun_akademik', $activeTa)->orWhereNull('tahun_akademik');
            });

            $prospectCount = (clone $prospectQuery)->count();
            $closing = (clone $prospectQuery)->whereIn('status', ['LUNAS', 'Closing', '07 LUNAS'])->count();
            $visits = $isCs ? 0 : Kunjungan::where('sales_id', $member->id)->count();

            $target = $member->targets()
                ->where('status', 'Aktif')
                ->latest()
                ->first();

            $targetNum = $target ? (int)$target->target_lunas : 10;
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

        return view('spv.dashboard', compact(
            'stats',
            'targetHm',
            'pipelineStats',
            'teamMembers',
            'teamPerformance',
            'recentFollowUps',
            'activeTa',
            'overdueHandovers'
        ));
    }
}
