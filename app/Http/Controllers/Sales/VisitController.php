<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Kunjungan;
use App\Models\Sekolah;
use App\Models\Perusahaan;
use App\Models\Prospek;
use App\Models\ProspekTimeline;
use App\Models\Prodi;
use App\Models\User;
use App\Services\GeoLocationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class VisitController extends Controller
{
    /**
     * List all visits by the logged-in Sales user.
     */
    public function index(Request $request): View
    {
        $user = auth()->user();

        $query = Kunjungan::with(['sales', 'participants', 'sekolah', 'perusahaan', 'prodi', 'dosen', 'event'])
            ->where(function ($q) use ($user) {
                $q->where('sales_id', $user->id)
                  ->orWhereHas('participants', function ($pq) use ($user) {
                      $pq->where('users.id', $user->id);
                  });
            })
            ->orderBy('tanggal', 'desc');

        // Optional filter by type
        if ($request->filled('jenis')) {
            $jenis = ucfirst(strtolower($request->jenis));
            if (in_array($jenis, ['Corporate', 'Perusahaan'])) {
                $query->whereIn('jenis', ['Perusahaan', 'Corporate']);
            } elseif ($jenis === 'Sekolah') {
                $query->where('jenis', 'Sekolah');
            }
        }

        $visits = $query->get()->map(fn ($k) => $this->formatKunjungan($k))->toArray();

        $sekolahs    = Sekolah::where('status', 'Aktif')->orderBy('nama')->get();
        $perusahaans = Perusahaan::where('status', 'Aktif')->orderBy('nama')->get();
        $prodis      = Prodi::where('status', 'Aktif')->orderBy('nama')->get();
        $dosens      = User::whereIn('role', ['Dosen', 'Staff', 'Admin', 'SPV'])->where('status', 'Aktif')->get();

        return view('kunjungan.index', compact('visits', 'sekolahs', 'perusahaans', 'prodis', 'dosens'));
    }

    /**
     * Show form to create a new visit.
     * 
     * ALUR 1 — DARI EVENT: GET /sales/kunjungan/create?event_id=25
     *   → $event diisi, form menampilkan data event read-only.
     * 
     * ALUR 2 — MANDIRI: GET /sales/kunjungan/create
     *   → $event = null, form menampilkan dropdown sekolah/perusahaan.
     */
    public function create(Request $request): View
    {
        $sekolahs    = Sekolah::where('status', 'Aktif')->orderBy('nama')->get();
        $perusahaans = Perusahaan::where('status', 'Aktif')->orderBy('nama')->get();
        $prodis      = Prodi::where('status', 'Aktif')->orderBy('nama')->get();
        $dosens      = User::whereIn('role', ['Dosen', 'Staff', 'Admin', 'SPV'])->where('status', 'Aktif')->get();

        $event = null;

        // Load event jika ada event_id di query string (Alur 1: Kunjungan dari Event)
        if ($request->filled('event_id')) {
            $eventId = $request->event_id;

            // Pastikan Sales ini memang ditugaskan ke event tersebut
            $isAssigned = \DB::table('event_sales')
                ->where('event_id', $eventId)
                ->where('sales_id', auth()->id())
                ->exists();

            if ($isAssigned) {
                $event = Event::with(['sekolah', 'perusahaan', 'type'])->find($eventId);
            }
        }

        $user = auth()->user();
        $prospekQuery = Prospek::where('type', 'Sekolah');
        if ($user && $user->role === 'Sales') {
            $prospekQuery->where('sales_id', $user->id);
        } elseif ($user && $user->role === 'SPV') {
            $subIds = User::where('supervisor_id', $user->id)->pluck('id')->push($user->id);
            $prospekQuery->whereIn('sales_id', $subIds);
        }
        $prospekSekolah = $prospekQuery->orderBy('name')->get();

        $csUsers = collect([]);
        if ($user && $user->role === 'CS') {
            $csUsers = User::where('role', 'CS')
                ->where('status', 'Aktif')
                ->where('id', '!=', $user->id)
                ->orderBy('name')
                ->get();
        }

        return view('kunjungan.create', compact('sekolahs', 'perusahaans', 'prodis', 'dosens', 'event', 'prospekSekolah', 'csUsers'));
    }

    /**
     * Store a new field visit.
     * 
     * Menangani dua alur:
     * 1. event_id diisi   → Kunjungan dari Event (data instansi/PIC berasal dari Event)
     * 2. event_id = null  → Kunjungan Mandiri (Sales memilih instansi manual)
     */
    public function store(Request $request): RedirectResponse
    {
        $rawParticipants = $request->input('participant_ids', $request->input('cs_participants', []));
        if (!is_array($rawParticipants)) {
            $rawParticipants = !empty($rawParticipants) ? [$rawParticipants] : [];
        }

        $user = auth()->user();
        $isCs = $user && $user->role === 'CS';

        if (!$isCs && !empty($rawParticipants)) {
            throw ValidationException::withMessages([
                'participant_ids' => 'Fitur kolaborasi kunjungan hanya tersedia untuk pengguna dengan role CS.',
            ]);
        }

        $validParticipantIds = [];
        if ($isCs && !empty($rawParticipants)) {
            $participantIds = array_values(array_unique(array_filter(array_map('intval', $rawParticipants))));

            if (in_array($user->id, $participantIds, true)) {
                throw ValidationException::withMessages([
                    'participant_ids' => 'Anda tidak dapat menambahkan diri sendiri sebagai anggota kolaborasi.',
                ]);
            }

            $validCsUsers = User::whereIn('id', $participantIds)
                ->where('role', 'CS')
                ->pluck('id')
                ->toArray();

            if (count($validCsUsers) !== count($participantIds)) {
                throw ValidationException::withMessages([
                    'participant_ids' => 'Setiap anggota kolaborasi yang dipilih harus memiliki role CS.',
                ]);
            }

            $validParticipantIds = $validCsUsers;
        }

        $rules = [
            'jenis'          => 'required|in:Sekolah,Perusahaan',
            'prodi_id'       => 'nullable|exists:prodis,id',
            'prodi_ids'      => 'nullable|array',
            'prodi_ids.*'    => 'exists:prodis,id',
            'tanggal'        => 'required|date',
            'waktu'          => 'required|date_format:H:i',
            'catatan'        => 'nullable|string|max:2000',
            'foto'           => 'required|image|mimes:jpg,jpeg|max:5120', // 5MB max
            'lat'            => 'required|numeric',
            'lng'            => 'required|numeric',
            'dosen_id'       => 'nullable|exists:users,id',
            'dosen_pemateri' => 'nullable|string|max:255',
            'event_id'       => 'nullable|exists:events,id',
            'lokasi_penugasan' => 'nullable|string|max:1000',
        ];

        // ─── Alur 1: Kunjungan dari Event ───────────────────────────────────────
        $event = null;
        if ($request->filled('event_id')) {
            // Authorization: Sales harus ditugaskan ke event ini
            $isAssigned = \DB::table('event_sales')
                ->where('event_id', $request->event_id)
                ->where('sales_id', auth()->id())
                ->exists();

            if (!$isAssigned) {
                throw ValidationException::withMessages([
                    'event_id' => 'Anda tidak ditugaskan pada Event ini.',
                ]);
            }

            $event = Event::find($request->event_id);

            // PIC wajib dari event (sudah ada), jadi tidak perlu required dari input
            $rules['pic_name']     = 'nullable|string|max:255';
            $rules['pic_whatsapp'] = ['nullable', 'string', 'max:25', new \App\Rules\IndonesianWhatsApp(allowEmpty: true)];
        } else {
            // ─── Alur 2: Kunjungan Mandiri ──────────────────────────────────────
            if ($request->filled('pic_whatsapp')) {
                $request->merge(['pic_whatsapp' => \App\Support\WhatsAppHelper::prepareForValidation($request->pic_whatsapp)]);
            }
            $rules['pic_name']     = 'required|string|max:255';
            $rules['pic_whatsapp'] = ['required', 'string', 'max:25', new \App\Rules\IndonesianWhatsApp(allowEmpty: false)];
        }

        // Conditional rules based on jenis
        if ($request->input('jenis') === 'Sekolah') {
            $rules['sekolah_id']            = 'nullable|exists:sekolahs,id';
            $rules['sekolah_manual']        = 'nullable|string|max:255';
            $rules['nama_institusi']        = 'nullable|string|max:255';
            $rules['alamat']                = 'nullable|string|max:500';
            $rules['potensi_mahasiswa']      = 'nullable|string|max:255';
            $rules['detail_potensi_mahasiswa'] = 'nullable|string|max:500';
            $rules['kesediaan_training_ai'] = 'nullable|boolean';
        } else {
            $rules['perusahaan_id']         = 'nullable|exists:perusahaans,id';
            $rules['perusahaan_manual']     = 'nullable|string|max:255';
            $rules['nama_institusi']        = 'nullable|string|max:255';
            $rules['alamat']                = 'nullable|string|max:500';
            $rules['bidang_usaha']          = 'nullable|string|max:255';
            $rules['potensi_s1']            = 'nullable|string|max:255';
            $rules['potensi_s2']            = 'nullable|string|max:255';
            $rules['potensi_csr']           = 'nullable|string|max:255';
        }

        $validated = $request->validate($rules);

        // ─── Override data instansi dari Event (Alur 1) ─────────────────────────
        if ($event) {
            // Merge sekolah_id / perusahaan_id dari event agar relasi tersimpan
            if ($event->sekolah_id) {
                $validated['sekolah_id'] = $event->sekolah_id;
                $request->merge(['sekolah_id' => $event->sekolah_id]);
            }
            if ($event->perusahaan_id) {
                $validated['perusahaan_id'] = $event->perusahaan_id;
                $request->merge(['perusahaan_id' => $event->perusahaan_id]);
            }

            // PIC dan WA berasal dari Event
            $validated['pic_name']     = $event->pic_name ?: ($validated['pic_name'] ?? '-');
            $validated['pic_whatsapp'] = $event->pic_whatsapp ?: ($validated['pic_whatsapp'] ?? '-');
        }

        // Validation: Training requires Dosen Pemateri
        $isTraining = $request->boolean('kesediaan_training_ai') || $request->input('is_training');
        if ($isTraining && empty($validated['dosen_id']) && empty($validated['dosen_pemateri'])) {
            throw ValidationException::withMessages([
                'dosen_id' => 'Dosen Pemateri wajib diisi untuk kunjungan / event Training.',
            ]);
        }

        $user = auth()->user();

        // Handle photo upload
        $fotoPath = null;
        if ($request->hasFile('foto') && $request->file('foto')->isValid()) {
            $fotoPath = $request->file('foto')->store('kunjungan', 'public');
        }

        // Generate unique visit number
        $nomor = 'KNJ-' . $user->id . '-' . now()->format('YmdHis') . '-' . strtoupper(\Illuminate\Support\Str::random(4));

        // Determine destination target (tujuan)
        $tujuanId      = 0;
        $namaInstitusi = $validated['nama_institusi'] ?? null;
        $tujuan        = null;
        $tier          = null;
        $budget        = null;

        if ($validated['jenis'] === 'Sekolah') {
            if (!empty($request->sekolah_manual)) {
                $manualName = trim($request->sekolah_manual);
                if ($manualName !== '') {
                    $sekolah = Sekolah::firstOrCreate(
                        ['nama' => $manualName],
                        [
                            'kode' => 'SCH-' . strtoupper(\Illuminate\Support\Str::random(6)),
                            'status' => 'Aktif',
                            'pic_name' => $validated['pic_name'] ?? null,
                            'pic_phone' => $validated['pic_whatsapp'] ?? null,
                        ]
                    );
                    $validated['sekolah_id'] = $sekolah->id;
                    $namaInstitusi = $sekolah->nama;
                    $tujuan = $sekolah;
                    $tujuanId = $sekolah->id;
                }
            } elseif (!empty($validated['sekolah_id'])) {
                $tujuan = Sekolah::find($validated['sekolah_id']);
            }
        } elseif ($validated['jenis'] === 'Perusahaan') {
            if (!empty($request->perusahaan_manual)) {
                $manualName = trim($request->perusahaan_manual);
                if ($manualName !== '') {
                    $perusahaan = Perusahaan::firstOrCreate(
                        ['nama' => $manualName],
                        [
                            'kode' => 'CORP-' . strtoupper(\Illuminate\Support\Str::random(6)),
                            'status' => 'Aktif',
                            'pic_name' => $validated['pic_name'] ?? null,
                            'pic_phone' => $validated['pic_whatsapp'] ?? null,
                        ]
                    );
                    $validated['perusahaan_id'] = $perusahaan->id;
                    $namaInstitusi = $perusahaan->nama;
                    $tujuan = $perusahaan;
                    $tujuanId = $perusahaan->id;
                }
            } elseif (!empty($validated['perusahaan_id'])) {
                $tujuan = Perusahaan::find($validated['perusahaan_id']);
            }
        }

        if ($tujuan) {
            $tujuanId = $tujuan->id;
            // Jika nama_institusi belum ada dari form, pakai nama dari master data
            if (!$namaInstitusi) {
                $namaInstitusi = $tujuan->nama;
            }
            if ($validated['jenis'] === 'Sekolah') {
                $tier   = $tujuan->tier ?? 'B';
                $budget = $tujuan->max_budget;
            }
        }

        // Jika dari Event: nama institusi dari event jika belum ada
        if ($event && !$namaInstitusi) {
            $namaInstitusi = $event->nama_institusi ?: $event->lokasi ?: 'Kunjungan Event';
        }

        if (!$namaInstitusi) {
            $namaInstitusi = 'Kunjungan';
        }

        // Validate Geolocation via GeoLocationService
        $geoResult = GeoLocationService::validateVisitLocation(
            (float) $validated['lat'],
            (float) $validated['lng'],
            $tujuan && !empty($tujuan->lat) ? (float) $tujuan->lat : null,
            $tujuan && !empty($tujuan->lng) ? (float) $tujuan->lng : null
        );

        if ($geoResult['target_lat_updated'] && $tujuan) {
            $tujuan->update([
                'lat' => $validated['lat'],
                'lng' => $validated['lng'],
            ]);
        }

        $rawProdiIds = $request->input('prodi_ids', []);
        if (!is_array($rawProdiIds)) {
            $rawProdiIds = !empty($rawProdiIds) ? [$rawProdiIds] : [];
        }
        if (empty($rawProdiIds) && $request->filled('prodi_id')) {
            $rawProdiIds = [$request->input('prodi_id')];
        }
        $prodiIds = array_values(array_unique(array_filter(array_map('intval', $rawProdiIds))));
        $firstProdiId = $prodiIds[0] ?? $validated['prodi_id'] ?? ($event->prodi_id ?? null);

        $kunjungan = Kunjungan::create([
            'nomor'                    => $nomor,
            'tanggal'                  => $validated['tanggal'],
            'waktu'                    => $validated['waktu'] . ':00',
            'sales_id'                 => $user->id,
            'prodi_id'                 => $firstProdiId,
            'prodi_ids'                => !empty($prodiIds) ? $prodiIds : ($firstProdiId ? [(int)$firstProdiId] : null),
            'jenis'                    => $validated['jenis'],
            'tujuan_id'                => $tujuanId > 0 ? $tujuanId : 0,
            'tujuan_kunjungan'         => $namaInstitusi,
            'hasil'                    => 'Kunjungan ' . $validated['jenis'] . ' — ' . $namaInstitusi,
            'catatan'                  => $validated['catatan'] ?? null,
            'status'                   => $geoResult['status'],
            'status_lokasi'            => $geoResult['status_lokasi'],
            'status_verifikasi'        => $geoResult['status_verifikasi'],
            'jarak_meter'              => $geoResult['distance_meters'],
            'is_outside_radius'        => $geoResult['is_outside_radius'],
            'is_verified'              => $geoResult['is_verified'],
            // Detail fields
            'nama_institusi'           => $namaInstitusi,
            'tier'                     => $tier,
            'budget_maksimum'          => $budget,
            'alamat'                   => $validated['alamat'] ?? null,
            'lokasi_penugasan'         => $validated['lokasi_penugasan'] ?? null,
            // event_id: diisi untuk Alur 1, NULL untuk Alur 2
            'event_id'                 => $validated['event_id'] ?? null,
            'pic_name'                 => $validated['pic_name'] ?? '-',
            'pic_whatsapp'             => \App\Support\WhatsAppHelper::formatForDatabase($validated['pic_whatsapp'] ?? null) ?? '-',
            'foto_path'                => $fotoPath,
            'lat'                      => $validated['lat'],
            'lng'                      => $validated['lng'],
            // Lecturer for Training
            'dosen_id'                 => $validated['dosen_id'] ?? null,
            'dosen_pemateri'           => $validated['dosen_pemateri'] ?? null,
            // School-specific
            'potensi_mahasiswa'        => $request->input('potensi_mahasiswa'),
            'detail_potensi_mahasiswa' => $request->input('detail_potensi_mahasiswa'),
            'kesediaan_training_ai'    => $request->boolean('kesediaan_training_ai'),
            // Corporate-specific
            'bidang_usaha'             => $request->input('bidang_usaha'),
            'potensi_s1'               => $request->input('potensi_s1'),
            'potensi_s2'               => $request->input('potensi_s2'),
            'potensi_csr'              => $request->input('potensi_csr'),
        ]);

        if ($isCs && !empty($validParticipantIds)) {
            $kunjungan->participants()->sync($validParticipantIds);
        }

        // Clear needs_visit_report for matching prospect owned by this sales user
        if ($request->filled('prospek_id')) {
            $matchedProspek = Prospek::find($request->prospek_id);
            if ($matchedProspek) {
                $matchedProspek->needs_visit_report = false;
                if ($matchedProspek->status === 'BARU') {
                    $matchedProspek->status = 'KONTAK';
                    $matchedProspek->stage_number = 2;
                }
                $matchedProspek->save();

                ProspekTimeline::create([
                    'prospek_id'   => $matchedProspek->id,
                    'user_id'      => $user->id,
                    'title'        => 'Laporan Kunjungan Selesai',
                    'notes'        => 'Kunjungan langsung telah dilaporkan (' . $nomor . ')',
                    'status_after' => $matchedProspek->status,
                    'time'         => now(),
                ]);
            }
        } elseif ($tujuanId > 0) {
            $column = $validated['jenis'] === 'Sekolah' ? 'sekolah_id' : 'perusahaan_id';
            Prospek::where('sales_id', $user->id)
                ->where($column, $tujuanId)
                ->where('needs_visit_report', true)
                ->update(['needs_visit_report' => false]);
        }

        if ($request->boolean('jadikan_prospek')) {
            $wilayahId = $user->wilayah_id;

            $prospek = Prospek::create([
                'name'         => $namaInstitusi,
                'type'         => $validated['jenis'],
                'category'     => null,
                'sekolah_id'   => $validated['jenis'] === 'Sekolah' ? ($validated['sekolah_id'] ?? null) : null,
                'perusahaan_id'=> in_array($validated['jenis'], ['Perusahaan', 'Corporate']) ? ($validated['perusahaan_id'] ?? null) : null,
                'prodi_id'     => $firstProdiId,
                'pic'          => $validated['pic_name'] ?? '-',
                'whatsapp'     => $validated['pic_whatsapp'] ?? '-',
                'status'       => 'BARU',
                'stage_number' => 1,
                'notes'        => 'Kunjungan ' . $nomor,
                'source'       => 'Kunjungan Langsung',
                'sales_id'     => $user->id,
                'cs_id'        => null,
                'wilayah_id'   => $wilayahId,
                'owner_id'     => $user->id,
            ]);

            ProspekTimeline::create([
                'prospek_id'   => $prospek->id,
                'user_id'      => $user->id,
                'title'        => 'Prospek Dibuat dari Kunjungan',
                'notes'        => 'Prospek baru ditambahkan otomatis dari pelaporan kunjungan (' . $nomor . ')',
                'status_after' => $prospek->status,
                'time'         => now(),
            ]);
        }

        $redirectRoute = auth()->user()->role === 'Sales' && \Illuminate\Support\Facades\Route::has('sales.kunjungan.index')
            ? 'sales.kunjungan.index'
            : 'kunjungan.index';

        return redirect()->route($redirectRoute)
            ->with('success', 'Kunjungan berhasil disimpan!');
    }

    /**
     * Confirm event attendance.
     */
    public function confirmEvent(Request $request, $eventId): RedirectResponse
    {
        $request->validate([
            'kehadiran' => 'required|in:Hadir,Tidak Hadir',
            'catatan'   => 'nullable|string|max:2000',
            'foto'      => 'nullable|image|mimes:jpg,jpeg|max:5120',
        ]);

        $event = \App\Models\Event::findOrFail($eventId);
        $user = auth()->user();

        // Handle photo upload
        $fotoPath = null;
        if ($request->hasFile('foto') && $request->file('foto')->isValid()) {
            $fotoPath = $request->file('foto')->store('kunjungan', 'public');
        }

        \Illuminate\Support\Facades\DB::table('event_sales')
            ->where('event_id', $eventId)
            ->where('sales_id', $user->id)
            ->update([
                'kehadiran'         => $request->kehadiran,
                'catatan_kehadiran' => $request->catatan,
                'foto_kehadiran'    => $fotoPath,
            ]);

        return redirect()->back()->with('success', 'Kehadiran event berhasil dikonfirmasi.');
    }

    /**
     * Show visit detail.
     */
    public function show(Kunjungan $kunjungan): View
    {
        $this->authorize('view', $kunjungan);

        $kunjungan->load(['sales', 'participants', 'sekolah', 'perusahaan', 'prodi', 'dosen', 'event']);
        $visit = $this->formatKunjungan($kunjungan);
        return view('kunjungan.show', compact('visit'));
    }

    /**
     * Update visit record.
     */
    public function update(Request $request, Kunjungan $kunjungan): RedirectResponse
    {
        $this->authorize('update', $kunjungan);

        $user = auth()->user();
        $isCs = $user && $user->role === 'CS';

        if ($request->has('participant_ids') || $request->has('cs_participants')) {
            $rawParticipants = $request->input('participant_ids', $request->input('cs_participants', []));
            if (!is_array($rawParticipants)) {
                $rawParticipants = !empty($rawParticipants) ? [$rawParticipants] : [];
            }

            if (!$isCs && !empty($rawParticipants)) {
                throw ValidationException::withMessages([
                    'participant_ids' => 'Fitur kolaborasi kunjungan hanya tersedia untuk pengguna dengan role CS.',
                ]);
            }

            $validParticipantIds = [];
            if ($isCs && !empty($rawParticipants)) {
                $participantIds = array_values(array_unique(array_filter(array_map('intval', $rawParticipants))));

                if (in_array((int)$kunjungan->sales_id, $participantIds, true)) {
                    throw ValidationException::withMessages([
                        'participant_ids' => 'Pembuat laporan tidak dapat ditambahkan sebagai anggota kolaborasi.',
                    ]);
                }

                $validCsUsers = User::whereIn('id', $participantIds)
                    ->where('role', 'CS')
                    ->pluck('id')
                    ->toArray();

                if (count($validCsUsers) !== count($participantIds)) {
                    throw ValidationException::withMessages([
                        'participant_ids' => 'Setiap anggota kolaborasi yang dipilih harus memiliki role CS.',
                    ]);
                }

                $validParticipantIds = $validCsUsers;
            }

            if ($isCs) {
                $kunjungan->participants()->sync($validParticipantIds);
            }
        }

        if ($request->filled('pic_whatsapp')) {
            $request->merge(['pic_whatsapp' => \App\Support\WhatsAppHelper::prepareForValidation($request->pic_whatsapp)]);
        }

        $validated = $request->validate([
            'tanggal'                  => 'required|date',
            'waktu'                    => 'nullable|string',
            'nama_institusi'           => 'nullable|string|max:255',
            'tujuan_kunjungan'         => 'nullable|string|max:255',
            'pic_name'                 => 'nullable|string|max:255',
            'pic_whatsapp'             => ['nullable', 'string', 'max:25', new \App\Rules\IndonesianWhatsApp(allowEmpty: true)],
            'alamat'                   => 'nullable|string|max:500',
            'catatan'                  => 'nullable|string|max:2000',
            'hasil'                    => 'nullable|string|max:2000',
            'status'                   => 'nullable|string|in:Menunggu,Proses,Selesai',
            'foto'                     => 'nullable|image|mimes:jpg,jpeg|max:5120',
            'potensi_mahasiswa'        => 'nullable|string|max:255',
            'detail_potensi_mahasiswa' => 'nullable|string|max:500',
            'kesediaan_training_ai'    => 'nullable|boolean',
            'bidang_usaha'             => 'nullable|string|max:255',
            'potensi_s1'               => 'nullable|string|max:255',
            'potensi_s2'               => 'nullable|string|max:255',
            'potensi_csr'              => 'nullable|string|max:255',
            'dosen_pemateri'           => 'nullable|string|max:255',
            'dosen_id'                 => 'nullable|exists:users,id',
        ]);

        if ($request->hasFile('foto') && $request->file('foto')->isValid()) {
            if ($kunjungan->foto_path) {
                Storage::disk('public')->delete($kunjungan->foto_path);
            }
            $validated['foto_path'] = $request->file('foto')->store('kunjungan', 'public');
        }

        if ($request->filled('nama_institusi')) {
            $validated['tujuan_kunjungan'] = $request->nama_institusi;
        } elseif ($request->filled('tujuan_kunjungan')) {
            $validated['nama_institusi'] = $request->tujuan_kunjungan;
        }

        if ($request->filled('catatan') && !$request->filled('hasil')) {
            $validated['hasil'] = $request->catatan;
        } elseif ($request->filled('hasil') && !$request->filled('catatan')) {
            $validated['catatan'] = $request->hasil;
        }

        if ($request->has('kesediaan_training_ai')) {
            $validated['kesediaan_training_ai'] = $request->boolean('kesediaan_training_ai');
        }

        if (array_key_exists('pic_whatsapp', $validated)) {
            $validated['pic_whatsapp'] = \App\Support\WhatsAppHelper::formatForDatabase($validated['pic_whatsapp']);
        }

        $kunjungan->update($validated);

        return redirect()->back()->with('success', 'Data kunjungan berhasil diperbarui!');
    }

    /**
     * Delete a visit record.
     */
    public function destroy(Kunjungan $kunjungan): RedirectResponse
    {
        $this->authorize('delete', $kunjungan);

        if ($kunjungan->foto_path) {
            Storage::disk('public')->delete($kunjungan->foto_path);
        }

        $kunjungan->delete();

        $referer = request()->header('referer');
        if ($referer && str_contains($referer, '/kunjungan/' . $kunjungan->id)) {
            $route = \Illuminate\Support\Facades\Route::has('sales.kunjungan.index') ? 'sales.kunjungan.index' : 'kunjungan.index';
            return redirect()->route($route)->with('success', 'Kunjungan berhasil dihapus.');
        }

        return redirect()->back()->with('success', 'Kunjungan berhasil dihapus.');
    }

    // ─── Private Helpers ─────────────────────────────────────────────────────

    private function formatKunjungan(Kunjungan $k): array
    {
        $potential = '-';
        if ($k->jenis === 'Sekolah') {
            $potential = $k->potensi_mahasiswa ?? $k->potensi_beasiswa ?? $k->hasil ?? '-';
        } else {
            $parts = array_filter([
                $k->potensi_s1 ? 'S1: ' . $k->potensi_s1 : null,
                $k->potensi_s2 ? 'S2: ' . $k->potensi_s2 : null,
                $k->potensi_csr ? 'CSR: ' . $k->potensi_csr : null,
            ]);
            $potential = count($parts) ? implode(' | ', $parts) : ($k->hasil ?? '-');
        }

        $photoUrl = $k->foto_path ? Storage::url($k->foto_path) : null;

        $user = auth()->user();
        $isCreator = $user ? ((int) $k->sales_id === (int) $user->id) : false;
        $isParticipant = $user ? $k->participants->contains('id', $user->id) : false;
        $participantsList = $k->participants->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'role' => $p->role,
        ])->values()->toArray();

        return [
            'id'                       => $k->id,
            'name'                     => $k->nama_institusi ?? $k->tujuan_kunjungan ?? '-',
            'type'                     => $k->jenis ?? '-',
            'pic'                      => $k->pic_name ?? '-',
            'whatsapp'                 => $k->pic_whatsapp ?? '-',
            'sales'                    => $k->sales ? $k->sales->name : '-',
            'creator_id'               => $k->sales_id,
            'creator_name'             => $k->sales ? $k->sales->name : '-',
            'creator_role'             => $k->sales ? $k->sales->role : '-',
            'is_creator'               => $isCreator,
            'is_shared'                => !$isCreator && $isParticipant,
            'participants'             => $participantsList,
            'participants_count'       => count($participantsList),
            'participants_str'         => $k->participants->pluck('name')->implode(', '),
            'prodi'                    => $k->prodi_names ?: ($k->prodi ? $k->prodi->nama : '-'),
            'prodi_id'                 => $k->prodi_id,
            'prodi_ids'                => $k->prodi_ids ?? ($k->prodi_id ? [$k->prodi_id] : []),
            'dosen'                    => $k->dosen ? $k->dosen->name : ($k->dosen_pemateri ?? '-'),
            'dosen_id'                 => $k->dosen_id,
            'dosen_pemateri'           => $k->dosen_pemateri,
            'date'                     => $k->tanggal ? $k->tanggal->format('d M Y') : '-',
            'raw_date'                 => $k->tanggal ? \Carbon\Carbon::parse($k->tanggal)->format('Y-m-d') : '',
            'time'                     => $k->waktu ?? '-',
            'raw_time'                 => $k->waktu ? \Carbon\Carbon::parse($k->waktu)->format('H:i') : '',
            'address'                  => $k->alamat ?? '-',
            'potential'                => $potential,
            'photo'                    => $photoUrl,
            'notes'                    => $k->catatan ?? $k->hasil ?? '-',
            'status'                   => $k->status,
            'status_lokasi'            => $k->status_lokasi ?? ($k->is_verified ? 'Valid' : 'Perlu Verifikasi'),
            'status_verifikasi'        => $k->status_verifikasi ?? ($k->is_verified ? 'Valid' : 'Perlu Verifikasi'),
            'jarak_meter'              => (float) ($k->jarak_meter ?? 0),
            'is_outside_radius'        => (bool) $k->is_outside_radius,
            'is_verified'              => (bool) $k->is_verified,
            'nomor'                    => $k->nomor,
            'qr_code'                  => $k->qr_code,
            'tier'                     => $k->tier,
            'budget_maksimum'          => $k->budget_maksimum,
            // Event linkage (Alur 1)
            'event_id'                 => $k->event_id,
            'event_name'               => $k->event ? ($k->event->nama ?? $k->event->name) : null,
            // School-specific
            'potensi_mahasiswa'        => $k->potensi_mahasiswa ?? '-',
            'detail_potensi_mahasiswa' => $k->detail_potensi_mahasiswa ?? '-',
            'kesediaan_training_ai'    => $k->kesediaan_training_ai,
            // Corporate-specific
            'bidang_usaha'             => $k->bidang_usaha ?? '-',
            'potensi_s1'               => $k->potensi_s1 ?? '-',
            'potensi_s2'               => $k->potensi_s2 ?? '-',
            'potensi_csr'              => $k->potensi_csr ?? '-',
            'created_at'               => $k->created_at ? $k->created_at->format('d M Y, H:i') : '-',
        ];
    }
}
