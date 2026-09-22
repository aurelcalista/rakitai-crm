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

        // Query prospek tim terikat TA Aktif
        $prospekQuery = $user->teamProspeks()->where(function ($q) use ($activeTa) {
            $q->where('tahun_akademik', $activeTa)->orWhereNull('tahun_akademik');
        });

        $totalProspek = (clone $prospekQuery)->count();
        $closingCount = (clone $prospekQuery)->whereIn('status', ['LUNAS', 'Closing', '07 LUNAS'])->count();
        $hotLeads     = (clone $prospekQuery)->whereIn('status', ['PANAS', 'FORMULIR', 'BERKAS', 'Follow Up', 'Beli Formulir', 'Pembayaran Termin 1'])->count();
        $lostCount    = (clone $prospekQuery)->whereIn('status', ['DINGIN', 'Lost', 'Ditolak/Batal', 'Ditolak / Batal'])->count();
        $totalFollowUp = FollowUp::whereIn('user_id', $teamMemberIds)->count();

        // Target Tim dari HM untuk TA Aktif
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
            // Handle legacy status mapping if exact stage 0
            if ($count === 0) {
                if ($stageName === 'BARU') $count = (clone $prospekQuery)->whereIn('status', ['BARU', 'Baru', 'Cold Lead'])->count();
                elseif ($stageName === 'KONTAK') $count = (clone $prospekQuery)->whereIn('status', ['KONTAK', 'Interested'])->count();
                elseif ($stageName === 'HANGAT') $count = (clone $prospekQuery)->whereIn('status', ['HANGAT', 'Follow Up', 'Follow Up 1'])->count();
                elseif ($stageName === 'PANAS') $count = (clone $prospekQuery)->whereIn('status', ['PANAS', 'Negosiasi'])->count();
                elseif ($stageName === 'FORMULIR') $count = (clone $prospekQuery)->whereIn('status', ['FORMULIR', 'Beli Formulir'])->count();
                elseif ($stageName === 'BERKAS') $count = (clone $prospekQuery)->whereIn('status', ['BERKAS', 'Pembayaran Termin 1'])->count();
                elseif ($stageName === 'LUNAS') $count = (clone $prospekQuery)->whereIn('status', ['LUNAS', 'Closing', 'Mendaftar'])->count();
                elseif ($stageName === 'DINGIN') $count = (clone $prospekQuery)->whereIn('status', ['DINGIN', 'Lost', 'Ditolak/Batal'])->count();
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
        $teamPerformance = $teamMembers->where('role', 'Sales')->map(function ($sales) use ($activeTa) {
            $prospectCount = Prospek::where('sales_id', $sales->id)
                ->where(function ($q) use ($activeTa) {
                    $q->where('tahun_akademik', $activeTa)->orWhereNull('tahun_akademik');
                })->count();

            $closing = Prospek::where('sales_id', $sales->id)
                ->whereIn('status', ['LUNAS', 'Closing', '07 LUNAS'])
                ->where(function ($q) use ($activeTa) {
                    $q->where('tahun_akademik', $activeTa)->orWhereNull('tahun_akademik');
                })->count();

            $visits = Kunjungan::where('sales_id', $sales->id)->count();

            $target = $sales->targets()
                ->where('status', 'Aktif')
                ->latest()
                ->first();

            $targetNum = $target ? (int)$target->target_lunas : 10;
            $achievedPct = $targetNum > 0 ? round(($closing / $targetNum) * 100) : 0;

            return [
                'user'         => $sales,
                'prospects'    => $prospectCount,
                'closing'      => $closing,
                'visits'       => $visits,
                'target'       => $targetNum,
                'achieved_pct' => $achievedPct,
            ];
        });

        return view('spv.dashboard', compact(
            'stats',
            'targetHm',
            'pipelineStats',
            'teamMembers',
            'teamPerformance',
            'recentFollowUps',
            'activeTa'
        ));
    }
}
