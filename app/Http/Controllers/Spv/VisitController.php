<?php

namespace App\Http\Controllers\Spv;

use App\Http\Controllers\Controller;
use App\Models\Kunjungan;
use App\Models\Sekolah;
use App\Models\Perusahaan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class VisitController extends Controller
{
    /**
     * List team visits with filter by Sales, Type, and Date.
     */
    public function index(Request $request): View
    {
        $user = auth()->user();
        $teamMemberIds = $user->teamMemberIds();

        $query = Kunjungan::with(['sales', 'sekolah', 'perusahaan'])
            ->whereIn('sales_id', $teamMemberIds)
            ->orderBy('tanggal', 'desc')
            ->orderBy('waktu', 'desc');

        // Filter by Sales member
        if ($request->filled('sales_id') && $request->sales_id !== 'all') {
            $query->where('sales_id', $request->sales_id);
        }

        // Filter by Type
        if ($request->filled('jenis') && in_array($request->jenis, ['Sekolah', 'Perusahaan'])) {
            $query->where('jenis', $request->jenis);
        }

        // Filter by Date
        if ($request->filled('tanggal')) {
            $query->whereDate('tanggal', $request->tanggal);
        }

        $visits = $query->get()->map(fn ($k) => $this->formatKunjungan($k))->toArray();
        $teamSales = User::whereIn('id', $teamMemberIds)->where('role', 'Sales')->get();
        $sekolahs = Sekolah::where('status', 'Aktif')->orderBy('nama')->get();
        $perusahaans = Perusahaan::where('status', 'Aktif')->orderBy('nama')->get();

        return view('spv.kunjungan.index', compact('visits', 'teamSales', 'sekolahs', 'perusahaans'));
    }

    /**
     * Show detail of a visit.
     */
    public function show(Kunjungan $kunjungan): View
    {
        $kunjungan->load(['sales', 'sekolah', 'perusahaan', 'prospek']);
        $visit = $this->formatKunjungan($kunjungan);

        return view('spv.kunjungan.show', compact('visit'));
    }

    /**
     * Format Kunjungan model for view.
     */
    private function formatKunjungan(Kunjungan $k): array
    {
        $namaInstitusi = $k->nama_institusi;
        if ($k->jenis === 'Sekolah' && $k->sekolah) {
            $namaInstitusi = $k->sekolah->nama;
        } elseif ($k->jenis === 'Perusahaan' && $k->perusahaan) {
            $namaInstitusi = $k->perusahaan->nama;
        }

        return [
            'id'                => $k->id,
            'jenis'             => $k->jenis,
            'nama_institusi'    => $namaInstitusi ?? '-',
            'alamat'            => $k->alamat ?? '-',
            'pic_name'          => $k->pic_name,
            'pic_whatsapp'      => $k->pic_whatsapp,
            'tanggal'           => Carbon::parse($k->tanggal)->format('d M Y'),
            'tanggal_raw'       => $k->tanggal,
            'waktu'             => $k->waktu ? Carbon::parse($k->waktu)->format('H:i') : '-',
            'sales_name'        => $k->sales ? $k->sales->name : 'Sistem',
            'sales_id'          => $k->sales_id,
            'potensi_beasiswa'  => $k->potensi_beasiswa ?? '-',
            'potensi_kerjasama' => $k->potensi_kerjasama ?? '-',
            'catatan'           => $k->catatan ?? '-',
            'foto'              => $k->foto ? asset('storage/' . $k->foto) : null,
            'created_at'        => $k->created_at->diffForHumans(),
        ];
    }
}
