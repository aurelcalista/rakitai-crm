<?php

namespace App\Services;

use App\Models\FollowUp;
use App\Models\Kunjungan;
use App\Models\Prospek;
use App\Models\TahunAkademik;
use App\Models\Target;
use App\Models\Transaksi;
use App\Models\User;
use App\Models\Wilayah;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TargetAchievementService
{
    public const DEFAULT_TA = '2027/2028';

    public const PERIODE_HARIAN = 'harian';

    public const PERIODE_MINGGUAN = 'mingguan';

    public const PERIODE_BULANAN = 'bulanan';

    public const PERIODE_TAHUNAN = 'tahunan';

    public const PERIODE_REALTIME = 'realtime';

    /**
     * Dapatkan TA aktif.
     */
    public function getActiveTa(): string
    {
        $ta = TahunAkademik::getAktif();
        return $ta ? $ta->nama : self::DEFAULT_TA;
    }

    /**
     * Resolve periode tanggal & sisa hari sesuai PRD Bab 6.2.
     *
     * @return array{
     *   key: string,
     *   label: string,
     *   start: Carbon,
     *   end: Carbon,
     *   sisa_hari: int,
     *   total_hari: int,
     *   is_realtime: bool
     * }
     */
    public function resolvePeriod(string $periode = self::PERIODE_BULANAN, ?string $tahunAkademik = null): array
    {
        $now = Carbon::now();
        $periode = strtolower(trim($periode));
        if ($periode === 'hari_ini' || $periode === 'daily')
            $periode = self::PERIODE_HARIAN;

        switch ($periode) {
            case self::PERIODE_HARIAN:
                $start = $now->copy()->startOfDay();
                $end = $now->copy()->endOfDay();
                $sisaHari = 1;
                $totalHari = 1;
                $label = 'Hari Ini (' . $now->translatedFormat('d M Y') . ')';
                $isRealtime = false;
                break;

            case self::PERIODE_MINGGUAN:
                $start = $now->copy()->startOfWeek();
                $end = $now->copy()->endOfWeek();
                $totalHari = 7;
                $sisaHari = max(1, (int) $now->diffInDays($end) + 1);
                $label = 'Minggu Ini (' . $start->format('d M') . ' - ' . $end->format('d M Y') . ')';
                $isRealtime = false;
                break;

            case self::PERIODE_TAHUNAN:
                $start = Carbon::create($now->year, 1, 1)->startOfDay();
                $end = Carbon::create($now->year, 12, 31)->endOfDay();
                $totalHari = (int) $start->diffInDays($end) + 1;
                $sisaHari = max(1, (int) $now->diffInDays($end));
                $label = 'Tahunan (' . ($tahunAkademik ?: $this->getActiveTa()) . ')';
                $isRealtime = false;
                break;

            case self::PERIODE_REALTIME:
                $start = $now->copy()->startOfDay();
                $end = $now->copy();
                $sisaHari = 1;
                $totalHari = 1;
                $label = 'Realtime (' . $now->format('d M Y, H:i:s') . ')';
                $isRealtime = true;
                break;

            case self::PERIODE_BULANAN:
            default:
                $periode = self::PERIODE_BULANAN;
                $start = $now->copy()->startOfMonth();
                $end = $now->copy()->endOfMonth();
                $totalHari = $now->daysInMonth;
                $sisaHari = max(1, (int) $now->diffInDays($end) + 1);
                $label = 'Bulan Ini (' . $now->translatedFormat('F Y') . ')';
                $isRealtime = false;
                break;
        }

        return [
            'key' => $periode,
            'label' => $label,
            'start' => $start,
            'end' => $end,
            'sisa_hari' => $sisaHari,
            'total_hari' => $totalHari,
            'is_realtime' => $isRealtime,
            'timestamp' => $now->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Hitung tabel Target & Pencapaian berjenjang (Sales, SPV, HM, Global).
     * Sesuai PRD Bab 6.2:
     * Metrik per baris: Target | Pencapaian | Kekurangan | % Achievement | Sisa Hari | Target Harian Berjalan.
     *
     * @param User $user Authenticated user
     * @param string $periode harian|mingguan|bulanan|tahunan|realtime
     * @param int|null $wilayahId Optional wilayah filter (for SPV, HM, Global)
     * @param string|null $tahunAkademik Academic year
     * @return array
     */
    public function getDashboardTargetData(User $user, string $periode = self::PERIODE_BULANAN, ?int $wilayahId = null, ?string $tahunAkademik = null): array
    {
        $ta = $tahunAkademik ?: $this->getActiveTa();
        $periodInfo = $this->resolvePeriod($periode, $ta);
        $role = $user->role;

        // Tentukan level akses wilayah (PRD Bab 4.4)
        $level = match ($role) {
            'Sales' => 'Sales',
            'CS' => 'Sales',  // CS evaluated similarly on handler level
            'SPV' => 'SPV',
            'HM' => 'HM',
            'Admin' => 'Global',
            default => 'Global',
        };

        // Kumpulkan data indikator dan breakdown berjenjang
        $kpiRows = [];
        $hierarchyRows = [];
        $wilayahOptions = collect();
        $selectedWilayah = null;

        if ($level === 'Sales') {
            $data = $this->buildSalesLevelData($user, $periodInfo, $ta);
            $kpiRows = $data['kpi_rows'];
            $hierarchyRows = $data['hierarchy_rows'];
        } elseif ($level === 'SPV') {
            $data = $this->buildSpvLevelData($user, $periodInfo, $wilayahId, $ta);
            $kpiRows = $data['kpi_rows'];
            $hierarchyRows = $data['hierarchy_rows'];
            $wilayahOptions = $data['wilayah_options'];
            $selectedWilayah = $data['selected_wilayah'];
        } elseif ($level === 'HM') {
            $data = $this->buildHmLevelData($user, $periodInfo, $wilayahId, $ta);
            $kpiRows = $data['kpi_rows'];
            $hierarchyRows = $data['hierarchy_rows'];
            $wilayahOptions = $data['wilayah_options'];
            $selectedWilayah = $data['selected_wilayah'];
            $territoryTable = $data['territory_table'] ?? [];
        } else {  // Global / Admin
            $data = $this->buildGlobalLevelData($periodInfo, $wilayahId, $ta);
            $kpiRows = $data['kpi_rows'];
            $hierarchyRows = $data['hierarchy_rows'];
            $wilayahOptions = $data['wilayah_options'];
            $selectedWilayah = $data['selected_wilayah'];
        }

        return [
            'level' => $level,
            'user' => $user,
            'role' => $role,
            'periode' => $periodInfo,
            'tahun_akademik' => $ta,
            'kpi_rows' => $kpiRows,
            'hierarchy_rows' => $hierarchyRows,
            'territory_table' => $territoryTable ?? [],
            'wilayah_options' => $wilayahOptions,
            'selected_wilayah' => $selectedWilayah,
            'period_options' => [
                ['key' => self::PERIODE_HARIAN, 'label' => 'Hari Ini / Harian'],
                ['key' => self::PERIODE_MINGGUAN, 'label' => 'Mingguan'],
                ['key' => self::PERIODE_BULANAN, 'label' => 'Bulanan'],
                ['key' => self::PERIODE_TAHUNAN, 'label' => 'Tahunan'],
            ],
        ];
    }

    /**
     * Hitung metrik baris standar (Target | Realisasi | Akumulasi Realisasi | Sisa Target | Defisit Target | Indikator Pencapaian).
     */
    public function formatMetricRow(string $label, int $target, int $pencapaian, int $sisaHari, array $meta = []): array
    {
        $kekurangan = max(0, $target - $pencapaian);
        $sisaTarget = $kekurangan;
        $defisitTarget = $pencapaian < $target ? ($target - $pencapaian) : 0;
        $akumulasi = $meta['akumulasi'] ?? $pencapaian;

        $percent = $target > 0
            ? round(($pencapaian / $target) * 100, 1)
            : ($pencapaian > 0 ? 100.0 : 0.0);

        if ($percent >= 100) {
            $statusLabel = 'Tercapai';
            $statusBadge = 'bg-emerald-100 text-emerald-800 border-emerald-300';
            $statusDot = 'bg-emerald-500';
        } elseif ($percent >= 70) {
            $statusLabel = 'Sesuai Target (On Track)';
            $statusBadge = 'bg-blue-100 text-blue-800 border-blue-300';
            $statusDot = 'bg-blue-500';
        } elseif ($percent >= 40) {
            $statusLabel = 'Perlu Perhatian';
            $statusBadge = 'bg-amber-100 text-amber-800 border-amber-300';
            $statusDot = 'bg-amber-500';
        } else {
            $statusLabel = 'Dibawah Target (Defisit)';
            $statusBadge = 'bg-rose-100 text-rose-800 border-rose-300';
            $statusDot = 'bg-rose-500';
        }

        return array_merge([
            'label' => $label,
            'target' => $target,
            'pencapaian' => $pencapaian,
            'akumulasi_realisasi' => $akumulasi,
            'sisa_target' => $sisaTarget,
            'defisit_target' => $defisitTarget,
            'kekurangan' => $kekurangan,
            'achievement_pct' => $percent,
            'status_label' => $statusLabel,
            'status_badge' => $statusBadge,
            'status_dot' => $statusDot,
            'sisa_hari' => $sisaHari,
            // Keep legacy key for safe backward compatibility
            'target_harian_berjalan' => 0,
        ], $meta);
    }

    /**
     * BUILDER: Level Sales (Personal Performance)
     */
    private function buildSalesLevelData(User $sales, array $period, string $ta): array
    {
        $start = $period['start'];
        $end = $period['end'];
        $sisa = $period['sisa_hari'];

        // Ambil target yang dialokasikan oleh SPV untuk Sales ini
        $target = Target::where('sales_id', $sales->id)
            ->where('status', 'Aktif')
            ->where(function ($q) use ($ta) {
                $q->where('tahun_akademik', $ta)->orWhereNull('tahun_akademik');
            })
            ->latest()
            ->first();

        $tLunas = $this->prorateTarget($target ? $target->target_lunas : 0, $period, $target?->tipe_periode);
        $tFormulir = $this->prorateTarget($target ? $target->target_formulir : 0, $period, $target?->tipe_periode);
        $tKontak = $this->prorateTarget($target ? $target->target_kontak : 0, $period, $target?->tipe_periode);
        $tFollowup = $this->prorateTarget($target ? $target->target_followup : 0, $period, $target?->tipe_periode);
        $tKunjungan = $this->prorateTarget($target ? $target->target_kunjungan : 0, $period, $target?->tipe_periode);

        // Realisasi Aktual
        $pLunas = Prospek::where('sales_id', $sales->id)
            ->where('status', 'LUNAS')
            ->whereBetween('updated_at', [$start, $end])
            ->count();

        $pFormulir = Prospek::where('sales_id', $sales->id)
            ->whereIn('status', ['FORMULIR', 'BERKAS', 'LUNAS'])
            ->whereBetween('updated_at', [$start, $end])
            ->count();

        $pKontak = Prospek::where('sales_id', $sales->id)
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $pFollowup = FollowUp::where('user_id', $sales->id)
            ->whereBetween('tanggal', [$start, $end])
            ->count();

        $pKunjungan = Kunjungan::where('sales_id', $sales->id)
            ->whereBetween('tanggal', [$start->toDateString(), $end->toDateString()])
            ->count();

        // Akumulasi Realisasi Periode Tahun Akademik Berjalan
        $activeTa = TahunAkademik::getAktif();
        $taId = $activeTa?->id;

        $akLunas = Prospek::where('sales_id', $sales->id)
            ->where('status', 'LUNAS')
            ->when($taId, fn($q) => $q->where('academic_year_id', $taId))
            ->count();

        $akFormulir = Prospek::where('sales_id', $sales->id)
            ->whereIn('status', ['FORMULIR', 'BERKAS', 'LUNAS'])
            ->when($taId, fn($q) => $q->where('academic_year_id', $taId))
            ->count();

        $akKontak = Prospek::where('sales_id', $sales->id)
            ->when($taId, fn($q) => $q->where('academic_year_id', $taId))
            ->count();

        $akFollowup = FollowUp::where('user_id', $sales->id)->count();

        $akKunjungan = Kunjungan::where('sales_id', $sales->id)
            ->when($taId, fn($q) => $q->where('academic_year_id', $taId))
            ->count();

        $kpiRows = [
            $this->formatMetricRow('Maba Lunas (Closing)', $tLunas, $pLunas, $sisa, ['icon' => 'closing', 'badge' => 'Utama', 'akumulasi' => $akLunas]),
            $this->formatMetricRow('Beli Formulir PMB', $tFormulir, $pFormulir, $sisa, ['icon' => 'formulir', 'akumulasi' => $akFormulir]),
            $this->formatMetricRow('Kontak / Database Baru', $tKontak, $pKontak, $sisa, ['icon' => 'kontak', 'akumulasi' => $akKontak]),
            $this->formatMetricRow('Follow Up Prospek', $tFollowup, $pFollowup, $sisa, ['icon' => 'followup', 'akumulasi' => $akFollowup]),
            $this->formatMetricRow('Kunjungan Lapangan', $tKunjungan, $pKunjungan, $sisa, ['icon' => 'kunjungan', 'akumulasi' => $akKunjungan]),
        ];

        $hierarchyRows = [
            $this->formatMetricRow($sales->name . ' (' . $sales->role . ')', $tLunas, $pLunas, $sisa, [
                'entity_id' => $sales->id,
                'entity_type' => 'user',
                'role' => $sales->role,
                'wilayah' => $sales->wilayah?->nama ?? 'Wilayah Personal',
                'allocator' => $target?->allocator?->name ?? 'Supervisor',
                'akumulasi' => $akLunas,
            ]),
        ];

        return [
            'kpi_rows' => $kpiRows,
            'hierarchy_rows' => $hierarchyRows,
        ];
    }

    /**
     * BUILDER: Level SPV (Wilayah Binaan & Tim Sales/CS)
     */
    private function buildSpvLevelData(User $spv, array $period, ?int $wilayahId, string $ta): array
    {
        $start = $period['start'];
        $end = $period['end'];
        $sisa = $period['sisa_hari'];

        $teamMemberIds = $spv->teamMemberIds();
        $teamMembers = User::whereIn('id', $teamMemberIds)->with('wilayah')->orderBy('role')->orderBy('name')->get();

        // Target Wilayah SPV yang Diberikan HM
        $targetHm = Target::where('sales_id', $spv->id)
            ->where('status', 'Aktif')
            ->where(function ($q) use ($ta) {
                $q->where('tahun_akademik', $ta)->orWhereNull('tahun_akademik');
            })
            ->latest()
            ->first();

        // Base target SPV dari HM (atau default)
        $tHmKontak = $targetHm ? $targetHm->target_kontak : 0;
        $tHmFormulir = $targetHm ? $targetHm->target_formulir : 0;
        $tHmLunas = $targetHm ? $targetHm->target_lunas : 0;

        $tLunas = $this->prorateTarget($tHmLunas, $period, $targetHm?->tipe_periode);
        $tFormulir = $this->prorateTarget($tHmFormulir, $period, $targetHm?->tipe_periode);
        $tKontak = $this->prorateTarget($tHmKontak, $period, $targetHm?->tipe_periode);
        $tFollowup = $this->prorateTarget($targetHm ? $targetHm->target_followup : 0, $period, $targetHm?->tipe_periode);
        $tKunjungan = $this->prorateTarget($targetHm ? $targetHm->target_kunjungan : 0, $period, $targetHm?->tipe_periode);

        // Realisasi Tim (roll-up)
        $pLunas = Prospek::whereIn('sales_id', $teamMemberIds)
            ->where('status', 'LUNAS')
            ->whereBetween('updated_at', [$start, $end])
            ->count();

        $pFormulir = Prospek::where(function ($q) use ($teamMemberIds) {
            $q->whereIn('sales_id', $teamMemberIds)->orWhereIn('cs_id', $teamMemberIds);
        })
            ->whereIn('status', ['FORMULIR', 'BERKAS', 'LUNAS'])
            ->whereBetween('updated_at', [$start, $end])
            ->count();

        $pKontak = Prospek::whereIn('sales_id', $teamMemberIds)
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $pFollowup = FollowUp::whereIn('user_id', $teamMemberIds)
            ->whereBetween('tanggal', [$start, $end])
            ->count();

        $pKunjungan = Kunjungan::whereIn('sales_id', $teamMemberIds)
            ->whereBetween('tanggal', [$start->toDateString(), $end->toDateString()])
            ->count();

        // Akumulasi Realisasi Tim Periode Tahun Akademik Berjalan
        $activeTa = TahunAkademik::getAktif();
        $taId = $activeTa?->id;

        $akLunas = Prospek::whereIn('sales_id', $teamMemberIds)
            ->where('status', 'LUNAS')
            ->when($taId, fn($q) => $q->where('academic_year_id', $taId))
            ->count();

        $akFormulir = Prospek::where(function ($q) use ($teamMemberIds) {
            $q->whereIn('sales_id', $teamMemberIds)->orWhereIn('cs_id', $teamMemberIds);
        })
            ->whereIn('status', ['FORMULIR', 'BERKAS', 'LUNAS'])
            ->when($taId, fn($q) => $q->where('academic_year_id', $taId))
            ->count();

        $akKontak = Prospek::whereIn('sales_id', $teamMemberIds)
            ->when($taId, fn($q) => $q->where('academic_year_id', $taId))
            ->count();

        $akFollowup = FollowUp::whereIn('user_id', $teamMemberIds)->count();

        $akKunjungan = Kunjungan::whereIn('sales_id', $teamMemberIds)
            ->when($taId, fn($q) => $q->where('academic_year_id', $taId))
            ->count();

        $kpiRows = [
            $this->formatMetricRow('Maba Lunas (Closing Tim)', $tLunas, $pLunas, $sisa, ['icon' => 'closing', 'badge' => 'Utama HM', 'akumulasi' => $akLunas]),
            $this->formatMetricRow('Pembelian Formulir Tim', $tFormulir, $pFormulir, $sisa, ['icon' => 'formulir', 'akumulasi' => $akFormulir]),
            $this->formatMetricRow('Kontak / Database Baru', $tKontak, $pKontak, $sisa, ['icon' => 'kontak', 'akumulasi' => $akKontak]),
            $this->formatMetricRow('Follow Up Tim (Sales & CS)', $tFollowup, $pFollowup, $sisa, ['icon' => 'followup', 'akumulasi' => $akFollowup]),
            $this->formatMetricRow('Kunjungan Sekolah/Corporate', $tKunjungan, $pKunjungan, $sisa, ['icon' => 'kunjungan', 'akumulasi' => $akKunjungan]),
        ];

        // Hierarchy Rows: Breakdown Per Anggota Tim
        $hierarchyRows = [];

        // Baris Utama: Target SPV Keseluruhan (Dari HM)
        $hierarchyRows[] = $this->formatMetricRow('TOTAL WILAYAH: ' . ($spv->wilayah?->nama ?? 'Wilayah SPV'), $tLunas, $pLunas, $sisa, [
            'entity_id' => $spv->id,
            'entity_type' => 'spv_total',
            'role' => 'SPV',
            'is_total' => true,
            'wilayah' => $spv->wilayah?->nama ?? 'Wilayah SPV',
            'allocated_by' => $targetHm?->allocator?->name ?? 'Head of Marketing (HM)',
            'akumulasi' => $akLunas,
        ]);

        foreach ($teamMembers as $member) {
            // Target per member yang dialokasikan SPV
            $mTarget = Target::where('sales_id', $member->id)
                ->where('allocated_by', $spv->id)
                ->where('status', 'Aktif')
                ->where(function ($q) use ($ta) {
                    $q->where('tahun_akademik', $ta)->orWhereNull('tahun_akademik');
                })
                ->latest()
                ->first();

            $mTgtLunas = $this->prorateTarget($mTarget ? $mTarget->target_lunas : 0, $period, $mTarget?->tipe_periode);

            if ($member->role === 'CS') {
                $mPencapaian = Prospek::where('cs_id', $member->id)
                    ->where('status', 'LUNAS')
                    ->whereBetween('updated_at', [$start, $end])
                    ->count();

                $mAkumulasi = Prospek::where('cs_id', $member->id)
                    ->where('status', 'LUNAS')
                    ->when($taId, fn($q) => $q->where('academic_year_id', $taId))
                    ->count();
            } else {
                $mPencapaian = Prospek::where('sales_id', $member->id)
                    ->where('status', 'LUNAS')
                    ->whereBetween('updated_at', [$start, $end])
                    ->count();

                $mAkumulasi = Prospek::where('sales_id', $member->id)
                    ->where('status', 'LUNAS')
                    ->when($taId, fn($q) => $q->where('academic_year_id', $taId))
                    ->count();
            }

            $hierarchyRows[] = $this->formatMetricRow($member->name, $mTgtLunas, $mPencapaian, $sisa, [
                'entity_id' => $member->id,
                'entity_type' => 'user',
                'role' => $member->role,
                'wilayah' => $member->wilayah?->nama ?? ($spv->wilayah?->nama ?? '-'),
                'allocated_by' => $spv->name . ' (SPV)',
                'akumulasi' => $mAkumulasi,
            ]);
        }

        $wilayahOptions = Wilayah::where('id', $spv->wilayah_id)->get();
        if ($wilayahOptions->isEmpty()) {
            $wilayahOptions = Wilayah::limit(5)->get();
        }

        return [
            'kpi_rows' => $kpiRows,
            'hierarchy_rows' => $hierarchyRows,
            'wilayah_options' => $wilayahOptions,
            'selected_wilayah' => $spv->wilayah,
        ];
    }

    /**
     * BUILDER: Level HM (Head of Marketing — Mengatur SPV & Seluruh Wilayah)
     */
    private function buildHmLevelData(User $hm, array $period, ?int $wilayahId, string $ta): array
    {
        $start = $period['start'];
        $end = $period['end'];
        $sisa = $period['sisa_hari'];

        $wilayahOptions = Wilayah::orderBy('nama')->get();
        $selectedWilayah = $wilayahId ? Wilayah::find($wilayahId) : null;

        // Ambil semua SPV
        $spvQuery = User::where('role', 'SPV')->with('wilayah');
        if ($wilayahId) {
            $spvQuery->where('wilayah_id', $wilayahId);
        }
        $spvs = $spvQuery->get();

        // Realisasi Filtered
        $prospekQuery = Prospek::query();
        $transaksiQuery = Transaksi::query();
        $visitQuery = Kunjungan::query();
        $fuQuery = FollowUp::query();

        if ($wilayahId) {
            $prospekQuery->where('wilayah_id', $wilayahId);
            $visitQuery->whereHas('sekolah', fn($q) => $q->where('wilayah_id', $wilayahId));
        }

        $pLunas = (clone $prospekQuery)->where('status', 'LUNAS')->whereBetween('updated_at', [$start, $end])->count();
        $pFormulir = (clone $prospekQuery)->whereIn('status', ['FORMULIR', 'BERKAS', 'LUNAS'])->whereBetween('updated_at', [$start, $end])->count();
        $pKontak = (clone $prospekQuery)->whereBetween('created_at', [$start, $end])->count();
        $pFollowup = (clone $fuQuery)->whereBetween('tanggal', [$start, $end])->count();
        $pKunjungan = (clone $visitQuery)->whereBetween('tanggal', [$start->toDateString(), $end->toDateString()])->count();

        // Akumulasi Realisasi Periode Tahun Akademik Berjalan
        $activeTa = TahunAkademik::getAktif();
        $taId = $activeTa?->id;

        $akLunas = (clone $prospekQuery)->where('status', 'LUNAS')->when($taId, fn($q) => $q->where('academic_year_id', $taId))->count();
        $akFormulir = (clone $prospekQuery)->whereIn('status', ['FORMULIR', 'BERKAS', 'LUNAS'])->when($taId, fn($q) => $q->where('academic_year_id', $taId))->count();
        $akKontak = (clone $prospekQuery)->when($taId, fn($q) => $q->where('academic_year_id', $taId))->count();
        $akFollowup = FollowUp::count();
        $akKunjungan = (clone $visitQuery)->when($taId, fn($q) => $q->where('academic_year_id', $taId))->count();

        // Total target yang diberikan HM ke seluruh SPV
        $targetSpvQuery = Target::whereIn('sales_id', $spvs->pluck('id'))
            ->where('status', 'Aktif')
            ->where(function ($q) use ($ta) {
                $q->where('tahun_akademik', $ta)->orWhereNull('tahun_akademik');
            });

        $firstTgt = (clone $targetSpvQuery)->latest()->first();
        $totTgtLunas = $this->prorateTarget($targetSpvQuery->sum('target_lunas') ?: 0, $period, $firstTgt?->tipe_periode);
        $totTgtFormulir = $this->prorateTarget($targetSpvQuery->sum('target_formulir') ?: 0, $period, $firstTgt?->tipe_periode);
        $totTgtKontak = $this->prorateTarget($targetSpvQuery->sum('target_kontak') ?: 0, $period, $firstTgt?->tipe_periode);
        $totTgtFollowup = $this->prorateTarget($targetSpvQuery->sum('target_followup') ?: 0, $period, $firstTgt?->tipe_periode);
        $totTgtKunjungan = $this->prorateTarget($targetSpvQuery->sum('target_kunjungan') ?: 0, $period, $firstTgt?->tipe_periode);

        $kpiRows = [
            $this->formatMetricRow('Maba Lunas (Closing Kampus)', $totTgtLunas, $pLunas, $sisa, ['icon' => 'closing', 'badge' => 'KPI Utama HM', 'akumulasi' => $akLunas]),
            $this->formatMetricRow('Pembelian Formulir PMB', $totTgtFormulir, $pFormulir, $sisa, ['icon' => 'formulir', 'akumulasi' => $akFormulir]),
            $this->formatMetricRow('Database Prospek Masuk', $totTgtKontak, $pKontak, $sisa, ['icon' => 'kontak', 'akumulasi' => $akKontak]),
            $this->formatMetricRow('Follow Up Total Tim', $totTgtFollowup, $pFollowup, $sisa, ['icon' => 'followup', 'akumulasi' => $akFollowup]),
            $this->formatMetricRow('Kunjungan Tim Lapangan', $totTgtKunjungan, $pKunjungan, $sisa, ['icon' => 'kunjungan', 'akumulasi' => $akKunjungan]),
        ];

        // Hierarchy Rows: Baris per SPV & Teritori
        $hierarchyRows = [];
        $hierarchyRows[] = $this->formatMetricRow(' TOTAL KAMPUS UCIC (Semua Wilayah)', $totTgtLunas, $pLunas, $sisa, [
            'entity_id' => 0,
            'entity_type' => 'campus_total',
            'role' => 'HM',
            'is_total' => true,
            'wilayah' => $selectedWilayah ? $selectedWilayah->nama : 'Seluruh Wilayah',
            'allocated_by' => 'Head of Marketing',
            'akumulasi' => $akLunas,
        ]);

        foreach ($spvs as $spv) {
            $spvTarget = Target::where('sales_id', $spv->id)
                ->where('status', 'Aktif')
                ->where(function ($q) use ($ta) {
                    $q->where('tahun_akademik', $ta)->orWhereNull('tahun_akademik');
                })
                ->latest()
                ->first();

            $spvTgtLunas = $this->prorateTarget($spvTarget ? $spvTarget->target_lunas : 0, $period, $spvTarget?->tipe_periode);

            // Realisasi SPV & timnya
            $teamIds = $spv->teamMemberIds();
            $teamIds[] = $spv->id;
            $spvReal = Prospek::whereIn('sales_id', $teamIds)
                ->where('status', 'LUNAS')
                ->whereBetween('updated_at', [$start, $end])
                ->count();

            $spvAkLunas = Prospek::whereIn('sales_id', $teamIds)
                ->where('status', 'LUNAS')
                ->when($taId, fn($q) => $q->where('academic_year_id', $taId))
                ->count();

            $hierarchyRows[] = $this->formatMetricRow('Wilayah: ' . ($spv->wilayah?->nama ?? 'Wilayah ' . $spv->name) . ' (' . $spv->name . ')', $spvTgtLunas, $spvReal, $sisa, [
                'entity_id' => $spv->id,
                'entity_type' => 'spv',
                'role' => 'SPV',
                'wilayah' => $spv->wilayah?->nama ?? '-',
                'allocated_by' => $spvTarget?->allocator?->name ?? 'HM',
                'spv_name' => $spv->name,
                'team_count' => count($spv->teamMemberIds()),
                'akumulasi' => $spvAkLunas,
            ]);
        }

        // Build Table Per Wilayah data
        $territoryTable = [];
        $hmKotaId = null;
        $mainWilayah = $hm->wilayah_id ? Wilayah::find($hm->wilayah_id) : null;
        if ($mainWilayah) {
            $hmKotaId = $mainWilayah->level === 'Kota/Kabupaten' ? $mainWilayah->id : $mainWilayah->parent_id;
        }

        $wilayahsList = $hmKotaId
            ? Wilayah::where('parent_id', $hmKotaId)->orWhere('id', $hmKotaId)->where('status', 'Aktif')->orderBy('nama')->get()
            : Wilayah::where('status', 'Aktif')->orderBy('nama')->get();

        foreach ($wilayahsList as $w) {
            // SPV covering this Wilayah or parent
            $spvUser = User::where('role', 'SPV')
                ->where('status', 'Aktif')
                ->where(function ($q) use ($w) {
                    $q->where('wilayah_id', $w->id);
                    if ($w->parent_id) {
                        $q->orWhere('wilayah_id', $w->parent_id);
                    }
                })
                ->first();

            // Active Sales assigned to this Wilayah (user_wilayah is_active = 1)
            $activeSalesIds = \Illuminate\Support\Facades\DB::table('user_wilayah')
                ->where('wilayah_id', $w->id)
                ->where('role', 'Sales')
                ->where('is_active', true)
                ->pluck('user_id');

            $salesNames = User::whereIn('id', $activeSalesIds)->pluck('name')->toArray();
            if (empty($salesNames) && $w->users()->where('role', 'Sales')->where('status', 'Aktif')->exists()) {
                $salesNames = $w->users()->where('role', 'Sales')->where('status', 'Aktif')->pluck('name')->toArray();
            }

            // Active CS assigned to this Wilayah (user_wilayah is_active = 1)
            $activeCsIds = \Illuminate\Support\Facades\DB::table('user_wilayah')
                ->where('wilayah_id', $w->id)
                ->where('role', 'CS')
                ->where('is_active', true)
                ->pluck('user_id');

            $csNames = User::whereIn('id', $activeCsIds)->pluck('name')->toArray();
            if (empty($csNames) && $w->users()->where('role', 'CS')->where('status', 'Aktif')->exists()) {
                $csNames = $w->users()->where('role', 'CS')->where('status', 'Aktif')->pluck('name')->toArray();
            }

            // Target & Achievement
            $wTarget = Target::where('wilayah_id', $w->id)
                ->where('status', 'Aktif')
                ->where(function ($q) use ($ta) {
                    $q->where('tahun_akademik', $ta)->orWhereNull('tahun_akademik');
                })
                ->latest()
                ->first();

            $targetVal = $this->prorateTarget($wTarget ? $wTarget->target_lunas : 0, $period, $wTarget?->tipe_periode);
            $achievementVal = Prospek::where('wilayah_id', $w->id)
                ->where('status', 'LUNAS')
                ->whereBetween('updated_at', [$start, $end])
                ->count();

            $rowFormatted = $this->formatMetricRow($w->nama, $targetVal, $achievementVal, $sisa, [
                'wilayah_nama' => $w->nama,
                'spv_name' => $spvUser ? $spvUser->name : '-',
                'sales_names' => !empty($salesNames) ? implode(', ', $salesNames) : 'Belum Ada',
                'cs_names' => !empty($csNames) ? implode(', ', $csNames) : 'Belum Ada',
            ]);

            $territoryTable[] = $rowFormatted;
        }

        return [
            'kpi_rows' => $kpiRows,
            'hierarchy_rows' => $hierarchyRows,
            'territory_table' => $territoryTable,
            'wilayah_options' => $wilayahOptions,
            'selected_wilayah' => $selectedWilayah,
        ];
    }

    /**
     * BUILDER: Level Global (Admin / Pimpinan Eksekutif)
     */
    private function buildGlobalLevelData(array $period, ?int $wilayahId, string $ta): array
    {
        $start = $period['start'];
        $end = $period['end'];
        $sisa = $period['sisa_hari'];

        $wilayahOptions = Wilayah::orderBy('nama')->get();
        $selectedWilayah = $wilayahId ? Wilayah::find($wilayahId) : null;

        $prospekQuery = Prospek::query();
        if ($wilayahId) {
            $prospekQuery->where('wilayah_id', $wilayahId);
        }

        $pLunas = (clone $prospekQuery)->where('status', 'LUNAS')->whereBetween('updated_at', [$start, $end])->count();
        $pFormulir = (clone $prospekQuery)->whereIn('status', ['FORMULIR', 'BERKAS', 'LUNAS'])->whereBetween('updated_at', [$start, $end])->count();
        $pKontak = (clone $prospekQuery)->whereBetween('created_at', [$start, $end])->count();
        $pFollowup = FollowUp::whereBetween('tanggal', [$start, $end])->count();
        $pKunjungan = Kunjungan::whereBetween('tanggal', [$start->toDateString(), $end->toDateString()])->count();

        // Akumulasi Realisasi Periode Tahun Akademik Berjalan
        $activeTa = TahunAkademik::getAktif();
        $taId = $activeTa?->id;

        $akLunas = (clone $prospekQuery)->where('status', 'LUNAS')->when($taId, fn($q) => $q->where('academic_year_id', $taId))->count();
        $akFormulir = (clone $prospekQuery)->whereIn('status', ['FORMULIR', 'BERKAS', 'LUNAS'])->when($taId, fn($q) => $q->where('academic_year_id', $taId))->count();
        $akKontak = (clone $prospekQuery)->when($taId, fn($q) => $q->where('academic_year_id', $taId))->count();
        $akFollowup = FollowUp::count();
        $akKunjungan = Kunjungan::when($taId, fn($q) => $q->where('academic_year_id', $taId))->count();

        $spvIds = User::where('role', 'SPV')->pluck('id');
        $targetSpvQuery = Target::whereIn('sales_id', $spvIds)
            ->where('status', 'Aktif')
            ->where(function ($q) use ($ta) {
                $q->where('tahun_akademik', $ta)->orWhereNull('tahun_akademik');
            });

        $firstTgt = (clone $targetSpvQuery)->latest()->first();
        $totTgtLunas = $this->prorateTarget($targetSpvQuery->sum('target_lunas') ?: 0, $period, $firstTgt?->tipe_periode);
        $totTgtFormulir = $this->prorateTarget($targetSpvQuery->sum('target_formulir') ?: 0, $period, $firstTgt?->tipe_periode);
        $totTgtKontak = $this->prorateTarget($targetSpvQuery->sum('target_kontak') ?: 0, $period, $firstTgt?->tipe_periode);
        $totTgtFollowup = $this->prorateTarget($targetSpvQuery->sum('target_followup') ?: 0, $period, $firstTgt?->tipe_periode);
        $totTgtKunjungan = $this->prorateTarget($targetSpvQuery->sum('target_kunjungan') ?: 0, $period, $firstTgt?->tipe_periode);

        $kpiRows = [
            $this->formatMetricRow('Maba Lunas (Global Closing)', $totTgtLunas, $pLunas, $sisa, ['icon' => 'closing', 'badge' => 'Global', 'akumulasi' => $akLunas]),
            $this->formatMetricRow('Pembelian Formulir PMB', $totTgtFormulir, $pFormulir, $sisa, ['icon' => 'formulir', 'akumulasi' => $akFormulir]),
            $this->formatMetricRow('Database Prospek Masuk', $totTgtKontak, $pKontak, $sisa, ['icon' => 'kontak', 'akumulasi' => $akKontak]),
            $this->formatMetricRow('Total Follow Up CRM', $totTgtFollowup, $pFollowup, $sisa, ['icon' => 'followup', 'akumulasi' => $akFollowup]),
            $this->formatMetricRow('Total Kunjungan Lapangan', $totTgtKunjungan, $pKunjungan, $sisa, ['icon' => 'kunjungan', 'akumulasi' => $akKunjungan]),
        ];

        // Breakdown per Teritori / Wilayah
        $hierarchyRows = [];
        $hierarchyRows[] = $this->formatMetricRow(' TOTAL GLOBAL CRM UCIC', $totTgtLunas, $pLunas, $sisa, [
            'entity_id' => 0,
            'entity_type' => 'global_total',
            'role' => 'Global',
            'is_total' => true,
            'wilayah' => $selectedWilayah ? $selectedWilayah->nama : 'Seluruh Wilayah',
            'allocated_by' => 'Sistem & Rektorat',
            'akumulasi' => $akLunas,
        ]);

        $wilayahsToInspect = $wilayahId ? Wilayah::where('id', $wilayahId)->get() : Wilayah::orderBy('nama')->get();
        foreach ($wilayahsToInspect as $w) {
            $wLunas = Prospek::where('wilayah_id', $w->id)->where('status', 'LUNAS')->whereBetween('updated_at', [$start, $end])->count();
            $wAkLunas = Prospek::where('wilayah_id', $w->id)->where('status', 'LUNAS')->when($taId, fn($q) => $q->where('academic_year_id', $taId))->count();
            
            $wTgtData = Target::where('wilayah_id', $w->id)
                ->where('status', 'Aktif')
                ->where(function ($q) use ($ta) {
                    $q->where('tahun_akademik', $ta)->orWhereNull('tahun_akademik');
                })->latest()->first();
            $wTgt = $this->prorateTarget($wTgtData ? $wTgtData->target_lunas : 0, $period, $wTgtData?->tipe_periode);

            $spvInWilayah = User::where('role', 'SPV')->where('wilayah_id', $w->id)->first();

            $hierarchyRows[] = $this->formatMetricRow('Teritori: ' . $w->nama, $wTgt, $wLunas, $sisa, [
                'entity_id' => $w->id,
                'entity_type' => 'wilayah',
                'role' => 'Wilayah',
                'wilayah' => $w->nama,
                'spv_name' => $spvInWilayah?->name ?? 'Belum ada SPV',
                'allocated_by' => 'Head Marketing',
                'akumulasi' => $wAkLunas,
            ]);
        }

        return [
            'kpi_rows' => $kpiRows,
            'hierarchy_rows' => $hierarchyRows,
            'wilayah_options' => $wilayahOptions,
            'selected_wilayah' => $selectedWilayah,
        ];
    }

    /**
     * Hitung proration target sesuai periode (Harian = Base/25, Mingguan = Base/4, Tahunan = Base*12, dsb).
     * Mampu menangani target harian, mingguan, bulanan, maupun tahunan secara dinamis.
     */
    private function prorateTarget(int $baseValue, array $period, ?string $targetTipePeriode = 'Bulanan'): int
    {
        if ($baseValue <= 0)
            return 0;

        $targetTipe = strtolower($targetTipePeriode ?: 'bulanan');
        $viewPeriod = strtolower($period['key'] ?? self::PERIODE_BULANAN);

        // Convert baseValue to a monthly equivalent first
        $monthlyBase = match ($targetTipe) {
            'harian' => $baseValue * 25,
            'mingguan' => $baseValue * 4,
            'tahunan' => (int) round($baseValue / 12),
            default => $baseValue,  // 'bulanan'
        };

        // Convert monthlyBase to requested viewPeriod
        return match ($viewPeriod) {
            self::PERIODE_HARIAN, self::PERIODE_REALTIME => max(1, (int) round($monthlyBase / 25)),
            self::PERIODE_MINGGUAN => max(1, (int) round($monthlyBase / 4)),
            self::PERIODE_TAHUNAN => (int) ($monthlyBase * 12),
            default => $monthlyBase,  // 'bulanan'
        };
    }

    /**
     * Evaluasi otomatis capaian target:
     * 1. Target Sudah Tuntas (100% Achievement) -> kirim notifikasi selamat & laporan tuntas ke SPV/HM
     * 2. Target Belum Tuntas (Defisit / Deadline Mendekati) -> kirim peringatan sisa hari & target harian berjalan
     *
     * @return array{tuntas: int, belum_tuntas: int}
     */
    public function checkAndNotifyTargetStatus(?User $targetUser = null): array
    {
        $users = $targetUser
            ? collect([$targetUser])
            : User::whereIn('role', ['Sales', 'CS', 'SPV'])->where('status', 'Aktif')->get();

        $notifiedCount = ['tuntas' => 0, 'belum_tuntas' => 0];

        foreach ($users as $u) {
            $targets = Target::where('sales_id', $u->id)->where('status', 'Aktif')->get();
            foreach ($targets as $t) {
                // Tentukan realisasi berdasarkan role
                if ($u->role === 'SPV') {
                    $teamIds = $u->teamMemberIds();
                    $teamIds[] = $u->id;
                    $realisasiLunas = Prospek::whereIn('sales_id', $teamIds)
                        ->where('status', 'LUNAS')
                        ->whereBetween('updated_at', [$t->tanggal_mulai . ' 00:00:00', $t->tanggal_selesai . ' 23:59:59'])
                        ->count();
                    $realisasiKontak = Prospek::where(function ($q) use ($teamIds) {
                        $q->whereIn('sales_id', $teamIds)->orWhereIn('cs_id', $teamIds);
                    })->whereBetween('created_at', [$t->tanggal_mulai . ' 00:00:00', $t->tanggal_selesai . ' 23:59:59'])->count();
                } else {
                    $realisasiLunas = Prospek::where('sales_id', $u->id)
                        ->where('status', 'LUNAS')
                        ->whereBetween('updated_at', [$t->tanggal_mulai . ' 00:00:00', $t->tanggal_selesai . ' 23:59:59'])
                        ->count();
                    $realisasiKontak = Prospek::where(function ($q) use ($u) {
                        if ($u->role === 'CS') {
                            $q->where('cs_id', $u->id);
                        } else {
                            $q->where('sales_id', $u->id);
                        }
                    })->whereBetween('created_at', [$t->tanggal_mulai . ' 00:00:00', $t->tanggal_selesai . ' 23:59:59'])->count();
                }

                $targetLunas = (int) ($t->target_lunas ?? 0);
                $targetKontak = (int) ($t->target_kontak ?? 0);
                $isTuntas = false;

                if ($targetLunas > 0 && $realisasiLunas >= $targetLunas) {
                    $isTuntas = true;
                } elseif ($targetLunas <= 0 && $targetKontak > 0 && $realisasiKontak >= $targetKontak) {
                    $isTuntas = true;
                }

                // 1. TARGET SUDAH TUNTAS (100% Achievement)
                if ($isTuntas) {
                    $alreadyNotifiedTuntas = $u
                        ->notifications()
                        ->where('data->target_id', $t->id)
                        ->where('data->event_type', 'target_tuntas')
                        ->exists();

                    if (!$alreadyNotifiedTuntas) {
                        $capaianText = $targetLunas > 0 ? "{$realisasiLunas}/{$targetLunas} Maba Lunas" : "{$realisasiKontak}/{$targetKontak} Kontak";

                        // Notifikasi ke staf / SPV penerima target
                        $u->notify(new \App\Notifications\TargetNotification(
                            title: "Selamat! Target {$t->tipe_periode} Tuntas 100%",
                            message: "Luar biasa! Target {$t->tipe_periode} Anda ({$capaianText}) telah tuntas tercapai 100%. Pertahankan kinerja luar biasa ini!",
                            type: 'success',
                            link: route($u->role === 'SPV' ? 'spv.performa.index' : 'performa.index'),
                            icon: '',
                            extraData: ['target_id' => $t->id, 'event_type' => 'target_tuntas']
                        ));

                        // Notifikasi ke Atasan Berjenjang (SPV / HM)
                        if ($u->role === 'SPV') {
                            $hms = User::where('role', 'HM')->where('status', 'Aktif')->get();
                            foreach ($hms as $hm) {
                                $hm->notify(new \App\Notifications\TargetNotification(
                                    title: "Target Wilayah SPV {$u->name} Tuntas 100%!",
                                    message: "SPV {$u->name} dan tim telah menuntaskan 100% target {$t->tipe_periode} ({$capaianText}).",
                                    type: 'success',
                                    link: route('admin.target.index'),
                                    icon: '',
                                    extraData: ['target_id' => $t->id, 'event_type' => 'target_tuntas_spv']
                                ));
                            }
                        } else {
                            $spv = $u->spv;
                            if ($spv) {
                                $spv->notify(new \App\Notifications\TargetNotification(
                                    title: "Anggota Tim {$u->name} Menuntaskan Target!",
                                    message: "{$u->name} ({$u->role}) telah menuntaskan target {$t->tipe_periode} 100% ({$capaianText}).",
                                    type: 'success',
                                    link: route('spv.performa.index'),
                                    icon: '',
                                    extraData: ['target_id' => $t->id, 'event_type' => 'target_tuntas_member']
                                ));
                            }
                        }

                        $notifiedCount['tuntas']++;
                    }
                } else {
                    // 2. TARGET BELUM TUNTAS (Defisit / Evaluasi Mendekati Akhir Periode)
                    if ($t->tipe_periode === 'Harian') {
                        $sisaHari = 1;
                    } else {
                        $targetEnd = Carbon::parse($t->tanggal_selesai)->startOfDay();
                        $today = Carbon::today();
                        $diff = (int) $today->diffInDays($targetEnd, false);
                        $sisaHari = max(1, $diff + 1);
                    }
                    $kekurangan = max(0, ($targetLunas ?: $targetKontak) - ($targetLunas ? $realisasiLunas : $realisasiKontak));
                    $indikator = $targetLunas > 0 ? 'Maba Lunas' : 'Kontak Baru';

                    // Beri notifikasi bila tersisa <= 3 hari atau target Harian
                    if (($sisaHari <= 3 || $t->tipe_periode === 'Harian') && $kekurangan > 0) {
                        $alreadyNotifiedWarning = $u
                            ->notifications()
                            ->where('data->target_id', $t->id)
                            ->where('data->event_type', 'target_warning')
                            ->whereDate('created_at', Carbon::today())
                            ->exists();

                        if (!$alreadyNotifiedWarning) {
                            // Notifikasi ke staf / SPV yang belum tuntas
                            $u->notify(new \App\Notifications\TargetNotification(
                                title: "Evaluasi Target: Belum Tuntas (Defisit {$kekurangan} {$indikator})",
                                message: "Perhatian: Target {$t->tipe_periode} Anda tersisa {$sisaHari} hari dengan defisit {$kekurangan} {$indikator}. Segera lakukan koordinasi dan tindak lanjut untuk mengejar target.",
                                type: 'warning',
                                link: route($u->role === 'SPV' ? 'spv.performa.index' : 'performa.index'),
                                icon: '',
                                extraData: ['target_id' => $t->id, 'event_type' => 'target_warning']
                            ));

                            // Notifikasi visibilitas ke atasan (SPV / HM)
                            if ($u->role === 'SPV') {
                                $hms = User::where('role', 'HM')->where('status', 'Aktif')->get();
                                foreach ($hms as $hm) {
                                    $hm->notify(new \App\Notifications\TargetNotification(
                                        title: 'Evaluasi SPV: Target Belum Tuntas',
                                        message: "SPV {$u->name} memiliki defisit {$kekurangan} {$indikator} pada target {$t->tipe_periode} dengan sisa {$sisaHari} hari.",
                                        type: 'warning',
                                        link: route('admin.target.index'),
                                        icon: '',
                                        extraData: ['target_id' => $t->id, 'event_type' => 'target_warning_spv']
                                    ));
                                }
                            } else {
                                $spv = $u->spv;
                                if ($spv) {
                                    $spv->notify(new \App\Notifications\TargetNotification(
                                        title: "Evaluasi Tim: {$u->name} Belum Tuntas",
                                        message: "Anggota tim {$u->name} ({$u->role}) memiliki defisit {$kekurangan} {$indikator} pada target {$t->tipe_periode} (Sisa {$sisaHari} hari).",
                                        type: 'warning',
                                        link: route('spv.performa.index'),
                                        icon: '',
                                        extraData: ['target_id' => $t->id, 'event_type' => 'target_warning_member']
                                    ));
                                }
                            }

                            $notifiedCount['belum_tuntas']++;
                        }
                    }
                }
            }
        }

        return $notifiedCount;
    }
}
