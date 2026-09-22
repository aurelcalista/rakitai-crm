<?php

namespace App\Http\Controllers\Spv;

use App\Http\Controllers\Controller;
use App\Models\Prospek;
use App\Models\ProspekTimeline;
use App\Models\Sekolah;
use App\Models\Perusahaan;
use App\Models\Prodi;
use App\Models\Wilayah;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ProspectController extends Controller
{
    /**
     * List all prospects belonging to the SPV's team with search & filters.
     */
    public function index(Request $request): View
    {
        $user = auth()->user();
        $teamMemberIds = $user->teamMemberIds();

        $query = Prospek::with(['sales', 'cs', 'owner', 'sekolah', 'perusahaan', 'wilayah', 'followUps' => function ($q) {
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

        // Filter by Sekolah
        if ($request->filled('sekolah_id') && $request->sekolah_id !== 'all') {
            $query->where('sekolah_id', $request->sekolah_id);
        }

        // Filter by Wilayah
        if ($request->filled('wilayah_id') && $request->wilayah_id !== 'all') {
            $query->where('wilayah_id', $request->wilayah_id);
        }

        // Filter by Prodi
        if ($request->filled('prodi_id') && $request->prodi_id !== 'all') {
            $prodi = Prodi::find($request->prodi_id);
            if ($prodi) {
                $query->where(function ($q) use ($prodi) {
                    $q->where('potential', 'like', '%' . $prodi->nama . '%')
                      ->orWhere('category', 'like', '%' . $prodi->nama . '%')
                      ->orWhere('notes', 'like', '%' . $prodi->nama . '%');
                });
            }
        }

        // Filter by Sumber / Source
        if ($request->filled('source') && $request->source !== 'all') {
            $query->where('source', $request->source);
        }

        // Search by keyword (nama, pic, whatsapp)
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

        $teamSales   = User::whereIn('id', $teamMemberIds)->where('role', 'Sales')->get();
        $teamMembers = User::whereIn('id', $teamMemberIds)->whereIn('role', ['Sales', 'CS'])->orderBy('role')->orderBy('name')->get();
        $sekolahs    = Sekolah::with(['wilayah', 'kategori'])->where('status', 'Aktif')->orderBy('nama')->get();
        $perusahaans = Perusahaan::where('status', 'Aktif')->orderBy('nama')->get();
        $wilayahs    = Wilayah::orderBy('nama')->get();
        $prodis      = Prodi::orderBy('nama')->get();
        $statuses    = Prospek::PIPELINE_8_STAGES;
        $lostReasons = Prospek::LOST_REASONS;

        return view('spv.prospek.index', compact(
            'prospects',
            'teamSales',
            'teamMembers',
            'sekolahs',
            'perusahaans',
            'wilayahs',
            'prodis',
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
            ['name' => 'BARU',     'number' => 1],
            ['name' => 'KONTAK',   'number' => 2],
            ['name' => 'HANGAT',   'number' => 3],
            ['name' => 'PANAS',    'number' => 4],
            ['name' => 'FORMULIR', 'number' => 5],
            ['name' => 'BERKAS',   'number' => 6],
            ['name' => 'LUNAS',    'number' => 7],
            ['name' => 'DINGIN',   'number' => 8],
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
     * Reset follow_up_count menjadi 0 pada pemilik baru, riwayat lama tetap tersimpan di timeline.
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
            'sales_id'        => $validated['sales_id'] ?? $prospek->sales_id,
            'cs_id'           => $validated['cs_id'] ?? $prospek->cs_id,
            'follow_up_count' => 0, // Reset follow-up counter ke 0 bagi penangan baru
        ]);

        ProspekTimeline::create([
            'prospek_id'   => $prospek->id,
            'user_id'      => auth()->id(),
            'title'        => 'Re-alokasi Handler oleh SPV',
            'notes'        => 'SPV mengalihkan penanganan prospek ke ' . $newSales . ' (follow_up_count di-reset ke 0 untuk pemilik baru)' . ($validated['reason'] ? ' (Alasan: ' . $validated['reason'] . ')' : ''),
            'status_after' => $prospek->status,
            'time'         => now(),
        ]);

        return redirect()->route('spv.prospek.show', $prospek)->with('success', 'Penugasan prospek berhasil dialihkan dan counter follow-up direset ke 0.');
    }

    /**
     * Closing oleh SPV sesuai aturan bisnis (Maba Lunas: Formulir + Termin 1).
     * Kredit/owner lead (Pemilik Lead / owner_id dan sales_id) TETAP milik Sales awal.
     */
    public function closing(Request $request, Prospek $prospek): RedirectResponse
    {
        Gate::authorize('transaction', $prospek);

        $validated = $request->validate([
            'nominal_formulir' => 'nullable|numeric|min:0',
            'nominal_termin1'  => 'required|numeric|min:0',
            'tanggal'          => 'required|date',
            'notes'            => 'nullable|string|max:500',
        ]);

        $user = auth()->user();

        // 1. Catat transaksi formulir jika belum ada
        $hasFormulir = Transaksi::where('prospek_id', $prospek->id)->where('jenis', 'Beli Formulir')->exists();
        if (!$hasFormulir && !empty($validated['nominal_formulir']) && $validated['nominal_formulir'] > 0) {
            Transaksi::create([
                'prospek_id' => $prospek->id,
                'user_id'    => $user->id, // SPV pencatat
                'jenis'      => 'Beli Formulir',
                'nominal'    => $validated['nominal_formulir'],
                'tanggal'    => $validated['tanggal'],
                'notes'      => 'Pembelian formulir via bantuan closing SPV ' . $user->name,
            ]);
        }

        // 2. Catat transaksi Termin 1
        Transaksi::create([
            'prospek_id' => $prospek->id,
            'user_id'    => $user->id, // SPV pencatat
            'jenis'      => 'Pembayaran Termin 1',
            'nominal'    => $validated['nominal_termin1'],
            'tanggal'    => $validated['tanggal'],
            'notes'      => $validated['notes'] ?? ('Pembayaran Termin 1 dibantu oleh SPV ' . $user->name),
        ]);

        // 3. Update status prospek menjadi LUNAS (Stage 7).
        // PENTING: sales_id dan owner_id TETAP milik Sales awal!
        $oldStatus = $prospek->status;
        $prospek->status = 'LUNAS';
        $prospek->stage_number = 7;
        $prospek->save();

        $originalSalesName = $prospek->sales ? $prospek->sales->name : ($prospek->owner ? $prospek->owner->name : 'Sales');

        ProspekTimeline::create([
            'prospek_id'    => $prospek->id,
            'user_id'       => $user->id,
            'title'         => 'Bantu Closing oleh SPV (Maba Lunas)',
            'notes'         => 'SPV ' . $user->name . ' membantu closing Maba Lunas (Formulir + Termin 1). Kepemilikan lead tetap pada ' . $originalSalesName . '.',
            'status_before' => $oldStatus,
            'status_after'  => 'LUNAS',
            'time'          => now(),
        ]);

        return redirect()->route('spv.prospek.show', $prospek)->with('success', 'Closing Maba Lunas berhasil dicatat! Hak kredit lead tetap pada ' . $originalSalesName . '.');
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
            'follow_up_count'=> $p->follow_up_count ?? 0,
            'sales_id'       => $p->sales_id,
            'sekolah_id'     => $p->sekolah_id,
            'wilayah_id'     => $p->wilayah_id,
            'sekolah_nama'   => $p->sekolah ? $p->sekolah->nama : null,
            'wilayah_nama'   => $p->wilayah ? $p->wilayah->nama : null,
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
