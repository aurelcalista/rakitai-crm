<?php

namespace App\Http\Controllers\Spv;

use App\Http\Controllers\Controller;
use App\Models\Prospek;
use App\Models\ProspekTimeline;
use App\Models\User;
use App\Models\MasterData;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class PipelineController extends Controller
{
    /**
     * SPV Pipeline Kanban Board - shows team's prospects with filter by Sales.
     */
    public function index(Request $request): View
    {
        $user = auth()->user();
        $teamMemberIds = $user->teamMemberIds();

        $query = Prospek::with(['sales', 'cs', 'owner', 'followUps' => function ($q) {
            $q->orderBy('tanggal', 'desc')->limit(1);
        }])->where(function ($q) use ($teamMemberIds, $user) {
            $q->whereIn('sales_id', $teamMemberIds)
              ->orWhereIn('owner_id', $teamMemberIds)
              ->orWhereIn('cs_id', $teamMemberIds);
            if ($user->wilayah_id) {
                $q->orWhere('wilayah_id', $user->wilayah_id);
            }
        });

        // Filter by specific Sales member
        if ($request->filled('sales_id') && $request->sales_id !== 'all') {
            $query->where('sales_id', $request->sales_id);
        }

        $prospectsRaw = $query->orderBy('updated_at', 'desc')->get();
        $prospects = $prospectsRaw->map(fn ($p) => $this->formatProspek($p))->toArray();

        $pipelineStages = Prospek::PIPELINE_8_STAGES;

        $teamSales = User::whereIn('id', $teamMemberIds)->where('role', 'Sales')->get();

        return view('spv.pipeline.index', compact('prospects', 'pipelineStages', 'teamSales'));
    }

    /**
     * Update prospect status from pipeline board by SPV.
     */
    public function updateStatus(Request $request): JsonResponse
    {
        $request->validate([
            'prospek_id' => 'required|exists:prospeks,id',
            'status'     => 'required|string|in:' . implode(',', Prospek::ACTIVE_STAGES),
        ]);

        $prospek = Prospek::findOrFail($request->prospek_id);
        $user = auth()->user();

        \Illuminate\Support\Facades\Gate::authorize('updateStatus', $prospek);

        if ($request->status === 'LUNAS' && !\App\Services\ProspekService::isClosingValid($prospek)) {
            return response()->json([
                'success' => false,
                'message' => 'Status LUNAS tidak valid. Prospek harus melunasi Pembayaran Formulir dan Termin 1.',
            ], 422);
        }

        $oldStatus = $prospek->status;
        $prospek->status = $request->status;
        $prospek->stage_number = Prospek::STAGES[$request->status] ?? $prospek->stage_number;
        $prospek->save();

        ProspekTimeline::create([
            'prospek_id'    => $prospek->id,
            'user_id'       => $user->id,
            'title'         => 'Status Diperbarui via Pipeline Board (SPV)',
            'notes'         => "SPV mengubah status dari {$oldStatus} menjadi {$prospek->status}",
            'status_before' => $oldStatus,
            'status_after'  => $prospek->status,
            'time'          => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Status berhasil diubah menjadi {$prospek->status}",
            'status'  => $prospek->status,
        ]);
    }

    private function formatProspek(Prospek $p): array
    {
        $latestFU = $p->followUps->first();
        $normalizedStatus = strtoupper(trim($p->status));
        $stageMap = [
            'BARU'                => 'BARU',
            'COLD LEAD'           => 'BARU',
            'KONTAK'              => 'KONTAK',
            'INTERESTED'          => 'KONTAK',
            'HANGAT'              => 'HANGAT',
            'FOLLOW UP'           => 'HANGAT',
            'FOLLOW UP 1'         => 'HANGAT',
            'PANAS'               => 'PANAS',
            'NEGOSIASI'           => 'PANAS',
            'FORMULIR'            => 'FORMULIR',
            'BELI FORMULIR'       => 'FORMULIR',
            'BERKAS'              => 'BERKAS',
            'PEMBAYARAN TERMIN 1' => 'BERKAS',
            'LUNAS'               => 'LUNAS',
            'MENDAFTAR'           => 'LUNAS',
            'CLOSING'             => 'LUNAS',
            'DINGIN'              => 'DINGIN',
            'LOST'                => 'DINGIN',
            'DITOLAK/BATAL'       => 'DINGIN',
            'DITOLAK / BATAL'     => 'DINGIN',
        ];
        $canonicalStatus = $stageMap[$normalizedStatus] ?? (in_array($normalizedStatus, Prospek::PIPELINE_8_STAGES) ? $normalizedStatus : 'BARU');

        return [
            'id'             => $p->id,
            'name'           => $p->name,
            'type'           => $p->type,
            'pic'            => $p->pic ?? '-',
            'whatsapp'       => $p->whatsapp ?? '-',
            'status'         => $canonicalStatus,
            'raw_status'     => $p->status,
            'stage_number'   => Prospek::STAGES[$canonicalStatus] ?? (Prospek::STAGES[$p->status] ?? 1),
            'notes'          => $p->notes ?? '',
            'sales_id'       => $p->sales_id,
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
