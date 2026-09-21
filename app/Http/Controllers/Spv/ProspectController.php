<?php

namespace App\Http\Controllers\Spv;

use App\Http\Controllers\Controller;
use App\Models\Prospek;
use App\Models\ProspekTimeline;
use App\Models\Sekolah;
use App\Models\Perusahaan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ProspectController extends Controller
{
    /**
     * List all prospects belonging to the SPV's team.
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

        // Filter by Sales handler
        if ($request->filled('sales_id') && $request->sales_id !== 'all') {
            $query->where('sales_id', $request->sales_id);
        }

        // Filter by Status
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Search by keyword
        if ($request->filled('q')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->q . '%')
                  ->orWhere('pic', 'like', '%' . $request->q . '%')
                  ->orWhere('whatsapp', 'like', '%' . $request->q . '%');
            });
        }

        $prospects = $query->orderBy('updated_at', 'desc')->get()
            ->map(fn ($p) => $this->formatProspek($p))
            ->toArray();

        $teamSales = User::whereIn('id', $teamMemberIds)->where('role', 'Sales')->get();
        $statuses = array_keys(Prospek::STAGES);
        $lostReasons = Prospek::LOST_REASONS;

        return view('spv.prospek.index', compact(
            'prospects',
            'teamSales',
            'statuses',
            'lostReasons'
        ));
    }

    /**
     * Show form to create a new prospect for team.
     */
    public function create(): View
    {
        $user = auth()->user();
        $teamSales = User::whereIn('id', $user->teamMemberIds())->where('role', 'Sales')->get();
        $sekolahs = Sekolah::where('status', 'Aktif')->orderBy('nama')->get();
        $perusahaans = Perusahaan::where('status', 'Aktif')->orderBy('nama')->get();
        $statuses = array_keys(Prospek::STAGES);
        $lostReasons = Prospek::LOST_REASONS;

        return view('spv.prospek.create', compact('sekolahs', 'perusahaans', 'statuses', 'lostReasons', 'teamSales'));
    }

    /**
     * Store new prospect and assign to Sales.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'          => 'nullable|string|max:255',
            'type'          => 'required|in:Sekolah,Corporate,Individu',
            'sekolah_id'    => 'nullable|exists:sekolahs,id',
            'perusahaan_id' => 'nullable|exists:perusahaans,id',
            'sales_id'      => 'nullable|exists:users,id',
            'category'      => 'nullable|string|max:100',
            'pic'           => 'required|string|max:255',
            'pic_phone'     => 'nullable|string|max:20',
            'whatsapp'      => 'required|string|max:20',
            'status'        => 'required|string|max:255',
            'potential'     => 'nullable|string|max:500',
            'ai_training'   => 'nullable|string|max:255',
            'notes'         => 'nullable|string',
            'source'        => 'nullable|string|max:100',
        ]);

        $user = auth()->user();

        $name = $validated['name'] ?? 'Prospek Baru';
        if ($validated['type'] === 'Sekolah' && !empty($validated['sekolah_id'])) {
            $sekolah = Sekolah::find($validated['sekolah_id']);
            if ($sekolah) $name = $sekolah->nama;
        } elseif ($validated['type'] === 'Corporate' && !empty($validated['perusahaan_id'])) {
            $perusahaan = Perusahaan::find($validated['perusahaan_id']);
            if ($perusahaan) $name = $perusahaan->nama;
        }

        $assignedSalesId = $validated['sales_id'] ?? null;
        $wilayahId = $user->wilayah_id;

        DB::transaction(function () use ($validated, $user, $name, $assignedSalesId, $wilayahId) {
            $stageNumber = Prospek::STAGES[$validated['status']] ?? 1;

            $prospek = Prospek::create([
                'name'               => $name,
                'type'               => $validated['type'],
                'category'           => $validated['category'] ?? null,
                'sekolah_id'         => $validated['type'] === 'Sekolah' ? ($validated['sekolah_id'] ?? null) : null,
                'perusahaan_id'      => $validated['type'] === 'Corporate' ? ($validated['perusahaan_id'] ?? null) : null,
                'pic'                => $validated['pic'],
                'pic_phone'          => $validated['pic_phone'] ?? null,
                'whatsapp'           => $validated['whatsapp'],
                'status'             => $validated['status'],
                'stage_number'       => $stageNumber,
                'potential'          => $validated['potential'] ?? null,
                'ai_training'        => $validated['ai_training'] ?? null,
                'notes'              => $validated['notes'] ?? null,
                'source'             => $validated['source'] ?? 'Supervisor',
                'sales_id'           => $assignedSalesId,
                'cs_id'              => null,
                'wilayah_id'         => $wilayahId,
                'owner_id'           => $user->id,
            ]);

            $assignedSalesName = $assignedSalesId ? User::find($assignedSalesId)?->name : 'Belum Ditugaskan';

            ProspekTimeline::create([
                'prospek_id'   => $prospek->id,
                'user_id'      => $user->id,
                'title'        => 'Prospek Dibuat oleh SPV',
                'notes'        => 'Prospek dibuat oleh Supervisor ' . $user->name . ' dan di-assign ke ' . $assignedSalesName,
                'status_after' => $prospek->status,
                'time'         => now(),
            ]);
        });

        return redirect()->route('spv.prospek.index')->with('success', 'Prospek baru berhasil ditambahkan!');
    }

    /**
     * Show detail of a prospect.
     */
    public function show(Prospek $prospek): View
    {
        Gate::authorize('view', $prospek);

        $prospek->load(['sales', 'cs', 'owner', 'followUps' => function ($q) {
            $q->orderBy('tanggal', 'desc')->with('user');
        }, 'timelines' => function ($q) {
            $q->orderBy('time', 'desc')->with('user');
        }]);

        $user = auth()->user();
        $teamMembers = User::whereIn('id', $user->teamMemberIds())->get();
        $teamSales = $teamMembers->where('role', 'Sales');
        $teamCs = $teamMembers->where('role', 'CS');

        $allStages = [
            ['name' => 'Cold Lead',           'number' => 1],
            ['name' => 'Interested',          'number' => 2],
            ['name' => 'Follow Up',           'number' => 3],
            ['name' => 'Beli Formulir',       'number' => 4],
            ['name' => 'Pembayaran Termin 1', 'number' => 5],
            ['name' => 'Closing',             'number' => 6],
        ];

        $prospect = $this->formatProspekDetail($prospek);
        $lostReasons = Prospek::LOST_REASONS;
        $metodeOptions = \App\Models\FollowUp::METODE_OPTIONS ?? ['WhatsApp', 'Telepon', 'Kunjungan Langsung', 'Email', 'Zoom/GMeet'];

        return view('spv.prospek.show', compact(
            'prospect',
            'allStages',
            'lostReasons',
            'metodeOptions',
            'teamSales',
            'teamCs'
        ));
    }

    /**
     * Update prospect details.
     */
    public function update(Request $request, Prospek $prospek): RedirectResponse
    {
        Gate::authorize('update', $prospek);

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

        ProspekTimeline::create([
            'prospek_id'   => $prospek->id,
            'user_id'      => auth()->id(),
            'title'        => 'Data Diperbarui SPV',
            'notes'        => 'Informasi prospek diperbarui oleh SPV ' . auth()->user()->name,
            'status_after' => $prospek->status,
            'time'         => now(),
        ]);

        return redirect()->route('spv.prospek.show', $prospek)->with('success', 'Data prospek berhasil diperbarui!');
    }

    /**
     * Update pipeline status directly by SPV.
     */
    public function updateStatus(Request $request, Prospek $prospek): RedirectResponse
    {
        Gate::authorize('updateStatus', $prospek);

        $validated = $request->validate([
            'status' => 'required|string|max:255',
        ]);

        $oldStatus = $prospek->status;
        $newStatus = $validated['status'];

        $prospek->update([
            'status'       => $newStatus,
            'stage_number' => Prospek::STAGES[$newStatus] ?? $prospek->stage_number,
        ]);

        if ($oldStatus !== $newStatus) {
            ProspekTimeline::create([
                'prospek_id'   => $prospek->id,
                'user_id'      => auth()->id(),
                'title'        => 'Status Diubah oleh SPV',
                'notes'        => 'SPV mengubah status dari ' . $oldStatus . ' menjadi ' . $newStatus,
                'status_after' => $newStatus,
                'time'         => now(),
            ]);
        }

        return redirect()->route('spv.prospek.show', $prospek)->with('success', 'Status pipeline berhasil diperbarui!');
    }

    /**
     * Reassign prospect to a different Sales or CS handler.
     */
    public function reassign(Request $request, Prospek $prospek): RedirectResponse
    {
        Gate::authorize('takeover', $prospek);

        $validated = $request->validate([
            'sales_id' => 'nullable|exists:users,id',
            'cs_id'    => 'nullable|exists:users,id',
            'reason'   => 'nullable|string|max:255',
        ]);

        $oldSales = $prospek->sales ? $prospek->sales->name : 'None';
        $newSales = !empty($validated['sales_id']) ? User::find($validated['sales_id'])?->name : $oldSales;

        $prospek->update([
            'sales_id' => $validated['sales_id'] ?? $prospek->sales_id,
            'cs_id'    => $validated['cs_id'] ?? $prospek->cs_id,
        ]);

        ProspekTimeline::create([
            'prospek_id'   => $prospek->id,
            'user_id'      => auth()->id(),
            'title'        => 'Re-assign Handler oleh SPV',
            'notes'        => 'SPV mengalihkan penanganan prospek ke ' . $newSales . ($validated['reason'] ? ' (Alasan: ' . $validated['reason'] . ')' : ''),
            'status_after' => $prospek->status,
            'time'         => now(),
        ]);

        return redirect()->route('spv.prospek.show', $prospek)->with('success', 'Penugasan prospek berhasil dialihkan!');
    }

    /**
     * Delete a prospect.
     */
    public function destroy(Prospek $prospek): RedirectResponse
    {
        Gate::authorize('delete', $prospek);

        $prospek->timelines()->delete();
        $prospek->followUps()->delete();
        $prospek->delete();

        return redirect()->route('spv.prospek.index')->with('success', 'Prospek berhasil dihapus.');
    }

    /**
     * Format a Prospek model for view.
     */
    private function formatProspek(Prospek $p): array
    {
        $latestFollowUp = $p->followUps->first();

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
            'sales_id'       => $p->sales_id,
            'takeover_sales' => $p->sales ? $p->sales->name : null,
            'takeover_cs'    => $p->cs ? $p->cs->name : null,
            'active_takeover' => $p->activeHandlerLabel(),
            'owner'          => $p->owner ? $p->owner->name : 'Sistem',
            'last_activity'  => $p->updated_at->diffForHumans(),
            'potential'      => $p->potential ?? '-',
            'source'         => $p->source ?? '-',
            'ai_training'    => $p->ai_training ?? '-',
            'notes'          => $p->notes ?? '',
            'created_at'     => $p->created_at ? $p->created_at->format('d M Y') : '-',
            'last_contact'   => $latestFollowUp
                ? \Carbon\Carbon::parse($latestFollowUp->tanggal)->format('d M Y, H:i')
                : '-',
            'next_follow_up' => $latestFollowUp && $latestFollowUp->next_follow_up
                ? \Carbon\Carbon::parse($latestFollowUp->next_follow_up)->format('d M Y, H:i')
                : '-',
        ];
    }

    /**
     * Format a Prospek model for detail view.
     */
    private function formatProspekDetail(Prospek $p): array
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
