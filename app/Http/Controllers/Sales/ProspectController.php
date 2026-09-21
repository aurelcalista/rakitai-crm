<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Prospek;
use App\Models\ProspekTimeline;
use App\Models\Sekolah;
use App\Models\Perusahaan;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class ProspectController extends Controller
{
    /**
     * List prospects handled by the logged-in Sales user.
     * Includes filter by status if provided.
     */
    public function index(Request $request): View
    {
        $user = auth()->user();

        $query = Prospek::with(['cs', 'owner', 'sekolah', 'perusahaan', 'sales', 'followUps' => function ($q) {
            $q->orderBy('tanggal', 'desc')->limit(1);
        }])
            ->where('sales_id', $user->id);

        // Optional status filter
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Optional search by name
        if ($request->filled('q')) {
            $query->where('name', 'like', '%' . $request->q . '%');
        }

        $prospects = $query->orderBy('updated_at', 'desc')->get()
            ->map(fn ($p) => $this->formatProspek($p))
            ->toArray();

        $statuses = array_keys(Prospek::STAGES);
        $lostReasons = Prospek::LOST_REASONS;

        return view('prospek.index', compact('prospects', 'statuses', 'lostReasons'));
    }

    /**
     * Show form to create a new prospect.
     */
    public function create(): View
    {
        $sekolahs   = Sekolah::where('status', 'Aktif')->orderBy('nama')->get();
        $perusahaans = Perusahaan::where('status', 'Aktif')->orderBy('nama')->get();
        $statuses   = array_keys(Prospek::STAGES);
        $lostReasons = Prospek::LOST_REASONS;

        return view('prospek.create', compact('sekolahs', 'perusahaans', 'statuses', 'lostReasons'));
    }

    /**
     * Store a new prospect created by the Sales user.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'     => 'nullable|string|max:255',
            'type'     => 'required|in:Sekolah,Corporate,Individu',
            'sekolah_id' => 'nullable|exists:sekolahs,id',
            'perusahaan_id' => 'nullable|exists:perusahaans,id',
            'category' => 'nullable|string|max:100',
            'pic'      => 'required|string|max:255',
            'pic_phone'=> 'nullable|string|max:20',
            'whatsapp' => 'required|string|max:20',
            'status'   => 'required|string|max:255',
            'potential'=> 'nullable|string|max:500',
            'ai_training' => 'nullable|string|max:255',
            'notes'    => 'nullable|string',
            'source'   => 'nullable|string|max:100',
        ]);

        $user = auth()->user();

        // Resolve name from Master Data if applicable
        $name = $validated['name'] ?? $validated['pic'] ?? 'Prospek Baru';

        DB::transaction(function () use ($validated, $user, $name) {
            // Auto-assign CS from same wilayah if Sales has a wilayah
            $csId     = null;
            $wilayahId = $user->wilayah_id;

            if ($wilayahId) {
                $cs = \App\Models\User::where('role', 'CS')
                    ->where('wilayah_id', $wilayahId)
                    ->first();
                $csId = $cs?->id;
            }

            $stageNumber = Prospek::STAGES[$validated['status']] ?? 1;

            $prospek = Prospek::create([
                'name'         => $name,
                'type'         => $validated['type'],
                'category'     => $validated['category'] ?? null,
                'sekolah_id'   => $validated['type'] === 'Sekolah' ? ($validated['sekolah_id'] ?? null) : null,
                'perusahaan_id'=> $validated['type'] === 'Corporate' ? ($validated['perusahaan_id'] ?? null) : null,
                'pic'          => $validated['pic'],
                'pic_phone'    => $validated['pic_phone'] ?? null,
                'whatsapp'     => $validated['whatsapp'],
                'status'       => $validated['status'],
                'stage_number' => $stageNumber,
                'potential'    => $validated['potential'] ?? null,
                'ai_training'  => $validated['ai_training'] ?? null,
                'notes'        => $validated['notes'] ?? null,
                'source'       => $validated['source'] ?? null,
                'sales_id'     => $user->id,
                'cs_id'        => null, // Will be set during takeover
                'wilayah_id'   => $wilayahId,
                'owner_id'     => $user->id,
                'needs_visit_report' => true,
            ]);

            // Record creation activity log
            ProspekTimeline::create([
                'prospek_id'  => $prospek->id,
                'user_id'     => $user->id,
                'title'       => 'Prospek Dibuat',
                'notes'       => 'Prospek baru ditambahkan oleh ' . $user->name . ' (Sales)',
                'status_after' => $prospek->status,
                'time'         => now(),
            ]);
        });

        return redirect()->route('sales.prospek.index')
            ->with('success', 'Prospek baru berhasil ditambahkan!');
    }

    /**
     * Show detail of a prospect.
     * Authorization: Sales can view if they are the handler, owner, or cs handler.
     */
    public function show(Prospek $prospek): View
    {
        \Illuminate\Support\Facades\Gate::authorize('view', $prospek);

        $prospek->load(['sales', 'cs', 'owner', 'followUps' => function ($q) {
            $q->orderBy('tanggal', 'desc')->with('user');
        }, 'timelines' => function ($q) {
            $q->orderBy('time', 'desc')->with('user');
        }]);

        $isHandler = $prospek->isHandledBySales(auth()->user());

        $allStages = [
            ['name' => 'Cold Lead',           'number' => 1],
            ['name' => 'Interested',          'number' => 2],
            ['name' => 'Follow Up',           'number' => 3],
            ['name' => 'Beli Formulir',       'number' => 4],
            ['name' => 'Pembayaran Termin 1', 'number' => 5],
            ['name' => 'Closing',             'number' => 6],
        ];

        $prospect   = $this->formatProspekDetail($prospek);
        $lostReasons = Prospek::LOST_REASONS;
        $metodeOptions = \App\Models\FollowUp::METODE_OPTIONS;

        return view('prospek.show', compact(
            'prospect',
            'isHandler',
            'allStages',
            'lostReasons',
            'metodeOptions'
        ));
    }

    /**
     * Update editable prospect fields.
     * Only allowed for active Sales handler.
     */
    public function update(Request $request, Prospek $prospek): RedirectResponse
    {
        \Illuminate\Support\Facades\Gate::authorize('update', $prospek);

        $validated = $request->validate([
            'pic'         => 'required|string|max:255',
            'pic_phone'   => 'nullable|string|max:20',
            'whatsapp'    => 'required|string|max:20',
            'potential'   => 'nullable|string|max:500',
            'ai_training' => 'nullable|string|max:255',
            'notes'       => 'nullable|string',
            'category'    => 'nullable|string|max:100',
        ]);

        $prospek->update($validated);

        // Log the update
        ProspekTimeline::create([
            'prospek_id'  => $prospek->id,
            'user_id'     => auth()->id(),
            'title'       => 'Data Prospek Diperbarui',
            'notes'       => 'Informasi prospek diperbarui oleh ' . auth()->user()->name,
            'status_after' => $prospek->status,
            'time'         => now(),
        ]);

        return redirect()->route('sales.prospek.show', $prospek)
            ->with('success', 'Data prospek berhasil diperbarui!');
    }

    /**
     * Update pipeline status.
     * Only active Sales handler can change status.
     */
    public function updateStatus(Request $request, Prospek $prospek): RedirectResponse
    {
        \Illuminate\Support\Facades\Gate::authorize('updateStatus', $prospek);

        $validated = $request->validate([
            'status' => 'required|string|max:255',
        ]);

        $oldStatus = $prospek->status;
        $newStatus = $validated['status'];

        // Prevent downgrading to Lost via this endpoint — use markLost instead
        if ($newStatus === 'Lost') {
            return redirect()->back()
                ->withErrors(['status' => 'Gunakan tombol "Mark as Lost" untuk mengubah ke status Lost.']);
        }

        $prospek->update([
            'status'       => $newStatus,
            'stage_number' => Prospek::STAGES[$newStatus] ?? $prospek->stage_number,
        ]);

        if ($oldStatus !== $newStatus) {
            ProspekTimeline::create([
                'prospek_id'   => $prospek->id,
                'user_id'      => auth()->id(),
                'title'        => 'Status Pipeline Diperbarui',
                'notes'        => 'Status berubah dari ' . $oldStatus . ' menjadi ' . $newStatus,
                'status_before' => $oldStatus,
                'status_after'  => $newStatus,
                'time'          => now(),
            ]);
        }

        return redirect()->back()->with('success', 'Status prospek berhasil diperbarui!');
    }

    /**
     * Mark a prospect as Lost with reason and note.
     * Only active Sales handler can mark as Lost.
     */
    public function markLost(Request $request, Prospek $prospek): RedirectResponse
    {
        \Illuminate\Support\Facades\Gate::authorize('markLost', $prospek);

        $validated = $request->validate([
            'lost_reason' => 'required|in:' . implode(',', Prospek::LOST_REASONS),
            'lost_note'   => 'nullable|string|max:1000',
        ]);

        $oldStatus = $prospek->status;

        $prospek->update([
            'status'       => 'Lost',
            'stage_number' => 0,
            'lost_reason'  => $validated['lost_reason'],
            'lost_note'    => $validated['lost_note'],
        ]);

        ProspekTimeline::create([
            'prospek_id'   => $prospek->id,
            'user_id'      => auth()->id(),
            'title'        => 'Prospek Ditandai Lost',
            'notes'        => 'Alasan: ' . $validated['lost_reason'] . ($validated['lost_note'] ? '. Catatan: ' . $validated['lost_note'] : ''),
            'status_before' => $oldStatus,
            'status_after'  => 'Lost',
            'time'          => now(),
        ]);

        return redirect()->route('sales.prospek.index')
            ->with('success', 'Prospek telah ditandai sebagai Lost.');
    }

    /**
     * Handover prospect from Sales to CS.
     */
    public function takeover(Request $request, Prospek $prospek): RedirectResponse
    {
        \Illuminate\Support\Facades\Gate::authorize('takeover', $prospek);

        // Find a CS to assign to. Try same wilayah first, otherwise pick any active CS.
        $cs = \App\Models\User::where('role', 'CS')
            ->when($prospek->wilayah_id, function ($q) use ($prospek) {
                return $q->where('wilayah_id', $prospek->wilayah_id);
            })
            ->first();

        if (!$cs) {
            $cs = \App\Models\User::where('role', 'CS')->first();
        }

        if (!$cs) {
            return redirect()->back()->withErrors(['cs_id' => 'Tidak ada user CS yang tersedia untuk menerima prospek.']);
        }

        $prospek->update([
            'cs_id' => $cs->id,
        ]);

        ProspekTimeline::create([
            'prospek_id'   => $prospek->id,
            'user_id'      => auth()->id(),
            'title'        => 'Prospek Diserahkan ke CS',
            'notes'        => 'Prospek diserahkan ke CS: ' . $cs->name,
            'status_after'  => $prospek->status,
            'time'          => now(),
        ]);

        return redirect()->back()->with('success', 'Prospek berhasil diserahkan ke CS ' . $cs->name);
    }

    // ─── Private Helpers ─────────────────────────────────────────────────────

    /**
     * Format a Prospek model to array shape for Blade views (list view).
     */
    private function formatProspek(\App\Models\Prospek $p): array
    {
        $latestFollowUp = $p->followUps->first();

        return [
            'id'             => $p->id,
            'name'           => $p->name,
            'type'           => $p->type,
            'sekolah_name'   => $p->type === 'Sekolah' ? ($p->sekolah ? $p->sekolah->nama : '-') : ($p->type === 'Corporate' ? ($p->perusahaan ? $p->perusahaan->nama : '-') : '-'),
            'sales_name'     => $p->sales ? $p->sales->name : '-',
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
            'notes'          => $p->notes ?? '',
            'source'         => $p->source ?? '-',
            'lost_reason'    => $p->lost_reason,
            'lost_note'      => $p->lost_note,
            'created_at'     => $p->created_at ? $p->created_at->format('d M Y') : '-',
            'takeover_time'  => $p->updated_at ? $p->updated_at->format('d M Y, H:i') : '-',
            'last_contact'   => $latestFollowUp
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

    /**
     * Format a Prospek model for detail view (includes timeline).
     */
    private function formatProspekDetail(\App\Models\Prospek $p): array
    {
        $base = $this->formatProspek($p);

        $base['timeline'] = $p->timelines->map(function ($t) {
            return [
                'time'   => $t->time ? $t->time->format('d M Y, H:i') : '-',
                'title'  => $t->title,
                'notes'  => $t->notes,
                'status' => $t->status_after,
                'user'   => $t->user ? $t->user->name : 'Sistem',
                'role'   => $t->user ? $t->user->role : 'System',
            ];
        })->toArray();

        $base['follow_ups'] = $p->followUps->map(function ($f) {
            return [
                'tanggal'      => \Carbon\Carbon::parse($f->tanggal)->format('d M Y, H:i'),
                'metode'       => $f->metode ?? '-',
                'hasil'        => $f->hasil ?? '-',
                'catatan'      => $f->catatan ?? '-',
                'next_follow_up' => $f->next_follow_up
                    ? \Carbon\Carbon::parse($f->next_follow_up)->format('d M Y, H:i')
                    : '-',
                'user'         => $f->user ? $f->user->name : 'Sistem',
            ];
        })->toArray();

        return $base;
    }
}
