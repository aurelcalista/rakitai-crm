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

        $allVisits = $query->get()->map(fn ($k) => $this->formatKunjungan($k))->toArray();
        $schoolVisits  = array_values(array_filter($allVisits, fn ($v) => $v['type'] === 'Sekolah'));
        $companyVisits = array_values(array_filter($allVisits, fn ($v) => $v['type'] === 'Perusahaan'));

        // Counter untuk status "Perlu Verifikasi"
        $perluVerifikasiCount = count(array_filter($allVisits, fn ($v) => $v['status_verifikasi'] === 'Perlu Verifikasi' || $v['is_outside_radius']));

        $teamSales   = User::whereIn('id', $teamMemberIds)->where('role', 'Sales')->get();
        $sekolahs    = Sekolah::where('status', 'Aktif')->orderBy('nama')->get();
        $perusahaans = Perusahaan::where('status', 'Aktif')->orderBy('nama')->get();

        return view('spv.kunjungan.index', compact(
            'allVisits',
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

        $sales = $kunjungan->sales ?? ($kunjungan->sales_id ? \App\Models\User::find($kunjungan->sales_id) : null);
        if ($sales && $sales->id !== $user->id) {
            $sales->notify(new \App\Notifications\CrmActivityNotification(
                title: 'Kunjungan Telah Diverifikasi',
                message: "Laporan kunjungan Anda ke '" . ($kunjungan->nama_institusi ?? 'Institusi') . "' telah diverifikasi valid oleh SPV {$user->name}.",
                type: 'success',
                link: '/sales/kunjungan',
                icon: '',
                senderName: $user->name,
                senderRole: 'SPV',
                action: 'kunjungan_verified'
            ));
        }

        return redirect()->back()->with('success', 'Kunjungan ' . $kunjungan->nomor . ' berhasil diverifikasi oleh SPV.');
    }

    public function update(Request $request, Kunjungan $kunjungan)
    {
        $this->authorize('update', $kunjungan);

        $validated = $request->validate([
            'tanggal'                  => 'required|date',
            'waktu'                    => 'nullable|string',
            'nama_institusi'           => 'nullable|string|max:255',
            'tujuan_kunjungan'         => 'nullable|string|max:255',
            'pic_name'                 => 'nullable|string|max:255',
            'pic_whatsapp'             => 'nullable|string|max:30',
            'alamat'                   => 'nullable|string|max:500',
            'catatan'                  => 'nullable|string|max:2000',
            'hasil'                    => 'nullable|string|max:2000',
            'status'                   => 'nullable|string|in:Menunggu,Proses,Selesai',
            'foto'                     => 'nullable|image|mimes:jpeg,jpg,png,webp|max:5120',
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

        $kunjungan->update($validated);

        return redirect()->back()->with('success', 'Data kunjungan tim berhasil diperbarui!');
    }

    public function destroy(Kunjungan $kunjungan)
    {
        $this->authorize('delete', $kunjungan);

        if ($kunjungan->foto_path) {
            Storage::disk('public')->delete($kunjungan->foto_path);
        }

        $kunjungan->delete();

        $referer = request()->header('referer');
        if ($referer && str_contains($referer, '/kunjungan/' . $kunjungan->id)) {
            return redirect()->route('spv.kunjungan.index')->with('success', 'Kunjungan berhasil dihapus.');
        }

        return redirect()->back()->with('success', 'Kunjungan berhasil dihapus.');
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
            'raw_date'          => $k->tanggal ? Carbon::parse($k->tanggal)->format('Y-m-d') : '',
            'time'              => $k->waktu ? Carbon::parse($k->waktu)->format('H:i') : '-',
            'raw_time'          => $k->waktu ? Carbon::parse($k->waktu)->format('H:i') : '',
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
            'dosen'             => $k->dosen ? $k->dosen->name : ($k->dosen_pemateri ?? '-'),
            'dosen_id'          => $k->dosen_id,
            'dosen_pemateri'    => $k->dosen_pemateri,
            'created_at'        => $k->created_at ? $k->created_at->format('d M Y, H:i') : '-',
        ];
    }
}
