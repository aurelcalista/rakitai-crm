<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Kunjungan;
use App\Models\Sekolah;
use App\Models\Perusahaan;
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
            'foto'       => 'nullable|image|mimes:jpeg,jpg,png,webp|max:5120', // 5MB max
        ];

        // Conditional rules based on jenis
        if ($request->input('jenis') === 'Sekolah') {
            $rules['nama_institusi']    = 'required|string|max:255';
            $rules['alamat']            = 'nullable|string|max:500';
            $rules['potensi_beasiswa']  = 'nullable|string|max:255';
            $rules['detail_beasiswa']   = 'nullable|string|max:500';
            $rules['kesediaan_training_ai'] = 'nullable|boolean';
        } else {
            $rules['nama_institusi']  = 'required|string|max:255';
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

        $kunjungan = Kunjungan::create([
            'nomor'           => $nomor,
            'tanggal'         => $validated['tanggal'],
            'waktu'           => $validated['waktu'] . ':00',
            'sales_id'        => $user->id,
            'jenis'           => $validated['jenis'],
            'tujuan_id'       => 0, // Not tied to master sekolah/perusahaan — free form
            'tujuan_kunjungan'=> $validated['nama_institusi'],
            'hasil'           => 'Kunjungan ' . $validated['jenis'] . ' — ' . $validated['nama_institusi'],
            'catatan'         => $validated['catatan'] ?? null,
            'status'          => 'Selesai',
            // Detail fields
            'nama_institusi'  => $validated['nama_institusi'],
            'alamat'          => $validated['alamat'] ?? null,
            'pic_name'        => $validated['pic_name'],
            'pic_whatsapp'    => $validated['pic_whatsapp'],
            'foto_path'       => $fotoPath,
            // School-specific
            'potensi_beasiswa'      => $request->input('potensi_beasiswa'),
            'detail_beasiswa'       => $request->input('detail_beasiswa'),
            'kesediaan_training_ai' => $request->boolean('kesediaan_training_ai'),
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

    // ─── Private Helpers ─────────────────────────────────────────────────────

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
