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

        $allProspects = Prospek::with(['cs', 'followUps' => function ($q) {
            $q->orderBy('tanggal', 'desc')->limit(1);
        }])
            ->where('sales_id', $user->id)
            ->whereNotIn('status', ['Lost']) // Lost prospects don't need follow-up
            ->get();

        $prospects = [
            'today'    => [],
            'upcoming' => [],
            'overdue'  => [],
            'done'     => [],
        ];

        foreach ($allProspects as $p) {
            $latestFU       = $p->followUps->first();
            $nextFollowUpDate = $latestFU && $latestFU->next_follow_up
                ? Carbon::parse($latestFU->next_follow_up)->toDateString()
                : null;

            $row = $this->formatProspekRow($p, $latestFU);

            if ($p->status === 'Closing') {
                $prospects['done'][] = $row;
            } elseif ($nextFollowUpDate === null) {
                // No scheduled follow-up → treat as overdue
                $prospects['overdue'][] = $row;
            } elseif ($nextFollowUpDate === $today) {
                $prospects['today'][] = $row;
            } elseif ($nextFollowUpDate > $today) {
                $prospects['upcoming'][] = $row;
            } else {
                $prospects['overdue'][] = $row;
            }
        }

        $metodeOptions = FollowUp::METODE_OPTIONS;
        $statuses      = array_keys(Prospek::STAGES);

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
            'status'        => 'required|in:Cold Lead,Interested,Follow Up,Beli Formulir,Pembayaran Termin 1,Closing',
            'next_follow_up'=> 'nullable|date|after_or_equal:today',
        ]);

        $prospek = Prospek::findOrFail($validated['prospek_id']);

        // Backend authorization: must be active Sales handler
        if ($prospek->sales_id !== auth()->id()) {
            abort(403, 'Anda bukan handler dari prospek ini sehingga tidak diizinkan membuat follow-up.');
        }

        $oldStatus = $prospek->status;
        $newStatus = $validated['status'];

        // Save follow-up record
        FollowUp::create([
            'prospek_id'    => $prospek->id,
            'user_id'       => auth()->id(),
            'metode'        => $validated['metode'],
            'tanggal'       => now(),
            'hasil'         => $validated['hasil'],
            'catatan'       => $validated['catatan'],
            'next_follow_up'=> $validated['next_follow_up'],
        ]);

        // Update prospect status if changed
        if ($oldStatus !== $newStatus) {
            $prospek->update([
                'status'       => $newStatus,
                'stage_number' => Prospek::STAGES[$newStatus] ?? $prospek->stage_number,
            ]);

            ProspekTimeline::create([
                'prospek_id'   => $prospek->id,
                'user_id'      => auth()->id(),
                'title'        => 'Follow Up & Update Status',
                'notes'        => 'Follow up via ' . $validated['metode'] . '. Status berubah dari ' . $oldStatus . ' menjadi ' . $newStatus . '. Hasil: ' . $validated['hasil'],
                'status_before' => $oldStatus,
                'status_after'  => $newStatus,
                'time'          => now(),
            ]);
        } else {
            // Log follow-up even without status change
            ProspekTimeline::create([
                'prospek_id'  => $prospek->id,
                'user_id'     => auth()->id(),
                'title'       => 'Follow Up via ' . $validated['metode'],
                'notes'       => $validated['hasil'] . '. ' . $validated['catatan'],
                'status_after' => $prospek->status,
                'time'         => now(),
            ]);
        }

        return redirect()->back()
            ->with('success', 'Follow-up berhasil disimpan!');
    }

    // ─── Private Helpers ─────────────────────────────────────────────────────

    private function formatProspekRow(\App\Models\Prospek $p, ?FollowUp $latestFU): array
    {
        return [
            'id'             => $p->id,
            'name'           => $p->name,
            'type'           => $p->type,
            'pic'            => $p->pic ?? '-',
            'whatsapp'       => $p->whatsapp ?? '-',
            'status'         => $p->status,
            'active_takeover' => $p->activeHandlerLabel(),
            'last_activity'  => $p->updated_at->diffForHumans(),
            'last_contact'   => $latestFU
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
        ];
    }
}
