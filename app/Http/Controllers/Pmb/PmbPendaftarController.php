<?php

namespace App\Http\Controllers\Pmb;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pmb\CamabaPortalController;
use App\Models\Prodi;
use App\Models\Prospek;
use App\Models\ProspekTimeline;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PmbPendaftarController extends Controller
{
    public function index(Request $request): View
    {
        $query = Prospek::with(['prodi', 'prodiPilihan2', 'sekolah', 'cs']);

        // Search Filter (Nama lengkap, email, WhatsApp/HP, No Registrasi, Agent)
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('whatsapp', 'like', "%{$search}%")
                  ->orWhere('pic_phone', 'like', "%{$search}%")
                  ->orWhere('no_registrasi', 'like', "%{$search}%")
                  ->orWhere('agent', 'like', "%{$search}%");
            });
        }

        // Filter: Agent
        if ($request->filled('agent')) {
            $agentVal = trim($request->input('agent'));
            if ($agentVal === 'none' || $agentVal === 'Tanpa Agent') {
                $query->where(function ($q) {
                    $q->whereNull('agent')
                      ->orWhere(DB::raw("TRIM(agent)"), '=', '');
                });
            } else {
                $query->where('agent', $agentVal);
            }
        }

        // Filter: Status Formulir
        if ($request->filled('status_formulir') && $request->status_formulir !== 'all') {
            $query->where('status_formulir', $request->status_formulir);
        }

        // Filter: Status Berkas
        if ($request->filled('status_berkas') && $request->status_berkas !== 'all') {
            if ($request->status_berkas === 'Belum Upload') {
                $query->where(function ($q) {
                    $q->where('status_berkas', 'Belum Upload')
                      ->orWhereNull('status_berkas');
                });
            } else {
                $query->where('status_berkas', $request->status_berkas);
            }
        }

        // Filter: Status Ujian
        if ($request->filled('status_ujian') && $request->status_ujian !== 'all') {
            $query->where('status_ujian', $request->status_ujian);
        }

        // Filter: Gelombang
        if ($request->filled('gelombang') && $request->gelombang !== 'all') {
            $query->where('gelombang', $request->gelombang);
        }

        // Filter: Program Studi
        if ($request->filled('prodi_id') && $request->prodi_id !== 'all') {
            $prodiId = (int) $request->prodi_id;
            $query->where(function ($q) use ($prodiId) {
                $q->where('prodi_id', $prodiId)
                  ->orWhere('prodi_pilihan_2_id', $prodiId);
            });
        }

        // 4 Summary Statistics (Actual DB Data)
        $totalUser = Prospek::count();
        $belumIsiFormulir = Prospek::where(function ($q) {
            $q->where('status_formulir', 'Belum Isi')
              ->orWhereNull('status_formulir');
        })->count();
        $belumUploadBerkas = Prospek::where(function ($q) {
            $q->where('status_berkas', 'Belum Upload')
              ->orWhereNull('status_berkas');
        })->count();
        $sudahUploadBerkas = Prospek::where('status_berkas', 'Sudah Upload')->count();

        // Dropdown filter options (Dinamis dari Database & Master Data)
        $prodis = Prodi::orderBy('nama', 'asc')->get();
        $gelombangFromDB = Prospek::whereNotNull('gelombang')
            ->distinct()
            ->pluck('gelombang')
            ->filter()
            ->values();

        $taGelombangs = \App\Models\TahunAkademik::pluck('nama')
            ->map(fn($nama) => 'Gelombang 1 - ' . $nama);

        $gelombangList = $gelombangFromDB->merge($taGelombangs)->unique()->values();
        if ($gelombangList->isEmpty()) {
            $gelombangList = collect(['Gelombang 1', 'Gelombang 2']);
        }

        // List Agent untuk Dropdown Filter
        $agentsList = Prospek::whereNotNull('agent')
            ->where(DB::raw("TRIM(agent)"), '!=', '')
            ->distinct()
            ->pluck('agent')
            ->filter()
            ->values();

        // Sorting & Pagination
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $allowedSorts = ['name', 'email', 'created_at', 'status_formulir', 'status_ujian'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'created_at';
        }
        if (!in_array(strtolower($sortOrder), ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }

        $perPage = (int) $request->input('per_page', 10);
        if (!in_array($perPage, [10, 25, 50, 100])) {
            $perPage = 10;
        }

        $pendaftarList = $query->orderBy($sortBy, $sortOrder)->paginate($perPage)->withQueryString();

        return view('pmb.pendaftar.index', compact(
            'pendaftarList',
            'totalUser',
            'belumIsiFormulir',
            'belumUploadBerkas',
            'sudahUploadBerkas',
            'prodis',
            'gelombangList',
            'agentsList'
        ));
    }

    public function show($id): View
    {
        $pendaftar = Prospek::with(['prodi', 'prodiPilihan2', 'sekolah', 'cs', 'transaksis'])->findOrFail($id);
        $dokumenList = CamabaPortalController::DOKUMEN_PERSYARATAN;
        return view('pmb.pendaftar.show', compact('pendaftar', 'dokumenList'));
    }

    /**
     * Tampilkan atau unduh dokumen persyaratan pendaftar secara aman (Khusus Admin/HM/CS).
     */
    public function viewDokumen(Request $request, $id, string $type)
    {
        $allowedKeys = array_keys(CamabaPortalController::DOKUMEN_PERSYARATAN);
        if (!in_array($type, $allowedKeys)) {
            abort(404, 'Jenis dokumen tidak valid.');
        }

        $pendaftar = Prospek::findOrFail($id);
        $berkasData = is_array($pendaftar->berkas_data) ? $pendaftar->berkas_data : [];

        if (empty($berkasData[$type]['file_path'])) {
            abort(404, 'Dokumen belum diunggah oleh calon mahasiswa.');
        }

        $filePath = $berkasData[$type]['file_path'];
        if (!Storage::disk('local')->exists($filePath)) {
            abort(404, 'File dokumen tidak ditemukan pada penyimpanan server.');
        }

        if ($request->boolean('download')) {
            $downloadName = $berkasData[$type]['original_name'] ?? $berkasData[$type]['file_name'];
            return Storage::disk('local')->download($filePath, $downloadName);
        }

        return Storage::disk('local')->response($filePath);
    }

    /**
     * Verifikasi status dokumen persyaratan pendaftar (Disetujui / Ditolak / Menunggu Verifikasi).
     */
    public function verifyDokumen(Request $request, $id, string $type)
    {
        $allowedKeys = array_keys(CamabaPortalController::DOKUMEN_PERSYARATAN);
        if (!in_array($type, $allowedKeys)) {
            abort(404, 'Jenis dokumen tidak valid.');
        }

        $pendaftar = Prospek::findOrFail($id);
        $berkasData = is_array($pendaftar->berkas_data) ? $pendaftar->berkas_data : [];

        if (empty($berkasData[$type]['file_path'])) {
            return redirect()->back()->with('error', 'Dokumen belum diunggah oleh calon mahasiswa.');
        }

        $validated = $request->validate([
            'status'  => 'required|in:Disetujui,Ditolak,Menunggu Verifikasi',
            'catatan' => 'nullable|string|max:500',
        ]);

        $berkasData[$type]['status'] = $validated['status'];
        $berkasData[$type]['catatan'] = $validated['catatan'] ?? null;
        $berkasData[$type]['verified_at'] = now()->format('Y-m-d H:i:s');
        $berkasData[$type]['verified_by'] = auth()->user()->name ?? 'Staff';

        $pendaftar->berkas_data = $berkasData;
        $pendaftar->save();

        $docTitle = CamabaPortalController::DOKUMEN_PERSYARATAN[$type]['title'] ?? $type;
        $user = auth()->user();

        ProspekTimeline::create([
            'prospek_id' => $pendaftar->id,
            'user_id'    => $user->id,
            'title'      => "Verifikasi Dokumen: {$docTitle}",
            'notes'      => "Status dokumen diubah menjadi '{$validated['status']}' oleh {$user->name} ({$user->role})." . (!empty($validated['catatan']) ? " Catatan: {$validated['catatan']}" : ''),
            'action'     => 'verifikasi_berkas',
        ]);

        return redirect()->back()->with('success', "Status dokumen {$docTitle} berhasil diperbarui menjadi {$validated['status']}.");
    }

    public function update(Request $request, $id)
    {
        $pendaftar = Prospek::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'whatsapp' => 'required|string|max:20',
            'gelombang' => 'nullable|string|max:100',
            'prodi_id' => 'nullable|exists:prodis,id',
            'prodi_pilihan_2_id' => 'nullable|exists:prodis,id',
            'status_formulir' => 'nullable|in:Belum Isi,Sudah Isi',
            'status_berkas' => 'nullable|in:Belum Upload,Pending,Sudah Upload',
            'status_ujian' => 'nullable|in:Belum Mengerjakan,Sudah Mengerjakan',
            'nilai_ujian' => 'nullable|numeric|min:0|max:100',
            'minat_kuliah' => 'nullable|in:ya,tidak',
            'minat_miniclass' => 'nullable|in:ya,tidak',
            'notes' => 'nullable|string',
        ]);

        if (!empty($validated['status_ujian']) && $validated['status_ujian'] === 'Sudah Mengerjakan' && empty($pendaftar->tanggal_ujian)) {
            $validated['tanggal_ujian'] = now();
        }

        $pendaftar->update($validated);

        return redirect()->back()->with('success', "Data pendaftar {$pendaftar->name} berhasil diperbarui.");
    }

    public function destroy($id)
    {
        $user = auth()->user();
        $userRole = strtolower($user->role ?? '');

        // Role CS tidak boleh menghapus pendaftar
        if ($userRole === 'cs') {
            abort(403, 'Role Customer Service (CS) tidak memiliki izin untuk menghapus data pendaftar.');
        }

        $pendaftar = Prospek::findOrFail($id);
        $nama = $pendaftar->name;

        // Hapus akun login Camaba jika ada agar tidak tertinggal sebagai orphan user
        if ($pendaftar->user_id) {
            $camabaUser = \App\Models\User::find($pendaftar->user_id);
            if ($camabaUser && strtolower($camabaUser->role) === 'camaba') {
                $camabaUser->delete();
            }
        }

        $pendaftar->delete();

        return redirect()->route('pmb.pendaftar.index')->with('success', "Data pendaftar {$nama} berhasil dihapus.");
    }

    public function resetPassword(Request $request, $id)
    {
        $pendaftar = Prospek::findOrFail($id);
        $user = auth()->user();

        // Catat di timeline / log aktivitas
        \App\Models\ProspekTimeline::create([
            'prospek_id' => $pendaftar->id,
            'user_id' => $user->id,
            'title' => 'Reset Password PMB',
            'notes' => "Password akun pendaftar {$pendaftar->name} di-reset oleh {$user->name} ({$user->role}). Password sementara diset ke email pendaftar.",
            'time' => now(),
        ]);

        $resetEmail = $pendaftar->email ?: $pendaftar->whatsapp . '@pmb.cic.ac.id';

        return redirect()->back()->with('success', "Password pendaftar {$pendaftar->name} berhasil direset. Password sementara: {$resetEmail}");
    }

    public function export(Request $request): StreamedResponse
    {
        $query = Prospek::with(['prodi', 'prodiPilihan2', 'sekolah']);

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('whatsapp', 'like', "%{$search}%")
                  ->orWhere('pic_phone', 'like', "%{$search}%")
                  ->orWhere('no_registrasi', 'like', "%{$search}%")
                  ->orWhere('agent', 'like', "%{$search}%");
            });
        }

        // Filter: Agent
        if ($request->filled('agent')) {
            $agentVal = trim($request->input('agent'));
            if ($agentVal === 'none' || $agentVal === 'Tanpa Agent') {
                $query->where(function ($q) {
                    $q->whereNull('agent')
                      ->orWhere(DB::raw("TRIM(agent)"), '=', '');
                });
            } else {
                $query->where('agent', $agentVal);
            }
        }

        if ($request->filled('status_formulir') && $request->status_formulir !== 'all') {
            $query->where('status_formulir', $request->status_formulir);
        }

        if ($request->filled('status_berkas') && $request->status_berkas !== 'all') {
            if ($request->status_berkas === 'Belum Upload') {
                $query->where(function ($q) {
                    $q->where('status_berkas', 'Belum Upload')
                      ->orWhereNull('status_berkas');
                });
            } else {
                $query->where('status_berkas', $request->status_berkas);
            }
        }

        if ($request->filled('status_ujian') && $request->status_ujian !== 'all') {
            $query->where('status_ujian', $request->status_ujian);
        }

        if ($request->filled('gelombang') && $request->gelombang !== 'all') {
            $query->where('gelombang', $request->gelombang);
        }

        if ($request->filled('prodi_id') && $request->prodi_id !== 'all') {
            $prodiId = (int) $request->prodi_id;
            $query->where(function ($q) use ($prodiId) {
                $q->where('prodi_id', $prodiId)
                  ->orWhere('prodi_pilihan_2_id', $prodiId);
            });
        }

        $pendaftars = $query->latest()->get();
        $fileName = 'Data_Pendaftar_PMB_' . date('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($pendaftars) {
            $handle = fopen('php://output', 'w');
            // UTF-8 BOM for Excel
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'No',
                'No Registrasi',
                'Nama Lengkap',
                'Email',
                'No HP / WhatsApp',
                'Program Studi Pilihan 1',
                'Program Studi Pilihan 2',
                'Gelombang',
                'Agent / Referral',
                'Status Formulir',
                'Status Berkas',
                'Status Ujian',
                'Nilai Ujian',
                'Tanggal Ujian',
                'Minat Kuliah',
                'Minat Miniclass',
                'Tanggal Daftar',
            ]);

            $no = 1;
            foreach ($pendaftars as $p) {
                fputcsv($handle, [
                    $no++,
                    $p->no_registrasi ?? '-',
                    $p->name,
                    $p->email ?? '-',
                    $p->whatsapp,
                    $p->prodi?->nama ?? '- Belum Pilih',
                    $p->prodiPilihan2?->nama ?? '-',
                    $p->gelombang ?? '- Data Lama',
                    !empty($p->agent) ? $p->agent : 'Tanpa Agent',
                    $p->status_formulir ?? 'Belum Isi',
                    $p->status_berkas ?? 'Belum Upload',
                    $p->status_ujian ?? 'Belum Mengerjakan',
                    $p->nilai_ujian !== null ? number_format($p->nilai_ujian, 2) : '-',
                    $p->tanggal_ujian ? $p->tanggal_ujian->format('d M Y H:i') : '-',
                    $p->minat_kuliah ?? 'ya',
                    $p->minat_miniclass ?? 'tidak',
                    $p->created_at ? $p->created_at->format('d M Y') : '-',
                ]);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }
}
