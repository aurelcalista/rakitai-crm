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
use Carbon\Carbon;

class ProspectController extends Controller
{
    /**
     * List all prospects belonging to the SPV's team with search & filters.
     */
    public function index(Request $request): View
    {
        $user = auth()->user();
        $teamMemberIds = $user->teamMemberIds();

        $query = Prospek::with(['sales', 'cs', 'owner', 'sekolah', 'perusahaan', 'wilayah', 'prodi', 'latestFollowUp'])->where(function ($q) use ($teamMemberIds, $user) {
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

        // Filter by Prodi (PRD Bab 7.2)
        if ($request->filled('prodi_id') && $request->prodi_id !== 'all') {
            $query->where(function ($q) use ($request) {
                $q->where('prodi_id', $request->prodi_id);
                $prodi = Prodi::find($request->prodi_id);
                if ($prodi) {
                    $q->orWhere('category', 'like', '%' . $prodi->nama . '%')
                      ->orWhere('notes', 'like', '%' . $prodi->nama . '%');
                }
            });
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
        $statuses    = Prospek::getActiveStages();
        $lostReasons = Prospek::LOST_REASONS;
        $sources     = Prospek::SOURCES;

        return view('spv.prospek.index', compact(
            'prospects',
            'teamSales',
            'teamMembers',
            'sekolahs',
            'perusahaans',
            'wilayahs',
            'prodis',
            'statuses',
            'lostReasons',
            'sources'
        ));
    }

    /**
     * Show form to create a new prospect for team (PRD Bab 7.2 & Bab 8.1).
     */
    public function create(): View
    {
        $user = auth()->user();
        $teamMemberIds = $user->teamMemberIds();

        $teamSales   = User::whereIn('id', $teamMemberIds)->where('role', 'Sales')->where('status', 'Aktif')->get();
        $teamCs      = User::where('role', 'CS')->where('status', 'Aktif')->get();
        $sekolahs    = Sekolah::getDynamicSchools();
        $perusahaans = Perusahaan::getDynamicPerusahaans();
        $prodis      = Prodi::where('status', 'Aktif')->orderBy('nama')->get();
        $wilayahsRaw = Wilayah::whereNull('parent_id')
            ->where('status', 'Aktif')
            ->with(['children' => function ($q) {
                $q->where('status', 'Aktif')->orderBy('nama');
            }])
            ->orderBy('nama')
            ->get();

        $wilayahsData = $wilayahsRaw->map(function ($w) {
            return [
                'id'       => $w->id,
                'nama'     => $w->nama,
                'children' => $w->children->map(fn($c) => [
                    'id'   => $c->id,
                    'nama' => $c->nama,
                ])->values()->all(),
            ];
        })->values()->all();

        $wilayahs = $wilayahsRaw;

        $statuses    = Prospek::getActiveStages();
        $lostReasons = Prospek::LOST_REASONS;

        // 10 Opsi Baku Dropdown Sumber Informasi (PRD Bab 8.1.1)
        $sources = Prospek::SOURCES;

        return view('spv.prospek.create', compact(
            'sekolahs',
            'perusahaans',
            'prodis',
            'wilayahs',
            'wilayahsData',
            'statuses',
            'lostReasons',
            'teamSales',
            'teamCs',
            'sources'
        ));
    }

    /**
     * Store new prospect according to PRD v4.0 specifications.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tanggal_masuk' => 'nullable|date',
            'name'          => 'nullable|string|max:255',
            'type'          => 'required|in:Sekolah,Corporate,Individu',
            'sekolah_id'    => 'nullable|exists:sekolahs,id',
            'sekolah_manual' => 'nullable|string|max:255',
            'perusahaan_id' => 'nullable|exists:perusahaans,id',
            'perusahaan_manual' => 'nullable|string|max:255',
            'pic'           => 'required|string|max:255',
            'pic_phone'     => 'nullable|string|max:20',
            'whatsapp'      => 'required|string|max:20',
            'prodi_id'      => 'nullable|exists:prodis,id',
            'kelas'         => 'nullable|in:Reguler,Karyawan',
            'status'        => 'required|string|max:255',
            'source'        => 'required|string|max:100',
            'custom_source' => 'nullable|string|max:100',
            'assign_type'   => 'nullable|in:sales,cs,self',
            'sales_id'      => 'nullable|exists:users,id',
            'cs_id'         => 'nullable|exists:users,id',
            'wilayah_id'    => 'nullable|exists:wilayahs,id',
            'kota_id'       => 'nullable|exists:wilayahs,id',
            'notes'         => 'nullable|string|max:500',
        ]);

        $user = auth()->user();

        // 1. Resolve Nama Prospek & Foreign Key
        $name = trim($validated['name'] ?? '');
        if (empty($name)) {
            $name = $validated['pic'] ?? 'Prospek Baru';
        }
        if ($validated['type'] === 'Sekolah') {
            if (!empty($request->sekolah_manual)) {
                $manualName = trim($request->sekolah_manual);
                if ($manualName !== '') {
                    $sekolah = Sekolah::firstOrCreate(
                        ['nama' => $manualName],
                        [
                            'kode' => 'SCH-' . strtoupper(\Illuminate\Support\Str::random(6)),
                            'status' => 'Aktif'
                        ]
                    );
                    $validated['sekolah_id'] = $sekolah->id;
                    $name = $sekolah->nama;
                }
            } elseif (!empty($validated['sekolah_id'])) {
                $sekolah = Sekolah::find($validated['sekolah_id']);
                if ($sekolah) $name = $sekolah->nama;
            }
        } elseif ($validated['type'] === 'Corporate') {
            if (!empty($request->perusahaan_manual)) {
                $manualName = trim($request->perusahaan_manual);
                if ($manualName !== '') {
                    $perusahaan = Perusahaan::firstOrCreate(
                        ['nama' => $manualName],
                        [
                            'kode' => 'CORP-' . strtoupper(\Illuminate\Support\Str::random(6)),
                            'status' => 'Aktif'
                        ]
                    );
                    $validated['perusahaan_id'] = $perusahaan->id;
                    $name = $perusahaan->nama;
                }
            } elseif (!empty($validated['perusahaan_id'])) {
                $perusahaan = Perusahaan::find($validated['perusahaan_id']);
                if ($perusahaan) $name = $perusahaan->nama;
            }
        }
        if (empty($name)) {
            $name = $validated['pic'];
        }

        // Default assign_type & kelas if omitted
        $assignType = $validated['assign_type'] ?? (!empty($validated['sales_id']) ? 'sales' : (!empty($validated['cs_id']) ? 'cs' : 'self'));
        $kelas = $validated['kelas'] ?? 'Reguler';

        // 2. Validasi Duplikat HP & Nama (PRD Bab 8.1)
        $cleanWa = preg_replace('/[^0-9]/', '', $validated['whatsapp']);
        $duplicate = Prospek::with(['owner', 'sales', 'cs'])->where(function($query) use ($validated, $name, $cleanWa) {
            $query->where('whatsapp', $validated['whatsapp'])
                  ->orWhere('name', $name);
            if (!empty($cleanWa)) {
                $query->orWhereRaw("REPLACE(REPLACE(REPLACE(whatsapp, '-', ''), ' ', ''), '+', '') = ?", [$cleanWa]);
            }
        })->first();

        if ($duplicate) {
            $errorField = ($duplicate->whatsapp === $validated['whatsapp'] || (strlen($cleanWa) > 6 && str_contains($duplicate->whatsapp, $cleanWa))) ? 'whatsapp' : 'name';
            $ownerName = $duplicate->owner ? $duplicate->owner->name : 'Sistem';
            $handlerName = $duplicate->sales ? ($duplicate->sales->name . ' (Sales)') : ($duplicate->cs ? ($duplicate->cs->name . ' (CS)') : 'Belum Ada Handler');
            
            $errorMessage = "Data prospek sudah ada di sistem (Duplicate {$errorField}).\n"
                          . "Pemilik Lead: {$ownerName}\n"
                          . "Handler aktif: {$handlerName}\n"
                          . "Status saat ini: {$duplicate->status}";
                          
            return back()->withInput()->withErrors([$errorField => $errorMessage]);
        }

        // 3. Sumber Informasi (PRD Bab 8.1.1)
        $source = $validated['source'];
        if ($source === 'Lainnya' && !empty($validated['custom_source'])) {
            $source = 'Lainnya: ' . trim($validated['custom_source']);
        }

        // 4. Penugasan Handler (Sales / CS / SPV Mandiri)
        $salesId = null;
        $csId    = null;
        $ownerId = $user->id;
        $handlerLabel = 'Belum Ditugaskan';

        if ($assignType === 'sales') {
            $salesId = $validated['sales_id'] ?: null;
            if ($salesId) {
                $ownerId = $salesId;
                $handlerLabel = User::find($salesId)?->name . ' (Sales)';
            }
        } elseif ($assignType === 'cs') {
            $csId = $validated['cs_id'] ?: null;
            if ($csId) {
                $ownerId = $csId;
                $handlerLabel = User::find($csId)?->name . ' (CS)';
            }
        } elseif ($assignType === 'self') {
            $salesId = $user->id;
            $ownerId = $user->id;
            $handlerLabel = $user->name . ' (SPV Penanganan Mandiri)';
        }

        $prodi = !empty($validated['prodi_id']) ? Prodi::find($validated['prodi_id']) : null;
        $prodiNama = $prodi ? $prodi->nama : null;
        $wilayahId = !empty($validated['wilayah_id']) ? $validated['wilayah_id'] : (!empty($validated['kota_id']) ? $validated['kota_id'] : $user->wilayah_id);

        // Auto-route active Sales & CS from active user_wilayah if not manually assigned
        if ($wilayahId) {
            $wilayahObj = Wilayah::find($wilayahId);
            if ($wilayahObj) {
                if (!$salesId && ($validated['assign_type'] ?? '') === 'sales') {
                    $activeSales = $wilayahObj->activeSalesUser();
                    if ($activeSales) {
                        $salesId = $activeSales->id;
                        $ownerId = $salesId;
                    }
                }
                if (!$csId) {
                    $activeCs = $wilayahObj->activeCsUser();
                    if ($activeCs) {
                        $csId = $activeCs->id;
                    }
                }
            }
        }

        DB::transaction(function () use ($validated, $user, $name, $prodi, $prodiNama, $source, $salesId, $csId, $ownerId, $handlerLabel, $wilayahId, $kelas) {
            $stageNumber = Prospek::STAGES[$validated['status']] ?? 1;

            $createdAt = !empty($validated['tanggal_masuk'])
                ? Carbon::parse($validated['tanggal_masuk'])->setTimeFrom(now())
                : now();

            $kelas = $validated['kelas'] ?? 'Reguler';
            $potential = $prodiNama ? "{$kelas} — {$prodiNama}" : null;

            $prospek = Prospek::create([
                'name'               => $name,
                'type'               => $validated['type'],
                'category'           => $prodiNama,
                'prodi_id'           => $prodi?->id,
                'kelas'              => $kelas,
                'sekolah_id'         => $validated['type'] === 'Sekolah' ? ($validated['sekolah_id'] ?? null) : null,
                'perusahaan_id'      => $validated['type'] === 'Corporate' ? ($validated['perusahaan_id'] ?? null) : null,
                'pic'                => $validated['pic'],
                'pic_phone'          => $validated['pic_phone'] ?? null,
                'whatsapp'           => $validated['whatsapp'],
                'status'             => $validated['status'],
                'stage_number'       => $stageNumber,
                'notes'              => $validated['notes'] ?? null,
                'source'             => $source,
                'sales_id'           => $salesId,
                'cs_id'              => $csId,
                'wilayah_id'         => $wilayahId,
                'owner_id'           => $ownerId,
                'created_at'         => $createdAt,
            ]);

            // Jika status langsung FORMULIR dan di-assign ke CS, catat handover_at
            if (in_array($validated['status'], ['FORMULIR', '05 FORMULIR']) && $csId) {
                $prospek->update(['handover_at' => now()]);
            }

            ProspekTimeline::create([
                'prospek_id'   => $prospek->id,
                'user_id'      => $user->id,
                'title'        => 'Prospek Dibuat oleh SPV',
                'notes'        => "Prospek dibuat oleh Supervisor {$user->name} (Prodi: {$prodiNama}, Kelas: {$validated['kelas']}, Sumber: {$source}) dan ditugaskan ke {$handlerLabel}.",
                'status_after' => $prospek->status,
                'time'         => now(),
            ]);
        });

        return redirect()->route('spv.prospek.index')->with('success', "Prospek '{$name}' berhasil ditambahkan dan ditugaskan ke {$handlerLabel}!");
    }

    /**
     * Show detail of a prospect.
     */
    public function show(Prospek $prospek): View
    {
        Gate::authorize('view', $prospek);

        $prospek->load(['sales', 'cs', 'owner', 'prodi', 'sekolah', 'wilayah', 'followUps' => function ($q) {
            $q->orderBy('tanggal', 'desc')->with('user');
        }, 'timelines' => function ($q) {
            $q->orderBy('time', 'desc')->with('user');
        }]);

        $user = auth()->user();
        $teamMembers = User::whereIn('id', $user->teamMemberIds())->get();
        $teamSales = $teamMembers->where('role', 'Sales');
        $teamCs = $teamMembers->where('role', 'CS');

        $stagesMap = Prospek::getDynamicStagesMap();
        $allStages = array_map(function ($stageName) use ($stagesMap) {
            return ['name' => $stageName, 'number' => $stagesMap[$stageName] ?? 1];
        }, Prospek::getActiveStages());

        $prospect = $this->formatProspekDetail($prospek);
        $transaksis = $prospek->transaksis()->with(['user', 'verifier', 'rejecter'])->orderBy('tanggal', 'desc')->orderBy('id', 'desc')->get();
        $lostReasons = Prospek::LOST_REASONS;
        $metodeOptions = \App\Models\FollowUp::METODE_OPTIONS ?? ['WhatsApp', 'Telepon', 'Kunjungan Langsung', 'Email', 'Zoom/GMeet'];

        return view('spv.prospek.show', compact(
            'prospect',
            'transaksis',
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
            'type'        => 'nullable|in:Sekolah,Corporate,Individu',
            'sekolah_id'  => 'nullable|exists:sekolahs,id',
            'sekolah_manual' => 'nullable|string|max:255',
            'perusahaan_id' => 'nullable|exists:perusahaans,id',
            'perusahaan_manual' => 'nullable|string|max:255',
            'name'        => 'nullable|string|max:255',
            'pic'         => 'required|string|max:255',
            'pic_phone'   => 'nullable|string|max:20',
            'whatsapp'    => 'required|string|max:20',
            'notes'       => 'nullable|string',
            'category'    => 'nullable|string|max:100',
            'source'      => 'required|string|max:100',
            'prodi_id'    => 'nullable|exists:prodis,id',
            'kelas'       => 'nullable|in:Reguler,Karyawan',
        ]);

        $type = $validated['type'] ?? $prospek->type;
        if ($type === 'Sekolah') {
            $validated['perusahaan_id'] = null;
            if (!empty($request->sekolah_manual)) {
                $manualName = trim($request->sekolah_manual);
                if ($manualName !== '') {
                    $sekolah = Sekolah::firstOrCreate(
                        ['nama' => $manualName],
                        [
                            'kode' => 'SCH-' . strtoupper(\Illuminate\Support\Str::random(6)),
                            'status' => 'Aktif'
                        ]
                    );
                    $validated['sekolah_id'] = $sekolah->id;
                    $validated['name'] = $sekolah->nama;
                }
            } elseif (!empty($validated['sekolah_id'])) {
                $sekolah = Sekolah::find($validated['sekolah_id']);
                if ($sekolah) $validated['name'] = $sekolah->nama;
            }
        } elseif ($type === 'Corporate') {
            $validated['sekolah_id'] = null;
            if (!empty($request->perusahaan_manual)) {
                $manualName = trim($request->perusahaan_manual);
                if ($manualName !== '') {
                    $perusahaan = Perusahaan::firstOrCreate(
                        ['nama' => $manualName],
                        [
                            'kode' => 'CORP-' . strtoupper(\Illuminate\Support\Str::random(6)),
                            'status' => 'Aktif'
                        ]
                    );
                    $validated['perusahaan_id'] = $perusahaan->id;
                    $validated['name'] = $perusahaan->nama;
                }
            } elseif (!empty($validated['perusahaan_id'])) {
                $perusahaan = Perusahaan::find($validated['perusahaan_id']);
                if ($perusahaan) $validated['name'] = $perusahaan->nama;
            }
        }

        $duplicate = Prospek::where('id', '!=', $prospek->id)
            ->where(function ($q) use ($validated, $prospek) {
                $name = $validated['name'] ?? $request->input('name', $prospek->name);
                $q->where('whatsapp', $validated['whatsapp'])
                  ->orWhere('name', $name);
            })->first();

        if ($duplicate) {
            $errorField = $duplicate->whatsapp === $validated['whatsapp'] ? 'whatsapp' : 'name';
            return back()->withInput()->withErrors([$errorField => 'Data prospek sudah ada (Duplicate ' . $errorField . ').']);
        }

        if (!empty($validated['prodi_id'])) {
            $prodi = Prodi::find($validated['prodi_id']);
            if ($prodi) {
                $validated['category'] = $prodi->nama;
            }
        }

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
            'sales_id'               => $validated['sales_id'] ?? $prospek->sales_id,
            'cs_id'                  => $validated['cs_id'] ?? $prospek->cs_id,
            'follow_up_count'        => 0, // Reset follow-up counter ke 0 bagi penangan baru
            'active_follow_up_count' => 0,
        ]);

        ProspekTimeline::create([
            'prospek_id'   => $prospek->id,
            'user_id'      => auth()->id(),
            'title'        => 'Re-alokasi Handler oleh SPV',
            'notes'        => 'SPV mengalihkan penanganan prospek ke ' . $newSales . ' (follow_up_count di-reset ke 0 untuk pemilik baru)' . (!empty($validated['reason']) ? ' (Alasan: ' . $validated['reason'] . ')' : ''),
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
            'nominal_formulir' => 'nullable|numeric|min:0|max:9999999999',
            'nominal_termin1'  => 'required|numeric|min:0|max:9999999999',
            'tanggal'          => 'required|date',
            'notes'            => 'nullable|string|max:1000',
        ], [
            'nominal_formulir.max' => 'Nominal formulir maksimal Rp 9.999.999.999.',
            'nominal_termin1.max'  => 'Nominal termin 1 maksimal Rp 9.999.999.999.',
        ]);

        $user = auth()->user();

        // 1. Catat transaksi formulir jika belum ada
        $hasFormulir = Transaksi::where('prospek_id', $prospek->id)->where('jenis', 'Beli Formulir')->exists();
        $isPayingFormulirNow = !empty($validated['nominal_formulir']) && $validated['nominal_formulir'] > 0;
        
        if (!$hasFormulir && !$isPayingFormulirNow) {
            return back()->withErrors(['nominal_formulir' => 'Pembayaran Formulir wajib diselesaikan sebelum bisa Closing LUNAS.']);
        }

        if (!$hasFormulir && $isPayingFormulirNow) {
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

        // Cek evaluasi target tuntas untuk sales dan SPV
        try {
            $targetService = app(\App\Services\TargetAchievementService::class);
            if ($prospek->sales) {
                $targetService->checkAndNotifyTargetStatus($prospek->sales);
            }
            $targetService->checkAndNotifyTargetStatus($user);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Gagal evaluasi target notification: " . $e->getMessage());
        }

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
            'prodi_id'       => $p->prodi_id,
            'prodi_nama'     => $p->prodi ? $p->prodi->nama : ($p->category ?: '-'),
            'kelas'          => $p->kelas ?: 'Reguler',
            'source'         => $p->source ?? '-',
            'ai_training'    => $p->ai_training ?? '-',
            'notes'          => $latestFollowUp && $latestFollowUp->catatan ? $latestFollowUp->catatan : ($p->notes ?? ''),
            'created_at'     => $p->created_at ? $p->created_at->format('d M Y') : '-',
            'last_contact'   => $latestFollowUp && $latestFollowUp->tanggal
                ? \Carbon\Carbon::parse($latestFollowUp->tanggal)->format('d M Y, H:i')
                : '-',
            'next_follow_up' => $latestFollowUp && $latestFollowUp->next_follow_up
                ? \Carbon\Carbon::parse($latestFollowUp->next_follow_up)->format('d M Y, H:i')
                : '-',
            'next_follow_up_date' => $latestFollowUp && $latestFollowUp->next_follow_up
                ? \Carbon\Carbon::parse($latestFollowUp->next_follow_up)->toDateString()
                : null,
            'metode_terakhir'=> $latestFollowUp ? ($latestFollowUp->metode ?? '-') : '-',
            'hasil_terakhir' => $latestFollowUp ? ($latestFollowUp->hasil ?? '-') : '-',
            'sla_status'     => $p->sla_status,
        ];
    }

    /**
     * Format a Prospek model for detail view.
     */
    private function formatProspekDetail(Prospek $p): array
    {
        $base = $this->formatProspek($p);

        $base['timeline'] = $p->timelines->map(function ($t) {
            $timeObj = $t->time ?? $t->created_at;
            return [
                'time'   => $timeObj ? $timeObj->format('d M Y, H:i') : '-',
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
