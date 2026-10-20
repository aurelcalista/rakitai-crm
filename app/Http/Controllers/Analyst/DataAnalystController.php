<?php

namespace App\Http\Controllers\Analyst;

use App\Http\Controllers\Controller;
use App\Models\FollowUp;
use App\Models\Kunjungan;
use App\Models\MasterData;
use App\Models\Perusahaan;
use App\Models\Prodi;
use App\Models\Prospek;
use App\Models\Sekolah;
use App\Models\TahunAkademik;
use App\Models\Target;
use App\Models\Transaksi;
use App\Models\User;
use App\Models\Wilayah;
use App\Services\AnalystExportService;
use App\Services\TargetAchievementService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataAnalystController extends Controller
{
    protected AnalystExportService $exportService;
    protected TargetAchievementService $targetService;

    public function __construct(AnalystExportService $exportService, TargetAchievementService $targetService)
    {
        $this->exportService = $exportService;
        $this->targetService = $targetService;
    }

    /**
     * Dashboard Analitik PMB khusus Role Data Analyst.
     * Mendukung render view HTML awal dan respons JSON realtime via AJAX/Fetch.
     */
    public function index(Request $request): View|\Illuminate\Http\JsonResponse
    {
        $filters = $this->resolveFilters($request);

        // Active Academic Year
        $activeTa = TahunAkademik::getAktif()?->nama ?? '2026/2027';
        $selectedTa = $filters['ta'] ?? $activeTa;

        // Base Query with Filters
        $baseQuery = $this->exportService->buildProspekQuery($filters);

        // ──────────────────────────────────────────────────────────────
        // 1. RINGKASAN METRIK UTAMA (SUMMARY CARDS)
        // ──────────────────────────────────────────────────────────────
        $totalProspekUnik = (clone $baseQuery)->count();

        // Kontak Baru (Dibuat dalam periode filter)
        $kontakBaruQuery = clone $baseQuery;
        if (!empty($filters['from_date']) && !empty($filters['to_date'])) {
            $kontakBaruCount = $kontakBaruQuery->whereBetween('prospeks.created_at', [$filters['from_date'] . ' 00:00:00', $filters['to_date'] . ' 23:59:59'])->count();
        } else {
            $kontakBaruCount = (clone $baseQuery)->whereIn('status', ['BARU', 'KONTAK'])->count();
        }

        // Formulir & Lunas
        $formulirCount = (clone $baseQuery)->whereIn('status', ['FORMULIR', 'BERKAS', 'CLOSING', 'LUNAS'])->count();
        $berkasCount = (clone $baseQuery)->whereIn('status', ['BERKAS', 'CLOSING', 'LUNAS'])->count();
        $lunasCount = (clone $baseQuery)->where('status', 'LUNAS')->count();
        $lostCount = (clone $baseQuery)->whereIn('status', ['DINGIN', 'NO RESPON', 'CANCEL'])->count();

        // Konversi
        $convKontakFormulir = $totalProspekUnik > 0 ? round(($formulirCount / $totalProspekUnik) * 100, 1) : 0;
        $convKontakLunas = $totalProspekUnik > 0 ? round(($lunasCount / $totalProspekUnik) * 100, 1) : 0;
        $convFormulirLunas = $formulirCount > 0 ? round(($lunasCount / $formulirCount) * 100, 1) : 0;

        // ──────────────────────────────────────────────────────────────
        // 2. FUNNEL PMB LENGKAP
        // ──────────────────────────────────────────────────────────────
        $stageBaru = (clone $baseQuery)->whereIn('status', ['BARU', 'KONTAK'])->count();
        $stageHangat = (clone $baseQuery)->whereIn('status', ['PROSPEK', 'HOT PROSPEK', 'HANGAT', 'PANAS'])->count();
        $stageFormulir = $formulirCount;
        $stageBerkas = $berkasCount;
        $stageLunas = $lunasCount;

        $funnelData = [
            [
                'stage' => '1. Kontak / Lead Masuk',
                'sub' => 'Registrasi kontak baru & inbound lead',
                'count' => $totalProspekUnik,
                'pct_total' => 100,
                'drop_count' => (clone $baseQuery)->whereIn('status', ['DINGIN', 'NO RESPON'])->count(),
                'color' => 'blue',
            ],
            [
                'stage' => '2. Audiensi & Prospek Aktif',
                'sub' => 'Prospek hangat/panas dalam follow-up',
                'count' => $stageHangat + $stageFormulir,
                'pct_total' => $totalProspekUnik > 0 ? round((($stageHangat + $stageFormulir) / $totalProspekUnik) * 100) : 0,
                'drop_count' => (clone $baseQuery)->whereIn('status', ['DINGIN', 'NO RESPON'])->where('stage_number', '<=', 4)->count(),
                'color' => 'indigo',
            ],
            [
                'stage' => '3. Pembelian Formulir',
                'sub' => 'Calon mahasiswa membeli formulir pendaftaran',
                'count' => $stageFormulir,
                'pct_total' => $totalProspekUnik > 0 ? round(($stageFormulir / $totalProspekUnik) * 100) : 0,
                'drop_count' => (clone $baseQuery)->where('status', 'CANCEL')->count(),
                'color' => 'purple',
            ],
            [
                'stage' => '4. Pemberkasan Dokumen',
                'sub' => 'Pengumpulan berkas persyaratan PMB',
                'count' => $stageBerkas,
                'pct_total' => $totalProspekUnik > 0 ? round(($stageBerkas / $totalProspekUnik) * 100) : 0,
                'drop_count' => max(0, $stageFormulir - $stageBerkas),
                'color' => 'amber',
            ],
            [
                'stage' => '5. Mahasiswa Lunas Tahap 1',
                'sub' => 'Maba resmi (Registrasi & Termin 1 Terverifikasi)',
                'count' => $stageLunas,
                'pct_total' => $totalProspekUnik > 0 ? round(($stageLunas / $totalProspekUnik) * 100) : 0,
                'drop_count' => max(0, $stageBerkas - $stageLunas),
                'color' => 'emerald',
            ],
        ];

        // ──────────────────────────────────────────────────────────────
        // 3. PENDAFTAR & PEMBAYARAN TRANSAKSI VALID (100% DINAMIS)
        // ──────────────────────────────────────────────────────────────
        // Transaksi disaring berdasarkan prospek yang lolos baseQuery filter
        $transaksiQuery = Transaksi::where(function ($q) {
            $q->where('payment_status', Transaksi::STATUS_VERIFIED)
              ->orWhereNull('payment_status');
        })->whereIn('prospek_id', (clone $baseQuery)->select('prospeks.id'));

        if (!empty($filters['from_date'])) {
            $transaksiQuery->whereDate('tanggal', '>=', $filters['from_date']);
        }
        if (!empty($filters['to_date'])) {
            $transaksiQuery->whereDate('tanggal', '<=', $filters['to_date']);
        }

        $transaksiFormulirCount = (clone $transaksiQuery)->where('jenis', 'Beli Formulir')->count();
        $transaksiFormulirNominal = (clone $transaksiQuery)->where('jenis', 'Beli Formulir')->sum('nominal');

        $transaksiTermin1Count = (clone $transaksiQuery)->where('jenis', 'Pembayaran Termin 1')->count();
        $transaksiTermin1Nominal = (clone $transaksiQuery)->where('jenis', 'Pembayaran Termin 1')->sum('nominal');

        $totalRevenueValid = $transaksiFormulirNominal + $transaksiTermin1Nominal;

        // Pembayaran per Prodi
        $pembayaranPerProdi = (clone $transaksiQuery)
            ->join('prospeks', 'transaksis.prospek_id', '=', 'prospeks.id')
            ->leftJoin('prodis', 'prospeks.prodi_id', '=', 'prodis.id')
            ->selectRaw('COALESCE(prodis.nama, "Prodi Lainnya") as nama_prodi, SUM(transaksis.nominal) as total_nominal, COUNT(transaksis.id) as total_transaksi')
            ->groupBy('nama_prodi')
            ->orderByDesc('total_nominal')
            ->take(6)
            ->get();

        // ──────────────────────────────────────────────────────────────
        // 4. PERFORMA SALES & TIM (100% DINAMIS TERFILTER)
        // ──────────────────────────────────────────────────────────────
        $salesQuery = User::where('role', 'Sales')
            ->where('status', 'Aktif')
            ->with(['supervisor', 'wilayah']);

        if (!empty($filters['wilayah_id']) && $filters['wilayah_id'] !== 'all') {
            $salesQuery->where('wilayah_id', $filters['wilayah_id']);
        }
        if (!empty($filters['sales_id']) && $filters['sales_id'] !== 'all') {
            $salesQuery->where('id', $filters['sales_id']);
        }

        $isSqlite = DB::connection()->getDriverName() === 'sqlite';
        $diffRaw = $isSqlite
            ? 'AVG((julianday(follow_ups.created_at) - julianday(prospeks.created_at)) * 24) as avg_hours'
            : 'AVG(TIMESTAMPDIFF(HOUR, prospeks.created_at, follow_ups.created_at)) as avg_hours';

        $salesList = $salesQuery->get()
            ->map(function ($s) use ($baseQuery, $diffRaw) {
                $prospeks = (clone $baseQuery)->where('sales_id', $s->id);

                $totalKontak = (clone $prospeks)->count();
                $formulir = (clone $prospeks)->whereIn('status', ['FORMULIR', 'BERKAS', 'CLOSING', 'LUNAS'])->count();
                $lunas = (clone $prospeks)->where('status', 'LUNAS')->count();
                
                $followUpCount = FollowUp::where('user_id', $s->id)
                    ->whereIn('prospek_id', (clone $prospeks)->select('prospeks.id'))
                    ->count();

                // First response speed
                $firstFollowUps = FollowUp::where('user_id', $s->id)
                    ->join('prospeks', 'follow_ups.prospek_id', '=', 'prospeks.id')
                    ->whereIn('follow_ups.prospek_id', (clone $prospeks)->select('prospeks.id'))
                    ->selectRaw($diffRaw)
                    ->value('avg_hours');

                $avgResponseHours = $firstFollowUps !== null ? round((float) $firstFollowUps, 1) : null;
                $konversi = $totalKontak > 0 ? round(($lunas / $totalKontak) * 100, 1) : 0;

                return [
                    'id'                 => $s->id,
                    'kode'               => $s->kode ?: '-',
                    'name'               => $s->name,
                    'spv'                => $s->supervisor?->name ?: 'Belum Ada',
                    'wilayah'            => $s->wilayah?->nama ?: 'Lintas Wilayah',
                    'total_kontak'       => $totalKontak,
                    'follow_up_count'    => $followUpCount,
                    'avg_response_hours' => $avgResponseHours,
                    'formulir_count'     => $formulir,
                    'lunas_count'        => $lunas,
                    'konversi'           => $konversi,
                ];
            })
            ->sortByDesc('lunas_count')
            ->values();

        // ──────────────────────────────────────────────────────────────
        // 5. SUMBER & KANAL MARKETING
        // ──────────────────────────────────────────────────────────────
        $sumberAnalysis = collect(Prospek::SOURCES)
            ->map(function ($src) use ($baseQuery) {
                $q = (clone $baseQuery)->where('source', $src);
                $kontak = (clone $q)->count();
                $formulir = (clone $q)->whereIn('status', ['FORMULIR', 'BERKAS', 'CLOSING', 'LUNAS'])->count();
                $lunas = (clone $q)->where('status', 'LUNAS')->count();
                $konversi = $kontak > 0 ? round(($lunas / $kontak) * 100, 1) : 0;

                return [
                    'nama' => $src,
                    'kontak' => $kontak,
                    'formulir' => $formulir,
                    'lunas' => $lunas,
                    'konversi' => $konversi,
                ];
            })
            ->filter(fn($item) => $item['kontak'] > 0 || in_array($item['nama'], ['Sekolah', 'Website CIC', 'Sosial Media (Facebook, Instagram, X)']))
            ->sortByDesc('kontak')
            ->values();

        $topChannel = $sumberAnalysis->sortByDesc('lunas')->first();
        $lowestChannel = $sumberAnalysis->filter(fn($s) => $s['kontak'] > 0)->sortBy('konversi')->first();

        // ──────────────────────────────────────────────────────────────
        // 6. SEKOLAH & PERUSAHAAN MITRA (DINAMIS WILAYAH)
        // ──────────────────────────────────────────────────────────────
        $sekolahQuery = Sekolah::query();
        if (!empty($filters['wilayah_id']) && $filters['wilayah_id'] !== 'all') {
            $sekolahQuery->where('wilayah_id', $filters['wilayah_id']);
        }
        $totalSekolah = (clone $sekolahQuery)->count();
        $totalPerusahaan = Perusahaan::count();

        // Sekolah yang sudah dihubungi (punya kunjungan atau punya prospek terdaftar)
        $sekolahDihubungiCount = (clone $sekolahQuery)->where(function ($q) {
            $q->has('kunjungans')->orHas('prospeks');
        })->count();

        $sekolahBelumDihubungiCount = max(0, $totalSekolah - $sekolahDihubungiCount);

        // Top 5 Sekolah Penyumbang Calon Mahasiswa Terbanyak (berdasarkan prospek terfilter)
        $topSekolah = (clone $sekolahQuery)->withCount([
            'prospeks as total_prospek' => function ($q) use ($baseQuery) {
                $q->whereIn('prospeks.id', (clone $baseQuery)->select('prospeks.id'));
            },
            'prospeks as total_lunas' => function ($q) use ($baseQuery) {
                $q->whereIn('prospeks.id', (clone $baseQuery)->select('prospeks.id'))->where('status', 'LUNAS');
            }
        ])
            ->orderByDesc('total_prospek')
            ->take(5)
            ->get();

        // ──────────────────────────────────────────────────────────────
        // 7. AKTIVITAS & KUALITAS DATA OPERASIONAL
        // ──────────────────────────────────────────────────────────────
        $belumFollowUpCount = (clone $baseQuery)
            ->where('follow_up_count', 0)
            ->whereNotIn('status', ['LUNAS', 'CANCEL'])
            ->count();

        // Duplikat nomor WhatsApp dalam hasil filter
        $duplicateWaCount = (clone $baseQuery)
            ->whereNotNull('whatsapp')
            ->where('whatsapp', '!=', '')
            ->select('whatsapp')
            ->groupBy('whatsapp')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        // Data prospek yang belum lengkap
        $incompleteDataCount = (clone $baseQuery)->where(function ($q) {
            $q
                ->whereNull('whatsapp')
                ->orWhere('whatsapp', '')
                ->orWhereNull('prodi_id')
                ->orWhereNull('source')
                ->orWhere(function ($sq) {
                    $sq->whereNull('sekolah_id')->whereNull('perusahaan_id');
                });
        })->count();

        // Riwayat follow-up terbaru (Read-only feed)
        $recentFollowUps = FollowUp::whereIn('prospek_id', (clone $baseQuery)->select('prospeks.id'))
            ->with(['prospek', 'user'])
            ->latest()
            ->take(5)
            ->get();

        // ──────────────────────────────────────────────────────────────
        // 8. TARGET DAN PENCAPAIAN (REUSE TARGET ACHIEVEMENT SERVICE)
        // ──────────────────────────────────────────────────────────────
        $targetAchievementData = $this->targetService->getDashboardTargetData(
            auth()->user(),
            $filters['periode_target'] ?? 'bulanan',
            !empty($filters['wilayah_id']) && $filters['wilayah_id'] !== 'all' ? (int) $filters['wilayah_id'] : null,
            $selectedTa
        );

        // ──────────────────────────────────────────────────────────────
        // 9. PERBANDINGAN PERIODE (100% DINAMIS DENGAN SCOPE FILTER)
        // ──────────────────────────────────────────────────────────────
        $periodComparison = $this->calculatePeriodComparison($filters);

        // ──────────────────────────────────────────────────────────────
        // 10. SEGMENTASI PENDAFTAR (TERMASUK GELOMBANG)
        // ──────────────────────────────────────────────────────────────
        // Segmentasi Prodi
        $segmentasiProdi = (clone $baseQuery)
            ->leftJoin('prodis', 'prospeks.prodi_id', '=', 'prodis.id')
            ->selectRaw('COALESCE(prodis.nama, "Belum Memilih Prodi") as nama_prodi, COUNT(prospeks.id) as total')
            ->groupBy('nama_prodi')
            ->orderByDesc('total')
            ->take(6)
            ->get();

        // Segmentasi Kelas (Reguler vs Karyawan)
        $segmentasiKelas = [
            'Reguler' => (clone $baseQuery)->where('kelas', 'Reguler')->count(),
            'Karyawan' => (clone $baseQuery)->where('kelas', 'Karyawan')->count(),
        ];

        // Segmentasi Jalur (Melalui Sales vs Tanpa Sales/Direct)
        $segmentasiJalur = [
            'sales' => (clone $baseQuery)->whereNotNull('sales_id')->count(),
            'direct' => (clone $baseQuery)->whereNull('sales_id')->count(),
        ];

        // Segmentasi Wilayah Domisili (Kota/Kabupaten)
        $segmentasiWilayah = (clone $baseQuery)
            ->leftJoin('wilayahs', 'prospeks.wilayah_id', '=', 'wilayahs.id')
            ->selectRaw('COALESCE(wilayahs.nama, "Luar Wilayah Terdaftar") as nama_wilayah, COUNT(prospeks.id) as total')
            ->groupBy('nama_wilayah')
            ->orderByDesc('total')
            ->take(6)
            ->get();

        // Segmentasi Gelombang Pendaftaran
        $segmentasiGelombang = (clone $baseQuery)
            ->whereNotNull('gelombang')
            ->where('gelombang', '!=', '')
            ->selectRaw('gelombang, COUNT(prospeks.id) as total')
            ->groupBy('gelombang')
            ->orderByDesc('total')
            ->get();

        // ──────────────────────────────────────────────────────────────
        // DATASET VISUALISASI GRAFIK INTERAKTIF (CHART.JS)
        // ──────────────────────────────────────────────────────────────
        $chartsData = $this->buildChartsData($baseQuery, $filters, $funnelData, $salesList);

        // ──────────────────────────────────────────────────────────────
        // DROPDOWN FILTER OPTIONS
        // ──────────────────────────────────────────────────────────────
        $wilayahOptions = Wilayah::where('level', 'Kota/Kabupaten')->where('status', 'Aktif')->orderBy('nama')->get();
        $prodiOptions = Prodi::where('status', 'Aktif')->orderBy('nama')->get();
        $taOptions = TahunAkademik::orderByDesc('nama')->get();
        $salesOptions = User::where('role', 'Sales')->where('status', 'Aktif')->orderBy('name')->get();
        $gelombangOptions = MasterData::where('type', 'gelombang')->where('status', 'Aktif')->orderBy('nama')->get();

        // Jika request via AJAX / Fetch realtime, kirim payload JSON
        if ($request->ajax() || $request->wantsJson() || $request->has('ajax')) {
            return response()->json([
                'success' => true,
                'filters' => $filters,
                'metrics' => [
                    'totalProspekUnik'        => $totalProspekUnik,
                    'kontakBaruCount'         => $kontakBaruCount,
                    'formulirCount'           => $formulirCount,
                    'lunasCount'              => $lunasCount,
                    'lostCount'               => $lostCount,
                    'convKontakFormulir'      => $convKontakFormulir,
                    'convKontakLunas'         => $convKontakLunas,
                    'convFormulirLunas'       => $convFormulirLunas,
                    'totalRevenueValid'       => $totalRevenueValid,
                    'transaksiFormulirNominal'=> $transaksiFormulirNominal,
                    'transaksiFormulirCount'  => $transaksiFormulirCount,
                    'transaksiTermin1Nominal' => $transaksiTermin1Nominal,
                    'transaksiTermin1Count'   => $transaksiTermin1Count,
                    'belumFollowUpCount'      => $belumFollowUpCount,
                    'duplicateWaCount'        => $duplicateWaCount,
                    'incompleteDataCount'     => $incompleteDataCount,
                ],
                'funnelData'            => $funnelData,
                'chartsData'            => $chartsData,
                'periodComparison'      => $periodComparison,
                'pembayaranPerProdi'    => $pembayaranPerProdi,
                'salesList'             => $salesList,
                'sumberAnalysis'        => $sumberAnalysis,
                'topChannel'            => $topChannel,
                'totalSekolah'          => $totalSekolah,
                'totalPerusahaan'       => $totalPerusahaan,
                'sekolahDihubungiCount' => $sekolahDihubungiCount,
                'topSekolah'            => $topSekolah,
                'segmentasiProdi'       => $segmentasiProdi,
                'segmentasiKelas'       => $segmentasiKelas,
                'segmentasiJalur'       => $segmentasiJalur,
                'segmentasiWilayah'     => $segmentasiWilayah,
                'segmentasiGelombang'   => $segmentasiGelombang,
            ]);
        }

        return view('analyst.dashboard', compact(
            'filters',
            'activeTa',
            'totalProspekUnik',
            'kontakBaruCount',
            'formulirCount',
            'lunasCount',
            'lostCount',
            'convKontakFormulir',
            'convKontakLunas',
            'convFormulirLunas',
            'funnelData',
            'transaksiFormulirCount',
            'transaksiFormulirNominal',
            'transaksiTermin1Count',
            'transaksiTermin1Nominal',
            'totalRevenueValid',
            'pembayaranPerProdi',
            'salesList',
            'sumberAnalysis',
            'topChannel',
            'lowestChannel',
            'totalSekolah',
            'totalPerusahaan',
            'sekolahDihubungiCount',
            'sekolahBelumDihubungiCount',
            'topSekolah',
            'belumFollowUpCount',
            'duplicateWaCount',
            'incompleteDataCount',
            'recentFollowUps',
            'targetAchievementData',
            'periodComparison',
            'segmentasiProdi',
            'segmentasiKelas',
            'segmentasiJalur',
            'segmentasiWilayah',
            'segmentasiGelombang',
            'chartsData',
            'wilayahOptions',
            'prodiOptions',
            'taOptions',
            'salesOptions',
            'gelombangOptions'
        ));
    }

    /**
     * Halaman Eksplorasi Data Prospek Read-Only untuk Data Analyst.
     */
    public function prospek(Request $request): View
    {
        $filters = $this->resolveFilters($request);

        $query = $this->exportService->buildProspekQuery($filters);

        // Scope filter
        if ($request->filled('scope')) {
            if ($request->scope === 'presensi') {
                $query->where(function ($pq) {
                    $pq->where('source', Prospek::SOURCE_PRESENSI_SISWA)
                       ->orWhere('source', 'Presensi Siswa')
                       ->orWhere('source', 'like', '%presensi%');
                });
            }
        }

        // Search text (nama, whatsapp, pic, no_registrasi, gelombang)
        if ($request->filled('search')) {
            $search = '%' . trim($request->search) . '%';
            $query->where(function ($q) use ($search) {
                $q
                    ->where('name', 'like', $search)
                    ->orWhere('whatsapp', 'like', $search)
                    ->orWhere('pic', 'like', $search)
                    ->orWhere('no_registrasi', 'like', $search)
                    ->orWhere('gelombang', 'like', $search);
            });
        }

        $prospeks = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        $wilayahOptions = Wilayah::where('level', 'Kota/Kabupaten')->where('status', 'Aktif')->orderBy('nama')->get();
        $prodiOptions = Prodi::where('status', 'Aktif')->orderBy('nama')->get();
        $taOptions = TahunAkademik::orderByDesc('nama')->get();
        $salesOptions = User::where('role', 'Sales')->where('status', 'Aktif')->orderBy('name')->get();
        $gelombangOptions = MasterData::where('type', 'gelombang')->where('status', 'Aktif')->orderBy('nama')->get();
        $sourceOptions = Prospek::SOURCES;

        return view('analyst.prospek', compact(
            'prospeks',
            'filters',
            'wilayahOptions',
            'prodiOptions',
            'taOptions',
            'salesOptions',
            'gelombangOptions',
            'sourceOptions'
        ));
    }

    /**
     * Endpoint Ekspor Excel / CSV.
     */
    public function exportExcel(Request $request): StreamedResponse
    {
        $type = $request->get('type', 'prospek');
        $filters = $this->resolveFilters($request);

        return $this->exportService->export($type, $filters);
    }

    /**
     * Resolve and normalize filter parameters from Request.
     */
    protected function resolveFilters(Request $request): array
    {
        $periode = $request->get('periode', 'semua');
        $now = Carbon::now();
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');

        if (empty($fromDate) || empty($toDate)) {
            switch ($periode) {
                case 'hari_ini':
                case 'harian':
                    $fromDate = $now->toDateString();
                    $toDate = $now->toDateString();
                    break;
                case 'kemarin':
                    $fromDate = $now->copy()->subDay()->toDateString();
                    $toDate = $now->copy()->subDay()->toDateString();
                    break;
                case 'minggu_ini':
                case 'mingguan':
                    $fromDate = $now->copy()->startOfWeek()->toDateString();
                    $toDate = $now->copy()->endOfWeek()->toDateString();
                    break;
                case 'bulan_ini':
                case 'bulanan':
                    $fromDate = $now->copy()->startOfMonth()->toDateString();
                    $toDate = $now->copy()->endOfMonth()->toDateString();
                    break;
                case 'tahunan':
                    $fromDate = $now->copy()->startOfYear()->toDateString();
                    $toDate = $now->copy()->endOfYear()->toDateString();
                    break;
            }
        }

        return [
            'periode'        => $periode,
            'from_date'      => $fromDate,
            'to_date'        => $toDate,
            'ta'             => $request->get('ta', TahunAkademik::getAktif()?->nama),
            'wilayah_id'     => $request->get('wilayah_id'),
            'prodi_id'       => $request->get('prodi_id'),
            'kelas'          => $request->get('kelas'),
            'gelombang'      => $request->get('gelombang'),
            'no_registrasi'  => $request->get('no_registrasi'),
            'source'         => $request->get('source'),
            'status'         => $request->get('status'),
            'sales_id'       => $request->get('sales_id'),
            'search'         => $request->get('search'),
            'periode_target' => $request->get('periode_target', $request->get('periode', 'bulanan')),
        ];
    }

    /**
     * Calculate period-over-period comparisons (Today vs Yesterday, This Week vs Last Week, This Month vs Last Month)
     * strictly adhering to the filtered prospect scope.
     */
    protected function calculatePeriodComparison(array $filters = []): array
    {
        $scopedQuery = $this->exportService->buildProspekQuery(array_merge($filters, [
            'from_date' => null,
            'to_date'   => null,
            'periode'   => null,
        ]));

        $todayStart = Carbon::today()->startOfDay();
        $todayEnd = Carbon::today()->endOfDay();

        $yesterdayStart = Carbon::yesterday()->startOfDay();
        $yesterdayEnd = Carbon::yesterday()->endOfDay();

        $thisWeekStart = Carbon::now()->startOfWeek();
        $thisWeekEnd = Carbon::now()->endOfWeek();

        $lastWeekStart = Carbon::now()->subWeek()->startOfWeek();
        $lastWeekEnd = Carbon::now()->subWeek()->endOfWeek();

        $thisMonthStart = Carbon::now()->startOfMonth();
        $thisMonthEnd = Carbon::now()->endOfMonth();

        $lastMonthStart = Carbon::now()->subMonth()->startOfMonth();
        $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth();

        // 1. Hari Ini vs Kemarin
        $kontakToday     = (clone $scopedQuery)->whereBetween('prospeks.created_at', [$todayStart, $todayEnd])->count();
        $kontakYesterday = (clone $scopedQuery)->whereBetween('prospeks.created_at', [$yesterdayStart, $yesterdayEnd])->count();

        $lunasToday     = (clone $scopedQuery)->where('status', 'LUNAS')->whereBetween('prospeks.updated_at', [$todayStart, $todayEnd])->count();
        $lunasYesterday = (clone $scopedQuery)->where('status', 'LUNAS')->whereBetween('prospeks.updated_at', [$yesterdayStart, $yesterdayEnd])->count();

        // 2. Minggu Ini vs Minggu Kemarin
        $kontakThisWeek = (clone $scopedQuery)->whereBetween('prospeks.created_at', [$thisWeekStart, $thisWeekEnd])->count();
        $kontakLastWeek = (clone $scopedQuery)->whereBetween('prospeks.created_at', [$lastWeekStart, $lastWeekEnd])->count();

        $lunasThisWeek = (clone $scopedQuery)->where('status', 'LUNAS')->whereBetween('prospeks.updated_at', [$thisWeekStart, $thisWeekEnd])->count();
        $lunasLastWeek = (clone $scopedQuery)->where('status', 'LUNAS')->whereBetween('prospeks.updated_at', [$lastWeekStart, $lastWeekEnd])->count();

        // 3. Bulan Ini vs Bulan Kemarin
        $kontakThisMonth = (clone $scopedQuery)->whereBetween('prospeks.created_at', [$thisMonthStart, $thisMonthEnd])->count();
        $kontakLastMonth = (clone $scopedQuery)->whereBetween('prospeks.created_at', [$lastMonthStart, $lastMonthEnd])->count();

        $lunasThisMonth = (clone $scopedQuery)->where('status', 'LUNAS')->whereBetween('prospeks.updated_at', [$thisMonthStart, $thisMonthEnd])->count();
        $lunasLastMonth = (clone $scopedQuery)->where('status', 'LUNAS')->whereBetween('prospeks.updated_at', [$lastMonthStart, $lastMonthEnd])->count();

        return [
            'today' => [
                'kontak'      => $kontakToday,
                'kontak_prev' => $kontakYesterday,
                'kontak_diff' => $kontakToday - $kontakYesterday,
                'lunas'       => $lunasToday,
                'lunas_prev'  => $lunasYesterday,
                'lunas_diff'  => $lunasToday - $lunasYesterday,
            ],
            'week' => [
                'kontak'      => $kontakThisWeek,
                'kontak_prev' => $kontakLastWeek,
                'kontak_diff' => $kontakThisWeek - $kontakLastWeek,
                'lunas'       => $lunasThisWeek,
                'lunas_prev'  => $lunasLastWeek,
                'lunas_diff'  => $lunasThisWeek - $lunasLastWeek,
            ],
            'month' => [
                'kontak'      => $kontakThisMonth,
                'kontak_prev' => $kontakLastMonth,
                'kontak_diff' => $kontakThisMonth - $kontakLastMonth,
                'lunas'       => $lunasThisMonth,
                'lunas_prev'  => $lunasLastMonth,
                'lunas_diff'  => $lunasThisMonth - $lunasLastMonth,
            ],
        ];
    }

    /**
     * Build Chart.js formatted data points for interactive visualizations.
     */
    protected function buildChartsData($baseQuery, array $filters, array $funnelData, $salesList): array
    {
        $endDate = !empty($filters['to_date']) ? Carbon::parse($filters['to_date']) : Carbon::today();
        $startDate = !empty($filters['from_date']) ? Carbon::parse($filters['from_date']) : $endDate->copy()->subDays(13);

        if ($startDate->gt($endDate)) {
            $startDate = $endDate->copy()->subDays(13);
        }

        $diffDays = $startDate->diffInDays($endDate);
        $step = max(1, (int) ceil(($diffDays + 1) / 14));

        $trendLabels = [];
        $trendKontak = [];
        $trendFormulir = [];
        $trendLunas = [];

        $current = $startDate->copy();
        for ($i = 0; $i < 14 && $current <= $endDate; $i++) {
            $dayStr = $current->toDateString();
            $trendLabels[] = $current->isoFormat('D MMM');

            $kontakDay  = (clone $baseQuery)->whereDate('prospeks.created_at', $dayStr)->count();
            $formulirDay = (clone $baseQuery)->whereIn('status', ['FORMULIR', 'BERKAS', 'CLOSING', 'LUNAS'])
                ->whereDate('prospeks.updated_at', $dayStr)->count();
            $lunasDay = (clone $baseQuery)->where('status', 'LUNAS')
                ->whereDate('prospeks.updated_at', $dayStr)->count();

            $trendKontak[] = $kontakDay;
            $trendFormulir[] = $formulirDay;
            $trendLunas[] = $lunasDay;

            $current->addDays($step);
        }

        // Funnel chart
        $funnelLabels = array_column($funnelData, 'stage');
        $funnelCounts = array_column($funnelData, 'count');

        // Top 5 Sales chart
        $topSales = collect($salesList)->take(5);
        $salesNames = $topSales->pluck('name')->toArray();
        $salesKontak = $topSales->pluck('total_kontak')->toArray();
        $salesFormulir = $topSales->pluck('formulir_count')->toArray();
        $salesLunas = $topSales->pluck('lunas_count')->toArray();

        return [
            'trend' => [
                'labels'   => $trendLabels,
                'kontak'   => $trendKontak,
                'formulir' => $trendFormulir,
                'lunas'    => $trendLunas,
            ],
            'funnel' => [
                'labels' => $funnelLabels,
                'counts' => $funnelCounts,
            ],
            'sales' => [
                'labels'   => $salesNames,
                'kontak'   => $salesKontak,
                'formulir' => $salesFormulir,
                'lunas'    => $salesLunas,
            ],
        ];
    }
}
