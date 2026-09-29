<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Prospek;
use App\Models\Kunjungan;
use App\Models\Sekolah;
use App\Models\Target;
use App\Models\Wilayah;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Services\WilayahPerformanceService;

class InfografisController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();
        $query = Prospek::query();

        // Scope by authorization
        if ($user->role === 'Sales') {
            $query->where('sales_id', $user->id);
        } elseif ($user->role === 'CS') {
            $query->where('cs_id', $user->id);
        } elseif ($user->role === 'SPV') {
            $query->whereIn('sales_id', User::where('supervisor_id', $user->id)->pluck('id'));
        } elseif ($user->role === 'HM') {
            $hmWilayahIds = $user->activeWilayahIds();
            if (!empty($hmWilayahIds)) {
                $query->whereIn('wilayah_id', $hmWilayahIds);
            }
        }

        // Metrics Summary
        $cancelCount = (clone $query)->where('status', 'CANCEL')->count();
        $pemberkasanCount = (clone $query)->where('status', 'BERKAS')->count();
        $lunasCount = (clone $query)->where('status', 'LUNAS')->count();
        $totalProspek = (clone $query)->count();

        // Target Pemberkasan
        $targetPemberkasan = Target::where('status', 'Aktif')->sum('target_pemberkasan');

        // Indikator & Skor Wilayah Performance Service
        $perfService = new WilayahPerformanceService();
        $scopedIds = strtolower($user->role) === 'hm' ? $user->activeWilayahIds() : null;
        $wilayahIndicators = $perfService->getAllWilayahPerformance(null, $scopedIds);
        $bobotWeights = $perfService->getWeights();
        $indicatorMap = collect($wilayahIndicators)->keyBy('id');

        // Fetch all Wilayah (Kota/Kabupaten and Kecamatan)
        $kotas = Wilayah::where('level', 'Kota/Kabupaten')
            ->where('status', 'Aktif')
            ->with(['children' => function($q) {
                $q->where('level', 'Kecamatan')->where('status', 'Aktif')->orderBy('nama');
            }])
            ->orderBy('nama')
            ->get();

        // Fetch all Sekolahs with related stats
        $allSekolahs = Sekolah::where('status', 'Aktif')
            ->with(['kategori'])
            ->withCount([
                'kunjungans as total_kunjungan',
                'prospeks as total_prospek',
                'prospeks as total_lunas' => function($q) {
                    $q->where('status', 'LUNAS');
                },
                'prospeks as total_berkas' => function($q) {
                    $q->where('status', 'BERKAS');
                },
                'prospeks as total_cancel' => function($q) {
                    $q->where('status', 'CANCEL');
                },
            ])
            ->get();

        // Province mapping for Kotas
        $provinsiMap = [
            'Kota Cirebon' => 'Jawa Barat',
            'Kabupaten Cirebon' => 'Jawa Barat',
            'Kabupaten Indramayu' => 'Jawa Barat',
            'Kabupaten Majalengka' => 'Jawa Barat',
            'Kabupaten Kuningan' => 'Jawa Barat',
            'Kabupaten Brebes' => 'Jawa Tengah',
            'Kota Tegal' => 'Jawa Tengah',
            'Kabupaten Tegal' => 'Jawa Tengah',
        ];

        // Assemble Hierarchical Tree (Provinsi -> Kota/Kabupaten -> Kecamatan -> Sekolah)
        $provinceTree = [];

        foreach ($kotas as $kota) {
            $provName = $provinsiMap[$kota->nama] ?? 'Jawa Barat';
            if (!isset($provinceTree[$provName])) {
                $provinceTree[$provName] = [
                    'nama' => $provName,
                    'kode' => $provName === 'Jawa Barat' ? 'JB' : ($provName === 'Jawa Tengah' ? 'JT' : 'ID'),
                    'total_kota' => 0,
                    'total_kecamatan' => 0,
                    'total_sekolah' => 0,
                    'total_sma' => 0,
                    'total_smk' => 0,
                    'total_ma' => 0,
                    'total_negeri' => 0,
                    'total_swasta' => 0,
                    'total_kunjungan' => 0,
                    'total_prospek' => 0,
                    'total_lunas' => 0,
                    'total_berkas' => 0,
                    'total_cancel' => 0,
                    'kotas' => [],
                ];
            }

            $kotaInd = $indicatorMap->get($kota->id);
            $kotaData = [
                'id' => $kota->id,
                'kode' => $kota->kode,
                'nama' => $kota->nama,
                'provinsi' => $provName,
                'skor_wilayah' => $kotaInd['skor_wilayah'] ?? null,
                'grade' => $kotaInd['grade']['code'] ?? 'N/A',
                'grade_badge' => $kotaInd['grade']['badge'] ?? 'bg-slate-100 text-slate-700',
                'matrix_label' => $kotaInd['matrix']['label'] ?? 'Standar',
                'total_kecamatan' => $kota->children->count(),
                'total_sekolah' => 0,
                'total_sma' => 0,
                'total_smk' => 0,
                'total_ma' => 0,
                'total_negeri' => 0,
                'total_swasta' => 0,
                'total_kunjungan' => 0,
                'total_prospek' => 0,
                'total_lunas' => 0,
                'total_berkas' => 0,
                'total_cancel' => 0,
                'kecamatans' => [],
            ];

            foreach ($kota->children as $kec) {
                // Find schools belonging to this kecamatan
                $kecSekolahs = $allSekolahs->filter(function($s) use ($kec, $kota) {
                    return $s->wilayah_id == $kec->id 
                        || (strtolower(trim($s->kecamatan ?? '')) === strtolower(trim($kec->nama)) && $s->wilayah_id == $kota->id)
                        || ($s->wilayah_id == $kec->id);
                })->values();

                $sekolahList = [];
                $kecSma = 0; $kecSmk = 0; $kecMa = 0; $kecNegeri = 0; $kecSwasta = 0;
                $kecKunjungan = 0; $kecProspek = 0; $kecLunas = 0; $kecBerkas = 0; $kecCancel = 0;

                foreach ($kecSekolahs as $s) {
                    $namaLower = strtolower($s->nama);
                    $bentuk = str_contains($namaLower, 'smk') ? 'SMK' : (str_contains($namaLower, 'man') || str_contains($namaLower, 'ma ') || str_contains($namaLower, 'mas ') ? 'MA' : 'SMA');
                    $isNegeri = str_contains($namaLower, 'negeri') || str_contains($namaLower, 'man ');
                    $statusSekolah = $isNegeri ? 'Negeri' : 'Swasta';

                    if ($bentuk === 'SMA') $kecSma++;
                    elseif ($bentuk === 'SMK') $kecSmk++;
                    elseif ($bentuk === 'MA') $kecMa++;

                    if ($isNegeri) $kecNegeri++;
                    else $kecSwasta++;

                    $kunjunganCount = (int) $s->total_kunjungan;
                    $prospekCount = (int) $s->total_prospek;
                    $lunasCountItem = (int) $s->total_lunas;
                    $berkasCountItem = (int) $s->total_berkas;
                    $cancelCountItem = (int) $s->total_cancel;

                    $kecKunjungan += $kunjunganCount;
                    $kecProspek += $prospekCount;
                    $kecLunas += $lunasCountItem;
                    $kecBerkas += $berkasCountItem;
                    $kecCancel += $cancelCountItem;

                    $sekolahList[] = [
                        'id' => $s->id,
                        'kode' => $s->kode,
                        'nama' => $s->nama,
                        'tier' => $s->tier ?? 'B',
                        'bentuk' => $bentuk,
                        'status_sekolah' => $statusSekolah,
                        'alamat' => $s->alamat ?? '-',
                        'telepon' => $s->telepon ?? '-',
                        'email' => $s->email ?? '-',
                        'pic_name' => $s->pic_name ?? '-',
                        'pic_jabatan' => $s->pic_jabatan ?? 'Guru BK / Hubinmas',
                        'pic_phone' => $s->pic_phone ?? '-',
                        'total_kunjungan' => $kunjunganCount,
                        'total_prospek' => $prospekCount,
                        'total_lunas' => $lunasCountItem,
                        'total_berkas' => $berkasCountItem,
                        'total_cancel' => $cancelCountItem,
                        'kecamatan' => $kec->nama,
                        'kota_nama' => $kota->nama,
                        'provinsi' => $provName,
                    ];
                }

                $kecData = [
                    'id' => $kec->id,
                    'kode' => $kec->kode,
                    'nama' => $kec->nama,
                    'parent_id' => $kota->id,
                    'kota_nama' => $kota->nama,
                    'provinsi' => $provName,
                    'total_sekolah' => count($sekolahList),
                    'total_sma' => $kecSma,
                    'total_smk' => $kecSmk,
                    'total_ma' => $kecMa,
                    'total_negeri' => $kecNegeri,
                    'total_swasta' => $kecSwasta,
                    'total_kunjungan' => $kecKunjungan,
                    'total_prospek' => $kecProspek,
                    'total_lunas' => $kecLunas,
                    'total_berkas' => $kecBerkas,
                    'total_cancel' => $kecCancel,
                    'sekolahs' => $sekolahList,
                ];

                $kotaData['total_sekolah'] += $kecData['total_sekolah'];
                $kotaData['total_sma'] += $kecSma;
                $kotaData['total_smk'] += $kecSmk;
                $kotaData['total_ma'] += $kecMa;
                $kotaData['total_negeri'] += $kecNegeri;
                $kotaData['total_swasta'] += $kecSwasta;
                $kotaData['total_kunjungan'] += $kecKunjungan;
                $kotaData['total_prospek'] += $kecProspek;
                $kotaData['total_lunas'] += $kecLunas;
                $kotaData['total_berkas'] += $kecBerkas;
                $kotaData['total_cancel'] += $kecCancel;

                $kotaData['kecamatans'][] = $kecData;
            }

            $provinceTree[$provName]['total_kota']++;
            $provinceTree[$provName]['total_kecamatan'] += $kotaData['total_kecamatan'];
            $provinceTree[$provName]['total_sekolah'] += $kotaData['total_sekolah'];
            $provinceTree[$provName]['total_sma'] += $kotaData['total_sma'];
            $provinceTree[$provName]['total_smk'] += $kotaData['total_smk'];
            $provinceTree[$provName]['total_ma'] += $kotaData['total_ma'];
            $provinceTree[$provName]['total_negeri'] += $kotaData['total_negeri'];
            $provinceTree[$provName]['total_swasta'] += $kotaData['total_swasta'];
            $provinceTree[$provName]['total_kunjungan'] += $kotaData['total_kunjungan'];
            $provinceTree[$provName]['total_prospek'] += $kotaData['total_prospek'];
            $provinceTree[$provName]['total_lunas'] += $kotaData['total_lunas'];
            $provinceTree[$provName]['total_berkas'] += $kotaData['total_berkas'];
            $provinceTree[$provName]['total_cancel'] += $kotaData['total_cancel'];

            $provinceTree[$provName]['kotas'][] = $kotaData;
        }

        uasort($provinceTree, function($a, $b) {
            if ($a['nama'] === 'Jawa Barat') return -1;
            if ($b['nama'] === 'Jawa Barat') return 1;
            return strcmp($a['nama'], $b['nama']);
        });
        $provinces = array_values($provinceTree);

        return view('infografis.index', compact(
            'cancelCount',
            'pemberkasanCount',
            'lunasCount',
            'totalProspek',
            'targetPemberkasan',
            'wilayahIndicators',
            'bobotWeights',
            'provinces'
        ));
    }
}
