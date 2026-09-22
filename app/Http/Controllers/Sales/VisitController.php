<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Kunjungan;
use App\Models\Sekolah;
use App\Models\Perusahaan;
use App\Models\Prospek;
use App\Models\ProspekTimeline;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;

class VisitController extends Controller
{
    /**
     * List all visits by the logged-in Sales user.
     */
    public function index(Request $request): View
    {
        $user = auth()->user();

        $query = Kunjungan::where('sales_id', $user->id)
            ->orderBy('tanggal', 'desc');

        // Optional filter by type
        if ($request->filled('jenis') && in_array($request->jenis, ['Sekolah', 'Perusahaan'])) {
            $query->where('jenis', $request->jenis);
        }

        $visits = $query->get()->map(fn ($k) => $this->formatKunjungan($k))->toArray();

        $sekolahs    = Sekolah::where('status', 'Aktif')->orderBy('nama')->get();
        $perusahaans = Perusahaan::where('status', 'Aktif')->orderBy('nama')->get();

        return view('kunjungan.index', compact('visits', 'sekolahs', 'perusahaans'));
    }

    /**
     * Show form to create a new visit.
     */
    public function create(): View
    {
        $sekolahs    = Sekolah::where('status', 'Aktif')->orderBy('nama')->get();
        $perusahaans = Perusahaan::where('status', 'Aktif')->orderBy('nama')->get();

        return view('kunjungan.create', compact('sekolahs', 'perusahaans'));
    }

    /**
     * Store a new field visit.
     * Handles photo upload to storage/app/public/kunjungan/
     * No GPS/geolocation — per requirements.
     */
    public function store(Request $request): RedirectResponse
    {
        $rules = [
            'jenis'      => 'required|in:Sekolah,Perusahaan',
            'tanggal'    => 'required|date',
            'waktu'      => 'required|date_format:H:i',
            'pic_name'   => 'required|string|max:255',
            'pic_whatsapp' => 'required|string|max:20',
            'catatan'    => 'nullable|string|max:2000',
            'foto'       => 'required|image|mimes:jpeg,jpg,png,webp|max:5120', // 5MB max
            'lat'        => 'required|numeric',
            'lng'        => 'required|numeric',
        ];

        // Conditional rules based on jenis
        if ($request->input('jenis') === 'Sekolah') {
            $rules['sekolah_id']        = 'nullable|exists:sekolahs,id';
            $rules['nama_institusi']    = 'nullable|string|max:255';
            $rules['alamat']            = 'nullable|string|max:500';
            $rules['potensi_beasiswa']  = 'nullable|string|max:255';
            $rules['detail_beasiswa']   = 'nullable|string|max:500';
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

        $user = auth()->user();

        // Handle photo upload
        $fotoPath = null;
        if ($request->hasFile('foto') && $request->file('foto')->isValid()) {
            $fotoPath = $request->file('foto')->store('kunjungan', 'public');
        }

        // Generate visit number
        $nomor = 'KNJ-' . $user->id . '-' . now()->format('YmdHis');

        // Determine tujuan_id and nama_institusi based on jenis and master data
        $tujuanId = 0;
        $namaInstitusi = $validated['nama_institusi'] ?? 'Kunjungan';
        $tujuan = null;
        $isVerified = false;
        if ($validated['jenis'] === 'Sekolah' && $request->filled('sekolah_id')) {
            $tujuan = Sekolah::find($request->sekolah_id);
        } elseif ($validated['jenis'] === 'Perusahaan' && $request->filled('perusahaan_id')) {
            $tujuan = Perusahaan::find($request->perusahaan_id);
        }

        if ($tujuan) {
            $tujuanId = $tujuan->id;
            $namaInstitusi = $tujuan->nama;

            if (empty($tujuan->lat) || empty($tujuan->lng)) {
                $tujuan->update([
                    'lat' => $validated['lat'],
                    'lng' => $validated['lng']
                ]);
                $isVerified = true;
            } else {
                $distance = $this->calculateDistance($validated['lat'], $validated['lng'], $tujuan->lat, $tujuan->lng);
                $isVerified = $distance <= 100;
            }
        }

        $kunjungan = Kunjungan::create([
            'nomor'           => $nomor,
            'tanggal'         => $validated['tanggal'],
            'waktu'           => $validated['waktu'] . ':00',
            'sales_id'        => $user->id,
            'jenis'           => $validated['jenis'],
            'tujuan_id'       => $tujuanId,
            'tujuan_kunjungan'=> $namaInstitusi,
            'hasil'           => 'Kunjungan ' . $validated['jenis'] . ' — ' . $namaInstitusi,
            'catatan'         => $validated['catatan'] ?? null,
            'status'          => $isVerified ? 'Selesai' : 'Perlu Verifikasi',
            // Detail fields
            'nama_institusi'  => $namaInstitusi,
            'alamat'          => $validated['alamat'] ?? null,
            'pic_name'        => $validated['pic_name'],
            'pic_whatsapp'    => $validated['pic_whatsapp'],
            'foto_path'       => $fotoPath,
            'lat'             => $validated['lat'],
            'lng'             => $validated['lng'],
            'is_verified'     => $isVerified,
            // School-specific
            'potensi_mahasiswa'       => $request->input('potensi_mahasiswa'),
            'detail_potensi_mahasiswa'=> $request->input('detail_potensi_mahasiswa'),
            'kesediaan_training_ai'   => $request->boolean('kesediaan_training_ai'),
            // Corporate-specific
            'bidang_usaha' => $request->input('bidang_usaha'),
            'potensi_s1'   => $request->input('potensi_s1'),
            'potensi_s2'   => $request->input('potensi_s2'),
            'potensi_csr'  => $request->input('potensi_csr'),
        ]);

        // Clear needs_visit_report for matching prospect owned by this sales user
        if ($tujuanId > 0) {
            $column = $validated['jenis'] === 'Sekolah' ? 'sekolah_id' : 'perusahaan_id';
            \App\Models\Prospek::where('sales_id', $user->id)
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
                'sekolah_id'   => $validated['jenis'] === 'Sekolah' ? ($request->sekolah_id ?? null) : null,
                'perusahaan_id'=> $validated['jenis'] === 'Perusahaan' || $validated['jenis'] === 'Corporate' ? ($request->perusahaan_id ?? null) : null,
                'pic'          => $validated['pic_name'],
                'whatsapp'     => $validated['pic_whatsapp'],
                'status'       => 'BARU',
                'stage_number' => 1,
                'potential'    => $validated['jenis'] === 'Sekolah' ? $request->input('potensi_mahasiswa') : ($request->input('potensi_s1') . ' ' . $request->input('potensi_csr')),
                'notes'        => $validated['catatan'] ?? 'Ditambahkan otomatis dari laporan kunjungan ' . $nomor,
                'source'       => 'Kunjungan Langsung',
                'sales_id'     => $user->id,
                'cs_id'        => null,
                'wilayah_id'   => $wilayahId,
                'owner_id'     => $user->id,
            ]);

            ProspekTimeline::create([
                'prospek_id'  => $prospek->id,
                'user_id'     => $user->id,
                'title'       => 'Prospek Dibuat dari Kunjungan',
                'notes'       => 'Prospek baru ditambahkan otomatis dari pelaporan kunjungan (' . $nomor . ')',
                'status_after' => $prospek->status,
                'time'         => now(),
            ]);
        }

        return redirect()->route('sales.kunjungan.index')
            ->with('success', 'Kunjungan berhasil disimpan!');
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

    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000; // in meters
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }

    private function formatKunjungan(Kunjungan $k): array
    {
        $potential = '-';
        if ($k->jenis === 'Sekolah') {
            $potential = $k->potensi_beasiswa ?? $k->hasil ?? '-';
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
            'id'             => $k->id,
            'name'           => $k->nama_institusi ?? $k->tujuan_kunjungan ?? '-',
            'type'           => $k->jenis ?? '-',
            'pic'            => $k->pic_name ?? '-',
            'whatsapp'       => $k->pic_whatsapp ?? '-',
            'sales'          => $k->sales ? $k->sales->name : '-',
            'date'           => $k->tanggal ? $k->tanggal->format('d M Y') : '-',
            'time'           => $k->waktu ?? '-',
            'address'        => $k->alamat ?? '-',
            'potential'      => $potential,
            'photo'          => $photoUrl,
            'notes'          => $k->catatan ?? $k->hasil ?? '-',
            'status'         => $k->status,
            'nomor'          => $k->nomor,
            // School-specific
            'potensi_beasiswa'      => $k->potensi_beasiswa ?? '-',
            'detail_beasiswa'       => $k->detail_beasiswa ?? '-',
            'kesediaan_training_ai' => $k->kesediaan_training_ai,
            // Corporate-specific
            'bidang_usaha' => $k->bidang_usaha ?? '-',
            'potensi_s1'   => $k->potensi_s1 ?? '-',
            'potensi_s2'   => $k->potensi_s2 ?? '-',
            'potensi_csr'  => $k->potensi_csr ?? '-',
            'created_at'   => $k->created_at ? $k->created_at->format('d M Y, H:i') : '-',
        ];
    }
}
