<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Prospek;
use App\Models\ProspekTimeline;
use App\Models\Sekolah;
use App\Models\Perusahaan;
use App\Models\Prodi;
use App\Models\FollowUp;
use App\Http\Requests\StoreProspectRequest;
use App\Http\Requests\UpdateProspectRequest;
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

        $query = Prospek::with(['cs', 'owner', 'sekolah.wilayah', 'perusahaan', 'sales', 'takeoverBy', 'latestFollowUp'])
            ->withCount('followUps')
            ->where(function ($q) use ($user) {
                $q->where('sales_id', $user->id)
                  ->orWhere('owner_id', $user->id)
                  ->orWhere('takeover_by_id', $user->id)
                  ->orWhere(function ($sub) {
                      $sub->where(function ($s) {
                          $s->whereHas('sales', function ($sq) {
                              $sq->whereIn('role', ['Sales', 'sales']);
                          })->orWhereHas('owner', function ($oq) {
                              $oq->whereIn('role', ['Sales', 'sales']);
                          });
                      });
                  })
                  // Akses lintas wilayah murni untuk sumber Presensi Siswa
                  ->orWhere('source', Prospek::SOURCE_PRESENSI_SISWA);
            });

        // Filter tab scope: all, own, others, presensi
        if ($request->filled('scope')) {
            if ($request->scope === 'own') {
                $query->where(function ($q) use ($user) {
                    $q->where('sales_id', $user->id)
                      ->orWhere('owner_id', $user->id)
                      ->orWhere('takeover_by_id', $user->id);
                });
            } elseif ($request->scope === 'others') {
                $query->where(function ($q) use ($user) {
                    $q->where(function ($sub) use ($user) {
                        $sub->whereNull('sales_id')->orWhere('sales_id', '!=', $user->id);
                    })
                    ->where(function ($sub) use ($user) {
                        $sub->whereNull('owner_id')->orWhere('owner_id', '!=', $user->id);
                    })
                    ->where(function ($sub) use ($user) {
                        $sub->whereNull('takeover_by_id')->orWhere('takeover_by_id', '!=', $user->id);
                    })
                    ->where(function ($sub) {
                        $sub->whereNotNull('sales_id')
                           ->orWhereNotNull('takeover_by_id')
                           ->orWhereHas('sales')
                           ->orWhereHas('owner');
                    });
                });
            } elseif ($request->scope === 'presensi') {
                $query->where('source', Prospek::SOURCE_PRESENSI_SISWA);
            }
        }

        // Optional status filter
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Optional search by name
        if ($request->filled('q')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->q . '%')
                  ->orWhere('pic', 'like', '%' . $request->q . '%')
                  ->orWhere('whatsapp', 'like', '%' . $request->q . '%');
            });
        }

        // Optional date filter
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $prospects = $query->orderBy('updated_at', 'desc')->get()
            ->map(fn ($p) => $this->formatProspek($p))
            ->toArray();

        $statuses = Prospek::getActiveStages();
        $lostReasons = Prospek::LOST_REASONS;

        // Sales Activity Metrics (Scoped specifically to this Sales user)
        $startDateCarbon = $request->filled('start_date') ? \Carbon\Carbon::parse($request->start_date)->startOfDay() : null;
        $endDateCarbon   = $request->filled('end_date') ? \Carbon\Carbon::parse($request->end_date)->endOfDay() : null;

        $salesBaseQuery = Prospek::where(function ($q) use ($user) {
            $q->where('sales_id', $user->id)
              ->orWhere('owner_id', $user->id);
        });

        // 1. Prospek Diinput oleh Sales ini
        $inputQuery = (clone $salesBaseQuery);
        if ($startDateCarbon) $inputQuery->where('created_at', '>=', $startDateCarbon);
        if ($endDateCarbon)   $inputQuery->where('created_at', '<=', $endDateCarbon);
        $totalDiinput = $inputQuery->count();

        // List ID prospek milik Sales ini
        $salesProspectIds = (clone $salesBaseQuery)->pluck('id');

        // 2. Prospek Dihubungi
        $contactQuery = \App\Models\FollowUp::where(function ($q) use ($user, $salesProspectIds) {
            $q->where('user_id', $user->id)
              ->orWhereIn('prospek_id', $salesProspectIds);
        });
        if ($startDateCarbon) $contactQuery->where('tanggal', '>=', $startDateCarbon);
        if ($endDateCarbon)   $contactQuery->where('tanggal', '<=', $endDateCarbon);
        $totalDihubungi = (clone $contactQuery)->where(function ($q) {
            $q->whereIn('metode', ['Telepon', 'WhatsApp', 'Panggilan', 'SMS', 'Chat'])
              ->orWhere('hasil', 'like', '%Hubung%')
              ->orWhere('hasil', 'like', '%Kontak%');
        })->distinct('prospek_id')->count('prospek_id');

        if ($totalDihubungi === 0) {
            $totalDihubungi = (clone $contactQuery)->distinct('prospek_id')->count('prospek_id');
        }

        // 3. Di-Follow-up
        $fuQuery = \App\Models\FollowUp::where(function ($q) use ($user, $salesProspectIds) {
            $q->where('user_id', $user->id)
              ->orWhereIn('prospek_id', $salesProspectIds);
        });
        if ($startDateCarbon) $fuQuery->where('tanggal', '>=', $startDateCarbon);
        if ($endDateCarbon)   $fuQuery->where('tanggal', '<=', $endDateCarbon);
        $totalFollowUp = $fuQuery->distinct('prospek_id')->count('prospek_id');

        // 4. Hot Prospect
        $hotQuery = (clone $salesBaseQuery)->where(function ($q) use ($startDateCarbon, $endDateCarbon) {
            $q->where(function ($sq) {
                $sq->whereIn('status', ['HOT PROSPEK', 'PANAS', 'HOT'])
                   ->orWhere('category', 'like', '%Hot%');
            });
            if ($startDateCarbon || $endDateCarbon) {
                $q->orWhereHas('timelines', function ($tq) use ($startDateCarbon, $endDateCarbon) {
                    $tq->where(function ($tsq) {
                        $tsq->whereIn('status_after', ['HOT PROSPEK', 'PANAS', 'HOT'])
                            ->orWhere('title', 'like', '%Hot%');
                    });
                    if ($startDateCarbon) $tq->where('time', '>=', $startDateCarbon);
                    if ($endDateCarbon)   $tq->where('time', '<=', $endDateCarbon);
                });
            }
        });
        if ($startDateCarbon) $hotQuery->where('created_at', '>=', $startDateCarbon);
        if ($endDateCarbon)   $hotQuery->where('created_at', '<=', $endDateCarbon);
        $totalHot = $hotQuery->count();

        $salesMetrics = [
            'diinput'      => $totalDiinput,
            'dihubungi'    => $totalDihubungi,
            'follow_up'    => $totalFollowUp,
            'hot_prospect' => $totalHot,
            'start_date'   => $request->start_date,
            'end_date'     => $request->end_date,
        ];

        $sekolahs    = Sekolah::getDynamicSchools();
        $perusahaans = Perusahaan::getDynamicPerusahaans();
        $prodis      = Prodi::where('status', 'Aktif')->orderBy('nama')->get();

        return view('prospek.index', compact('prospects', 'statuses', 'lostReasons', 'salesMetrics', 'sekolahs', 'perusahaans', 'prodis'));
    }

    /**
     * Show form to create a new prospect.
     */
    public function create(): View
    {
        $sekolahs    = Sekolah::getDynamicSchools();
        $perusahaans = Perusahaan::getDynamicPerusahaans();
        $statuses = Prospek::getActiveStages();
        $lostReasons = Prospek::LOST_REASONS;

        return view('prospek.create', compact('sekolahs', 'perusahaans', 'statuses', 'lostReasons'));
    }

    /**
     * Store a new prospect created by the Sales user.
     */
    public function store(StoreProspectRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = auth()->user();

        // Resolve name from Master Data or Manual Input if applicable
        $name = $validated['name'] ?? $validated['pic'] ?? 'Prospek Baru';

        if ($validated['type'] === 'Sekolah') {
            if (!empty($request->sekolah_manual)) {
                $manualName = trim($request->sekolah_manual);
                if ($manualName !== '') {
                    $sekolah = Sekolah::where('nama', $manualName)->first();
                    if (!$sekolah) {
                        $kecVal = $request->input('sekolah_kecamatan');
                        $kecName = $kecVal;
                        if (is_numeric($kecVal)) {
                            $wKec = \App\Models\Wilayah::find($kecVal);
                            $kecName = $wKec?->nama;
                        }

                        $sekolah = Sekolah::create([
                            'kode'        => 'SCH-' . strtoupper(\Illuminate\Support\Str::random(6)),
                            'nama'        => $manualName,
                            'kategori_id' => $request->input('sekolah_kategori_id') ?: null,
                            'wilayah_id'  => $request->input('sekolah_wilayah_id') ?: null,
                            'kecamatan'   => $kecName ?: '-',
                            'telepon'     => $request->input('sekolah_telepon') ?: null,
                            'status'      => 'Aktif',
                            'sales_id'    => $user->id,
                        ]);
                    }
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

        if (empty($validated['prodi_id'])) {
            $validated['prodi_id'] = null;
            $validated['prodi_lainnya'] = null;
        } elseif ($validated['prodi_id'] === 'lainnya') {
            $validated['prodi_id'] = null;
        } else {
            $validated['prodi_lainnya'] = null;
        }

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
                'notes'        => $validated['notes'] ?? null,
                'source'       => $validated['source'] ?? null,
                'prodi_id'     => $validated['prodi_id'] ?? null,
                'kelas'        => $validated['kelas'] ?? 'Reguler',
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

        $prospek->load(['sales', 'cs', 'owner', 'takeoverBy', 'sekolah.wilayah', 'wilayah', 'perusahaan', 'prodi', 'followUps' => function ($q) {
            $q->orderBy('tanggal', 'desc')->with('user');
        }, 'timelines' => function ($q) {
            $q->orderBy('time', 'desc')->with('user');
        }, 'transaksis' => function ($q) {
            $q->orderBy('tanggal', 'desc')->orderBy('id', 'desc')->with(['user', 'verifier', 'rejecter']);
        }]);

        $isHandler = $prospek->isHandledBySales(auth()->user());
        $transaksis = $prospek->transaksis;
        $pendingList         = $transaksis->where('payment_status', 'pending');
        $hasPendingTrx       = $pendingList->isNotEmpty();
        $hasVerifiedTermin1  = $transaksis->where('payment_status', 'verified')->filter(fn($t) => in_array($t->jenis, ['Pembayaran Termin 1', 'DP/Konversi', 'UKT', 'Perlengkapan']) || $t->kategori === 'Lunas')->isNotEmpty();
        $hasVerifiedFormulir = $transaksis->where('payment_status', 'verified')->filter(fn($t) => in_array($t->jenis, ['Beli Formulir', 'Pendaftaran']) || $t->kategori === 'DP Daftar')->isNotEmpty();
        $totalPaid           = (float) $transaksis->where('payment_status', 'verified')->sum('nominal');
        $totalPending        = (float) $transaksis->where('payment_status', 'pending')->sum('nominal');

        $stagesMap = Prospek::getDynamicStagesMap();
        $allStages = array_map(function ($stageName) use ($stagesMap) {
            return ['name' => $stageName, 'number' => $stagesMap[$stageName] ?? 1];
        }, Prospek::getActiveStages());

        $prospect   = $this->formatProspekDetail($prospek);
        $lostReasons = Prospek::LOST_REASONS;
        $metodeOptions = \App\Models\FollowUp::METODE_OPTIONS;

        $sekolahsList        = Sekolah::getDynamicSchools();
        $perusahaansList     = Perusahaan::getDynamicPerusahaans();
        $prodisList          = \App\Models\Prodi::where('status', 'Aktif')->orderBy('nama')->get();
        $sumberProspekList   = \App\Models\MasterData::where('type', 'sumber_prospek')->where('status', 'Aktif')->get();
        $kategoriProspekList = \App\Models\MasterData::where('type', 'kategori_prospek')->where('status', 'Aktif')->get();

        // Sales tidak bisa re-alokasi, kirim collection kosong agar view tidak error
        $salesTeam = \Illuminate\Support\Collection::make();
        $prospekModel = $prospek;
        $followUps = $prospek->followUps;

        return view('prospek.show', compact(
            'prospect',
            'prospekModel',
            'followUps',
            'transaksis',
            'isHandler',
            'allStages',
            'lostReasons',
            'metodeOptions',
            'salesTeam',
            'sekolahsList',
            'perusahaansList',
            'prodisList',
            'sumberProspekList',
            'kategoriProspekList',
            'hasPendingTrx',
            'pendingList',
            'hasVerifiedTermin1',
            'hasVerifiedFormulir',
            'totalPaid',
            'totalPending'
        ));
    }

    /**
     * Update editable prospect fields.
     * Only allowed for active Sales handler.
     */
    public function update(UpdateProspectRequest $request, Prospek $prospek): RedirectResponse
    {
        \Illuminate\Support\Facades\Gate::authorize('update', $prospek);

        $validated = $request->validated();

        if ($validated['type'] === 'Sekolah') {
            $validated['perusahaan_id'] = null;
            if (!empty($request->sekolah_manual)) {
                $manualName = trim($request->sekolah_manual);
                if ($manualName !== '') {
                    $sekolah = Sekolah::where('nama', $manualName)->first();
                    if (!$sekolah) {
                        $kecVal = $request->input('sekolah_kecamatan');
                        $kecName = $kecVal;
                        if (is_numeric($kecVal)) {
                            $wKec = \App\Models\Wilayah::find($kecVal);
                            $kecName = $wKec?->nama;
                        }

                        $sekolah = Sekolah::create([
                            'kode'        => 'SCH-' . strtoupper(\Illuminate\Support\Str::random(6)),
                            'nama'        => $manualName,
                            'kategori_id' => $request->input('sekolah_kategori_id') ?: null,
                            'wilayah_id'  => $request->input('sekolah_wilayah_id') ?: null,
                            'kecamatan'   => $kecName ?: '-',
                            'telepon'     => $request->input('sekolah_telepon') ?: null,
                            'status'      => 'Aktif',
                            'sales_id'    => $prospek->sales_id,
                        ]);
                    }
                    $validated['sekolah_id'] = $sekolah->id;
                    $validated['name'] = $sekolah->nama;
                }
            } elseif (!empty($validated['sekolah_id'])) {
                $sekolah = Sekolah::find($validated['sekolah_id']);
                if ($sekolah) $validated['name'] = $sekolah->nama;
            }
        } elseif ($validated['type'] === 'Corporate') {
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
        } else {
            $validated['sekolah_id'] = null;
            $validated['perusahaan_id'] = null;
            if (empty($validated['name'])) {
                $validated['name'] = $validated['pic'] ?? $prospek->name;
            }
        }

        if (empty($validated['prodi_id'])) {
            $validated['prodi_id'] = null;
            $validated['prodi_lainnya'] = null;
        } elseif ($validated['prodi_id'] === 'lainnya') {
            $validated['prodi_id'] = null;
        } else {
            $validated['prodi_lainnya'] = null;
        }

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
        if ($newStatus === 'DINGIN') {
            return redirect()->back()
                ->withErrors(['status' => 'Gunakan tombol "Mark as Lost" untuk mengubah ke status Lost.']);
        }

        if ($newStatus === 'LUNAS') {
            if (strtolower(auth()->user()->role ?? '') === 'sales') {
                return redirect()->back()
                    ->withErrors(['status' => 'Status LUNAS hanya dapat diproses oleh CS setelah pelunasan pembayaran. Sales dapat memilih status CLOSING.']);
            }
            if (!\App\Services\ProspekService::isClosingValid($prospek)) {
                return redirect()->back()
                    ->withErrors(['status' => 'Status LUNAS tidak valid. Prospek harus melunasi Pembayaran Formulir dan Termin 1.']);
            }
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
            'status'       => 'DINGIN',
            'stage_number' => 8,
            'lost_reason'  => $validated['lost_reason'],
            'lost_note'    => $validated['lost_note'],
        ]);

        ProspekTimeline::create([
            'prospek_id'   => $prospek->id,
            'user_id'      => auth()->id(),
            'title'        => 'Prospek Ditandai DINGIN',
            'notes'        => 'Alasan: ' . $validated['lost_reason'] . ($validated['lost_note'] ? '. Catatan: ' . $validated['lost_note'] : ''),
            'status_before' => $oldStatus,
            'status_after'  => 'DINGIN',
            'time'          => now(),
        ]);

        return redirect()->route('sales.prospek.index')
            ->with('success', 'Prospek telah ditandai sebagai DINGIN.');
    }

    /**
     * Takeover prospect (Presensi Siswa or Handover).
     */
    public function takeover(Request $request, Prospek $prospek): RedirectResponse
    {
        $user = auth()->user();
        if (!$user) {
            abort(401);
        }

        $userId = $user->id;
        $userRole = strtolower($user->role);
        $prospekId = $prospek->id;

        $result = DB::transaction(function () use ($prospekId, $user, $userId, $userRole) {
            // Atomic row locking to prevent race condition between concurrent requests
            $lead = Prospek::where('id', $prospekId)->lockForUpdate()->firstOrFail();
            \Illuminate\Support\Facades\Gate::authorize('takeover', $lead);

            // Double takeover prevention
            if ($lead->status_takeover === 'Sudah Di-takeover' || !is_null($lead->takeover_by_id)) {
                $takenByName = $lead->takeoverBy?->name ?? 'pengguna lain';
                return [
                    'success' => false,
                    'message' => 'Prospek ini sudah di-takeover sebelumnya oleh ' . $takenByName . '.',
                ];
            }

            // Presensi Siswa takeover by Sales
            if ($lead->source === Prospek::SOURCE_PRESENSI_SISWA || $lead->source === 'Presensi Siswa') {
                $lead->update([
                    'sales_id'        => $userId,
                    'status_takeover' => 'Sudah Di-takeover',
                    'takeover_by_id'  => $userId,
                    'takeover_at'     => now(),
                ]);

                ProspekTimeline::create([
                    'prospek_id'   => $lead->id,
                    'user_id'      => $userId,
                    'title'        => 'Takeover Presensi Siswa',
                    'notes'        => 'Prospek Presensi Siswa berhasil di-takeover oleh ' . $user->name . ' (' . $user->role . ').',
                    'status_after' => $lead->status,
                    'time'         => now(),
                ]);

                return [
                    'success' => true,
                    'message' => 'Prospek Presensi Siswa berhasil di-takeover oleh Anda.',
                ];
            }

            // Non-Presensi Siswa: Handover from Sales to CS
            $cs = \App\Models\User::where('role', 'CS')
                ->when($lead->wilayah_id, function ($q) use ($lead) {
                    return $q->where('wilayah_id', $lead->wilayah_id);
                })
                ->first();

            if (!$cs) {
                $cs = \App\Models\User::where('role', 'CS')->first();
            }

            if (!$cs) {
                return [
                    'success' => false,
                    'message' => 'Tidak ada user CS yang tersedia untuk menerima prospek.',
                ];
            }

            $lead->update([
                'cs_id'                  => $cs->id,
                'active_follow_up_count' => 0,
                'status_takeover'        => 'Sudah Di-takeover',
                'takeover_by_id'         => $userId,
                'takeover_at'            => now(),
            ]);

            ProspekTimeline::create([
                'prospek_id'   => $lead->id,
                'user_id'      => $userId,
                'title'        => 'Prospek Diserahkan ke CS',
                'notes'        => 'Prospek diserahkan ke CS: ' . $cs->name,
                'status_after' => $lead->status,
                'time'         => now(),
            ]);

            return [
                'success' => true,
                'message' => 'Prospek berhasil diserahkan ke CS ' . $cs->name,
            ];
        });

        if (!$result['success']) {
            return redirect()->back()->with('error', $result['message'])->withErrors(['takeover' => $result['message']]);
        }

        return redirect()->back()->with('success', $result['message']);
    }

    // ─── Private Helpers ─────────────────────────────────────────────────────

    /**
     * Format a Prospek model to array shape for Blade views (list view).
     */
    private function formatProspek(\App\Models\Prospek $p): array
    {
        $latestFollowUp = $p->latestFollowUp ?? $p->followUps->first();
        $authId = auth()->id();

        $sekolahNama = $p->sekolah?->nama ?? ($p->type === 'Sekolah' && $p->name !== $p->pic ? $p->name : '-');
        $wilayahNama = $p->sekolah?->wilayah?->nama ?? $p->wilayah?->nama ?? 'Belum Diisi';

        return [
            'id'             => $p->id,
            'name'           => $p->name,
            'sales_id'       => $p->sales_id,
            'owner_id'       => $p->owner_id,
            'cs_id'          => $p->cs_id,
            'takeover_by_id' => $p->takeover_by_id,
            'type'           => $p->type,
            'kelas'          => $p->kelas ?: 'Reguler',
            'sekolah_id'     => $p->sekolah_id,
            'perusahaan_id'  => $p->perusahaan_id,
            'prodi_id'       => $p->prodi_id,
            'prodi_nama'     => $p->prodi ? $p->prodi->nama : '-',
            'sekolah_name'   => $p->type === 'Sekolah' ? $sekolahNama : ($p->type === 'Corporate' ? ($p->perusahaan ? $p->perusahaan->nama : '-') : '-'),
            'wilayah_name'   => $wilayahNama,
            'sales_name'     => $p->sales ? $p->sales->name : '-',
            'cs_name'        => $p->cs ? $p->cs->name : ($p->owner && strtolower($p->owner->role ?? '') === 'cs' ? $p->owner->name : '-'),
            'category'       => $p->category ?? '-',
            'pic'            => $p->pic ?? '-',
            'pic_phone'      => $p->pic_phone ?? '-',
            'whatsapp'       => $p->whatsapp ?? '-',
            'wa_ortu'        => $p->wa_ortu ?? '',
            'asal_kelas'     => $p->asal_kelas ?? '',
            'prodi_lainnya'  => $p->prodi_lainnya ?? '',
            'status'         => $p->status,
            'stage_number'   => $p->stage_number,
            'takeover_sales' => $p->sales ? $p->sales->name : null,
            'takeover_cs'    => $p->cs ? $p->cs->name : ($p->owner && strtolower($p->owner->role ?? '') === 'cs' ? $p->owner->name : null),
            'active_takeover' => $p->activeHandlerLabel(),
            'owner'          => $p->owner_id ? ($p->owner?->name ?? 'User') : 'Sistem',
            'status_takeover' => $p->status_takeover ?: ($p->takeover_by_id ? 'Sudah Di-takeover' : 'Belum Di-takeover'),
            'takeover_by_name' => $p->takeoverBy?->name ?? null,
            'takeover_by_role' => $p->takeoverBy?->role ?? null,
            'takeover_at'    => $p->takeover_at ? $p->takeover_at->format('d M Y, H:i') : null,
            'is_presensi'    => (bool) ($p->source === Prospek::SOURCE_PRESENSI_SISWA || $p->source === 'Presensi Siswa'),
            'last_activity'  => $p->updated_at ? $p->updated_at->diffForHumans() : '-',
            'notes'          => $latestFollowUp && $latestFollowUp->catatan ? $latestFollowUp->catatan : ($p->notes ?? ''),
            'source'         => $p->source ?? '-',
            'lost_reason'    => $p->lost_reason,
            'lost_note'      => $p->lost_note,
            'created_at'     => $p->created_at ? $p->created_at->format('d M Y') : '-',
            'takeover_time'  => $p->updated_at ? $p->updated_at->format('d M Y, H:i') : '-',
            'last_contact'   => $latestFollowUp && $latestFollowUp->tanggal
                ? \Carbon\Carbon::parse($latestFollowUp->tanggal)->format('d M Y, H:i')
                : '-',
            'next_follow_up' => $latestFollowUp && $latestFollowUp->next_follow_up
                ? \Carbon\Carbon::parse($latestFollowUp->next_follow_up)->format('d M Y, H:i')
                : '-',
            'next_follow_up_date' => $latestFollowUp && $latestFollowUp->next_follow_up
                ? \Carbon\Carbon::parse($latestFollowUp->next_follow_up)->toDateString()
                : null,
            'metode_terakhir' => $latestFollowUp ? ($latestFollowUp->metode ?? '-') : '-',
            'hasil_terakhir'  => $latestFollowUp ? ($latestFollowUp->hasil ?? '-') : '-',
            'is_own'          => (bool) ($authId && ($p->sales_id == $authId || $p->owner_id == $authId || $p->takeover_by_id == $authId)),
            'is_other'        => (bool) ($authId && !($p->sales_id == $authId || $p->owner_id == $authId || $p->takeover_by_id == $authId) && (strtolower(optional($p->sales)->role ?? '') === 'sales' || strtolower(optional($p->owner)->role ?? '') === 'sales' || !is_null($p->sales_id))),
            'follow_up_count' => $p->follow_ups_count ?? ($p->relationLoaded('followUps') ? $p->followUps->count() : 0),
            'has_follow_up_history' => ($p->follow_ups_count > 0) || ($latestFollowUp !== null),
            'can_update_status' => (bool) (auth()->user() && auth()->user()->can('updateStatus', $p)),
        ];
    }

    /**
     * Format a Prospek model for detail view (includes timeline).
     */
    private function formatProspekDetail(\App\Models\Prospek $p): array
    {
        $base = $this->formatProspek($p);

        $base['timeline'] = $p->timelines->map(function ($t) {
            $timeObj = $t->time ?? $t->created_at;
            return [
                'id'            => $t->id,
                'user_id'       => $t->user_id,
                'time'          => $timeObj ? $timeObj->format('d M Y, H:i') : '-',
                'title'         => $t->title,
                'notes'         => $t->notes,
                'status_before' => $t->status_before,
                'status'        => $t->status_after,
                'user'          => $t->user ? $t->user->name : 'Sistem',
                'role'          => $t->user ? $t->user->role : 'System',
            ];
        })->toArray();

        $base['follow_ups'] = $p->followUps->map(function ($f) use ($p) {
            $isHelper = (bool) ($f->user_id && $f->user_id != $p->sales_id && $f->user_id != $p->owner_id);
            return [
                'id'             => $f->id,
                'user_id'        => $f->user_id,
                'user'           => $f->user ? $f->user->name : 'Sistem',
                'role'           => $f->user ? $f->user->role : 'Sales',
                'is_helper'      => $isHelper,
                'tanggal'        => \Carbon\Carbon::parse($f->tanggal)->format('d M Y, H:i'),
                'metode'         => $f->metode ?? '-',
                'hasil'          => $f->hasil ?? '-',
                'catatan'        => $f->catatan ?? '-',
                'next_follow_up' => $f->next_follow_up
                    ? \Carbon\Carbon::parse($f->next_follow_up)->format('d M Y, H:i')
                    : '-',
            ];
        })->toArray();

        return $base;
    }
}
