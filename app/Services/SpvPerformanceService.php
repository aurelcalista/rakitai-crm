<?php

namespace App\Services;

use App\Models\FollowUp;
use App\Models\Kunjungan;
use App\Models\Prospek;
use App\Models\Target;
use App\Models\Transaksi;
use App\Models\User;
use Carbon\Carbon;

/**
 * SPV Performance Service.
 *
 * Historical override: all public methods accept `?string $tahunAkademik` for
 * viewing a past academic year. When null, the ACTIVE academic year is used
 * via AkademikService::getAktifOrFail() — never a hardcoded fallback.
 *
 * If no TA is active, a RuntimeException is raised (configuration problem).
 */
class SpvPerformanceService
{
    /**
     * Resolve the Tahun Akademik name to use.
     * If an explicit override is given, use it (historical view).
     * Otherwise, require the active TA from AkademikService.
     *
     * @throws \RuntimeException if no active TA and no override given
     */
    private function resolveTa(?string $override): string
    {
        if ($override !== null) {
            return $override;
        }

        return AkademikService::getAktifOrFail()->nama;
    }

    /**
     * Resolve the academic_year_id for the given TA name.
     * Returns null for historical TAs that have no FK record.
     */
    private function resolveAcademicYearId(string $taNama): ?int
    {
        return \App\Models\TahunAkademik::where('nama', $taNama)->value('id');
    }

    /**
     * Dapatkan Target yang diberikan HM ke SPV.
     * Queries by academic_year_id (FK) with tahun_akademik string as fallback.
     */
    public function getTargetHmForSpv(User $spv, ?string $tahunAkademik = null): array
    {
        $ta = $this->resolveTa($tahunAkademik);
        $taId = $this->resolveAcademicYearId($ta);

        // Find active target allocated for this SPV in this academic year
        $target = Target::where('sales_id', $spv->id)
            ->where('status', 'Aktif')
            ->where(function ($q) use ($ta, $taId) {
                if ($taId) {
                    $q->where('academic_year_id', $taId);
                } else {
                    // Legacy fallback for historical TAs without FK
                    $q->where('tahun_akademik', $ta)->orWhereNull('tahun_akademik');
                }
            })
            ->latest()
            ->first();

        $targetKontak   = $target ? (int)$target->target_kontak : 120;
        $targetFormulir = $target ? (int)$target->target_formulir : 60;
        $targetLunas    = $target ? (int)$target->target_lunas : 35;
        $tipePeriode    = $target ? $target->tipe_periode : 'Bulanan';
        $tanggalMulai   = $target ? $target->tanggal_mulai : now()->startOfMonth();
        $tanggalSelesai = $target ? $target->tanggal_selesai : now()->endOfMonth();

        $teamMemberIds = $spv->teamMemberIds();
        $allocatedTargets = Target::whereIn('sales_id', $teamMemberIds)
            ->where('allocated_by', $spv->id)
            ->where('status', 'Aktif')
            ->where(function ($q) use ($ta, $taId) {
                if ($taId) {
                    $q->where('academic_year_id', $taId);
                } else {
                    $q->where('tahun_akademik', $ta)->orWhereNull('tahun_akademik');
                }
            })
            ->get();

        $allocatedKontak   = $allocatedTargets->sum('target_kontak');
        $allocatedFormulir = $allocatedTargets->sum('target_formulir');
        $allocatedLunas    = $allocatedTargets->sum('target_lunas');

        $sisaKontak   = max(0, $targetKontak - $allocatedKontak);
        $sisaFormulir = max(0, $targetFormulir - $allocatedFormulir);
        $sisaLunas    = max(0, $targetLunas - $allocatedLunas);

        $realisasi = $this->getTeamRealization($spv, $tanggalMulai, $tanggalSelesai, $ta);

        return [
            'has_hm_target'     => $target !== null,
            'target_id'         => $target?->id,
            'tahun_akademik'    => $ta,
            'tipe_periode'      => $tipePeriode,
            'tanggal_mulai'     => $tanggalMulai,
            'tanggal_selesai'   => $tanggalSelesai,
            'target_kontak'     => $targetKontak,
            'target_formulir'   => $targetFormulir,
            'target_lunas'      => $targetLunas,
            'allocated_kontak'  => $allocatedKontak,
            'allocated_formulir'=> $allocatedFormulir,
            'allocated_lunas'   => $allocatedLunas,
            'sisa_kontak'       => $sisaKontak,
            'sisa_formulir'     => $sisaFormulir,
            'sisa_lunas'        => $sisaLunas,
            'realisasi_kontak'  => $realisasi['kontak'],
            'realisasi_formulir'=> $realisasi['formulir'],
            'realisasi_lunas'   => $realisasi['lunas'],
            'achieve_pct'       => $targetLunas > 0 ? round(($realisasi['lunas'] / $targetLunas) * 100, 1) : 0,
            'wilayah'           => $spv->wilayah ? $spv->wilayah->nama : 'Wilayah SPV',
            'sisa_lunas'        => $sisaLunas,
            'realisasi_lunas'   => $realisasi['lunas'],
        ];
    }

    /**
     * Hitung realisasi seluruh tim SPV dalam rentang tanggal tertentu.
     * Scoped by academic_year_id (FK) for current/new TAs.
     * Falls back to legacy tahun_akademik string for historical TAs without FK.
     */
    public function getTeamRealization(User $spv, Carbon $from, Carbon $to, ?string $tahunAkademik = null): array
    {
        $ta = $this->resolveTa($tahunAkademik);
        $taId = $this->resolveAcademicYearId($ta);
        $teamMemberIds = $spv->teamMemberIds();

        $prospekQuery = Prospek::where(function ($q) use ($teamMemberIds, $spv) {
            $q->whereIn('sales_id', $teamMemberIds)
              ->orWhereIn('owner_id', $teamMemberIds)
              ->orWhereIn('cs_id', $teamMemberIds);
            if ($spv->wilayah_id) {
                $q->orWhere('wilayah_id', $spv->wilayah_id);
            }
        });

        // Scope by academic year using FK when available
        if ($taId) {
            $prospekQuery->where('academic_year_id', $taId);
        } elseif ($ta) {
            // Legacy string fallback for historical data
            $prospekQuery->where(function ($q) use ($ta) {
                $q->where('tahun_akademik', $ta)->orWhereNull('tahun_akademik');
            });
        }

        $realisasiKontak = (clone $prospekQuery)
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->count();

        $prospekIds = (clone $prospekQuery)->pluck('id')->toArray();

        $transaksiFormulirCount = Transaksi::whereIn('prospek_id', $prospekIds)
            ->where('jenis', 'Beli Formulir')
            ->whereBetween('tanggal', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->distinct('prospek_id')
            ->count('prospek_id');

        $statusFormulirCount = (clone $prospekQuery)
            ->whereIn('status', ['FORMULIR', 'BERKAS', 'LUNAS'])
            ->whereBetween('updated_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->count();

        $realisasiFormulir = max($transaksiFormulirCount, $statusFormulirCount);

        $transaksiLunasCount = Transaksi::whereIn('prospek_id', $prospekIds)
            ->where('jenis', 'Pembayaran Termin 1')
            ->whereBetween('tanggal', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->distinct('prospek_id')
            ->count('prospek_id');

        $statusLunasCount = (clone $prospekQuery)
            ->where('status', 'LUNAS')
            ->whereBetween('updated_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->count();

        $realisasiLunas = max($transaksiLunasCount, $statusLunasCount);

        return [
            'kontak'   => $realisasiKontak,
            'formulir' => $realisasiFormulir,
            'lunas'    => $realisasiLunas,
        ];
    }

    /**
     * TABEL TARGET TEAM PER WEEK (Whiteboard Image 1).
     */
    public function getTargetTeamPerWeek(User $spv, ?Carbon $referenceDate = null, ?string $tahunAkademik = null): array
    {
        $ta = $this->resolveTa($tahunAkademik);
        $ref = $referenceDate ? $referenceDate->copy() : now();
        $startOfWeek = $ref->copy()->startOfWeek(Carbon::MONDAY);
        $endOfWeek   = $ref->copy()->endOfWeek(Carbon::SUNDAY);

        $targetHm = $this->getTargetHmForSpv($spv, $ta);
        $dailyTargetKontak = (int) round($targetHm['target_kontak'] / 25);
        if ($dailyTargetKontak < 5) $dailyTargetKontak = 6;

        $daysName = [
            1 => 'SENIN', 2 => 'SELASA', 3 => 'RABU', 4 => 'KAMIS',
            5 => "JUM'AT", 6 => 'SABTU', 7 => 'MINGGU',
        ];

        $rows = [];
        $kumulatifLunas = 0;
        $totalTargetKontak = $totalRealisasiKontak = $totalFormulir = $totalLunas = 0;

        for ($i = 1; $i <= 7; $i++) {
            $currentDate = $startOfWeek->copy()->addDays($i - 1);
            $targetKontakHariIni = in_array($i, [1, 2, 3, 4, 5]) ? $dailyTargetKontak
                : ($i === 6 ? (int)round($dailyTargetKontak * 0.5) : 0);

            $realisasiHari = $this->getTeamRealization($spv, $currentDate, $currentDate, $ta);
            $kumulatifLunas += $realisasiHari['lunas'];

            $rows[] = [
                'hari'                => $daysName[$i],
                'tanggal'             => $currentDate->format('d M Y'),
                'is_today'            => $currentDate->isToday(),
                'target_kontak'       => $targetKontakHariIni,
                'realisasi_kontak'    => $realisasiHari['kontak'],
                'pembayaran_formulir' => $realisasiHari['formulir'],
                'maba_lunas'          => $realisasiHari['lunas'],
                'kumulatif_lunas'     => $kumulatifLunas,
            ];

            $totalTargetKontak     += $targetKontakHariIni;
            $totalRealisasiKontak  += $realisasiHari['kontak'];
            $totalFormulir         += $realisasiHari['formulir'];
            $totalLunas            += $realisasiHari['lunas'];
        }

        return [
            'periode_label' => $startOfWeek->translatedFormat('d M Y') . ' - ' . $endOfWeek->translatedFormat('d M Y'),
            'rows'          => $rows,
            'total'         => [
                'hari'                => 'TOTAL',
                'tanggal'             => '-',
                'is_today'            => false,
                'target_kontak'       => $totalTargetKontak,
                'realisasi_kontak'    => $totalRealisasiKontak,
                'pembayaran_formulir' => $totalFormulir,
                'maba_lunas'          => $totalLunas,
                'kumulatif_lunas'     => $kumulatifLunas,
            ],
        ];
    }

    /**
     * TABEL PERFORMA HARIAN / PERORANG (Whiteboard Image 2).
     */
    public function getPerformaHarianPerorang(User $spv, ?Carbon $date = null, ?string $tahunAkademik = null): array
    {
        $ta = $this->resolveTa($tahunAkademik);
        $taId = $this->resolveAcademicYearId($ta);
        $today = $date ? $date->copy() : now();
        $yesterday = $today->copy()->subDay();

        $teamMemberIds = $spv->teamMemberIds();
        $salesUsers = User::whereIn('id', $teamMemberIds)->where('role', 'Sales')->get();
        if ($salesUsers->isEmpty()) {
            $salesUsers = User::where('role', 'Sales')->get();
        }

        $rows = [];

        foreach ($salesUsers as $sales) {
            $targetQuery = Target::where('sales_id', $sales->id)->where('status', 'Aktif');
            if ($taId) {
                $targetQuery->where('academic_year_id', $taId);
            } else {
                $targetQuery->where(function ($q) use ($ta) {
                    $q->where('tahun_akademik', $ta)->orWhereNull('tahun_akademik');
                });
            }
            $salesTarget = $targetQuery->latest()->first();

            $targetNormalKontak   = $salesTarget ? (int) max(1, round($salesTarget->target_kontak / 25)) : 5;
            $targetNormalFormulir = $salesTarget ? (int) max(1, round($salesTarget->target_formulir / 25)) : 2;
            $targetNormalLunas    = $salesTarget ? (int) max(1, round($salesTarget->target_lunas / 25)) : 1;

            $capaianKemarinKontak   = Prospek::where('sales_id', $sales->id)
                ->whereDate('created_at', $yesterday->toDateString())->count();

            $capaianKemarinFormulir = Transaksi::whereHas('prospek', fn($q) => $q->where('sales_id', $sales->id))
                ->where('jenis', 'Beli Formulir')
                ->whereDate('tanggal', $yesterday->toDateString())->count();

            $capaianKemarinLunas = Transaksi::whereHas('prospek', fn($q) => $q->where('sales_id', $sales->id))
                ->where('jenis', 'Pembayaran Termin 1')
                ->whereDate('tanggal', $yesterday->toDateString())->count();

            $utangKontak   = max(0, $targetNormalKontak - $capaianKemarinKontak);
            $utangFormulir = max(0, $targetNormalFormulir - $capaianKemarinFormulir);
            $utangLunas    = max(0, $targetNormalLunas - $capaianKemarinLunas);

            $sasaranHariIniKontak   = $targetNormalKontak + $utangKontak;
            $sasaranHariIniFormulir = $targetNormalFormulir + $utangFormulir;
            $sasaranHariIniLunas    = $targetNormalLunas + $utangLunas;

            $kunjunganHariIni = Kunjungan::where('sales_id', $sales->id)
                ->whereDate('tanggal', $today->toDateString())->first();

            $lokasiPenugasan = 'Online / Follow Up UCIC';
            if ($kunjunganHariIni) {
                $lokasiPenugasan = $kunjunganHariIni->lokasi_penugasan
                    ?: ($kunjunganHariIni->nama_institusi ?: 'Kunjungan ' . $kunjunganHariIni->jenis);
            }

            $rows[] = [
                'sales_id'         => $sales->id,
                'nama_sales'       => $sales->name,
                'avatar'           => strtoupper(substr($sales->name, 0, 2)),
                'target_normal'    => [
                    'kontak'   => $targetNormalKontak,
                    'formulir' => $targetNormalFormulir,
                    'lunas'    => $targetNormalLunas,
                    'summary'  => "{$targetNormalKontak} Kontak | {$targetNormalFormulir} Form | {$targetNormalLunas} Lunas",
                ],
                'capaian_kemarin'  => [
                    'kontak'   => $capaianKemarinKontak,
                    'formulir' => $capaianKemarinFormulir,
                    'lunas'    => $capaianKemarinLunas,
                ],
                'utang_angka'      => [
                    'kontak'   => $utangKontak,
                    'formulir' => $utangFormulir,
                    'lunas'    => $utangLunas,
                    'has_utang'=> ($utangKontak + $utangFormulir + $utangLunas) > 0,
                    'summary'  => ($utangKontak + $utangFormulir + $utangLunas > 0)
                        ? "{$utangKontak} Ktk, {$utangFormulir} Frm, {$utangLunas} Lns" : '0 (Lunas)',
                ],
                'sasaran_hari_ini' => [
                    'kontak'   => $sasaranHariIniKontak,
                    'formulir' => $sasaranHariIniFormulir,
                    'lunas'    => $sasaranHariIniLunas,
                    'summary'  => "{$sasaranHariIniKontak} Ktk | {$sasaranHariIniFormulir} Frm | {$sasaranHariIniLunas} Lns",
                ],
                'lokasi_penugasan' => $lokasiPenugasan,
            ];
        }

        return [
            'tanggal_hari_ini' => $today->translatedFormat('l, d F Y'),
            'tanggal_kemarin'  => $yesterday->translatedFormat('d F Y'),
            'rows'             => $rows,
        ];
    }

    /**
     * DATA AKUMULASI TIM SPV.
     * Scoped by academic_year_id when available.
     */
    public function getDataAkumulasi(User $spv, ?string $tahunAkademik = null): array
    {
        $ta = $this->resolveTa($tahunAkademik);
        $taId = $this->resolveAcademicYearId($ta);
        $targetHm = $this->getTargetHmForSpv($spv, $ta);
        $teamMemberIds = $spv->teamMemberIds();

        $salesUsers = User::whereIn('id', $teamMemberIds)->where('role', 'Sales')->get();
        if ($salesUsers->isEmpty()) {
            $salesUsers = User::where('role', 'Sales')->get();
        }

        $salesBreakdown = [];
        $totalClosingMaba = $totalProspek = $totalFormulir = 0;

        foreach ($salesUsers as $s) {
            $prospekQuery = Prospek::where('sales_id', $s->id);
            if ($taId) {
                $prospekQuery->where('academic_year_id', $taId);
            } else {
                $prospekQuery->where(function ($q) use ($ta) {
                    $q->where('tahun_akademik', $ta)->orWhereNull('tahun_akademik');
                });
            }
            $prospekCount = $prospekQuery->count();

            $formCount = Transaksi::whereHas('prospek', function ($q) use ($s, $ta, $taId) {
                    $q->where('sales_id', $s->id);
                    if ($taId) {
                        $q->where('academic_year_id', $taId);
                    } elseif ($ta) {
                        $q->where(function ($sub) use ($ta) {
                            $sub->where('tahun_akademik', $ta)->orWhereNull('tahun_akademik');
                        });
                    }
                })
                ->where('jenis', 'Beli Formulir')
                ->count();

            $lunasCount = (clone $prospekQuery)->where('status', 'LUNAS')->count();

            $salesTarget = Target::where('sales_id', $s->id)
                ->where('status', 'Aktif')->latest()->first();
            $targetPersonal = $salesTarget ? (int)$salesTarget->target_lunas : 10;
            $achievement    = $targetPersonal > 0 ? round(($lunasCount / $targetPersonal) * 100) : 0;

            $salesBreakdown[] = [
                'user'            => $s,
                'name'            => $s->name,
                'avatar'          => strtoupper(substr($s->name, 0, 2)),
                'prospek'         => $prospekCount,
                'formulir'        => $formCount,
                'lunas'           => $lunasCount,
                'target_lunas'    => $targetPersonal,
                'achievement_pct' => $achievement,
                'status'          => $achievement >= 100 ? 'Target Achieved' : ($achievement >= 50 ? 'On Track' : 'Need Push'),
            ];

            $totalProspek       += $prospekCount;
            $totalFormulir      += $formCount;
            $totalClosingMaba   += $lunasCount;
        }

        return [
            'tahun_akademik'     => $ta,
            'target_tim_hm'      => $targetHm,
            'total_prospek'      => $totalProspek,
            'total_formulir'     => $totalFormulir,
            'total_closing_maba' => $totalClosingMaba,
            'sales_breakdown'    => $salesBreakdown,
        ];
    }
}
