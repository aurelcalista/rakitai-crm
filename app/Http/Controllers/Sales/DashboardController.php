<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\ProspekTimeline;
use App\Services\SalesTargetService;
use App\Services\TargetAchievementService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private SalesTargetService $targetService,
        private TargetAchievementService $targetAchievementService
    ) {
    }

    /**
     * Sales Dashboard — fully powered by real database data.
     */
    public function index(Request $request): View
    {
        $user = auth()->user();

        // Real stats from DB, scoped to this Sales user
        $stats = $this->targetService->getStats($user);

        // Pipeline stages distribution for this Sales
        $pipelineStages = $this->targetService->getPipelineStages($user);

        // Recent 5 prospects this Sales is handling (latest updated first)
        $recentProspectsRaw = \App\Models\Prospek::with(['sales', 'cs', 'owner', 'latestFollowUp'])
            ->where('sales_id', $user->id)
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();

        $recentProspects = $recentProspectsRaw->map(fn ($p) => $this->formatProspek($p))->toArray();

        // Recent activity timeline — last 5 timeline events for this Sales's prospects
        $recentActivity = ProspekTimeline::whereHas('prospek', function ($q) use ($user) {
            $q->where('sales_id', $user->id);
        })
            ->with(['prospek', 'user'])
            ->orderBy('time', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($t) {
                $timeObj = $t->time ?? $t->created_at;
                return [
                    'time'         => $timeObj ? $timeObj->format('H:i') : '-',
                    'time_full'    => $timeObj ? $timeObj->format('d M Y, H:i') : '-',
                    'title'        => $t->title,
                    'notes'        => $t->notes,
                    'prospek_name' => $t->prospek ? $t->prospek->name : '-',
                    'prospek_id'   => $t->prospek_id,
                    'user_name'    => $t->user ? $t->user->name : 'Sistem',
                    'user_role'    => $t->user ? $t->user->role : 'System',
                    'status_after' => $t->status_after,
                ];
            })->toArray();

        // Daily target (snowball calculation)
        $dailyTarget = $this->targetService->calculateDailyTarget($user);

        // Dashboard Target & Pencapaian Berjenjang (PRD Bab 6.2)
        $targetAchievementData = $this->targetAchievementService->getDashboardTargetData(
            $user,
            $request->get('periode', 'bulanan'),
            null,
            $request->get('ta')
        );

        return view('sales.dashboard', compact(
            'stats',
            'pipelineStages',
            'recentProspects',
            'recentActivity',
            'dailyTarget',
            'targetAchievementData'
        ));
    }

    /**
     * Format a Prospek Eloquent model to the array shape expected by Sales Blade views.
     */
    private function formatProspek(\App\Models\Prospek $p): array
    {
        $latestFollowUp = $p->latestFollowUp ?? ($p->relationLoaded('followUps') ? $p->followUps->first() : null);

        return [
            'id'             => $p->id,
            'name'           => $p->name,
            'type'           => $p->type,
            'category'       => $p->category ?? '-',
            'pic'            => $p->pic ?? '-',
            'pic_phone'      => $p->pic_phone ?? '-',
            'whatsapp'       => $p->whatsapp ?? '-',
            'status'         => $p->status,
            'stage_number'   => $p->stage_number,
            'takeover_sales' => $p->sales ? $p->sales->name : null,
            'takeover_cs'    => $p->cs ? $p->cs->name : null,
            'active_takeover' => $p->activeHandlerLabel(),
            'owner'          => $p->owner ? $p->owner->name : 'Sistem',
            'last_activity'  => $p->updated_at->diffForHumans(),
            'potential'      => $p->potential ?? '-',
            'ai_training'    => $p->ai_training ?? '-',
            'notes'          => $latestFollowUp && $latestFollowUp->catatan ? $latestFollowUp->catatan : ($p->notes ?? ''),
            'created_at'     => $p->created_at ? $p->created_at->format('d M Y') : '-',
            'takeover_time'  => $p->updated_at ? $p->updated_at->format('d M Y, H:i') : '-',
            'last_contact'   => $latestFollowUp && $latestFollowUp->tanggal
                ? \Carbon\Carbon::parse($latestFollowUp->tanggal)->format('d M Y, H:i')
                : '-',
            'next_follow_up' => $latestFollowUp && $latestFollowUp->next_follow_up
                ? \Carbon\Carbon::parse($latestFollowUp->next_follow_up)->format('d M Y, H:i')
                : '-',
            'next_follow_up_date' => $latestFollowUp && $latestFollowUp->next_follow_up
                ? \Carbon\Carbon::parse($latestFollowUp->next_follow_up)->toDateString()
                : null,
        ];
    }
}
