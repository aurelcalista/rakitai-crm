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
    public function getActiveTarget(User $sales): ?Target
    {
        $now       = Carbon::now();
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
    public function calculateDailyTarget(User $sales): array
    {
        $target = $this->getActiveTarget($sales);

        if (!$target) {
            return [
                'target_hari_ini_kontak'      => 0,
                'target_hari_ini_followup'    => 0,
                'sisa_akumulasi_kontak'       => 0,
                'sisa_akumulasi_followup'     => 0,
                'pencapaian_hari_ini_kontak'  => 0,
                'pencapaian_hari_ini_followup'=> 0,
            ];
        }

        $now       = Carbon::now();
        $start     = $target->tanggal_mulai->copy();
        $end       = $target->tanggal_selesai->copy();
        $totalDays = max(1, $start->diffInDays($end) + 1);
        $taId      = $target->academic_year_id ?? AkademikService::getAktifId();

        if ($target->tipe_periode === 'Harian') {
            $dailyKontak   = (int) $target->target_kontak;
            $dailyFollowup = (int) $target->target_followup;
        } else {
            $dailyKontak   = (int) ceil($target->target_kontak / $totalDays);
            $dailyFollowup = (int) ceil($target->target_followup / $totalDays);
        }

        // Days elapsed before today
        $elapsedDays = max(0, $start->startOfDay()->diffInDays($now->copy()->startOfDay()));

        // Expected achievement up to yesterday
        $expectedKontak   = $dailyKontak * $elapsedDays;
        $expectedFollowup = $dailyFollowup * $elapsedDays;

        // Actual achievement up to yesterday
        $achievedUpToYesterday = $this->getAchievementUpToDate($sales, $target, $now->copy()->subDay(), $taId);

        // Deficit (carry-over) — never negative
        $sisaKontak   = max(0, $expectedKontak - $achievedUpToYesterday['kontak_baru']);
        $sisaFollowup = max(0, $expectedFollowup - $achievedUpToYesterday['follow_up']);

        // Achievement today (incremental)
        $achievedToday = $this->getAchievementUpToDate($sales, $target, $now, $taId);
        $pencapaianKontakHariIni   = max(0, $achievedToday['kontak_baru'] - $achievedUpToYesterday['kontak_baru']);
        $pencapaianFollowupHariIni = max(0, $achievedToday['follow_up'] - $achievedUpToYesterday['follow_up']);

        return [
            'target_hari_ini_kontak'      => $dailyKontak + $sisaKontak,
            'target_hari_ini_followup'    => $dailyFollowup + $sisaFollowup,
            'sisa_akumulasi_kontak'       => $sisaKontak,
            'sisa_akumulasi_followup'     => $sisaFollowup,
            'pencapaian_hari_ini_kontak'  => $pencapaianKontakHariIni,
            'pencapaian_hari_ini_followup'=> $pencapaianFollowupHariIni,
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
        $closing = (clone $prospekQuery)
            ->where('status', 'LUNAS')
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
        $stageNames = ['BARU', 'KONTAK', 'HANGAT', 'PANAS', 'FORMULIR', 'BERKAS', 'LUNAS', 'DINGIN'];

        $colorMap = [
            'BARU'     => 'badge-cold-lead',
            'KONTAK'   => 'badge-interested',
            'HANGAT'   => 'badge-follow-up',
            'PANAS'    => 'badge-hot-lead',
            'FORMULIR' => 'badge-beli-formulir',
            'BERKAS'   => 'badge-pembayaran-termin-1',
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
