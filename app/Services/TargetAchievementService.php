<?php

namespace App\Services;

use App\Models\FollowUp;
use App\Models\Kunjungan;
use App\Models\Prospek;
use App\Models\Target;
use App\Models\Transaksi;
use App\Models\User;
use App\Models\Wilayah;
use App\Models\TahunAkademik;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TargetAchievementService
{
    public const DEFAULT_TA = '2027/2028';

    public const PERIODE_HARIAN   = 'harian';
    public const PERIODE_MINGGUAN = 'mingguan';
    public const PERIODE_BULANAN  = 'bulanan';
    public const PERIODE_TAHUNAN  = 'tahunan';
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
        if ($periode === 'hari_ini' || $periode === 'daily') $periode = self::PERIODE_HARIAN;

        switch ($periode) {
            case self::PERIODE_HARIAN:
                $start = $now->copy()->startOfDay();
                $end   = $now->copy()->endOfDay();
                $sisaHari = 1;
                $totalHari = 1;
                $label = 'Hari Ini (' . $now->translatedFormat('d M Y') . ')';
                $isRealtime = false;
                break;

            case self::PERIODE_MINGGUAN:
                $start = $now->copy()->startOfWeek();
                $end   = $now->copy()->endOfWeek();
                $totalHari = 7;
                $sisaHari = max(1, (int)$now->diffInDays($end) + 1);
                $label = 'Minggu Ini (' . $start->format('d M') . ' - ' . $end->format('d M Y') . ')';
                $isRealtime = false;
                break;

            case self::PERIODE_TAHUNAN:
                $start = Carbon::create($now->year, 1, 1)->startOfDay();
                $end   = Carbon::create($now->year, 12, 31)->endOfDay();
                $totalHari = (int)$start->diffInDays($end) + 1;
                $sisaHari = max(1, (int)$now->diffInDays($end));
                $label = 'Tahunan (' . ($tahunAkademik ?: $this->getActiveTa()) . ')';
                $isRealtime = false;
                break;

            case self::PERIODE_REALTIME:
                $start = $now->copy()->startOfDay();
                $end   = $now->copy();
                $sisaHari = 1;
                $totalHari = 1;
                $label = 'Realtime (' . $now->format('d M Y, H:i:s') . ')';
                $isRealtime = true;
                break;

            case self::PERIODE_BULANAN:
            default:
                $periode = self::PERIODE_BULANAN;
                $start = $now->copy()->startOfMonth();
                $end   = $now->copy()->endOfMonth();
                $totalHari = $now->daysInMonth;
                $sisaHari = max(1, (int)$now->diffInDays($end) + 1);
                $label = 'Bulan Ini (' . $now->translatedFormat('F Y') . ')';
                $isRealtime = false;
                break;
        }

        return [
            'key'         => $periode,
            'label'       => $label,
            'start'       => $start,
            'end'         => $end,
            'sisa_hari'   => $sisaHari,
            'total_hari'  => $totalHari,
            'is_realtime' => $isRealtime,
            'timestamp'   => $now->format('Y-m-d H:i:s'),
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
            'CS'    => 'Sales', // CS evaluated similarly on handler level
            'SPV'   => 'SPV',
            'HM'    => 'HM',
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
        } else { // Global / Admin
            $data = $this->buildGlobalLevelData($periodInfo, $wilayahId, $ta);
            $kpiRows = $data['kpi_rows'];
            $hierarchyRows = $data['hierarchy_rows'];
            $wilayahOptions = $data['wilayah_options'];
            $selectedWilayah = $data['selected_wilayah'];
        }

        return [
            'level'            => $level,
            'user'             => $user,
            'role'             => $role,
            'periode'          => $periodInfo,
            'tahun_akademik'   => $ta,
            'kpi_rows'         => $kpiRows,
            'hierarchy_rows'   => $hierarchyRows,
            'wilayah_options'  => $wilayahOptions,
            'selected_wilayah' => $selectedWilayah,
            'period_options'   => [
                ['key' => self::PERIODE_HARIAN,   'label' => 'Hari Ini / Harian'],
                ['key' => self::PERIODE_MINGGUAN, 'label' => 'Mingguan'],
                ['key' => self::PERIODE_BULANAN,  'label' => 'Bulanan'],
                ['key' => self::PERIODE_TAHUNAN,  'label' => 'Tahunan'],
                ['key' => self::PERIODE_REALTIME, 'label' => 'Realtime ⚡'],
            ],
        ];
    }

    /**
     * Hitung metrik baris standar (Target | Pencapaian | Kekurangan | % Achievement | Sisa Hari | Target Harian Berjalan).
     */
    public function formatMetricRow(string $label, int $target, int $pencapaian, int $sisaHari, array $meta = []): array
    {
        $kekurangan = max(0, $target - $pencapaian);
        $percent = $target > 0
            ? round(($pencapaian / $target) * 100, 1)
            : ($pencapaian > 0 ? 100.0 : 0.0);

        // Target Harian Berjalan (Kekurangan / Sisa Hari)
        $targetHarianBerjalan = ($kekurangan > 0 && $sisaHari > 0)
            ? round($kekurangan / $sisaHari, 1)
            : 0;

        return array_merge([
            'label'                  => $label,
            'target'                 => $target,
            'pencapaian'             => $pencapaian,
            'kekurangan'             => $kekurangan,
            'achievement_pct'        => $percent,
            'sisa_hari'              => $sisaHari,
            'target_harian_berjalan' => $targetHarianBerjalan,
        ], $meta);
    }

    /**
     * BUILDER: Level Sales (Personal Performance)
     */
    private function buildSalesLevelData(User $sales, array $period, string $ta): array
    {
        $start = $period['start'];
        $end   = $period['end'];
        $sisa  = $period['sisa_hari'];

        // Ambil target yang dialokasikan oleh SPV untuk Sales ini
        $target = Target::where('sales_id', $sales->id)
            ->where('status', 'Aktif')
            ->where(function ($q) use ($ta) {
                $q->where('tahun_akademik', $ta)->orWhereNull('tahun_akademik');
            })
            ->latest()
            ->first();

        $tLunas     = $this->prorateTarget($target ? $target->target_lunas : 8, $period);
        $tFormulir  = $this->prorateTarget($target ? $target->target_formulir : 15, $period);
        $tKontak    = $this->prorateTarget($target ? $target->target_kontak : 30, $period);
        $tFollowup  = $this->prorateTarget($target ? $target->target_followup : 40, $period);
        $tKunjungan = $this->prorateTarget($target ? $target->target_kunjungan : 8, $period);

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

        $kpiRows = [
            $this->formatMetricRow('Maba Lunas (Closing)', $tLunas, $pLunas, $sisa, ['icon' => 'closing', 'badge' => 'Utama']),
            $this->formatMetricRow('Beli Formulir PMB', $tFormulir, $pFormulir, $sisa, ['icon' => 'formulir']),
            $this->formatMetricRow('Kontak / Database Baru', $tKontak, $pKontak, $sisa, ['icon' => 'kontak']),
            $this->formatMetricRow('Follow Up Prospek', $tFollowup, $pFollowup, $sisa, ['icon' => 'followup']),
            $this->formatMetricRow('Kunjungan Lapangan', $tKunjungan, $pKunjungan, $sisa, ['icon' => 'kunjungan']),
        ];

        $hierarchyRows = [
            $this->formatMetricRow($sales->name . ' (' . $sales->role . ')', $tLunas, $pLunas, $sisa, [
                'entity_id'   => $sales->id,
                'entity_type' => 'user',
                'role'        => $sales->role,
                'wilayah'     => $sales->wilayah?->nama ?? 'Wilayah Personal',
                'allocator'   => $target?->allocator?->name ?? 'Supervisor',
            ]),
        ];

        return [
            'kpi_rows'       => $kpiRows,
            'hierarchy_rows' => $hierarchyRows,
        ];
    }

    /**
     * BUILDER: Level SPV (Wilayah Binaan & Tim Sales/CS)
     */
    private function buildSpvLevelData(User $spv, array $period, ?int $wilayahId, string $ta): array
    {
        $start = $period['start'];
        $end   = $period['end'];
        $sisa  = $period['sisa_hari'];

        $teamMemberIds = $spv->teamMemberIds();
        $teamMembers   = User::whereIn('id', $teamMemberIds)->with('wilayah')->orderBy('role')->orderBy('name')->get();

        // Target Wilayah SPV yang Diberikan HM
        $targetHm = Target::where('sales_id', $spv->id)
            ->where('status', 'Aktif')
            ->where(function ($q) use ($ta) {
                $q->where('tahun_akademik', $ta)->orWhereNull('tahun_akademik');
            })
            ->latest()
            ->first();

        // Base target SPV dari HM (atau default)
        $tHmKontak   = $targetHm ? $targetHm->target_kontak : 120;
        $tHmFormulir = $targetHm ? $targetHm->target_formulir : 60;
        $tHmLunas    = $targetHm ? $targetHm->target_lunas : 35;

        $tLunas     = $this->prorateTarget($tHmLunas, $period);
        $tFormulir  = $this->prorateTarget($tHmFormulir, $period);
        $tKontak    = $this->prorateTarget($tHmKontak, $period);
        $tFollowup  = $this->prorateTarget(150, $period);
        $tKunjungan = $this->prorateTarget(30, $period);

        // Realisasi Tim (roll-up)
        $pLunas = Prospek::whereIn('sales_id', $teamMemberIds)
            ->where('status', 'LUNAS')
            ->whereBetween('updated_at', [$start, $end])
            ->count();

        $pFormulir = Prospek::where(function($q) use ($teamMemberIds) {
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

        $kpiRows = [
            $this->formatMetricRow('Maba Lunas (Closing Tim)', $tLunas, $pLunas, $sisa, ['icon' => 'closing', 'badge' => 'Utama HM']),
            $this->formatMetricRow('Pembelian Formulir Tim', $tFormulir, $pFormulir, $sisa, ['icon' => 'formulir']),
            $this->formatMetricRow('Kontak / Database Baru', $tKontak, $pKontak, $sisa, ['icon' => 'kontak']),
            $this->formatMetricRow('Follow Up Tim (Sales & CS)', $tFollowup, $pFollowup, $sisa, ['icon' => 'followup']),
            $this->formatMetricRow('Kunjungan Sekolah/Corporate', $tKunjungan, $pKunjungan, $sisa, ['icon' => 'kunjungan']),
        ];

        // Hierarchy Rows: Breakdown Per Anggota Tim
        $hierarchyRows = [];

        // Baris Utama: Target SPV Keseluruhan (Dari HM)
        $hierarchyRows[] = $this->formatMetricRow('🌟 TOTAL WILAYAH: ' . ($spv->wilayah?->nama ?? 'Wilayah SPV'), $tLunas, $pLunas, $sisa, [
            'entity_id'   => $spv->id,
            'entity_type' => 'spv_total',
            'role'        => 'SPV',
            'is_total'    => true,
            'wilayah'     => $spv->wilayah?->nama ?? 'Wilayah SPV',
            'allocated_by'=> $targetHm?->allocator?->name ?? 'Head of Marketing (HM)',
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

            $mTgtLunas = $this->prorateTarget($mTarget ? $mTarget->target_lunas : ($member->role === 'CS' ? 5 : 8), $period);

            if ($member->role === 'CS') {
                $mPencapaian = Prospek::where('cs_id', $member->id)
                    ->where('status', 'LUNAS')
                    ->whereBetween('updated_at', [$start, $end])
                    ->count();
            } else {
                $mPencapaian = Prospek::where('sales_id', $member->id)
                    ->where('status', 'LUNAS')
                    ->whereBetween('updated_at', [$start, $end])
                    ->count();
            }

            $hierarchyRows[] = $this->formatMetricRow($member->name, $mTgtLunas, $mPencapaian, $sisa, [
                'entity_id'   => $member->id,
                'entity_type' => 'user',
                'role'        => $member->role,
                'wilayah'     => $member->wilayah?->nama ?? ($spv->wilayah?->nama ?? '-'),
                'allocated_by'=> $spv->name . ' (SPV)',
            ]);
        }

        $wilayahOptions = Wilayah::where('id', $spv->wilayah_id)->get();
        if ($wilayahOptions->isEmpty()) {
            $wilayahOptions = Wilayah::limit(5)->get();
        }

        return [
            'kpi_rows'         => $kpiRows,
            'hierarchy_rows'   => $hierarchyRows,
            'wilayah_options'  => $wilayahOptions,
            'selected_wilayah' => $spv->wilayah,
        ];
    }

    /**
     * BUILDER: Level HM (Head of Marketing — Mengatur SPV & Seluruh Wilayah)
     */
    private function buildHmLevelData(User $hm, array $period, ?int $wilayahId, string $ta): array
    {
        $start = $period['start'];
        $end   = $period['end'];
        $sisa  = $period['sisa_hari'];

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

        // Total target yang diberikan HM ke seluruh SPV
        $targetSpvQuery = Target::whereIn('sales_id', $spvs->pluck('id'))
            ->where('status', 'Aktif')
            ->where(function ($q) use ($ta) {
                $q->where('tahun_akademik', $ta)->orWhereNull('tahun_akademik');
            });

        $totTgtLunas    = $this->prorateTarget($targetSpvQuery->sum('target_lunas') ?: 100, $period);
        $totTgtFormulir = $this->prorateTarget($targetSpvQuery->sum('target_formulir') ?: 200, $period);
        $totTgtKontak   = $this->prorateTarget($targetSpvQuery->sum('target_kontak') ?: 400, $period);

        $kpiRows = [
            $this->formatMetricRow('Maba Lunas (Closing Kampus)', $totTgtLunas, $pLunas, $sisa, ['icon' => 'closing', 'badge' => 'KPI Utama HM']),
            $this->formatMetricRow('Pembelian Formulir PMB', $totTgtFormulir, $pFormulir, $sisa, ['icon' => 'formulir']),
            $this->formatMetricRow('Database Prospek Masuk', $totTgtKontak, $pKontak, $sisa, ['icon' => 'kontak']),
            $this->formatMetricRow('Follow Up Total Tim', $this->prorateTarget(500, $period), $pFollowup, $sisa, ['icon' => 'followup']),
            $this->formatMetricRow('Kunjungan Tim Lapangan', $this->prorateTarget(80, $period), $pKunjungan, $sisa, ['icon' => 'kunjungan']),
        ];

        // Hierarchy Rows: Baris per SPV & Teritori
        $hierarchyRows = [];
        $hierarchyRows[] = $this->formatMetricRow('🏛️ TOTAL KAMPUS UCIC (Semua Wilayah)', $totTgtLunas, $pLunas, $sisa, [
            'entity_id'   => 0,
            'entity_type' => 'campus_total',
            'role'        => 'HM',
            'is_total'    => true,
            'wilayah'     => $selectedWilayah ? $selectedWilayah->nama : 'Seluruh Wilayah',
            'allocated_by'=> 'Head of Marketing',
        ]);

        foreach ($spvs as $spv) {
            $spvTarget = Target::where('sales_id', $spv->id)
                ->where('status', 'Aktif')
                ->where(function ($q) use ($ta) {
                    $q->where('tahun_akademik', $ta)->orWhereNull('tahun_akademik');
                })
                ->latest()
                ->first();

            $spvTgtLunas = $this->prorateTarget($spvTarget ? $spvTarget->target_lunas : 35, $period);

            // Realisasi SPV & timnya
            $teamIds = $spv->teamMemberIds();
            $teamIds[] = $spv->id;
            $spvReal = Prospek::whereIn('sales_id', $teamIds)
                ->where('status', 'LUNAS')
                ->whereBetween('updated_at', [$start, $end])
                ->count();

            $hierarchyRows[] = $this->formatMetricRow('Wilayah: ' . ($spv->wilayah?->nama ?? 'Wilayah ' . $spv->name) . ' (' . $spv->name . ')', $spvTgtLunas, $spvReal, $sisa, [
                'entity_id'   => $spv->id,
                'entity_type' => 'spv',
                'role'        => 'SPV',
                'wilayah'     => $spv->wilayah?->nama ?? '-',
                'allocated_by'=> $spvTarget?->allocator?->name ?? 'HM',
                'spv_name'    => $spv->name,
                'team_count'  => count($spv->teamMemberIds()),
            ]);
        }

        return [
            'kpi_rows'         => $kpiRows,
            'hierarchy_rows'   => $hierarchyRows,
            'wilayah_options'  => $wilayahOptions,
            'selected_wilayah' => $selectedWilayah,
        ];
    }

    /**
     * BUILDER: Level Global (Admin / Pimpinan Eksekutif)
     */
    private function buildGlobalLevelData(array $period, ?int $wilayahId, string $ta): array
    {
        $start = $period['start'];
        $end   = $period['end'];
        $sisa  = $period['sisa_hari'];

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

        $totTgtLunas    = $this->prorateTarget(150, $period);
        $totTgtFormulir = $this->prorateTarget(300, $period);
        $totTgtKontak   = $this->prorateTarget(600, $period);

        $kpiRows = [
            $this->formatMetricRow('Maba Lunas (Global Closing)', $totTgtLunas, $pLunas, $sisa, ['icon' => 'closing', 'badge' => 'Global']),
            $this->formatMetricRow('Pembelian Formulir PMB', $totTgtFormulir, $pFormulir, $sisa, ['icon' => 'formulir']),
            $this->formatMetricRow('Database Prospek Masuk', $totTgtKontak, $pKontak, $sisa, ['icon' => 'kontak']),
            $this->formatMetricRow('Total Follow Up CRM', $this->prorateTarget(800, $period), $pFollowup, $sisa, ['icon' => 'followup']),
            $this->formatMetricRow('Total Kunjungan Lapangan', $this->prorateTarget(120, $period), $pKunjungan, $sisa, ['icon' => 'kunjungan']),
        ];

        // Breakdown per Teritori / Wilayah
        $hierarchyRows = [];
        $hierarchyRows[] = $this->formatMetricRow('🌐 TOTAL GLOBAL CRM UCIC', $totTgtLunas, $pLunas, $sisa, [
            'entity_id'   => 0,
            'entity_type' => 'global_total',
            'role'        => 'Global',
            'is_total'    => true,
            'wilayah'     => $selectedWilayah ? $selectedWilayah->nama : 'Seluruh Wilayah',
            'allocated_by'=> 'Sistem & Rektorat',
        ]);

        $wilayahsToInspect = $wilayahId ? Wilayah::where('id', $wilayahId)->get() : Wilayah::orderBy('nama')->get();
        foreach ($wilayahsToInspect as $w) {
            $wLunas = Prospek::where('wilayah_id', $w->id)->where('status', 'LUNAS')->whereBetween('updated_at', [$start, $end])->count();
            $wTgt   = $this->prorateTarget(30, $period);

            $spvInWilayah = User::where('role', 'SPV')->where('wilayah_id', $w->id)->first();

            $hierarchyRows[] = $this->formatMetricRow('Teritori: ' . $w->nama, $wTgt, $wLunas, $sisa, [
                'entity_id'   => $w->id,
                'entity_type' => 'wilayah',
                'role'        => 'Wilayah',
                'wilayah'     => $w->nama,
                'spv_name'    => $spvInWilayah?->name ?? 'Belum ada SPV',
                'allocated_by'=> 'Head Marketing',
            ]);
        }

        return [
            'kpi_rows'         => $kpiRows,
            'hierarchy_rows'   => $hierarchyRows,
            'wilayah_options'  => $wilayahOptions,
            'selected_wilayah' => $selectedWilayah,
        ];
    }

    /**
     * Hitung proration target sesuai periode (Harian = Monthly/working_days, Mingguan = Monthly/4, dsb).
     */
    private function prorateTarget(int $monthlyBase, array $period): int
    {
        if ($monthlyBase <= 0) return 0;

        return match ($period['key']) {
            self::PERIODE_HARIAN   => max(1, (int)round($monthlyBase / max(1, $period['total_hari'] ?: 25))),
            self::PERIODE_MINGGUAN => max(1, (int)round($monthlyBase / 4)),
            self::PERIODE_TAHUNAN  => (int)($monthlyBase * 12),
            self::PERIODE_REALTIME => max(1, (int)round($monthlyBase / max(1, $period['total_hari'] ?: 25))),
            default                => $monthlyBase,
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
                    $realisasiLunas = Prospek::whereIn('sales_id', $teamIds)->where('status', 'LUNAS')
                        ->whereBetween('updated_at', [$t->tanggal_mulai . ' 00:00:00', $t->tanggal_selesai . ' 23:59:59'])->count();
                    $realisasiKontak = Prospek::where(function($q) use ($teamIds) {
                        $q->whereIn('sales_id', $teamIds)->orWhereIn('cs_id', $teamIds);
                    })->whereBetween('created_at', [$t->tanggal_mulai . ' 00:00:00', $t->tanggal_selesai . ' 23:59:59'])->count();
                } else {
                    $realisasiLunas = Prospek::where('sales_id', $u->id)->where('status', 'LUNAS')
                        ->whereBetween('updated_at', [$t->tanggal_mulai . ' 00:00:00', $t->tanggal_selesai . ' 23:59:59'])->count();
                    $realisasiKontak = Prospek::where(function($q) use ($u) {
                        if ($u->role === 'CS') {
                            $q->where('cs_id', $u->id);
                        } else {
                            $q->where('sales_id', $u->id);
                        }
                    })->whereBetween('created_at', [$t->tanggal_mulai . ' 00:00:00', $t->tanggal_selesai . ' 23:59:59'])->count();
                }

                $targetLunas = (int)($t->target_lunas ?? 0);
                $targetKontak = (int)($t->target_kontak ?? 0);
                $isTuntas = false;

                if ($targetLunas > 0 && $realisasiLunas >= $targetLunas) {
                    $isTuntas = true;
                } elseif ($targetLunas <= 0 && $targetKontak > 0 && $realisasiKontak >= $targetKontak) {
                    $isTuntas = true;
                }

                // 1. TARGET SUDAH TUNTAS (100% Achievement)
                if ($isTuntas) {
                    $alreadyNotifiedTuntas = $u->notifications()
                        ->where('data->target_id', $t->id)
                        ->where('data->event_type', 'target_tuntas')
                        ->exists();

                    if (!$alreadyNotifiedTuntas) {
                        $capaianText = $targetLunas > 0 ? "{$realisasiLunas}/{$targetLunas} Maba Lunas" : "{$realisasiKontak}/{$targetKontak} Kontak";

                        // Notifikasi ke staf / SPV penerima target
                        $u->notify(new \App\Notifications\TargetNotification(
                            title: "🎉 Selamat! Target {$t->tipe_periode} Tuntas 100%",
                            message: "Luar biasa! Target {$t->tipe_periode} Anda ({$capaianText}) telah tuntas tercapai 100%. Pertahankan kinerja luar biasa ini!",
                            type: 'success',
                            link: route($u->role === 'SPV' ? 'spv.performa.index' : 'performa.index'),
                            icon: '🎉',
                            extraData: ['target_id' => $t->id, 'event_type' => 'target_tuntas']
                        ));

                        // Notifikasi ke Atasan Berjenjang (SPV / HM)
                        if ($u->role === 'SPV') {
                            $hms = User::where('role', 'HM')->where('status', 'Aktif')->get();
                            foreach ($hms as $hm) {
                                $hm->notify(new \App\Notifications\TargetNotification(
                                    title: "🏆 Target Wilayah SPV {$u->name} Tuntas 100%!",
                                    message: "SPV {$u->name} dan tim telah menuntaskan 100% target {$t->tipe_periode} ({$capaianText}).",
                                    type: 'success',
                                    link: route('admin.target.index'),
                                    icon: '🏆',
                                    extraData: ['target_id' => $t->id, 'event_type' => 'target_tuntas_spv']
                                ));
                            }
                        } else {
                            $spv = $u->spv;
                            if ($spv) {
                                $spv->notify(new \App\Notifications\TargetNotification(
                                    title: "🏆 Anggota Tim {$u->name} Menuntaskan Target!",
                                    message: "{$u->name} ({$u->role}) telah menuntaskan target {$t->tipe_periode} 100% ({$capaianText}).",
                                    type: 'success',
                                    link: route('spv.performa.index'),
                                    icon: '🏆',
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
                        $diff = (int)$today->diffInDays($targetEnd, false);
                        $sisaHari = max(1, $diff + 1);
                    }
                    $kekurangan = max(0, ($targetLunas ?: $targetKontak) - ($targetLunas ? $realisasiLunas : $realisasiKontak));
                    $indikator = $targetLunas > 0 ? 'Maba Lunas' : 'Kontak Baru';

                    // Beri notifikasi bila tersisa <= 3 hari atau target Harian
                    if (($sisaHari <= 3 || $t->tipe_periode === 'Harian') && $kekurangan > 0) {
                        $alreadyNotifiedWarning = $u->notifications()
                            ->where('data->target_id', $t->id)
                            ->where('data->event_type', 'target_warning')
                            ->whereDate('created_at', Carbon::today())
                            ->exists();

                        if (!$alreadyNotifiedWarning) {
                            $targetHarianBerjalan = (int)ceil($kekurangan / max(1, $sisaHari));

                            // Notifikasi ke staf / SPV yang belum tuntas
                            $u->notify(new \App\Notifications\TargetNotification(
                                title: "⚠️ Evaluasi Target: Belum Tuntas (Defisit {$kekurangan} {$indikator})",
                                message: "Perhatian: Target {$t->tipe_periode} Anda tersisa {$sisaHari} hari dengan kekurangan {$kekurangan} {$indikator}. Diperlukan {$targetHarianBerjalan} {$indikator} per hari untuk menutup target.",
                                type: 'warning',
                                link: route($u->role === 'SPV' ? 'spv.performa.index' : 'performa.index'),
                                icon: '⚠️',
                                extraData: ['target_id' => $t->id, 'event_type' => 'target_warning']
                            ));

                            // Notifikasi visibilitas ke atasan (SPV / HM)
                            if ($u->role === 'SPV') {
                                $hms = User::where('role', 'HM')->where('status', 'Aktif')->get();
                                foreach ($hms as $hm) {
                                    $hm->notify(new \App\Notifications\TargetNotification(
                                        title: "⚠️ Evaluasi SPV: Target Belum Tuntas",
                                        message: "SPV {$u->name} memiliki defisit {$kekurangan} {$indikator} pada target {$t->tipe_periode} dengan sisa {$sisaHari} hari.",
                                        type: 'warning',
                                        link: route('admin.target.index'),
                                        icon: '📉',
                                        extraData: ['target_id' => $t->id, 'event_type' => 'target_warning_spv']
                                    ));
                                }
                            } else {
                                $spv = $u->spv;
                                if ($spv) {
                                    $spv->notify(new \App\Notifications\TargetNotification(
                                        title: "⚠️ Evaluasi Tim: {$u->name} Belum Tuntas",
                                        message: "Anggota tim {$u->name} ({$u->role}) memiliki defisit {$kekurangan} {$indikator} pada target {$t->tipe_periode} (Sisa {$sisaHari} hari).",
                                        type: 'warning',
                                        link: route('spv.performa.index'),
                                        icon: '📉',
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
