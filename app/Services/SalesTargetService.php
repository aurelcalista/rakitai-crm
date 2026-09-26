<?php

namespace App\Services;

use App\Models\FollowUp;
use App\Models\Prospek;
use App\Models\Target;
use App\Models\User;
use Carbon\Carbon;

/**
 * SalesTargetService — Target queries for individual Sales/CS users.
 *
 * All achievement queries are scoped to the active Tahun Akademik via AkademikService.
 * Historical override: pass explicit $taId parameter where needed.
 *
 * Note: For the full deficit/daily/rollup calculation, prefer TargetMetricsService.
 * This service handles target retrieval and basic achievement stats for dashboards.
 */
class SalesTargetService
{
    /**
     * Get the active target for the current period for a given Sales/CS user.
     *
     * Two conditions must both hold:
     *   1. The target period is active (tanggal_mulai <= now <= tanggal_selesai).
     *   2. The target belongs to the active Tahun Akademik (when one is configured).
     *
     * The date-range check is intentionally preserved — it defines "target bulan ini",
     * NOT replaced by academic year logic.
     */
    public function getActiveTarget(User $sales, ?Carbon $asOf = null): ?Target
    {
        $now       = $asOf ?? Carbon::now();
        $activeAyId = AkademikService::getAktifId();

        return Target::where('sales_id', $sales->id)
            ->where('status', 'Aktif')
            ->where('tanggal_mulai', '<=', $now->toDateString())
            ->where('tanggal_selesai', '>=', $now->toDateString())
            ->when($activeAyId, fn($q) => $q->where('academic_year_id', $activeAyId))
            ->latest()
            ->first();
    }

    /**
     * Calculate actual achievement for a Sales user within a target period.
     * Scoped to the target's academic_year_id (or active TA if not set on target).
     *
     * @return array{kontakBaru: int, followUp: int, kunjungan: int}
     */
    public function getAchievement(User $sales, Target $target): array
    {
        $from  = $target->tanggal_mulai->copy()->startOfDay();
        $to    = $target->tanggal_selesai->copy()->endOfDay();
        // Scope to target's own TA; fallback to active TA
        $taId  = $target->academic_year_id ?? AkademikService::getAktifId();

        // Kontak baru = new prospects created by this Sales in the period, scoped to TA
        $kontakBaru = Prospek::where('sales_id', $sales->id)
            ->when($taId, fn($q) => $q->where('academic_year_id', $taId))
            ->whereBetween('created_at', [$from, $to])
            ->count();

        // Follow-up = total follow-up records by this Sales in the period
        $followUp = FollowUp::where('user_id', $sales->id)
            ->whereBetween('tanggal', [$from, $to])
            ->count();

        // Kunjungan = total visits by this Sales in the period
        $kunjungan = \App\Models\Kunjungan::where('sales_id', $sales->id)
            ->whereBetween('tanggal', [$from, $to])
            ->count();

        return compact('kontakBaru', 'followUp', 'kunjungan');
    }

    /**
     * Snowball daily target calculation:
     * Target Hari Ini = Target Base Harian + Deficit dari hari-hari sebelumnya (carry-over).
     *
     * - Base Daily = Target Periode / jumlah hari periode
     * - Deficit = Expected up-to-yesterday - Achieved up-to-yesterday (min 0)
     * - Deficit NEVER becomes negative; over-achievement doesn't reduce tomorrow's base
     *
     * For full metrics (remaining_days, running_daily, color), use TargetMetricsService::computeMetrics().
     *
     * @return array{target_hari_ini_kontak: int, target_hari_ini_followup: int, sisa_akumulasi_kontak: int, sisa_akumulasi_followup: int, pencapaian_hari_ini_kontak: int, pencapaian_hari_ini_followup: int}
     */
    public function calculateDailyTarget(User $sales, ?Carbon $asOf = null): array
    {
        $target = $this->getActiveTarget($sales, $asOf);

        if (!$target) {
            return [
                'target_hari_ini_kontak'         => 0,
                'target_hari_ini_followup'       => 0,
                'target_minggu_ini_formulir'     => 0,
                'target_hari_ini_formulir'       => 0,
                'sisa_akumulasi_kontak'          => 0,
                'sisa_akumulasi_formulir'        => 0,
                'sisa_akumulasi_followup'        => 0,
                'deficit_kontak'                 => 0,
                'deficit_formulir'               => 0,
                'pencapaian_hari_ini_kontak'     => 0,
                'pencapaian_hari_ini_followup'   => 0,
                'pencapaian_minggu_ini_formulir' => 0,
            ];
        }

        $now       = $asOf ?? Carbon::now();
        $start     = $target->tanggal_mulai->copy();
        $end       = $target->tanggal_selesai->copy();
        $totalDays = max(1, $start->diffInDays($end) + 1);
        $totalWeeks = max(1, (int) ceil($totalDays / 7));
        $taId      = $target->academic_year_id ?? AkademikService::getAktifId();

        // 1. Daily Kontak
        $elapsedDays = max(0, $start->startOfDay()->diffInDays($now->copy()->startOfDay()));
        if ($target->tipe_periode === 'Harian') {
            $dailyKontak = (int) $target->target_kontak;
            $dailyFollowup = (int) $target->target_followup;
            $expectedKontak   = $dailyKontak * $elapsedDays;
            $expectedFollowup = $dailyFollowup * $elapsedDays;
        } else {
            $expectedKontak = (int) floor(($target->target_kontak * $elapsedDays) / $totalDays);
            $expectedFollowup = (int) floor(($target->target_followup * $elapsedDays) / $totalDays);
            
            $nextExpectedKontak = (int) floor(($target->target_kontak * ($elapsedDays + 1)) / $totalDays);
            $nextExpectedFollowup = (int) floor(($target->target_followup * ($elapsedDays + 1)) / $totalDays);
            
            $dailyKontak = max(0, $nextExpectedKontak - $expectedKontak);
            $dailyFollowup = max(0, $nextExpectedFollowup - $expectedFollowup);
        }

        $achievedUpToYesterday = $this->getAchievementUpToDate($sales, $target, $now->copy()->subDay(), $taId);

        $sisaKontak   = max(0, $expectedKontak - $achievedUpToYesterday['kontak_baru']);
        $sisaFollowup = max(0, $expectedFollowup - $achievedUpToYesterday['follow_up']);

        // Check if there is a previous daily target with deficit if on day 0
        if ($sisaKontak === 0 && $elapsedDays === 0 && $target->tipe_periode === 'Harian') {
            $prevTarget = Target::where('sales_id', $sales->id)
                ->where('status', 'Aktif')
                ->where('tanggal_selesai', '<', $start->toDateString())
                ->when($taId, fn($q) => $q->where('academic_year_id', $taId))
                ->latest('tanggal_selesai')
                ->first();
            if ($prevTarget && $prevTarget->target_kontak > 0) {
                $prevAchieved = $this->getAchievementUpToDate($sales, $prevTarget, $prevTarget->tanggal_selesai, $taId);
                $sisaKontak = max(0, (int)$prevTarget->target_kontak - $prevAchieved['kontak_baru']);
            }
        }

        $achievedToday = $this->getAchievementUpToDate($sales, $target, $now, $taId);
        $pencapaianKontakHariIni   = max(0, $achievedToday['kontak_baru'] - $achievedUpToYesterday['kontak_baru']);
        $pencapaianFollowupHariIni = max(0, $achievedToday['follow_up'] - $achievedUpToYesterday['follow_up']);

        // 2. Weekly Formulir (strictly independent)
        $elapsedWeeks = (int) floor($elapsedDays / 7);
        if ($target->tipe_periode === 'Mingguan') {
            $weeklyFormulir = (int) $target->target_formulir;
            $expectedFormulir = $weeklyFormulir * $elapsedWeeks;
        } else {
            $expectedFormulir = (int) floor(($target->target_formulir * $elapsedWeeks) / $totalWeeks);
            $nextExpectedFormulir = (int) floor(($target->target_formulir * ($elapsedWeeks + 1)) / $totalWeeks);
            $weeklyFormulir = max(0, $nextExpectedFormulir - $expectedFormulir);
        }

        $lastWeekEnd = $start->copy()->addDays($elapsedWeeks * 7)->subSecond();
        if ($elapsedWeeks > 0) {
            $achievedUpToLastWeek = $this->getFormulirAchievementUpToDate($sales, $target, $lastWeekEnd, $taId);
            $sisaFormulir = max(0, $expectedFormulir - $achievedUpToLastWeek);
        } else {
            $sisaFormulir = 0;
            if ($target->tipe_periode === 'Mingguan') {
                $prevTarget = Target::where('sales_id', $sales->id)
                    ->where('status', 'Aktif')
                    ->where('tanggal_selesai', '<', $start->toDateString())
                    ->when($taId, fn($q) => $q->where('academic_year_id', $taId))
                    ->latest('tanggal_selesai')
                    ->first();
                if ($prevTarget && $prevTarget->target_formulir > 0) {
                    $prevAchieved = $this->getFormulirAchievementUpToDate($sales, $prevTarget, $prevTarget->tanggal_selesai, $taId);
                    $sisaFormulir = max(0, (int)$prevTarget->target_formulir - $prevAchieved);
                }
            }
        }

        $achievedTotalFormulir = $this->getFormulirAchievementUpToDate($sales, $target, $now, $taId);
        $achievedBeforeThisWeek = $elapsedWeeks > 0 ? $this->getFormulirAchievementUpToDate($sales, $target, $lastWeekEnd, $taId) : 0;
        $pencapaianFormulirMingguIni = max(0, $achievedTotalFormulir - $achievedBeforeThisWeek);

        return [
            // Daily Kontak (independent)
            'base_daily_kontak'            => $dailyKontak,
            'target_hari_ini_kontak'       => $dailyKontak + $sisaKontak,
            'sisa_akumulasi_kontak'        => $sisaKontak,
            'deficit_kontak'               => $sisaKontak,
            'pencapaian_hari_ini_kontak'   => $pencapaianKontakHariIni,

            // Weekly Formulir (independent)
            'base_weekly_formulir'         => $weeklyFormulir,
            'target_minggu_ini_formulir'   => $weeklyFormulir + $sisaFormulir,
            'target_hari_ini_formulir'     => $weeklyFormulir + $sisaFormulir,
            'sisa_akumulasi_formulir'      => $sisaFormulir,
            'deficit_formulir'             => $sisaFormulir,
            'pencapaian_minggu_ini_formulir' => $pencapaianFormulirMingguIni,

            // Follow-up
            'target_hari_ini_followup'     => $dailyFollowup + $sisaFollowup,
            'sisa_akumulasi_followup'      => $sisaFollowup,
            'pencapaian_hari_ini_followup' => $pencapaianFollowupHariIni,
        ];
    }

    /**
     * Get cumulative achievement from target start up to a specific date.
     * Scoped to academic_year_id.
     */
    private function getAchievementUpToDate(User $sales, Target $target, Carbon $upToDate, ?int $taId = null): array
    {
        $from = $target->tanggal_mulai->copy()->startOfDay();
        $to   = $upToDate->copy()->endOfDay();
        $taId = $taId ?? ($target->academic_year_id ?? AkademikService::getAktifId());

        if ($to < $from) {
            return ['kontak_baru' => 0, 'follow_up' => 0];
        }

        $kontakBaru = Prospek::where('sales_id', $sales->id)
            ->when($taId, fn($q) => $q->where('academic_year_id', $taId))
            ->whereBetween('created_at', [$from, $to])
            ->count();

        $followUp = FollowUp::where('user_id', $sales->id)
            ->whereBetween('tanggal', [$from, $to])
            ->count();

        return [
            'kontak_baru' => $kontakBaru,
            'follow_up'   => $followUp,
        ];
    }

    /**
     * Get cumulative formulir achievement from target start up to a specific date.
     */
    private function getFormulirAchievementUpToDate(User $sales, Target $target, Carbon $upToDate, ?int $taId = null): int
    {
        $from = $target->tanggal_mulai->copy()->startOfDay();
        $to   = $upToDate->copy()->endOfDay();
        $taId = $taId ?? ($target->academic_year_id ?? AkademikService::getAktifId());

        if ($to < $from) {
            return 0;
        }

        $formulirProspekIds = Prospek::where('sales_id', $sales->id)
            ->when($taId, fn($q) => $q->where('academic_year_id', $taId))
            ->pluck('id');

        return \App\Models\Transaksi::whereIn('prospek_id', $formulirProspekIds)
            ->where('jenis', 'Beli Formulir')
            ->whereBetween('tanggal', [$from, $to])
            ->distinct('prospek_id')
            ->count('prospek_id');
    }

    /**
     * Build the complete stats array for Sales Dashboard.
     * All prospect counts are scoped to the active Tahun Akademik.
     * Falls back gracefully when no target exists.
     *
     * NOTE: Uses target_lunas (not target_closing — that column does not exist).
     */
    public function getStats(User $user): array
    {
        $now    = Carbon::now();
        $target = $this->getActiveTarget($user);
        $role   = strtolower($user->role);
        $taId   = AkademikService::getAktifId();

        // Count prospects scoped to this user AND active TA
        $prospekQuery = Prospek::query()
            ->when($taId, fn($q) => $q->where('academic_year_id', $taId));

        if ($role === 'cs') {
            $prospekQuery->where('cs_id', $user->id);
        } else {
            $prospekQuery->where('sales_id', $user->id);
        }

        $totalProspek  = (clone $prospekQuery)->count();
        $activeProspek = (clone $prospekQuery)
            ->whereNotIn('status', ['LUNAS', 'DINGIN'])
            ->count();
        $closingStatuses = strtolower($user->role) === 'sales' ? ['CLOSING'] : ['LUNAS'];
        $closing = (clone $prospekQuery)
            ->whereIn('status', $closingStatuses)
            ->count();
        $lost = (clone $prospekQuery)
            ->where('status', 'DINGIN')
            ->count();

        $followUpCount = (clone $prospekQuery)
            ->where('status', 'HANGAT')
            ->count();

        if ($target) {
            $achievement     = $this->getAchievement($user, $target);
            // Use target_lunas (not target_closing — does not exist in DB)
            $targetBulanIni  = $target->target_lunas > 0 ? $target->target_lunas : $target->target_kontak;
            $realisasiKontak = $achievement['kontakBaru'];
            $realisasiClosing = $closing;
            $percentage      = $targetBulanIni > 0
                ? min(100, round(($realisasiClosing / $targetBulanIni) * 100))
                : 0;
            $sisaTarget      = max(0, $targetBulanIni - $realisasiClosing);
        } else {
            $targetBulanIni  = 0;
            $realisasiKontak = 0;
            $realisasiClosing = $closing;
            $percentage      = 0;
            $sisaTarget      = 0;
        }

        return [
            'total_prospek'    => $totalProspek,
            'active_prospek'   => $activeProspek,
            'follow_up'        => $followUpCount,
            'closing'          => $closing,
            'DINGIN'           => $lost,
            'lost'             => $lost,
            'target_bulan_ini' => $targetBulanIni,
            'realisasi_closing' => $realisasiClosing,
            'percentage'       => $percentage,
            'sisa_target'      => $sisaTarget,
            'bulan_label'      => $now->locale('id')->isoFormat('MMMM Y'),
        ];
    }

    /**
     * Get pipeline stage distribution for a specific Sales user.
     * Scoped to active TA.
     */
    public function getPipelineStages(User $sales): array
    {
        $taId = AkademikService::getAktifId();
        $stageNames = ['BARU', 'KONTAK', 'HANGAT', 'PANAS', 'FORMULIR', 'BERKAS', 'CLOSING', 'LUNAS', 'DINGIN'];
        if (strtolower($sales->role) === 'sales') {
            $stageNames = array_filter($stageNames, fn($s) => strtoupper($s) !== 'LUNAS');
        }

        $colorMap = [
            'BARU'     => 'badge-cold-lead',
            'KONTAK'   => 'badge-interested',
            'HANGAT'   => 'badge-follow-up',
            'PANAS'    => 'badge-hot-lead',
            'FORMULIR' => 'badge-beli-formulir',
            'BERKAS'   => 'badge-pembayaran-termin-1',
            'CLOSING'  => 'badge-closing',
            'LUNAS'    => 'badge-closing',
            'DINGIN'   => 'badge-lost',
        ];

        return array_map(function ($name) use ($sales, $colorMap, $taId) {
            $count = Prospek::where('sales_id', $sales->id)
                ->where('status', $name)
                ->when($taId, fn($q) => $q->where('academic_year_id', $taId))
                ->count();
            return [
                'name'  => $name,
                'count' => $count,
                'color' => $colorMap[$name] ?? '',
            ];
        }, $stageNames);
    }
}
