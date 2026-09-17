<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Prospek;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class PipelineController extends Controller
{
    private const STAGES = [
        'Cold Lead'           => 1,
        'Interested'          => 2,
        'Follow Up'           => 3,
        'Beli Formulir'       => 4,
        'Pembayaran Termin 1' => 5,
        'Closing'             => 6,
    ];

    /**
     * Kanban board — shows all prospects the Sales user handles.
     * Includes both sales_id (direct handler) and prospects where
     * the Sales user is the owner (original creator) but CS may have taken over.
     */
    public function index(): View
    {
        $user = auth()->user();

        $prospectsRaw = Prospek::with(['sales', 'cs', 'owner', 'followUps' => function ($q) {
            $q->orderBy('tanggal', 'desc')->limit(1);
        }])
            ->where('sales_id', $user->id)
            ->orderBy('updated_at', 'desc')
            ->get();

        $prospects = $prospectsRaw->map(fn ($p) => $this->formatProspek($p))->toArray();

        return view('pipeline.index', compact('prospects'));
    }

    /**
     * Update prospect status from pipeline board.
     * Backend-enforced: only the active Sales handler can change status.
     */
    public function updateStatus(Request $request): JsonResponse
    {
        $request->validate([
            'prospek_id' => 'required|exists:prospeks,id',
            'status'     => 'required|string|in:' . implode(',', array_keys(self::STAGES)),
        ]);

        $prospek = Prospek::findOrFail($request->prospek_id);
        $user    = auth()->user();

        // Authorization: must be the active Sales handler
        if ($prospek->sales_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda bukan handler aktif prospek ini.',
            ], 403);
        }

        // Cannot change status of Lost via this endpoint — use markLost
        if ($prospek->status === 'Lost') {
            return response()->json([
                'success' => false,
                'message' => 'Prospek dengan status Lost tidak dapat diubah.',
            ], 422);
        }

        $oldStatus = $prospek->status;
        $prospek->status       = $request->status;
        $prospek->stage_number = self::STAGES[$request->status] ?? $prospek->stage_number;
        $prospek->save();

        // Activity log
        \App\Models\ProspekTimeline::create([
            'prospek_id'   => $prospek->id,
            'user_id'      => $user->id,
            'title'        => 'Status Diperbarui via Pipeline Board',
            'notes'        => "Status diubah dari {$oldStatus} menjadi {$prospek->status}",
            'status_before'=> $oldStatus,
            'status_after' => $prospek->status,
            'time'         => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Status berhasil diubah menjadi {$prospek->status}",
            'status'  => $prospek->status,
        ]);
    }

    // ─── Private Helpers ─────────────────────────────────────────────────────

    private function formatProspek(Prospek $p): array
    {
        $latestFU = $p->followUps->first();

        return [
            'id'             => $p->id,
            'name'           => $p->name,
            'type'           => $p->type,
            'pic'            => $p->pic ?? '-',
            'whatsapp'       => $p->whatsapp ?? '-',
            'status'         => $p->status,
            'stage_number'   => $p->stage_number,
            'notes'          => $p->notes ?? '',
            'takeover_sales' => $p->sales ? $p->sales->name : null,
            'takeover_cs'    => $p->cs ? $p->cs->name : null,
            'active_takeover'=> $p->activeHandlerLabel(),
            'last_activity'  => $p->updated_at->diffForHumans(),
            'last_contact'   => $latestFU
                ? \Carbon\Carbon::parse($latestFU->tanggal)->format('d M Y, H:i')
                : '-',
            'next_follow_up' => $latestFU && $latestFU->next_follow_up
                ? \Carbon\Carbon::parse($latestFU->next_follow_up)->format('d M Y, H:i')
                : '-',
            'next_follow_up_date' => $latestFU && $latestFU->next_follow_up
                ? \Carbon\Carbon::parse($latestFU->next_follow_up)->toDateString()
                : null,
            'created_at'     => $p->created_at ? $p->created_at->format('d M Y') : '-',
        ];
    }
}
