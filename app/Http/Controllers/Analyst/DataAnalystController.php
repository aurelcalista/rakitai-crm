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
     */
    public function index(Request $request): View
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
            $kontakBaruCount = $kontakBaruQuery->whereBetween('created_at', [$filters['from_date'] . ' 00:00:00', $filters['to_date'] . ' 23:59:59'])->count();
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
        // 3. PENDAFTAR & PEMBAYARAN TRANSAKSI VALID
        // ──────────────────────────────────────────────────────────────
        $transaksiQuery = Transaksi::where(function ($q) {
            $q
                ->where('payment_status', Transaksi::STATUS_VERIFIED)
                ->orWhereNull('payment_status');
        });

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
        // 4. PERFORMA SALES & TIM
        // ──────────────────────────────────────────────────────────────
        $salesList = User::where('role', 'Sales')
            ->where('status', 'Aktif')
            ->with(['supervisor', 'wilayah'])
            ->get()
            ->map(function ($s) use ($filters) {
                $prospeks = Prospek::where('sales_id', $s->id);
                if (!empty($filters['from_date'])) {
                    $prospeks->whereDate('created_at', '>=', $filters['from_date']);
                }
                if (!empty($filters['to_date'])) {
                    $prospeks->whereDate('created_at', '<=', $filters['to_date']);
                }

                $totalKontak = (clone $prospeks)->count();
                $formulir = (clone $prospeks)->whereIn('status', ['FORMULIR', 'BERKAS', 'CLOSING', 'LUNAS'])->count();
                $lunas = (clone $prospeks)->where('status', 'LUNAS')->count();
                $followUpCount = FollowUp::where('user_id', $s->id)->count();

                // First response speed
                $isSqlite = DB::connection()->getDriverName() === 'sqlite';
                $diffRaw = $isSqlite
                    ? 'AVG((julianday(follow_ups.created_at) - julianday(prospeks.created_at)) * 24) as avg_hours'
                    : 'AVG(TIMESTAMPDIFF(HOUR, prospeks.created_at, follow_ups.created_at)) as avg_hours';

                $firstFollowUps = FollowUp::where('user_id', $s->id)
                    ->join('prospeks', 'follow_ups.prospek_id', '=', 'prospeks.id')
                    ->selectRaw($diffRaw)
                    ->value('avg_hours');

                $avgResponseHours = $firstFollowUps !== null ? round((float) $firstFollowUps, 1) : null;

                $konversi = $totalKontak > 0 ? round(($lunas / $totalKontak) * 100, 1) : 0;

                return [
                    'id' => $s->id,
                    'kode' => $s->kode ?: '-',
                    'name' => $s->name,
                    'spv' => $s->supervisor?->name ?: 'Belum Ada',
                    'wilayah' => $s->wilayah?->nama ?: 'Lintas Wilayah',
                    'total_kontak' => $totalKontak,
                    'follow_up_count' => $followUpCount,
                    'avg_response_hours' => $avgResponseHours,
                    'formulir_count' => $formulir,
                    'lunas_count' => $lunas,
                    'konversi' => $konversi,
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
        // 6. SEKOLAH & PERUSAHAAN MITRA
        // ──────────────────────────────────────────────────────────────
        $totalSekolah = Sekolah::count();
        $totalPerusahaan = Perusahaan::count();

        // Sekolah yang sudah dihubungi (punya kunjungan atau punya prospek)
        $sekolahDihubungiCount = Sekolah::where(function ($q) {
            $q->has('kunjungans')->orHas('prospeks');
        })->count();

        $sekolahBelumDihubungiCount = max(0, $totalSekolah - $sekolahDihubungiCount);

        // Top 5 Sekolah Penyumbang Calon Mahasiswa Terbanyak
        $topSekolah = Sekolah::withCount(['prospeks as total_prospek', 'prospeks as total_lunas' => function ($q) {
            $q->where('status', 'LUNAS');
        }])
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

        // Duplikat nomor WhatsApp
        $duplicateWaCount = Prospek::whereNotNull('whatsapp')
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
        $recentFollowUps = FollowUp::with(['prospek', 'user'])
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
        // 9. PERBANDINGAN PERIODE (DAILY, WEEKLY, MONTHLY)
        // ──────────────────────────────────────────────────────────────
        $periodComparison = $this->calculatePeriodComparison();

        // ──────────────────────────────────────────────────────────────
        // 10. SEGMENTASI PENDAFTAR
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

        // ──────────────────────────────────────────────────────────────
        // DROPDOWN FILTER OPTIONS
        // ──────────────────────────────────────────────────────────────
        $wilayahOptions = Wilayah::where('level', 'Kota/Kabupaten')->where('status', 'Aktif')->orderBy('nama')->get();
        $prodiOptions = Prodi::where('status', 'Aktif')->orderBy('nama')->get();
        $taOptions = TahunAkademik::orderByDesc('nama')->get();
        $salesOptions = User::where('role', 'Sales')->where('status', 'Aktif')->orderBy('name')->get();

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
            'wilayahOptions',
            'prodiOptions',
            'taOptions',
            'salesOptions'
        ));
    }

    /**
     * Halaman Eksplorasi Data Prospek Read-Only untuk Data Analyst.
     */
    public function prospek(Request $request): View
    {
        $filters = $this->resolveFilters($request);

        $query = $this->exportService->buildProspekQuery($filters);

        // Search text
        if ($request->filled('search')) {
            $search = '%' . trim($request->search) . '%';
            $query->where(function ($q) use ($search) {
                $q
                    ->where('name', 'like', $search)
                    ->orWhere('whatsapp', 'like', $search)
                    ->orWhere('pic', 'like', $search);
            });
        }

        $prospeks = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        $wilayahOptions = Wilayah::where('level', 'Kota/Kabupaten')->where('status', 'Aktif')->orderBy('nama')->get();
        $prodiOptions = Prodi::where('status', 'Aktif')->orderBy('nama')->get();
        $taOptions = TahunAkademik::orderByDesc('nama')->get();
        $salesOptions = User::where('role', 'Sales')->where('status', 'Aktif')->orderBy('name')->get();

        return view('analyst.prospek', compact(
            'prospeks',
            'filters',
            'wilayahOptions',
            'prodiOptions',
            'taOptions',
            'salesOptions'
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
            'periode' => $periode,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'ta' => $request->get('ta', TahunAkademik::getAktif()?->nama),
            'wilayah_id' => $request->get('wilayah_id'),
            'prodi_id' => $request->get('prodi_id'),
            'kelas' => $request->get('kelas'),
            'source' => $request->get('source'),
            'status' => $request->get('status'),
            'sales_id' => $request->get('sales_id'),
            'periode_target' => $request->get('periode_target', 'bulanan'),
        ];
    }

    /**
     * Calculate period-over-period comparisons (Today vs Yesterday, This Week vs Last Week, This Month vs Last Month).
     */
    protected function calculatePeriodComparison(): array
    {
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
        $kontakToday = Prospek::whereBetween('created_at', [$todayStart, $todayEnd])->count();
        $kontakYesterday = Prospek::whereBetween('created_at', [$yesterdayStart, $yesterdayEnd])->count();

        $lunasToday = Prospek::where('status', 'LUNAS')->whereBetween('updated_at', [$todayStart, $todayEnd])->count();
        $lunasYesterday = Prospek::where('status', 'LUNAS')->whereBetween('updated_at', [$yesterdayStart, $yesterdayEnd])->count();

        // 2. Minggu Ini vs Minggu Kemarin
        $kontakThisWeek = Prospek::whereBetween('created_at', [$thisWeekStart, $thisWeekEnd])->count();
        $kontakLastWeek = Prospek::whereBetween('created_at', [$lastWeekStart, $lastWeekEnd])->count();

        $lunasThisWeek = Prospek::where('status', 'LUNAS')->whereBetween('updated_at', [$thisWeekStart, $thisWeekEnd])->count();
        $lunasLastWeek = Prospek::where('status', 'LUNAS')->whereBetween('updated_at', [$lastWeekStart, $lastWeekEnd])->count();

        // 3. Bulan Ini vs Bulan Kemarin
        $kontakThisMonth = Prospek::whereBetween('created_at', [$thisMonthStart, $thisMonthEnd])->count();
        $kontakLastMonth = Prospek::whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])->count();

        $lunasThisMonth = Prospek::where('status', 'LUNAS')->whereBetween('updated_at', [$thisMonthStart, $thisMonthEnd])->count();
        $lunasLastMonth = Prospek::where('status', 'LUNAS')->whereBetween('updated_at', [$lastMonthStart, $lastMonthEnd])->count();

        return [
            'today' => [
                'kontak' => $kontakToday,
                'kontak_prev' => $kontakYesterday,
                'kontak_diff' => $kontakToday - $kontakYesterday,
                'lunas' => $lunasToday,
                'lunas_prev' => $lunasYesterday,
                'lunas_diff' => $lunasToday - $lunasYesterday,
            ],
            'week' => [
                'kontak' => $kontakThisWeek,
                'kontak_prev' => $kontakLastWeek,
                'kontak_diff' => $kontakThisWeek - $kontakLastWeek,
                'lunas' => $lunasThisWeek,
                'lunas_prev' => $lunasLastWeek,
                'lunas_diff' => $lunasThisWeek - $lunasLastWeek,
            ],
            'month' => [
                'kontak' => $kontakThisMonth,
                'kontak_prev' => $kontakLastMonth,
                'kontak_diff' => $kontakThisMonth - $kontakLastMonth,
                'lunas' => $lunasThisMonth,
                'lunas_prev' => $lunasLastMonth,
                'lunas_diff' => $lunasThisMonth - $lunasLastMonth,
            ],
        ];
    }
}
