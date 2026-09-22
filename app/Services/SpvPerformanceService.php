<?php

namespace App\Services;

use App\Models\FollowUp;
use App\Models\Kunjungan;
use App\Models\Prospek;
use App\Models\Target;
use App\Models\Transaksi;
use App\Models\User;
use Carbon\Carbon;

class SpvPerformanceService
{
    public const DEFAULT_TA = '2027/2028';

    /**
     * Dapatkan Target yang diberikan HM ke SPV (atau fallback default wilayah).
     */
    public function getTargetHmForSpv(User $spv, ?string $tahunAkademik = null): array
    {
        $ta = $tahunAkademik ?: self::DEFAULT_TA;

        // Cari target aktif yang dialokasikan khusus untuk SPV ini
        $target = Target::where('sales_id', $spv->id)
            ->where('status', 'Aktif')
            ->where(function ($q) use ($ta) {
                $q->where('tahun_akademik', $ta)->orWhereNull('tahun_akademik');
            })
            ->latest()
            ->first();

        // Jika belum ada target spesifik untuk SPV dari HM, gunakan target wilayah SPV atau default operasional
        $targetKontak   = $target ? (int)$target->target_kontak : 120;
        $targetFormulir = $target ? (int)$target->target_formulir : 60;
        $targetLunas    = $target ? (int)$target->target_lunas : 35;
        $tipePeriode    = $target ? $target->tipe_periode : 'Bulanan';
        $tanggalMulai   = $target ? $target->tanggal_mulai : now()->startOfMonth();
        $tanggalSelesai = $target ? $target->tanggal_selesai : now()->endOfMonth();

        // Hitung target yang sudah dialokasikan SPV ke Sales & CS di timnya
        $teamMemberIds = $spv->teamMemberIds();
        $allocatedTargets = Target::whereIn('sales_id', $teamMemberIds)
            ->where('allocated_by', $spv->id)
            ->where('status', 'Aktif')
            ->where(function ($q) use ($ta) {
                $q->where('tahun_akademik', $ta)->orWhereNull('tahun_akademik');
            })
            ->get();

        $allocatedKontak   = $allocatedTargets->sum('target_kontak');
        $allocatedFormulir = $allocatedTargets->sum('target_formulir');
        $allocatedLunas    = $allocatedTargets->sum('target_lunas');

        // Sisa target yang belum dialokasikan ke anggota tim
        $sisaKontak   = max(0, $targetKontak - $allocatedKontak);
        $sisaFormulir = max(0, $targetFormulir - $allocatedFormulir);
        $sisaLunas    = max(0, $targetLunas - $allocatedLunas);

        // Realisasi Tim (roll-up)
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
        ];
    }

    /**
     * Hitung realisasi seluruh tim SPV dalam rentang tanggal tertentu.
     */
    public function getTeamRealization(User $spv, Carbon $from, Carbon $to, ?string $tahunAkademik = null): array
    {
        $teamMemberIds = $spv->teamMemberIds();
        $ta = $tahunAkademik ?: self::DEFAULT_TA;

        $prospekQuery = Prospek::where(function ($q) use ($teamMemberIds, $spv) {
            $q->whereIn('sales_id', $teamMemberIds)
              ->orWhereIn('owner_id', $teamMemberIds)
              ->orWhereIn('cs_id', $teamMemberIds);
            if ($spv->wilayah_id) {
                $q->orWhere('wilayah_id', $spv->wilayah_id);
            }
        });

        if ($ta) {
            $prospekQuery->where(function ($q) use ($ta) {
                $q->where('tahun_akademik', $ta)->orWhereNull('tahun_akademik');
            });
        }

        // Realisasi Kontak = prospek baru dibuat dalam periode
        $realisasiKontak = (clone $prospekQuery)
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->count();

        // Realisasi Formulir = prospek yang berstatus FORMULIR, BERKAS, LUNAS atau ada transaksi Beli Formulir
        $prospekIds = (clone $prospekQuery)->pluck('id')->toArray();

        $transaksiFormulirCount = Transaksi::whereIn('prospek_id', $prospekIds)
            ->where('jenis', 'Beli Formulir')
            ->whereBetween('tanggal', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->distinct('prospek_id')
            ->count('prospek_id');

        $statusFormulirCount = (clone $prospekQuery)
            ->whereIn('status', ['FORMULIR', 'BERKAS', 'LUNAS', 'Beli Formulir', 'Pembayaran Termin 1', 'Closing'])
            ->whereBetween('updated_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->count();

        $realisasiFormulir = max($transaksiFormulirCount, $statusFormulirCount);

        // Realisasi Maba Lunas = Transaksi Termin 1 + Formulir ATAU status LUNAS
        $transaksiLunasCount = Transaksi::whereIn('prospek_id', $prospekIds)
            ->where('jenis', 'Pembayaran Termin 1')
            ->whereBetween('tanggal', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->distinct('prospek_id')
            ->count('prospek_id');

        $statusLunasCount = (clone $prospekQuery)
            ->whereIn('status', ['LUNAS', 'Closing', '07 LUNAS'])
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
     * TABEL TARGET TEAM PER WEEK (Whiteboard Image 1)
     * Kolom: HARI (SENIN - MINGGU + TOTAL), TARGET KONTAK, REALISASI KONTAK, PEMBAYARAN FORMULIR, MABA LUNAS, KUMULATIF LUNAS.
     */
    public function getTargetTeamPerWeek(User $spv, ?Carbon $referenceDate = null, ?string $tahunAkademik = null): array
    {
        $ref = $referenceDate ? $referenceDate->copy() : now();
        $startOfWeek = $ref->copy()->startOfWeek(Carbon::MONDAY); // Senin
        $endOfWeek   = $ref->copy()->endOfWeek(Carbon::SUNDAY);   // Minggu
        $ta = $tahunAkademik ?: self::DEFAULT_TA;

        $targetHm = $this->getTargetHmForSpv($spv, $ta);
        // Target harian rata-rata kontak untuk tim
        $dailyTargetKontak = (int) round($targetHm['target_kontak'] / 25); // ~per hari kerja
        if ($dailyTargetKontak < 5) $dailyTargetKontak = 6;

        $daysName = [
            1 => 'SENIN',
            2 => 'SELASA',
            3 => 'RABU',
            4 => 'KAMIS',
            5 => "JUM'AT",
            6 => 'SABTU',
            7 => 'MINGGU',
        ];

        $rows = [];
        $kumulatifLunas = 0;
        $totalTargetKontak = 0;
        $totalRealisasiKontak = 0;
        $totalFormulir = 0;
        $totalLunas = 0;

        for ($i = 1; $i <= 7; $i++) {
            $currentDate = $startOfWeek->copy()->addDays($i - 1);
            $dayName = $daysName[$i];

            // Target kontak: Senin-Sabtu normal, Minggu istirahat/lebih ringan
            $targetKontakHariIni = in_array($i, [1, 2, 3, 4, 5]) ? $dailyTargetKontak : ($i === 6 ? (int)round($dailyTargetKontak * 0.5) : 0);

            $realisasiHari = $this->getTeamRealization($spv, $currentDate, $currentDate, $ta);

            $kumulatifLunas += $realisasiHari['lunas'];

            $rows[] = [
                'hari'                 => $dayName,
                'tanggal'              => $currentDate->format('d M Y'),
                'is_today'             => $currentDate->isToday(),
                'target_kontak'        => $targetKontakHariIni,
                'realisasi_kontak'     => $realisasiHari['kontak'],
                'pembayaran_formulir'  => $realisasiHari['formulir'],
                'maba_lunas'           => $realisasiHari['lunas'],
                'kumulatif_lunas'      => $kumulatifLunas,
            ];

            $totalTargetKontak += $targetKontakHariIni;
            $totalRealisasiKontak += $realisasiHari['kontak'];
            $totalFormulir += $realisasiHari['formulir'];
            $totalLunas += $realisasiHari['lunas'];
        }

        $totalRow = [
            'hari'                 => 'TOTAL',
            'tanggal'              => '-',
            'is_today'             => false,
            'target_kontak'        => $totalTargetKontak,
            'realisasi_kontak'     => $totalRealisasiKontak,
            'pembayaran_formulir'  => $totalFormulir,
            'maba_lunas'           => $totalLunas,
            'kumulatif_lunas'      => $kumulatifLunas,
        ];

        return [
            'periode_label' => $startOfWeek->translatedFormat('d M Y') . ' - ' . $endOfWeek->translatedFormat('d M Y'),
            'rows'          => $rows,
            'total'         => $totalRow,
        ];
    }

    /**
     * TABEL PERFORMA HARIAN / PERORANG (Whiteboard Image 2)
     * Kolom: NAMA SALES, TARGET NORMAL, Capaian Kemarin (sub: Kontak, Formulir, Lunas),
     * UTANG ANGKA (wajib dibayar), SASARAN HARI INI (Target Normal + Utang), Lokasi/Penugasan Hari ini.
     *
     * Logika Utang Angka:
     * Utang Angka = max(0, Target Normal - Realisasi).
     * Carry-over otomatis ke hari berikutnya tanpa perlu approval.
     * Sasaran Hari Ini = Target Normal Hari Ini + Utang Sebelumnya.
     */
    public function getPerformaHarianPerorang(User $spv, ?Carbon $date = null, ?string $tahunAkademik = null): array
    {
        $today = $date ? $date->copy() : now();
        $yesterday = $today->copy()->subDay();
        $ta = $tahunAkademik ?: self::DEFAULT_TA;

        $teamMemberIds = $spv->teamMemberIds();
        $salesUsers = User::whereIn('id', $teamMemberIds)->where('role', 'Sales')->get();

        if ($salesUsers->isEmpty()) {
            $salesUsers = User::where('role', 'Sales')->get();
        }

        $rows = [];

        foreach ($salesUsers as $sales) {
            // Target yang dialokasikan ke sales ini
            $salesTarget = Target::where('sales_id', $sales->id)
                ->where('status', 'Aktif')
                ->where(function ($q) use ($ta) {
                    $q->where('tahun_akademik', $ta)->orWhereNull('tahun_akademik');
                })
                ->latest()
                ->first();

            // Target normal harian (default: 5 kontak, 2 formulir, 1 lunas)
            $targetNormalKontak   = $salesTarget ? (int) max(1, round($salesTarget->target_kontak / 25)) : 5;
            $targetNormalFormulir = $salesTarget ? (int) max(1, round($salesTarget->target_formulir / 25)) : 2;
            $targetNormalLunas    = $salesTarget ? (int) max(1, round($salesTarget->target_lunas / 25)) : 1;

            // Capaian Kemarin (Yesterday)
            $capaianKemarinKontak = Prospek::where('sales_id', $sales->id)
                ->whereDate('created_at', $yesterday->toDateString())
                ->count();

            $capaianKemarinFormulir = Transaksi::whereHas('prospek', function ($q) use ($sales) {
                    $q->where('sales_id', $sales->id);
                })
                ->where('jenis', 'Beli Formulir')
                ->whereDate('tanggal', $yesterday->toDateString())
                ->count();

            $capaianKemarinLunas = Transaksi::whereHas('prospek', function ($q) use ($sales) {
                    $q->where('sales_id', $sales->id);
                })
                ->where('jenis', 'Pembayaran Termin 1')
                ->whereDate('tanggal', $yesterday->toDateString())
                ->count();

            // Utang Angka Kemarin = max(0, Target Normal - Realisasi Kemarin)
            $utangKontak   = max(0, $targetNormalKontak - $capaianKemarinKontak);
            $utangFormulir = max(0, $targetNormalFormulir - $capaianKemarinFormulir);
            $utangLunas    = max(0, $targetNormalLunas - $capaianKemarinLunas);

            // Sasaran Hari Ini = Target Normal Hari Ini + Utang Kemarin
            $sasaranHariIniKontak   = $targetNormalKontak + $utangKontak;
            $sasaranHariIniFormulir = $targetNormalFormulir + $utangFormulir;
            $sasaranHariIniLunas    = $targetNormalLunas + $utangLunas;

            // Lokasi / Penugasan Hari Ini
            $kunjunganHariIni = Kunjungan::where('sales_id', $sales->id)
                ->whereDate('tanggal', $today->toDateString())
                ->first();

            $lokasiPenugasan = 'Online / Follow Up UCIC';
            if ($kunjunganHariIni) {
                $lokasiPenugasan = $kunjunganHariIni->lokasi_penugasan
                    ?: ($kunjunganHariIni->nama_institusi ?: 'Kunjungan ' . $kunjunganHariIni->jenis);
            }

            $rows[] = [
                'sales_id'              => $sales->id,
                'nama_sales'            => $sales->name,
                'avatar'                => strtoupper(substr($sales->name, 0, 2)),
                'target_normal'         => [
                    'kontak'   => $targetNormalKontak,
                    'formulir' => $targetNormalFormulir,
                    'lunas'    => $targetNormalLunas,
                    'summary'  => "{$targetNormalKontak} Kontak | {$targetNormalFormulir} Form | {$targetNormalLunas} Lunas",
                ],
                'capaian_kemarin'       => [
                    'kontak'   => $capaianKemarinKontak,
                    'formulir' => $capaianKemarinFormulir,
                    'lunas'    => $capaianKemarinLunas,
                ],
                'utang_angka'           => [
                    'kontak'   => $utangKontak,
                    'formulir' => $utangFormulir,
                    'lunas'    => $utangLunas,
                    'has_utang'=> ($utangKontak + $utangFormulir + $utangLunas) > 0,
                    'summary'  => ($utangKontak + $utangFormulir + $utangLunas > 0)
                        ? "{$utangKontak} Ktk, {$utangFormulir} Frm, {$utangLunas} Lns"
                        : '0 (Lunas)',
                ],
                'sasaran_hari_ini'      => [
                    'kontak'   => $sasaranHariIniKontak,
                    'formulir' => $sasaranHariIniFormulir,
                    'lunas'    => $sasaranHariIniLunas,
                    'summary'  => "{$sasaranHariIniKontak} Ktk | {$sasaranHariIniFormulir} Frm | {$sasaranHariIniLunas} Lns",
                ],
                'lokasi_penugasan'      => $lokasiPenugasan,
            ];
        }

        return [
            'tanggal_hari_ini' => $today->translatedFormat('l, d F Y'),
            'tanggal_kemarin'  => $yesterday->translatedFormat('d F Y'),
            'rows'             => $rows,
        ];
    }

    /**
     * DATA AKUMULASI TIM SPV
     * Menampilkan perbandingan target kumulatif vs realisasi, pencapaian per sales,
     * serta sisa target yang harus dicapai dalam TA aktif.
     */
    public function getDataAkumulasi(User $spv, ?string $tahunAkademik = null): array
    {
        $ta = $tahunAkademik ?: self::DEFAULT_TA;
        $targetHm = $this->getTargetHmForSpv($spv, $ta);
        $teamMemberIds = $spv->teamMemberIds();

        $salesUsers = User::whereIn('id', $teamMemberIds)->where('role', 'Sales')->get();
        if ($salesUsers->isEmpty()) {
            $salesUsers = User::where('role', 'Sales')->get();
        }

        $salesBreakdown = [];
        $totalClosingMaba = 0;
        $totalProspek = 0;
        $totalFormulir = 0;

        foreach ($salesUsers as $s) {
            $prospekCount = Prospek::where('sales_id', $s->id)
                ->where(function ($q) use ($ta) {
                    $q->where('tahun_akademik', $ta)->orWhereNull('tahun_akademik');
                })->count();

            $formCount = Transaksi::whereHas('prospek', function ($q) use ($s, $ta) {
                    $q->where('sales_id', $s->id);
                    if ($ta) $q->where(function ($sub) use ($ta) { $sub->where('tahun_akademik', $ta)->orWhereNull('tahun_akademik'); });
                })
                ->where('jenis', 'Beli Formulir')
                ->count();

            $lunasCount = Prospek::where('sales_id', $s->id)
                ->where(function ($q) use ($ta) {
                    $q->where('tahun_akademik', $ta)->orWhereNull('tahun_akademik');
                })
                ->whereIn('status', ['LUNAS', 'Closing', '07 LUNAS'])
                ->count();

            $salesTarget = Target::where('sales_id', $s->id)
                ->where('status', 'Aktif')
                ->latest()
                ->first();

            $targetPersonal = $salesTarget ? (int)$salesTarget->target_lunas : 10;
            $achievement = $targetPersonal > 0 ? round(($lunasCount / $targetPersonal) * 100) : 0;

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

            $totalProspek += $prospekCount;
            $totalFormulir += $formCount;
            $totalClosingMaba += $lunasCount;
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
