<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\FollowUp;
use App\Models\Prospek;
use App\Models\ProspekTimeline;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FollowUpController extends Controller
{
    /**
     * List prospects that need follow-up, categorized by timing.
     * Scoped to the logged-in Sales user's prospects.
     */
    public function index(Request $request): View
    {
        $user  = auth()->user();
        $today = now()->toDateString();

        $allProspects = Prospek::with(['cs', 'sales', 'owner', 'latestFollowUp'])
            ->where(function ($q) use ($user) {
                $q->where('sales_id', $user->id)
                  ->orWhere('owner_id', $user->id)
                  ->orWhere(function ($sub) {
                      $sub->where(function ($s) {
                          $s->whereHas('sales', function ($sq) {
                              $sq->whereIn('role', ['Sales', 'sales']);
                          })->orWhereHas('owner', function ($oq) {
                              $oq->whereIn('role', ['Sales', 'sales']);
                          });
                      });
                  });
            })
            ->withCount('followUps')
            ->orderBy('updated_at', 'desc')
            ->get();

        $prospects = [
            'uncontacted' => [],
            'today'       => [],
            'overdue'     => [],
            'unscheduled' => [],
            'upcoming'    => [],
            'others'      => [],
            'done'        => [],
        ];

        foreach ($allProspects as $p) {
            $latestFU         = $p->latestFollowUp ?? ($p->relationLoaded('followUps') ? $p->followUps->first() : null);
            $nextFollowUpDate = $latestFU && $latestFU->next_follow_up
                ? Carbon::parse($latestFU->next_follow_up)->toDateString()
                : null;
            $hasHistory       = ($p->follow_ups_count > 0) || ($latestFU !== null);
            $isDone           = in_array(strtoupper($p->status), ['LUNAS', 'DINGIN']);
            $authId           = $user ? $user->id : 0;
            $isOwn            = (bool) ($authId && ($p->sales_id == $authId || $p->owner_id == $authId));
            $salesRole        = strtolower(optional($p->sales)->role ?? '');
            $ownerRole        = strtolower(optional($p->owner)->role ?? '');
            $hasSalesOwner    = ($salesRole === 'sales') || ($ownerRole === 'sales');
            $isOther          = (bool) ($authId && !$isOwn && $hasSalesOwner);

            $row = $this->formatProspekRow($p, $latestFU);

            if ($isDone) {
                $prospects['done'][] = $row;
            } else {
                if (!$hasHistory) {
                    $prospects['uncontacted'][] = $row;
                } elseif ($nextFollowUpDate === null) {
                    $prospects['unscheduled'][] = $row;
                } elseif ($nextFollowUpDate === $today) {
                    $prospects['today'][] = $row;
                } elseif ($nextFollowUpDate < $today) {
                    $prospects['overdue'][] = $row;
                } else {
                    $prospects['upcoming'][] = $row;
                }

                if ($isOther) {
                    $prospects['others'][] = $row;
                }
            }
        }

        $metodeOptions = FollowUp::METODE_OPTIONS;
        $statuses      = Prospek::getActiveStages();

        return view('follow-up.index', compact('prospects', 'metodeOptions', 'statuses'));
    }

    /**
     * Store a new follow-up record.
     *
     * Authorization rules (backend enforced):
     * - User must be authenticated Sales
     * - Prospect must have sales_id = auth()->id() (active handler)
     * - If prospect's handler is CS only, Sales cannot follow-up
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'prospek_id'    => 'required|exists:prospeks,id',
            'metode'        => 'required|in:WhatsApp,Telepon,Meeting,Email',
            'hasil'         => 'required|string|max:255',
            'catatan'       => 'required|string|max:2000',
            'status'        => 'required|string|max:255',
            'next_follow_up'=> 'nullable|date',
        ]);

        $prospek = Prospek::findOrFail($validated['prospek_id']);

        // Backend authorization: must be active handler (Sales or CS)
        \Illuminate\Support\Facades\Gate::authorize('followUp', $prospek);

        $oldStatus = $prospek->status;
        $newStatus = $validated['status'];

        $actorId = auth()->id();
        if (!$actorId) {
            abort(401, 'Silakan login terlebih dahulu.');
        }

        // Save follow-up record - executor is authenticated user, sales_id and owner_id are untouched
        FollowUp::create([
            'prospek_id'    => $prospek->id,
            'user_id'       => $actorId,
            'metode'        => $validated['metode'],
            'tanggal'       => now(),
            'hasil'         => $validated['hasil'],
            'catatan'       => $validated['catatan'],
            'next_follow_up'=> $validated['next_follow_up'] ?? null,
        ]);

        $prospek->increment('active_follow_up_count');
        $prospek->increment('follow_up_count');

        // Update prospect status if changed
        if (strtoupper(trim((string)$oldStatus)) !== strtoupper(trim((string)$newStatus))) {
            \Illuminate\Support\Facades\Gate::authorize('updateStatus', $prospek);

            // Prevent Sales from setting status directly to LUNAS (requires CS / closing payment)
            if (strtoupper($newStatus) === 'LUNAS' && strtolower(auth()->user()->role ?? '') === 'sales') {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['status' => 'Status LUNAS hanya dapat diproses oleh CS setelah pelunasan pembayaran. Sales dapat memilih status CLOSING.']);
            }

            $stagesMap = Prospek::getDynamicStagesMap();
            $prospek->update([
                'status'       => $newStatus,
                'stage_number' => $stagesMap[$newStatus] ?? $prospek->stage_number,
            ]);

            ProspekTimeline::create([
                'prospek_id'   => $prospek->id,
                'user_id'      => $actorId,
                'title'        => 'Follow Up & Update Status',
                'notes'        => 'Follow up via ' . $validated['metode'] . '. Status berubah dari ' . $oldStatus . ' menjadi ' . $newStatus . '. Hasil: ' . $validated['hasil'] . '. Catatan: ' . $validated['catatan'],
                'status_before' => $oldStatus,
                'status_after'  => $newStatus,
                'time'          => now(),
            ]);
        } else {
            // Log follow-up even without status change
            ProspekTimeline::create([
                'prospek_id'   => $prospek->id,
                'user_id'      => $actorId,
                'title'        => 'Follow Up via ' . $validated['metode'],
                'notes'        => 'Hasil: ' . $validated['hasil'] . '. Catatan: ' . $validated['catatan'],
                'status_before' => $oldStatus,
                'status_after'  => $prospek->status,
                'time'          => now(),
            ]);
        }

        $prospek->touch();

        return redirect()->back()
            ->with('success', 'Follow-up berhasil disimpan!');
    }

    // ─── Private Helpers ─────────────────────────────────────────────────────

    private function formatProspekRow(\App\Models\Prospek $p, ?FollowUp $latestFU): array
    {
        return [
            'id'             => $p->id,
            'name'           => $p->name,
            'sales_id'       => $p->sales_id,
            'owner_id'       => $p->owner_id,
            'cs_id'          => $p->cs_id,
            'type'           => $p->type,
            'pic'            => $p->pic ?? '-',
            'whatsapp'       => $p->whatsapp ?? '-',
            'status'         => $p->status,
            'active_takeover' => $p->activeHandlerLabel(),
            'last_activity'  => $p->updated_at ? $p->updated_at->diffForHumans() : '-',
            'last_contact'   => $latestFU && $latestFU->tanggal
                ? Carbon::parse($latestFU->tanggal)->format('d M Y, H:i')
                : '-',
            'next_follow_up' => $latestFU && $latestFU->next_follow_up
                ? Carbon::parse($latestFU->next_follow_up)->format('d M Y, H:i')
                : '-',
            'next_follow_up_date' => $latestFU && $latestFU->next_follow_up
                ? Carbon::parse($latestFU->next_follow_up)->toDateString()
                : null,
            'metode_terakhir' => $latestFU ? ($latestFU->metode ?? '-') : '-',
            'hasil_terakhir'  => $latestFU ? ($latestFU->hasil ?? '-') : '-',
            'notes'           => $latestFU && $latestFU->catatan ? $latestFU->catatan : ($p->notes ?? '-'),
            'sales_name'      => $p->sales ? $p->sales->name : ($p->owner ? $p->owner->name : '-'),
            'owner'           => $p->owner ? $p->owner->name : 'Sistem',
            'is_own'          => (bool) (auth()->id() && ($p->sales_id == auth()->id() || $p->owner_id == auth()->id())),
            'is_other'        => (bool) (auth()->id() && !($p->sales_id == auth()->id() || $p->owner_id == auth()->id()) && (strtolower(optional($p->sales)->role ?? '') === 'sales' || strtolower(optional($p->owner)->role ?? '') === 'sales')),
            'follow_up_count' => $p->follow_ups_count ?? ($p->relationLoaded('followUps') ? $p->followUps->count() : 0),
            'has_follow_up_history' => ($p->follow_ups_count > 0) || ($latestFU !== null),
            'can_update_status' => (bool) (auth()->user() && auth()->user()->can('updateStatus', $p)),
        ];
    }
}
