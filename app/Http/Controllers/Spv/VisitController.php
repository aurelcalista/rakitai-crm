<?php

namespace App\Http\Controllers\Spv;

use App\Http\Controllers\Controller;
use App\Models\Kunjungan;
use App\Models\Sekolah;
use App\Models\Perusahaan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;

class VisitController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();
        $teamMemberIds = $user->teamMemberIds();

        $query = Kunjungan::with(['sales', 'sekolah', 'perusahaan'])
            ->whereIn('sales_id', $teamMemberIds)
            ->orderBy('tanggal', 'desc')
            ->orderBy('waktu', 'desc');

        if ($request->filled('sales_id') && $request->sales_id !== 'all') {
            $query->where('sales_id', $request->sales_id);
        }
        if ($request->filled('jenis') && in_array($request->jenis, ['Sekolah', 'Perusahaan'])) {
            $query->where('jenis', $request->jenis);
        }
        if ($request->filled('tanggal')) {
            $query->whereDate('tanggal', $request->tanggal);
        }

        $visitsPaginated = $query->paginate(10)->withQueryString();
        $allVisits = $visitsPaginated->getCollection()->map(fn ($k) => $this->formatKunjungan($k))->toArray();
        $schoolVisits  = array_values(array_filter($allVisits, fn ($v) => $v['type'] === 'Sekolah'));
        $companyVisits = array_values(array_filter($allVisits, fn ($v) => $v['type'] === 'Perusahaan'));

        // Counter untuk status "Perlu Verifikasi"
        $perluVerifikasiCount = count(array_filter($allVisits, fn ($v) => $v['status_verifikasi'] === 'Perlu Verifikasi' || $v['is_outside_radius']));

        $teamSales   = User::whereIn('id', $teamMemberIds)->where('role', 'Sales')->get();
        $sekolahs    = Sekolah::where('status', 'Aktif')->orderBy('nama')->get();
        $perusahaans = Perusahaan::where('status', 'Aktif')->orderBy('nama')->get();

        return view('spv.kunjungan.index', compact(
            'allVisits',
            'visitsPaginated',
            'schoolVisits',
            'companyVisits',
            'perluVerifikasiCount',
            'teamSales',
            'sekolahs',
            'perusahaans'
        ));
    }

    public function show(Kunjungan $kunjungan): View
    {
        $user = auth()->user();
        // Strict Backend Scoping
        if (!in_array($kunjungan->sales_id, $user->teamMemberIds())) {
            abort(403, 'Anda tidak memiliki otorisasi untuk melihat kunjungan tim di luar cakupan wilayah Anda.');
        }

        $kunjungan->load(['sales', 'sekolah', 'perusahaan']);
        $visit = $this->formatKunjungan($kunjungan);
        return view('spv.kunjungan.show', compact('visit'));
    }

    /**
     * Verifikasi Kunjungan oleh SPV (penanganan status Perlu Verifikasi akibat warning radius).
     */
    public function verifikasi(Request $request, Kunjungan $kunjungan)
    {
        $user = auth()->user();
        if (!in_array($kunjungan->sales_id, $user->teamMemberIds())) {
            abort(403, 'Akses ditolak.');
        }

        $kunjungan->update([
            'status_verifikasi' => 'Terverifikasi',
            'status'            => 'Selesai',
            'catatan'           => ($kunjungan->catatan ? $kunjungan->catatan . "\n" : '') . '[Verifikasi SPV ' . $user->name . ' pada ' . now()->translatedFormat('d M Y H:i') . ': Laporan kunjungan telah diverifikasi valid]',
        ]);

        return redirect()->back()->with('success', 'Kunjungan ' . $kunjungan->nomor . ' berhasil diverifikasi oleh SPV.');
    }

    private function formatKunjungan(Kunjungan $k): array
    {
        $potential = '-';
        if ($k->jenis === 'Sekolah') {
            $potential = $k->potensi_beasiswa ?? $k->hasil ?? '-';
        } else {
            $parts = array_filter([
                $k->potensi_s1  ? 'S1: '  . $k->potensi_s1  : null,
                $k->potensi_s2  ? 'S2: '  . $k->potensi_s2  : null,
                $k->potensi_csr ? 'CSR: ' . $k->potensi_csr : null,
            ]);
            $potential = count($parts) ? implode(' | ', $parts) : ($k->hasil ?? '-');
        }

        $namaInstitusi = $k->nama_institusi ?? $k->tujuan_kunjungan ?? '-';
        if ($k->jenis === 'Sekolah' && $k->sekolah) {
            $namaInstitusi = $k->sekolah->nama;
        } elseif ($k->jenis === 'Perusahaan' && $k->perusahaan) {
            $namaInstitusi = $k->perusahaan->nama;
        }

        $photoUrl = $k->foto_path ? Storage::url($k->foto_path) : null;

        return [
            'id'                => $k->id,
            'name'              => $namaInstitusi,
            'type'              => $k->jenis ?? '-',
            'pic'               => $k->pic_name ?? '-',
            'whatsapp'          => $k->pic_whatsapp ?? '-',
            'sales'             => $k->sales ? $k->sales->name : '-',
            'sales_id'          => $k->sales_id,
            'date'              => $k->tanggal ? Carbon::parse($k->tanggal)->format('d M Y') : '-',
            'time'              => $k->waktu ? Carbon::parse($k->waktu)->format('H:i') : '-',
            'address'           => $k->alamat ?? '-',
            'lokasi_penugasan'  => $k->lokasi_penugasan ?? $k->alamat ?? '-',
            'potential'         => $potential,
            'photo'             => $photoUrl,
            'notes'             => $k->catatan ?? $k->hasil ?? '-',
            'status'            => $k->status,
            'status_verifikasi' => $k->status_verifikasi ?? 'Terverifikasi',
            'is_outside_radius' => (bool) $k->is_outside_radius,
            'nomor'             => $k->nomor,
            'potensi_mahasiswa' => $k->potensi_beasiswa ?? '-',
            'potensi_beasiswa'  => $k->potensi_beasiswa ?? '-',
            'detail_beasiswa'   => $k->detail_beasiswa ?? '-',
            'kesediaan_training_ai' => $k->kesediaan_training_ai,
            'bidang_usaha'      => $k->bidang_usaha ?? '-',
            'potensi_s1'        => $k->potensi_s1 ?? '-',
            'potensi_s2'        => $k->potensi_s2 ?? '-',
            'potensi_csr'       => $k->potensi_csr ?? '-',
            'created_at'        => $k->created_at ? $k->created_at->format('d M Y, H:i') : '-',
        ];
    }
}
