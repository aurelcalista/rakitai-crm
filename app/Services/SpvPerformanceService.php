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
     * Ambil TA aktif dari DB, fallback ke DEFAULT_TA jika belum di-seed.
     */
    public function getActiveTa(): string
    {
        $ta = \App\Models\TahunAkademik::getAktif();
        return $ta ? $ta->nama : self::DEFAULT_TA;
    }

    /**
     * Dapatkan Target yang diberikan HM ke SPV (atau fallback default wilayah).
     */
    public function getTargetHmForSpv(User $spv, ?string $tahunAkademik = null): array
    {
        $ta = $tahunAkademik ?: $this->getActiveTa();

        // Cari target aktif yang dialokasikan khusus untuk SPV ini (oleh HM)
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
            'has_hm_target'      => $target !== null,
            'target_id'          => $target?->id,
            'allocated_by_id'    => $target?->allocated_by,
            'tahun_akademik'     => $ta,
            'tipe_periode'       => $tipePeriode,
            'tanggal_mulai'      => $tanggalMulai,
            'tanggal_selesai'    => $tanggalSelesai,
            'target_kontak'      => $targetKontak,
            'target_formulir'    => $targetFormulir,
            'target_lunas'       => $targetLunas,
            'allocated_kontak'   => $allocatedKontak,
            'allocated_formulir' => $allocatedFormulir,
            'allocated_lunas'    => $allocatedLunas,
            'sisa_kontak'        => $sisaKontak,
            'sisa_formulir'      => $sisaFormulir,
            'sisa_lunas'         => max(0, $targetLunas - $realisasi['lunas']),
            'realisasi_kontak'   => $realisasi['kontak'],
            'realisasi_formulir' => $realisasi['formulir'],
            'realisasi_lunas'    => $realisasi['lunas'],
            'achieve_pct'        => $targetLunas > 0 ? round(($realisasi['lunas'] / $targetLunas) * 100, 1) : 0,
            'sumber'             => $target ? 'Target Resmi HM (DB)' : 'Default P0 (Belum dialokasikan HM)',
            'wilayah'            => $spv->wilayah ? $spv->wilayah->nama : 'Wilayah SPV',
        ];
    }

    /**
     * Hitung realisasi seluruh tim SPV dalam rentang tanggal tertentu.
     */
    public function getTeamRealization(User $spv, Carbon $from, Carbon $to, ?string $tahunAkademik = null): array
    {
        $teamMemberIds = $spv->teamMemberIds();
        $ta = $tahunAkademik ?: $this->getActiveTa();

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

        $prospekIds = (clone $prospekQuery)->pluck('id')->toArray();

        // Realisasi Formulir = transaksi Beli Formulir dalam periode
        $realisasiFormulir = empty($prospekIds) ? 0 : Transaksi::whereIn('prospek_id', $prospekIds)
            ->where('jenis', 'Beli Formulir')
            ->whereBetween('tanggal', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->distinct('prospek_id')
            ->count('prospek_id');

        // Realisasi Maba Lunas = Transaksi Termin 1 (Formulir + Termin 1 = Maba Lunas per PRD)
        $realisasiLunas = empty($prospekIds) ? 0 : Transaksi::whereIn('prospek_id', $prospekIds)
            ->where('jenis', 'Pembayaran Termin 1')
            ->whereBetween('tanggal', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->distinct('prospek_id')
            ->count('prospek_id');

        // Fallback: jika tidak ada transaksi, hitung dari status LUNAS
        if ($realisasiLunas === 0) {
            $realisasiLunas = (clone $prospekQuery)
                ->whereIn('status', ['LUNAS', 'Closing', '07 LUNAS'])
                ->whereBetween('updated_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
                ->count();
        }

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
        $ta = $tahunAkademik ?: $this->getActiveTa();

        $targetHm = $this->getTargetHmForSpv($spv, $ta);

        // Target harian per hari kerja (~25 hari kerja per bulan)
        $dailyTargetKontak   = max(5, (int) round($targetHm['target_kontak'] / 25));
        $dailyTargetFormulir = max(1, (int) round($targetHm['target_formulir'] / 25));
        $dailyTargetLunas    = max(1, (int) round($targetHm['target_lunas'] / 25));

        $daysName = [
            1 => 'SENIN', 2 => 'SELASA', 3 => 'RABU', 4 => 'KAMIS',
            5 => "JUM'AT", 6 => 'SABTU', 7 => 'MINGGU',
        ];

        $rows = [];
        $kumulatifLunas = 0;
        $totalTargetKontak = 0;
        $totalRealisasiKontak = 0;
        $totalFormulir = 0;
        $totalLunas = 0;

        // Carry-over utang angka antar hari (kumulatif sejak Senin)
        $utangCarryKontak   = 0;
        $utangCarryFormulir = 0;
        $utangCarryLunas    = 0;

        for ($i = 1; $i <= 7; $i++) {
            $currentDate = $startOfWeek->copy()->addDays($i - 1);
            $dayName     = $daysName[$i];
            $isFuture    = $currentDate->isFuture() && !$currentDate->isToday();

            // Target per hari: Senin-Jumat normal, Sabtu 50%, Minggu 0
            $targetKontakHariIni   = in_array($i, [1,2,3,4,5]) ? $dailyTargetKontak   : ($i===6 ? (int)round($dailyTargetKontak*0.5)   : 0);
            $targetFormulirHariIni = in_array($i, [1,2,3,4,5]) ? $dailyTargetFormulir : ($i===6 ? (int)round($dailyTargetFormulir*0.5) : 0);
            $targetLunasHariIni    = in_array($i, [1,2,3,4,5]) ? $dailyTargetLunas    : ($i===6 ? (int)round($dailyTargetLunas*0.5)    : 0);

            // Sasaran Hari Ini = Target Normal + Utang Carry-Over
            $sasaranKontak   = $targetKontakHariIni + $utangCarryKontak;
            $sasaranFormulir = $targetFormulirHariIni + $utangCarryFormulir;
            $sasaranLunas    = $targetLunasHariIni + $utangCarryLunas;

            $realisasiHari = $isFuture
                ? ['kontak' => 0, 'formulir' => 0, 'lunas' => 0]
                : $this->getTeamRealization($spv, $currentDate, $currentDate, $ta);

            // Utang = max(0, Sasaran - Realisasi) — carry ke hari berikutnya
            $utangCarryKontak   = $isFuture ? $utangCarryKontak   : max(0, $sasaranKontak   - $realisasiHari['kontak']);
            $utangCarryFormulir = $isFuture ? $utangCarryFormulir : max(0, $sasaranFormulir - $realisasiHari['formulir']);
            $utangCarryLunas    = $isFuture ? $utangCarryLunas    : max(0, $sasaranLunas    - $realisasiHari['lunas']);

            $kumulatifLunas += $realisasiHari['lunas'];

            $rows[] = [
                'hari'                => $dayName,
                'tanggal'             => $currentDate->format('d M Y'),
                'is_today'            => $currentDate->isToday(),
                'is_future'           => $isFuture,
                'target_kontak'       => $targetKontakHariIni,
                'sasaran_kontak'      => $sasaranKontak,
                'realisasi_kontak'    => $realisasiHari['kontak'],
                'pembayaran_formulir' => $realisasiHari['formulir'],
                'maba_lunas'          => $realisasiHari['lunas'],
                'kumulatif_lunas'     => $kumulatifLunas,
                'utang_kontak'        => $utangCarryKontak,
                'utang_formulir'      => $utangCarryFormulir,
                'utang_lunas'         => $utangCarryLunas,
                'has_utang'           => ($utangCarryKontak + $utangCarryFormulir + $utangCarryLunas) > 0,
            ];

            $totalTargetKontak    += $targetKontakHariIni;
            $totalRealisasiKontak += $realisasiHari['kontak'];
            $totalFormulir        += $realisasiHari['formulir'];
            $totalLunas           += $realisasiHari['lunas'];
        }

        $totalRow = [
            'hari'                => 'TOTAL',
            'tanggal'             => '-',
            'is_today'            => false,
            'is_future'           => false,
            'target_kontak'       => $totalTargetKontak,
            'sasaran_kontak'      => $totalTargetKontak,
            'realisasi_kontak'    => $totalRealisasiKontak,
            'pembayaran_formulir' => $totalFormulir,
            'maba_lunas'          => $totalLunas,
            'kumulatif_lunas'     => $kumulatifLunas,
            'utang_kontak'        => max(0, $totalTargetKontak - $totalRealisasiKontak),
            'utang_formulir'      => 0,
            'utang_lunas'         => 0,
            'has_utang'           => false,
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
        $today     = $date ? $date->copy() : now();
        $yesterday = $today->copy()->subDay();
        $ta        = $tahunAkademik ?: $this->getActiveTa();

        $teamMemberIds = $spv->teamMemberIds();

        // Include Sales DAN CS
        $teamUsers = User::whereIn('id', $teamMemberIds)
            ->whereIn('role', ['Sales', 'CS'])
            ->orderByRaw("FIELD(role, 'Sales', 'CS')")
            ->get();

        if ($teamUsers->isEmpty()) {
            $teamUsers = User::whereIn('role', ['Sales', 'CS'])->get();
        }

        $rows = [];

        foreach ($teamUsers as $member) {
            $isCs = $member->role === 'CS';

            $memberTarget = Target::where('sales_id', $member->id)
                ->where('status', 'Aktif')
                ->where(function ($q) use ($ta) {
                    $q->where('tahun_akademik', $ta)->orWhereNull('tahun_akademik');
                })
                ->latest()
                ->first();

            $targetNormalKontak   = $memberTarget ? (int) max(1, round($memberTarget->target_kontak / 25))   : 5;
            $targetNormalFormulir = $memberTarget ? (int) max(1, round($memberTarget->target_formulir / 25)) : 2;
            $targetNormalLunas    = $memberTarget ? (int) max(1, round($memberTarget->target_lunas / 25))    : 1;

            // Capaian Kemarin — CS: berdasarkan cs_id; Sales: sales_id
            $capaianKemarinKontak = $isCs
                ? Prospek::where('cs_id', $member->id)->whereDate('updated_at', $yesterday->toDateString())->count()
                : Prospek::where('sales_id', $member->id)->whereDate('created_at', $yesterday->toDateString())->count();

            $capaianKemarinFormulir = Transaksi::whereHas('prospek', function ($q) use ($member, $isCs) {
                    $isCs ? $q->where('cs_id', $member->id) : $q->where('sales_id', $member->id);
                })->where('jenis', 'Beli Formulir')->whereDate('tanggal', $yesterday->toDateString())->count();

            $capaianKemarinLunas = Transaksi::whereHas('prospek', function ($q) use ($member, $isCs) {
                    $isCs ? $q->where('cs_id', $member->id) : $q->where('sales_id', $member->id);
                })->where('jenis', 'Pembayaran Termin 1')->whereDate('tanggal', $yesterday->toDateString())->count();

            $utangKontak   = max(0, $targetNormalKontak - $capaianKemarinKontak);
            $utangFormulir = max(0, $targetNormalFormulir - $capaianKemarinFormulir);
            $utangLunas    = max(0, $targetNormalLunas - $capaianKemarinLunas);

            $sasaranHariIniKontak   = $targetNormalKontak + $utangKontak;
            $sasaranHariIniFormulir = $targetNormalFormulir + $utangFormulir;
            $sasaranHariIniLunas    = $targetNormalLunas + $utangLunas;

            // Lokasi penugasan: CS selalu Centralized
            $lokasiPenugasan = $isCs ? 'Centralized — Follow Up UCIC' : 'Online / Follow Up UCIC';
            if (!$isCs) {
                $kunjunganHariIni = Kunjungan::where('sales_id', $member->id)
                    ->whereDate('tanggal', $today->toDateString())
                    ->first();
                if ($kunjunganHariIni) {
                    $lokasiPenugasan = $kunjunganHariIni->lokasi_penugasan
                        ?: ($kunjunganHariIni->nama_institusi ?: 'Kunjungan ' . $kunjunganHariIni->jenis);
                } elseif ($member->wilayah) {
                    $lokasiPenugasan = $member->wilayah->nama;
                }
            }

            $rows[] = [
                'member_id'        => $member->id,
                'nama_sales'       => $member->name,  // legacy key compat
                'nama'             => $member->name,
                'role'             => $member->role,
                'is_cs'            => $isCs,
                'avatar'           => strtoupper(substr($member->name, 0, 2)),
                'wilayah'          => $isCs ? 'Centralized' : ($member->wilayah ? $member->wilayah->nama : 'Belum Ditugaskan'),
                'target_normal'    => [
                    'kontak'   => $targetNormalKontak,
                    'formulir' => $targetNormalFormulir,
                    'lunas'    => $targetNormalLunas,
                    'summary'  => "{$targetNormalKontak} Ktk | {$targetNormalFormulir} Frm | {$targetNormalLunas} Lns",
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
                        ? "{$utangKontak} Ktk, {$utangFormulir} Frm, {$utangLunas} Lns"
                        : '0 (Lunas)',
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
     * DATA AKUMULASI TIM SPV
     * Menampilkan perbandingan target kumulatif vs realisasi, pencapaian per sales,
     * serta sisa target yang harus dicapai dalam TA aktif.
     */
    public function getDataAkumulasi(User $spv, ?string $tahunAkademik = null): array
    {
        $ta = $tahunAkademik ?: $this->getActiveTa();
        $targetHm = $this->getTargetHmForSpv($spv, $ta);
        $teamMemberIds = $spv->teamMemberIds();

        // Include Sales DAN CS
        $salesUsers = User::whereIn('id', $teamMemberIds)->whereIn('role', ['Sales', 'CS'])->get();
        if ($salesUsers->isEmpty()) {
            $salesUsers = User::whereIn('role', ['Sales', 'CS'])->get();
        }

        $salesBreakdown = [];
        $totalClosingMaba = 0;
        $totalProspek = 0;
        $totalFormulir = 0;

        foreach ($salesUsers as $s) {
            $isCs = $s->role === 'CS';

            $prospekCount = $isCs
                ? Prospek::where('cs_id', $s->id)->where(function ($q) use ($ta) { $q->where('tahun_akademik', $ta)->orWhereNull('tahun_akademik'); })->count()
                : Prospek::where('sales_id', $s->id)->where(function ($q) use ($ta) { $q->where('tahun_akademik', $ta)->orWhereNull('tahun_akademik'); })->count();

            $formCount = Transaksi::whereHas('prospek', function ($q) use ($s, $isCs, $ta) {
                    $isCs ? $q->where('cs_id', $s->id) : $q->where('sales_id', $s->id);
                    if ($ta) $q->where(function ($sub) use ($ta) { $sub->where('tahun_akademik', $ta)->orWhereNull('tahun_akademik'); });
                })
                ->where('jenis', 'Beli Formulir')
                ->count();

            $lunasCount = $isCs
                ? Prospek::where('cs_id', $s->id)->where(function ($q) use ($ta) { $q->where('tahun_akademik', $ta)->orWhereNull('tahun_akademik'); })->whereIn('status', ['LUNAS', 'Closing', '07 LUNAS'])->count()
                : Prospek::where('sales_id', $s->id)->where(function ($q) use ($ta) { $q->where('tahun_akademik', $ta)->orWhereNull('tahun_akademik'); })->whereIn('status', ['LUNAS', 'Closing', '07 LUNAS'])->count();

            $salesTarget = Target::where('sales_id', $s->id)->where('status', 'Aktif')->latest()->first();
            $targetPersonal = $salesTarget ? (int)$salesTarget->target_lunas : 10;
            $achievement    = $targetPersonal > 0 ? round(($lunasCount / $targetPersonal) * 100) : 0;

            $salesBreakdown[] = [
                'user'            => $s,
                'name'            => $s->name,
                'role'            => $s->role,
                'is_cs'           => $isCs,
                'avatar'          => strtoupper(substr($s->name, 0, 2)),
                'prospek'         => $prospekCount,
                'formulir'        => $formCount,
                'lunas'           => $lunasCount,
                'target_lunas'    => $targetPersonal,
                'achievement_pct' => $achievement,
                'status'          => $targetPersonal <= 0 ? 'Belum Ada Target' : ($achievement >= 100 ? 'Target Tercapai' : ($achievement >= 50 ? 'On Track' : 'Perlu Akselerasi')),
            ];

            $totalProspek     += $prospekCount;
            $totalFormulir    += $formCount;
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
