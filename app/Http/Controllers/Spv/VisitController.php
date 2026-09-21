?php

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

        $visits      = $query->get()->map(fn ($k) => $this->formatKunjungan($k))->toArray();
        $teamSales   = User::whereIn('id', $teamMemberIds)->where('role', 'Sales')->get();
        $sekolahs    = Sekolah::where('status', 'Aktif')->orderBy('nama')->get();
        $perusahaans = Perusahaan::where('status', 'Aktif')->orderBy('nama')->get();

        return view('spv.kunjungan.index', compact('visits', 'teamSales', 'sekolahs', 'perusahaans'));
    }

    public function show(Kunjungan $kunjungan): View
    {
        $kunjungan->load(['sales', 'sekolah', 'perusahaan']);
        $visit = $this->formatKunjungan($kunjungan);
        return view('spv.kunjungan.show', compact('visit'));
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
            'id'             => $k->id,
            'name'           => $namaInstitusi,
            'type'           => $k->jenis ?? '-',
            'pic'            => $k->pic_name ?? '-',
            'whatsapp'       => $k->pic_whatsapp ?? '-',
            'sales'          => $k->sales ? $k->sales->name : '-',
            'sales_id'       => $k->sales_id,
            'date'           => $k->tanggal ? Carbon::parse($k->tanggal)->format('d M Y') : '-',
            'time'           => $k->waktu ? Carbon::parse($k->waktu)->format('H:i') : '-',
            'address'        => $k->alamat ?? '-',
            'potential'      => $potential,
            'photo'          => $photoUrl,
            'notes'          => $k->catatan ?? $k->hasil ?? '-',
            'status'         => $k->status,
            'nomor'          => $k->nomor,
            'potensi_beasiswa'      => $k->potensi_beasiswa ?? '-',
            'detail_beasiswa'       => $k->detail_beasiswa ?? '-',
            'kesediaan_training_ai' => $k->kesediaan_training_ai,
            'bidang_usaha' => $k->bidang_usaha ?? '-',
            'potensi_s1'   => $k->potensi_s1 ?? '-',
            'potensi_s2'   => $k->potensi_s2 ?? '-',
            'potensi_csr'  => $k->potensi_csr ?? '-',
            'created_at'   => $k->created_at ? $k->created_at->format('d M Y, H:i') : '-',
        ];
    }
}
