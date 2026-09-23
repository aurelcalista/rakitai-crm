<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Prospek;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * Halaman Laporan khusus Sales.
     */
    public function index(Request $request): View
    {
        $user = auth()->user();

        // Hanya prospek yang dihandle oleh Sales ini
        $prospectsRaw = clone Prospek::with(['sales', 'cs', 'owner', 'followUps' => function($q) {
            $q->orderBy('tanggal', 'desc')->limit(1);
        }, 'timelines' => function($q) {
            $q->orderBy('time', 'desc')->with('user');
        }])
        ->where('sales_id', $user->id)
        ->get();

        $prospects = $prospectsRaw->map(function ($p) {
            $activeTakeover = [];
            if ($p->sales) $activeTakeover[] = 'Sales';
            if ($p->cs) $activeTakeover[] = 'CS';

            return [
                'id' => $p->id,
                'name' => $p->name,
                'type' => $p->type,
                'category' => $p->category ?? '-',
                'pic' => $p->pic ?? '-',
                'pic_phone' => $p->pic_phone ?? '-',
                'whatsapp' => $p->whatsapp ?? '-',
                'status' => $p->status,
                'stage_number' => $p->stage_number,
                'takeover_sales' => $p->sales ? $p->sales->name : null,
                'takeover_cs' => $p->cs ? $p->cs->name : null,
                'active_takeover' => count($activeTakeover) > 0 ? implode(' & ', $activeTakeover) : 'Belum Ada',
                'owner' => $p->owner ? $p->owner->name : 'Sistem',
                'last_activity' => $p->updated_at->diffForHumans(),
                'potential' => $p->potential ?? '-',
                'ai_training' => $p->ai_training ?? '-',
                'notes' => $p->notes ?? '',
                'created_at' => $p->created_at ? $p->created_at->format('d M Y') : '-',
                'takeover_time' => $p->updated_at ? $p->updated_at->format('d M Y, H:i') : '-',
                'last_contact' => $p->followUps->first() ? \Carbon\Carbon::parse($p->followUps->first()->tanggal)->format('d M Y, H:i') : '-',
                'next_follow_up' => $p->followUps->first() && $p->followUps->first()->next_follow_up ? \Carbon\Carbon::parse($p->followUps->first()->next_follow_up)->format('d M Y, H:i') : '-',
                'next_follow_up_date' => $p->followUps->first() && $p->followUps->first()->next_follow_up ? \Carbon\Carbon::parse($p->followUps->first()->next_follow_up)->toDateString() : null,
                'timeline' => $p->timelines->map(function ($t) {
                    return [
                        'time' => $t->time->format('d M, H:i'),
                        'title' => $t->title,
                        'notes' => $t->notes,
                        'status' => $t->status_after,
                        'user' => $t->user ? $t->user->name : 'Sistem',
                        'role' => $t->user ? $t->user->role : 'Admin',
                    ];
                })->toArray(),
            ];
        })->toArray();
        
        $totalProspek = Prospek::where('sales_id', $user->id)->count();
        $closing = Prospek::where('sales_id', $user->id)->where('status', 'LUNAS')->count();
        
        $summary = [
            'total_prospek'   => $totalProspek,
            'active'          => Prospek::where('sales_id', $user->id)->whereNotIn('status', ['LUNAS', 'DINGIN'])->count(),
            'closing'         => $closing,
            'lost'            => Prospek::where('sales_id', $user->id)->where('status', 'DINGIN')->count(),
            'conversion_rate' => $totalProspek > 0 ? round(($closing / $totalProspek) * 100, 1) : 0,
        ];

        return view('laporan.index', compact('prospects', 'summary'));
    }
}
