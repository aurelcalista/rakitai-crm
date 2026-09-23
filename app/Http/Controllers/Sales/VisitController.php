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

        $query = Kunjungan::with(['sales', 'sekolah', 'perusahaan', 'prodi', 'dosen', 'event'])
            ->where('sales_id', $user->id)
            ->orderBy('tanggal', 'desc');

        // Optional filter by type
        if ($request->filled('jenis') && in_array($request->jenis, ['Sekolah', 'Perusahaan'])) {
            $query->where('jenis', $request->jenis);
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

        return view('kunjungan.create', compact('sekolahs', 'perusahaans', 'prodis', 'dosens', 'event'));
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
        $rules = [
            'jenis'          => 'required|in:Sekolah,Perusahaan',
            'prodi_id'       => 'nullable|exists:prodis,id',
            'tanggal'        => 'required|date',
            'waktu'          => 'required|date_format:H:i',
            'catatan'        => 'nullable|string|max:2000',
            'foto'           => 'required|image|mimes:jpeg,jpg,png,webp|max:5120', // 5MB max
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
            $rules['pic_whatsapp'] = 'nullable|string|max:20';
        } else {
            // ─── Alur 2: Kunjungan Mandiri ──────────────────────────────────────
            $rules['pic_name']     = 'required|string|max:255';
            $rules['pic_whatsapp'] = 'required|string|max:20';
        }

        // Conditional rules based on jenis
        if ($request->input('jenis') === 'Sekolah') {
            $rules['sekolah_id']            = 'nullable|exists:sekolahs,id';
            $rules['nama_institusi']        = 'nullable|string|max:255';
            $rules['alamat']                = 'nullable|string|max:500';
            $rules['potensi_mahasiswa']      = 'nullable|string|max:255';
            $rules['detail_potensi_mahasiswa'] = 'nullable|string|max:500';
            $rules['kesediaan_training_ai'] = 'nullable|boolean';
        } else {
            $rules['perusahaan_id']   = 'nullable|exists:perusahaans,id';
            $rules['nama_institusi']  = 'nullable|string|max:255';
            $rules['alamat']          = 'nullable|string|max:500';
            $rules['bidang_usaha']    = 'nullable|string|max:255';
            $rules['potensi_s1']      = 'nullable|string|max:255';
            $rules['potensi_s2']      = 'nullable|string|max:255';
            $rules['potensi_csr']     = 'nullable|string|max:255';
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

        if ($validated['jenis'] === 'Sekolah' && !empty($validated['sekolah_id'])) {
            $tujuan = Sekolah::find($validated['sekolah_id']);
        } elseif ($validated['jenis'] === 'Perusahaan' && !empty($validated['perusahaan_id'])) {
            $tujuan = Perusahaan::find($validated['perusahaan_id']);
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

        $kunjungan = Kunjungan::create([
            'nomor'                    => $nomor,
            'tanggal'                  => $validated['tanggal'],
            'waktu'                    => $validated['waktu'] . ':00',
            'sales_id'                 => $user->id,
            'prodi_id'                 => $validated['prodi_id'] ?? ($event->prodi_id ?? null),
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
            'pic_whatsapp'             => $validated['pic_whatsapp'] ?? '-',
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

        // Clear needs_visit_report for matching prospect owned by this sales user
        if ($tujuanId > 0) {
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
                'prodi_id'     => $validated['prodi_id'] ?? ($event->prodi_id ?? null),
                'pic'          => $validated['pic_name'] ?? '-',
                'whatsapp'     => $validated['pic_whatsapp'] ?? '-',
                'status'       => 'BARU',
                'stage_number' => 1,
                'potential'    => $validated['jenis'] === 'Sekolah' ? $request->input('potensi_mahasiswa') : ($request->input('potensi_s1') . ' ' . $request->input('potensi_csr')),
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

        return redirect()->route('sales.kunjungan.index')
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
            'foto'      => 'nullable|image|mimes:jpeg,jpg,png,webp|max:5120',
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
     * Only the owning Sales user can view their own visits.
     */
    public function show(Kunjungan $kunjungan): View
    {
        // Backend authorization: only own visits
        if ($kunjungan->sales_id !== auth()->id()) {
            abort(403, 'Anda tidak memiliki akses ke kunjungan ini.');
        }

        $kunjungan->load(['sales', 'sekolah', 'perusahaan', 'prodi', 'dosen', 'event']);
        $visit = $this->formatKunjungan($kunjungan);
        return view('kunjungan.show', compact('visit'));
    }

    /**
     * Delete a visit record.
     * Authorization via KunjunganPolicy: Sales hanya bisa menghapus kunjungannya sendiri.
     */
    public function destroy(Kunjungan $kunjungan): RedirectResponse
    {
        // Gate check via KunjunganPolicy::delete()
        $this->authorize('delete', $kunjungan);

        // Hapus foto dari storage jika ada
        if ($kunjungan->foto_path) {
            Storage::disk('public')->delete($kunjungan->foto_path);
        }

        $kunjungan->delete();

        return redirect()
            ->route('sales.kunjungan.index')
            ->with('success', 'Kunjungan berhasil dihapus.');
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

        return [
            'id'                       => $k->id,
            'name'                     => $k->nama_institusi ?? $k->tujuan_kunjungan ?? '-',
            'type'                     => $k->jenis ?? '-',
            'pic'                      => $k->pic_name ?? '-',
            'whatsapp'                 => $k->pic_whatsapp ?? '-',
            'sales'                    => $k->sales ? $k->sales->name : '-',
            'prodi'                    => $k->prodi ? $k->prodi->nama : '-',
            'prodi_id'                 => $k->prodi_id,
            'dosen'                    => $k->dosen ? $k->dosen->name : ($k->dosen_pemateri ?? '-'),
            'dosen_id'                 => $k->dosen_id,
            'dosen_pemateri'           => $k->dosen_pemateri,
            'date'                     => $k->tanggal ? $k->tanggal->format('d M Y') : '-',
            'time'                     => $k->waktu ?? '-',
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
