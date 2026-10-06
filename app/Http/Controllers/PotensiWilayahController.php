<?php

namespace App\Http\Controllers;

use App\Models\Kunjungan;
use App\Models\Prospek;
use App\Models\Sekolah;
use App\Models\User;
use App\Models\Wilayah;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PotensiWilayahController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();
        if (!in_array($user->role, ['Admin', 'HM', 'SPV', 'Sales', 'CS'])) {
            abort(403, 'Unauthorized action.');
        }

        // Role Scoping
        $isSales = ($user->role === 'Sales');
        $isSpv   = ($user->role === 'SPV');
        // HM, CS, Admin have full access (all data visible)

        $salesKecamatanIds = [];
        $salesKotaIds = [];
        $spvKotaIds = [];

        if ($isSales) {
            $assignedWilayahs = Wilayah::whereIn('id', $user->activeWilayahIds())->get();
            foreach ($assignedWilayahs as $w) {
                if ($w->level === 'Kecamatan') {
                    $salesKecamatanIds[] = $w->id;
                    if ($w->parent_id) {
                        $salesKotaIds[] = $w->parent_id;
                    }
                } elseif ($w->level === 'Kota/Kabupaten' || is_null($w->parent_id)) {
                    $salesKotaIds[] = $w->id;
                    $childIds = Wilayah::where('parent_id', $w->id)->where('level', 'Kecamatan')->pluck('id')->toArray();
                    $salesKecamatanIds = array_merge($salesKecamatanIds, $childIds);
                }
            }
            $salesKecamatanIds = array_values(array_unique($salesKecamatanIds));
            $salesKotaIds = array_values(array_unique($salesKotaIds));
        } elseif ($isSpv) {
            $assignedWilayahs = Wilayah::whereIn('id', $user->activeWilayahIds())->get();
            foreach ($assignedWilayahs as $w) {
                if ($w->level === 'Kota/Kabupaten' || is_null($w->parent_id)) {
                    $spvKotaIds[] = $w->id;
                } elseif ($w->parent_id) {
                    $spvKotaIds[] = $w->parent_id;
                }
            }
            $spvKotaIds = array_values(array_unique($spvKotaIds));
        }

        // Get Kotas with Kecamatans and assigned Sales
        $query = Wilayah::whereNull('parent_id')
            ->where('level', 'Kota/Kabupaten')
            ->where('status', 'Aktif')
            ->with([
                'children' => function ($q) use ($isSales, $salesKecamatanIds) {
                    $q->where('level', 'Kecamatan')
                        ->where('status', 'Aktif')
                        ->with(['assignedUsers' => function ($qu) {
                            $qu->wherePivot('is_active', true);
                        }])
                        ->orderBy('nama');

                    if ($isSales) {
                        if (!empty($salesKecamatanIds)) {
                            $q->whereIn('id', $salesKecamatanIds);
                        } else {
                            $q->whereRaw('1 = 0');
                        }
                    }
                },
                'assignedUsers' => function ($qu) {
                    $qu->wherePivot('is_active', true);
                }
            ])
            ->orderBy('nama');

        // Apply Kota filter based on role
        if ($isSales) {
            if (!empty($salesKotaIds)) {
                $query->whereIn('id', $salesKotaIds);
            } else {
                $query->whereRaw('1 = 0');
            }
        } elseif ($isSpv) {
            if (!empty($spvKotaIds)) {
                $query->whereIn('id', $spvKotaIds);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $kotas = $query->get();

        // Fetch Sekolahs with related stats (scoped by role)
        $sekolahQuery = Sekolah::where('status', 'Aktif')
            ->with(['kategori'])
            ->withCount([
                'kunjungans as total_kunjungan',
                'prospeks as total_prospek',
                'prospeks as total_closing' => function ($q) {
                    $q->whereIn('status', ['CLOSING', 'LUNAS']);
                },
                'prospeks as total_lunas' => function ($q) {
                    $q->where('status', 'LUNAS');
                },
                'prospeks as total_berkas' => function ($q) {
                    $q->where('status', 'BERKAS');
                },
                'prospeks as total_formulir' => function ($q) {
                    $q->where('status', 'FORMULIR');
                },
                'prospeks as total_cancel' => function ($q) {
                    $q->whereIn('status', ['CANCEL', 'BATAL', 'DROP', 'TIDAK_MINAT', 'NO_RESPON', 'SP-08-NO-RESPON']);
                },
            ]);

        if ($isSales) {
            if (!empty($salesKecamatanIds)) {
                $sekolahQuery->where(function ($q) use ($salesKecamatanIds, $salesKotaIds) {
                    $q->whereIn('wilayah_id', $salesKecamatanIds);
                });
            } else {
                $sekolahQuery->whereRaw('1 = 0');
            }
        } elseif ($isSpv) {
            if (!empty($spvKotaIds)) {
                $spvWilayahSearchIds = array_merge($spvKotaIds, Wilayah::whereIn('parent_id', $spvKotaIds)->pluck('id')->toArray());
                $sekolahQuery->whereIn('wilayah_id', $spvWilayahSearchIds);
            } else {
                $sekolahQuery->whereRaw('1 = 0');
            }
        }

        $allSekolahs = $sekolahQuery->get();

        // Province mapping for Kotas
        $provinsiMap = [
            'Kota Cirebon'        => 'Jawa Barat',
            'Kabupaten Cirebon'   => 'Jawa Barat',
            'Kabupaten Indramayu' => 'Jawa Barat',
            'Kabupaten Majalengka'=> 'Jawa Barat',
            'Kabupaten Kuningan'  => 'Jawa Barat',
            'Kabupaten Brebes'    => 'Jawa Tengah',
            'Kota Tegal'          => 'Jawa Tengah',
            'Kabupaten Tegal'     => 'Jawa Tengah',
        ];

        // Overall Totals
        $globalTotalProspek = 0;
        $globalTotalClosing = 0;
        $globalTotalKunjungan = 0;
        $globalTotalSekolah = 0;
        $globalSekolahTerjangkau = 0;

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
                    'total_sekolah_terjangkau' => 0,
                    'total_kunjungan' => 0,
                    'total_prospek' => 0,
                    'total_closing' => 0,
                    'total_lunas' => 0,
                    'total_berkas' => 0,
                    'total_formulir' => 0,
                    'total_cancel' => 0,
                    'total_sales' => 0,
                    'conversion_rate' => 0,
                    'kotas' => [],
                ];
            }

            $hmUser = $kota->assignedUsers->where('role', 'HM')->first();

            $kotaData = [
                'id' => $kota->id,
                'kode' => $kota->kode,
                'nama' => $kota->nama,
                'provinsi' => $provName,
                'hm_name' => $hmUser ? $hmUser->name : '-',
                'total_kecamatan' => $kota->children->count(),
                'total_sekolah' => 0,
                'total_sma' => 0,
                'total_smk' => 0,
                'total_ma' => 0,
                'total_negeri' => 0,
                'total_swasta' => 0,
                'total_sekolah_terjangkau' => 0,
                'total_kunjungan' => 0,
                'total_prospek' => 0,
                'total_closing' => 0,
                'total_lunas' => 0,
                'total_berkas' => 0,
                'total_formulir' => 0,
                'total_cancel' => 0,
                'total_sales' => 0,
                'conversion_rate' => 0,
                'potensi_rating' => 'Sedang',
                'potensi_badge' => 'bg-amber-50 text-amber-700 border-amber-200',
                'kecamatans' => [],
            ];

            $kotaSalesIds = [];

            foreach ($kota->children as $kec) {
                // Find schools belonging to this kecamatan
                $kecSekolahs = $allSekolahs->filter(function ($s) use ($kec, $kota) {
                    return $s->wilayah_id == $kec->id 
                        || (strtolower(trim($s->kecamatan ?? '')) === strtolower(trim($kec->nama)) && $s->wilayah_id == $kota->id);
                })->values();

                $sekolahList = [];
                $kecKunjungan = 0; $kecProspek = 0; $kecClosing = 0; $kecLunas = 0; $kecBerkas = 0; $kecFormulir = 0; $kecCancel = 0;
                $kecSekolahTerjangkau = 0;
                $kecSma = 0; $kecSmk = 0; $kecMa = 0; $kecNegeri = 0; $kecSwasta = 0;

                foreach ($kecSekolahs as $s) {
                    $namaLower = strtolower($s->nama);
                    $bentuk = str_contains($namaLower, 'smk') ? 'SMK' : (str_contains($namaLower, 'sma') ? 'SMA' : (str_contains($namaLower, 'man') || str_contains($namaLower, 'ma ') ? 'MA' : 'SMA'));
                    $isNegeri = str_contains($namaLower, 'negeri') || str_contains($namaLower, 'man ');
                    $statusSekolah = $isNegeri ? 'Negeri' : 'Swasta';

                    if ($bentuk === 'SMA') $kecSma++;
                    elseif ($bentuk === 'SMK') $kecSmk++;
                    elseif ($bentuk === 'MA') $kecMa++;

                    if ($statusSekolah === 'Negeri') $kecNegeri++;
                    else $kecSwasta++;

                    $kunjunganCount = (int) $s->total_kunjungan;
                    $prospekCount = (int) $s->total_prospek;
                    $closingCount = (int) $s->total_closing;
                    $lunasCount = (int) $s->total_lunas;
                    $berkasCount = (int) $s->total_berkas;
                    $formulirCount = (int) $s->total_formulir;
                    $cancelCount = (int) $s->total_cancel;

                    $kecKunjungan += $kunjunganCount;
                    $kecProspek += $prospekCount;
                    $kecClosing += $closingCount;
                    $kecLunas += $lunasCount;
                    $kecBerkas += $berkasCount;
                    $kecFormulir += $formulirCount;
                    $kecCancel += $cancelCount;

                    if ($kunjunganCount > 0 || $prospekCount > 0 || $closingCount > 0) {
                        $kecSekolahTerjangkau++;
                    }

                    $convRate = $prospekCount > 0 ? round(($closingCount / $prospekCount) * 100, 1) : 0;

                    // Rating Potensi Sekolah
                    $rating = 'Sedang';
                    $badge = 'bg-amber-50 text-amber-700 border border-amber-200';
                    if ($closingCount >= 8 || $prospekCount >= 25 || ($s->tier ?? '') === 'A') {
                        $rating = 'Sangat Tinggi';
                        $badge = 'bg-emerald-50 text-emerald-700 border border-emerald-200';
                    } elseif ($closingCount >= 3 || $prospekCount >= 10 || ($s->tier ?? '') === 'B') {
                        $rating = 'Tinggi';
                        $badge = 'bg-blue-50 text-blue-700 border border-blue-200';
                    } elseif ($prospekCount === 0 && $kunjunganCount === 0) {
                        $rating = 'Potensial';
                        $badge = 'bg-slate-100 text-slate-600 border border-slate-200';
                    }

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
                        'pic_jabatan' => $s->pic_jabatan ?? 'Guru BK / Hubin',
                        'pic_phone' => $s->pic_phone ?? '-',
                        'total_kunjungan' => $kunjunganCount,
                        'total_prospek' => $prospekCount,
                        'total_closing' => $closingCount,
                        'total_lunas' => $lunasCount,
                        'total_berkas' => $berkasCount,
                        'total_formulir' => $formulirCount,
                        'total_cancel' => $cancelCount,
                        'conversion_rate' => $convRate,
                        'potensi_rating' => $rating,
                        'potensi_badge' => $badge,
                        'kecamatan' => $kec->nama,
                        'kota_nama' => $kota->nama,
                        'provinsi' => $provName,
                    ];
                }

                // Sort sekolahs by total_closing desc, total_prospek desc
                usort($sekolahList, function ($a, $b) {
                    if ($a['total_closing'] !== $b['total_closing']) {
                        return $b['total_closing'] <=> $a['total_closing'];
                    }
                    if ($a['total_prospek'] !== $b['total_prospek']) {
                        return $b['total_prospek'] <=> $a['total_prospek'];
                    }
                    return strcmp($a['nama'], $b['nama']);
                });

                $kecSales = $kec->assignedUsers->filter(fn ($u) => in_array($u->pivot->role ?? $u->role, ['Sales', 'SPV']))->values();
                foreach ($kecSales as $ks) {
                    $kotaSalesIds[$ks->id] = true;
                }

                $kecConvRate = $kecProspek > 0 ? round(($kecClosing / $kecProspek) * 100, 1) : 0;

                // Rating Potensi Kecamatan
                $kecRating = 'Sedang';
                $kecBadge = 'bg-amber-50 text-amber-700 border border-amber-200';
                if ($kecClosing >= 15 || $kecProspek >= 50) {
                    $kecRating = 'Sangat Tinggi';
                    $kecBadge = 'bg-emerald-50 text-emerald-700 border border-emerald-200';
                } elseif ($kecClosing >= 5 || $kecProspek >= 20) {
                    $kecRating = 'Tinggi';
                    $kecBadge = 'bg-blue-50 text-blue-700 border border-blue-200';
                } elseif ($kecProspek === 0 && $kecKunjungan === 0) {
                    $kecRating = 'Potensial';
                    $kecBadge = 'bg-slate-100 text-slate-600 border border-slate-200';
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
                    'total_sekolah_terjangkau' => $kecSekolahTerjangkau,
                    'total_kunjungan' => $kecKunjungan,
                    'total_prospek' => $kecProspek,
                    'total_closing' => $kecClosing,
                    'total_lunas' => $kecLunas,
                    'total_berkas' => $kecBerkas,
                    'total_formulir' => $kecFormulir,
                    'total_cancel' => $kecCancel,
                    'conversion_rate' => $kecConvRate,
                    'assigned_sales' => $kecSales->map(fn ($u) => [
                        'id' => $u->id,
                        'name' => $u->name,
                        'role' => $u->role,
                        'phone' => $u->phone ?? '-',
                        'email' => $u->email,
                    ])->toArray(),
                    'potensi_rating' => $kecRating,
                    'potensi_badge' => $kecBadge,
                    'sekolahs' => $sekolahList,
                ];

                $kotaData['total_sekolah'] += $kecData['total_sekolah'];
                $kotaData['total_sma'] += $kecSma;
                $kotaData['total_smk'] += $kecSmk;
                $kotaData['total_ma'] += $kecMa;
                $kotaData['total_negeri'] += $kecNegeri;
                $kotaData['total_swasta'] += $kecSwasta;
                $kotaData['total_sekolah_terjangkau'] += $kecSekolahTerjangkau;
                $kotaData['total_kunjungan'] += $kecKunjungan;
                $kotaData['total_prospek'] += $kecProspek;
                $kotaData['total_closing'] += $kecClosing;
                $kotaData['total_lunas'] += $kecLunas;
                $kotaData['total_berkas'] += $kecBerkas;
                $kotaData['total_formulir'] += $kecFormulir;
                $kotaData['total_cancel'] += $kecCancel;

                $kotaData['kecamatans'][] = $kecData;
            }

            // Sort kecamatans by total_closing desc, total_prospek desc
            usort($kotaData['kecamatans'], function ($a, $b) {
                if ($a['total_closing'] !== $b['total_closing']) {
                    return $b['total_closing'] <=> $a['total_closing'];
                }
                if ($a['total_prospek'] !== $b['total_prospek']) {
                    return $b['total_prospek'] <=> $a['total_prospek'];
                }
                return strcmp($a['nama'], $b['nama']);
            });

            $kotaData['total_sales'] = count($kotaSalesIds);
            $kotaData['conversion_rate'] = $kotaData['total_prospek'] > 0 
                ? round(($kotaData['total_closing'] / $kotaData['total_prospek']) * 100, 1) 
                : 0;

            if ($kotaData['total_closing'] >= 30 || $kotaData['total_prospek'] >= 100) {
                $kotaData['potensi_rating'] = 'Sangat Tinggi';
                $kotaData['potensi_badge'] = 'bg-emerald-50 text-emerald-700 border border-emerald-200';
            } elseif ($kotaData['total_closing'] >= 10 || $kotaData['total_prospek'] >= 40) {
                $kotaData['potensi_rating'] = 'Tinggi';
                $kotaData['potensi_badge'] = 'bg-blue-50 text-blue-700 border border-blue-200';
            }

            $provinceTree[$provName]['total_kota']++;
            $provinceTree[$provName]['total_kecamatan'] += $kotaData['total_kecamatan'];
            $provinceTree[$provName]['total_sekolah'] += $kotaData['total_sekolah'];
            $provinceTree[$provName]['total_sma'] += $kotaData['total_sma'];
            $provinceTree[$provName]['total_smk'] += $kotaData['total_smk'];
            $provinceTree[$provName]['total_ma'] += $kotaData['total_ma'];
            $provinceTree[$provName]['total_negeri'] += $kotaData['total_negeri'];
            $provinceTree[$provName]['total_swasta'] += $kotaData['total_swasta'];
            $provinceTree[$provName]['total_sekolah_terjangkau'] += $kotaData['total_sekolah_terjangkau'];
            $provinceTree[$provName]['total_kunjungan'] += $kotaData['total_kunjungan'];
            $provinceTree[$provName]['total_prospek'] += $kotaData['total_prospek'];
            $provinceTree[$provName]['total_closing'] += $kotaData['total_closing'];
            $provinceTree[$provName]['total_lunas'] += $kotaData['total_lunas'];
            $provinceTree[$provName]['total_berkas'] += $kotaData['total_berkas'];
            $provinceTree[$provName]['total_formulir'] += $kotaData['total_formulir'];
            $provinceTree[$provName]['total_cancel'] += $kotaData['total_cancel'];
            $provinceTree[$provName]['total_sales'] += $kotaData['total_sales'];

            $provinceTree[$provName]['kotas'][] = $kotaData;

            // Sort kotas by total_closing desc, total_prospek desc
            usort($provinceTree[$provName]['kotas'], function ($a, $b) {
                if ($a['total_closing'] !== $b['total_closing']) {
                    return $b['total_closing'] <=> $a['total_closing'];
                }
                if ($a['total_prospek'] !== $b['total_prospek']) {
                    return $b['total_prospek'] <=> $a['total_prospek'];
                }
                return strcmp($a['nama'], $b['nama']);
            });

            $globalTotalProspek += $kotaData['total_prospek'];
            $globalTotalClosing += $kotaData['total_closing'];
            $globalTotalKunjungan += $kotaData['total_kunjungan'];
            $globalTotalSekolah += $kotaData['total_sekolah'];
            $globalSekolahTerjangkau += $kotaData['total_sekolah_terjangkau'];
        }

        foreach ($provinceTree as $k => $p) {
            $provinceTree[$k]['conversion_rate'] = $p['total_prospek'] > 0 
                ? round(($p['total_closing'] / $p['total_prospek']) * 100, 1) 
                : 0;
        }

        uasort($provinceTree, function ($a, $b) {
            if ($a['nama'] === 'Jawa Barat') return -1;
            if ($b['nama'] === 'Jawa Barat') return 1;
            return strcmp($a['nama'], $b['nama']);
        });

        $provinces = array_values($provinceTree);

        $globalStats = [
            'total_prospek' => $globalTotalProspek,
            'total_closing' => $globalTotalClosing,
            'total_kunjungan' => $globalTotalKunjungan,
            'total_sekolah' => $globalTotalSekolah,
            'sekolah_terjangkau' => $globalSekolahTerjangkau,
            'penetrasi_rate' => $globalTotalSekolah > 0 ? round(($globalSekolahTerjangkau / $globalTotalSekolah) * 100, 1) : 0,
            'conversion_rate' => $globalTotalProspek > 0 ? round(($globalTotalClosing / $globalTotalProspek) * 100, 1) : 0,
        ];

        $firstProv = $provinces[0] ?? null;
        $firstKota = ($firstProv && !empty($firstProv['kotas'])) ? $firstProv['kotas'][0] : null;
        $firstKec = ($firstKota && !empty($firstKota['kecamatans'])) ? $firstKota['kecamatans'][0] : null;

        $initialProvinsi = $firstProv['nama'] ?? 'Jawa Barat';
        $initialKotaId = null;
        $initialKecamatanId = null;
        $initialLevel = 'provinsi';

        if ($user->role === 'Sales') {
            $initialKotaId = $firstKota['id'] ?? null;
            $initialKecamatanId = $firstKec['id'] ?? null;
            $initialLevel = $initialKecamatanId ? 'kecamatan' : ($initialKotaId ? 'kota' : 'provinsi');
        } elseif ($user->role === 'SPV') {
            $initialKotaId = $firstKota['id'] ?? null;
            $initialLevel = $initialKotaId ? 'kota' : 'provinsi';
        }

        return view('potensi-wilayah.index', compact(
            'provinces',
            'globalStats',
            'initialProvinsi',
            'initialKotaId',
            'initialKecamatanId',
            'initialLevel'
        ));
    }
}
