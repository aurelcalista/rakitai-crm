<?php

namespace App\Http\Controllers\Spv;

use App\Http\Controllers\Controller;
use App\Models\Prospek;
use App\Models\Kunjungan;
use App\Models\User;
use App\Models\FollowUp;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display SPV Team Analytics Dashboard.
     */
    public function index(Request $request): View
    {
        $user = auth()->user();
        $teamMemberIds = $user->teamMemberIds();

        // Team members (Sales & CS under SPV)
        $teamMembers = User::whereIn('id', $teamMemberIds)->with('wilayah')->get();

        // Pipeline stage counts for team
        $stages = [
            'BARU' => 1,
            'KONTAK' => 2,
            'HANGAT' => 3,
            'PANAS' => 4,
            'FORMULIR' => 5,
            'BERKAS' => 6,
            'LUNAS' => 7,
            'DINGIN' => 8,
        ];

        $totalProspek = $user->teamProspeks()->count();
        $closingCount = $user->teamProspeks()->where('status', 'LUNAS')->count();
        $hotLeads = $user->teamProspeks()->whereIn('status', ['PANAS', 'FORMULIR', 'BERKAS'])->count();
        $lostCount = $user->teamProspeks()->where('status', 'Lost')->count();

        $conversionRate = $totalProspek > 0 ? round(($closingCount / $totalProspek) * 100, 1) : 0;

        $stats = [
            'total_prospek' => $totalProspek,
            'closing_count' => $closingCount,
            'hot_leads' => $hotLeads,
            'lost_count' => $lostCount,
            'conversion_rate' => $conversionRate,
            'total_visits' => $user->teamKunjungans()->count(),
            'total_team_members' => $teamMembers->count(),
        ];

        // Pipeline stage distribution
        $pipelineStats = [];
        foreach ($stages as $stageName => $stageNum) {
            $count = $user->teamProspeks()->where('status', $stageName)->count();
            $pipelineStats[] = [
                'name' => $stageName,
                'number' => $stageNum,
                'count' => $count,
                'pct' => $totalProspek > 0 ? round(($count / $totalProspek) * 100) : 0,
            ];
        }

        // Recent team activities / follow ups
        $recentFollowUps = FollowUp::whereIn('user_id', $teamMemberIds)
            ->with(['user', 'prospek'])
            ->orderBy('tanggal', 'desc')
            ->limit(8)
            ->get();

        // Sales Leaderboard / Team performance summary
        $teamPerformance = $teamMembers->where('role', 'Sales')->map(function ($sales) {
            $prospectCount = Prospek::where('sales_id', $sales->id)->count();
            $closing = Prospek::where('sales_id', $sales->id)->where('status', 'LUNAS')->count();
            $visits = Kunjungan::where('sales_id', $sales->id)->count();
            $target = $sales->targets()
                ->where('tipe_periode', 'Bulanan')
                ->where('tanggal_mulai', '<=', now()->endOfMonth())
                ->where('tanggal_selesai', '>=', now()->startOfMonth())
                ->where('status', 'Aktif')
                ->first();
            $targetNum = $target ? $target->target_kontak : 30;
            $achievedPct = $targetNum > 0 ? round(($closing / $targetNum) * 100) : 0;

            return [
                'user' => $sales,
                'prospects' => $prospectCount,
                'closing' => $closing,
                'visits' => $visits,
                'target' => $targetNum,
                'achieved_pct' => $achievedPct,
            ];
        });

        return view('spv.dashboard', compact(
            'stats',
            'pipelineStats',
            'teamMembers',
            'teamPerformance',
            'recentFollowUps'
        ));
    }
}
