<?php

namespace App\Http\Controllers\Spv;

use App\Http\Controllers\Controller;
use App\Models\Prospek;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * Team recap reports for SPV.
     */
    public function index(Request $request): View
    {
        $user = auth()->user();
        $teamMemberIds = $user->teamMemberIds();

        $query = Prospek::with(['sales', 'cs', 'owner', 'followUps' => function ($q) {
            $q->orderBy('tanggal', 'desc')->limit(1);
        }, 'timelines' => function ($q) {
            $q->orderBy('time', 'desc')->with('user');
        }])->where(function ($q) use ($teamMemberIds, $user) {
            $q->whereIn('sales_id', $teamMemberIds)
              ->orWhereIn('owner_id', $teamMemberIds)
              ->orWhereIn('cs_id', $teamMemberIds);
            if ($user->wilayah_id) {
                $q->orWhere('wilayah_id', $user->wilayah_id);
            }
        });

        // Optional filter by Sales
        if ($request->filled('sales_id') && $request->sales_id !== 'all') {
            $query->where('sales_id', $request->sales_id);
        }

        // Optional filter by Status
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Optional filter by Date Range
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $prospectsRaw = $query->orderBy('updated_at', 'desc')->get();

        $prospects = $prospectsRaw->map(function ($p) {
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
                'sales_name'     => $p->sales ? $p->sales->name : 'Belum Ditugaskan',
                'sales_id'       => $p->sales_id,
                'takeover_sales' => $p->sales ? $p->sales->name : null,
                'takeover_cs'    => $p->cs ? $p->cs->name : null,
                'active_takeover'=> $p->activeHandlerLabel(),
                'owner'          => $p->owner ? $p->owner->name : 'Sistem',
                'last_activity'  => $p->updated_at->diffForHumans(),
                'potential'      => $p->potential ?? '-',
                'source'         => $p->source ?? '-',
                'notes'          => $p->notes ?? '',
                'created_at'     => $p->created_at ? $p->created_at->format('d M Y') : '-',
                'last_contact'   => $p->followUps->first() ? Carbon::parse($p->followUps->first()->tanggal)->format('d M Y, H:i') : '-',
                'timeline'       => $p->timelines->map(function ($t) {
                    return [
                        'time'   => $t->time->format('d M, H:i'),
                        'title'  => $t->title,
                        'notes'  => $t->notes,
                        'status' => $t->status_after,
                        'user'   => $t->user ? $t->user->name : 'Sistem',
                        'role'   => $t->user ? $t->user->role : 'Admin',
                    ];
                })->toArray(),
            ];
        })->toArray();

        $totalProspek = count($prospects);
        $closing = count(array_filter($prospects, fn($p) => $p['status'] === 'LUNAS'));
        $lost = count(array_filter($prospects, fn($p) => $p['status'] === 'DINGIN'));
        $active = $totalProspek - $closing - $lost;

        $summary = [
            'total_prospek'   => $totalProspek,
            'active'          => $active,
            'closing'         => $closing,
            'lost'            => $lost,
            'conversion_rate' => $totalProspek > 0 ? round(($closing / $totalProspek) * 100, 1) : 0,
        ];

        $teamSales = User::whereIn('id', $teamMemberIds)->where('role', 'Sales')->get();

        return view('spv.laporan.index', compact('prospects', 'summary', 'teamSales'));
    }
}
