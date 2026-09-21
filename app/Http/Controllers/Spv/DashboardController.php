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
            'Cold Lead' => 1,
            'Interested' => 2,
            'Follow Up' => 3,
            'Beli Formulir' => 4,
            'Pembayaran Termin 1' => 5,
            'Closing' => 6,
        ];

        $totalProspek = $user->teamProspeks()->count();
        $closingCount = $user->teamProspeks()->where('status', 'Closing')->count();
        $hotLeads = $user->teamProspeks()->whereIn('status', ['Follow Up', 'Beli Formulir', 'Pembayaran Termin 1'])->count();
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
            $closing = Prospek::where('sales_id', $sales->id)->where('status', 'Closing')->count();
            $visits = Kunjungan::where('sales_id', $sales->id)->count();
            $target = $sales->targets()->where('bulan', now()->month)->where('tahun', now()->year)->first();
            $targetNum = $target ? $target->target : 30;
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
