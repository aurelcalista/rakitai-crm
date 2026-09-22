<?php

namespace App\Services;

use App\Models\FollowUp;
use App\Models\Prospek;
use App\Models\Target;
use App\Models\Transaksi;
use App\Models\User;
use Carbon\Carbon;

/**
 * Central single source of truth for all Target & Deficit calculations.
 *
 * PRD P0 requirements implemented here:
 *   - Target Today = Base Daily + Remaining Deficit from yesterday
 *   - Deficit carries over; never becomes negative; reduces as achievement grows
 *   - Remaining Days = days left until period end (min 0)
 *   - Running Daily Target = remaining period target / remaining days
 *
 * COLOR STATUS (Implementor Decision — not a PRD numeric requirement):
 *   >= 100% = green
 *   >= 80%  = yellow   (YELLOW_THRESHOLD constant — change here if business decides otherwise)
 *   < 80%   = red
 *
 * Academic Year scoping:
 *   All achievement queries use academic_year_id from AkademikService.
 *   Pass explicit $taId for historical override.
 */
class TargetMetricsService
{
    /**
     * Implementor Decision: lower threshold for Yellow status.
     * PRD specifies Red/Yellow/Green concept without a numeric value.
     * Change this constant to adjust without hunting through views.
     */
    public const YELLOW_THRESHOLD = 80;

    // ──────────────────────────────────────────────────────────────
    // Primary Entry Point
    // ──────────────────────────────────────────────────────────────

    /**
     * Compute all metrics for a Sales user for their active period target.
     *
     * @param  User          $sales
     * @param  Target        $target   The period target to compute against
     * @param  Carbon|null   $asOf     Reference "now" (defaults to Carbon::now(); override in tests)
     * @param  int|null      $taId     academic_year_id override (null = use active TA)
     * @return array<string, mixed>
     */
    public function computeMetrics(User $sales, Target $target, ?Carbon $asOf = null, ?int $taId = null): array
    {
        $now    = $asOf ?? Carbon::now();
        $taId   = $taId ?? AkademikService::getAktifId();
        $start  = $target->tanggal_mulai->copy()->startOfDay();
        $end    = $target->tanggal_selesai->copy()->endOfDay();

        $totalDays = max(1, $start->diffInDays($end->copy()->startOfDay()) + 1);

        // ── Base Daily ──────────────────────────────────────────────
        if ($target->tipe_periode === 'Harian') {
            $baseDailyKontak  = (int) $target->target_kontak;
            $baseDailyLunas   = (int) $target->target_lunas;
        } else {
            $baseDailyKontak  = (int) ceil($target->target_kontak / $totalDays);
            $baseDailyLunas   = (int) ceil($target->target_lunas / $totalDays);
        }

        // ── Elapsed Days (days fully completed before today) ────────
        $elapsedDays = max(0, $start->copy()->startOfDay()->diffInDays($now->copy()->startOfDay()));

        // ── Expected achievement up to yesterday ────────────────────
        $expectedKontakUpToYesterday = $baseDailyKontak * $elapsedDays;
        $expectedLunasUpToYesterday  = $baseDailyLunas  * $elapsedDays;

        // ── Actual achievement up to yesterday ──────────────────────
        $yesterday = $now->copy()->subDay()->endOfDay();
        $achievedYesterday = $this->getAchievementInRange($sales, $target, $start, $yesterday, $taId);

        // ── Deficit from yesterday (carry-over, never negative) ─────
        $deficitKontak = max(0, $expectedKontakUpToYesterday - $achievedYesterday['kontak']);
        $deficitLunas  = max(0, $expectedLunasUpToYesterday  - $achievedYesterday['lunas']);

        // ── Target Today = Base Daily + Deficit carry-over ──────────
        $targetTodayKontak = $baseDailyKontak + $deficitKontak;
        $targetTodayLunas  = $baseDailyLunas  + $deficitLunas;

        // ── Full period achievement (up to now) ─────────────────────
        $achievedTotal = $this->getAchievementInRange($sales, $target, $start, $now, $taId);

        // ── Remaining Days ──────────────────────────────────────────
        $remainingDays = max(0, (int) $now->copy()->startOfDay()->diffInDays($end->copy()->startOfDay(), false));

        // ── Remaining target in period ──────────────────────────────
        $remainingKontak = max(0, $target->target_kontak - $achievedTotal['kontak']);
        $remainingLunas  = max(0, $target->target_lunas  - $achievedTotal['lunas']);

        // ── Running Daily Target = remaining / remaining days ───────
        $runningDailyKontak = $remainingDays > 0
            ? (int) ceil($remainingKontak / $remainingDays)
            : $remainingKontak;

        $runningDailyLunas = $remainingDays > 0
            ? (int) ceil($remainingLunas / $remainingDays)
            : $remainingLunas;

        // ── % Achievement ───────────────────────────────────────────
        $pctKontak = $target->target_kontak > 0
            ? round(($achievedTotal['kontak'] / $target->target_kontak) * 100, 1)
            : ($achievedTotal['kontak'] > 0 ? 100.0 : 0.0);

        $pctLunas = $target->target_lunas > 0
            ? round(($achievedTotal['lunas'] / $target->target_lunas) * 100, 1)
            : ($achievedTotal['lunas'] > 0 ? 100.0 : 0.0);

        // ── Color Status (based on kontak % as primary metric) ──────
        $colorKontak = $this->colorStatus($pctKontak);
        $colorLunas  = $this->colorStatus($pctLunas);

        return [
            // Period info
            'tanggal_mulai'         => $target->tanggal_mulai,
            'tanggal_selesai'       => $target->tanggal_selesai,
            'tipe_periode'          => $target->tipe_periode,
            'total_days'            => $totalDays,
            'elapsed_days'          => $elapsedDays,
            'remaining_days'        => $remainingDays,
            'period_label'          => $target->tanggal_mulai->translatedFormat('d M') . ' – ' . $target->tanggal_selesai->translatedFormat('d M Y'),
            'academic_year_id'      => $taId,

            // Targets
            'target_kontak'         => $target->target_kontak,
            'target_lunas'          => $target->target_lunas,
            'target_formulir'       => $target->target_formulir,
            'target_followup'       => $target->target_followup,

            // Base Daily
            'base_daily_kontak'     => $baseDailyKontak,
            'base_daily_lunas'      => $baseDailyLunas,

            // Deficit from yesterday
            'deficit_kontak'        => $deficitKontak,
            'deficit_lunas'         => $deficitLunas,

            // Target Today (base + deficit)
            'target_today_kontak'   => $targetTodayKontak,
            'target_today_lunas'    => $targetTodayLunas,

            // Achievement (period-to-date)
            'achievement_kontak'    => $achievedTotal['kontak'],
            'achievement_lunas'     => $achievedTotal['lunas'],
            'achievement_formulir'  => $achievedTotal['formulir'],
            'achievement_followup'  => $achievedTotal['followup'],

            // Remaining
            'remaining_kontak'      => $remainingKontak,
            'remaining_lunas'       => $remainingLunas,

            // Running Daily Target (NOT the same as base_daily)
            'running_daily_kontak'  => $runningDailyKontak,
            'running_daily_lunas'   => $runningDailyLunas,

            // % Achievement (capped at 100 for display, raw for logic)
            'pct_kontak'            => min(100.0, $pctKontak),
            'pct_lunas'             => min(100.0, $pctLunas),
            'pct_kontak_raw'        => $pctKontak,
            'pct_lunas_raw'         => $pctLunas,

            // Color/Status
            'color_kontak'          => $colorKontak,
            'color_lunas'           => $colorLunas,
            'color_status'          => $colorLunas, // primary color = lunas as closing metric
        ];
    }

    /**
     * Compute metrics filtered to TODAY only.
     */
    public function computeDaily(User $sales, Target $target, ?Carbon $asOf = null, ?int $taId = null): array
    {
        $now   = $asOf ?? Carbon::now();
        $taId  = $taId ?? AkademikService::getAktifId();
        $start = $now->copy()->startOfDay();
        $end   = $now->copy()->endOfDay();

        $achieved = $this->getAchievementInRange($sales, $target, $start, $end, $taId);

        return $this->buildFilteredResult('Harian', $target, $achieved, $now, $taId);
    }

    /**
     * Compute metrics for the current ISO week (Monday–Sunday).
     */
    public function computeWeekly(User $sales, Target $target, ?Carbon $asOf = null, ?int $taId = null): array
    {
        $now   = $asOf ?? Carbon::now();
        $taId  = $taId ?? AkademikService::getAktifId();
        $start = $now->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
        $end   = $now->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();

        $achieved = $this->getAchievementInRange($sales, $target, $start, $end, $taId);

        return $this->buildFilteredResult('Mingguan', $target, $achieved, $now, $taId);
    }

    /**
     * Compute metrics for the full target period (monthly view).
     */
    public function computeMonthly(User $sales, Target $target, ?Carbon $asOf = null, ?int $taId = null): array
    {
        $now   = $asOf ?? Carbon::now();
        $taId  = $taId ?? AkademikService::getAktifId();
        $start = $target->tanggal_mulai->copy()->startOfDay();
        $end   = $now->copy()->endOfDay(); // up to now

        $achieved = $this->getAchievementInRange($sales, $target, $start, $end, $taId);

        return $this->buildFilteredResult('Bulanan', $target, $achieved, $now, $taId);
    }

    /**
     * Compute metrics for the full active academic year.
     * Achievement is all records within the academic year, not just the period.
     */
    public function computeAnnual(User $sales, ?int $taId = null): array
    {
        $taId = $taId ?? AkademikService::getAktifId();

        $kontak = Prospek::where('sales_id', $sales->id)
            ->when($taId, fn($q) => $q->where('academic_year_id', $taId))
            ->count();

        $lunas = Prospek::where('sales_id', $sales->id)
            ->where('status', 'LUNAS')
            ->when($taId, fn($q) => $q->where('academic_year_id', $taId))
            ->count();

        $formulir = Transaksi::whereHas('prospek', fn($q) => $q->where('sales_id', $sales->id))
            ->where('jenis', 'Beli Formulir')
            ->when($taId, fn($q) => $q->whereHas('prospek', fn($p) => $p->where('academic_year_id', $taId)))
            ->count();

        $followup = FollowUp::where('user_id', $sales->id)->count();

        return [
            'filter'              => 'Annual',
            'achievement_kontak'  => $kontak,
            'achievement_lunas'   => $lunas,
            'achievement_formulir'=> $formulir,
            'achievement_followup'=> $followup,
            'academic_year_id'    => $taId,
        ];
    }

    // ──────────────────────────────────────────────────────────────
    // Roll-up: Sales → SPV → HM → Global
    // ──────────────────────────────────────────────────────────────

    /**
     * Aggregate metrics for all Sales under an SPV.
     * Each Prospek is counted exactly once (via sales_id), preventing double-count.
     *
     * @param  User    $spv
     * @param  Carbon|null $asOf
     * @param  int|null    $taId
     * @return array
     */
    public function rollUpForSpv(User $spv, ?Carbon $asOf = null, ?int $taId = null): array
    {
        $taId  = $taId ?? AkademikService::getAktifId();
        $now   = $asOf ?? Carbon::now();
        $salesIds = collect($spv->teamMemberIds())
            ->filter(fn($id) => User::find($id)?->role === 'Sales')
            ->values()
            ->toArray();

        return $this->aggregateForSalesIds($salesIds, $taId, $now, 'SPV:' . $spv->id);
    }

    /**
     * Aggregate metrics for all SPV/Sales under an HM (wilayah-scoped).
     * HM sees all Sales in all their wilayahs.
     * Avoids double-count by aggregating at Sales level only.
     *
     * @param  User    $hm
     * @param  Carbon|null $asOf
     * @param  int|null    $taId
     * @return array
     */
    public function rollUpForHm(User $hm, ?Carbon $asOf = null, ?int $taId = null): array
    {
        $taId  = $taId ?? AkademikService::getAktifId();
        $now   = $asOf ?? Carbon::now();

        $query = User::where('role', 'Sales');
        if ($hm->wilayah_id) {
            $query->whereIn('id', $hm->hmMemberIds());
        }
        $salesIds = $query->pluck('id')->toArray();

        return $this->aggregateForSalesIds($salesIds, $taId, $now, 'HM:' . $hm->id);
    }

    /**
     * Global aggregate across all Sales users and all wilayahs.
     */
    public function rollUpGlobal(?Carbon $asOf = null, ?int $taId = null): array
    {
        $taId    = $taId ?? AkademikService::getAktifId();
        $now     = $asOf ?? Carbon::now();
        $salesIds = User::where('role', 'Sales')->pluck('id')->toArray();

        return $this->aggregateForSalesIds($salesIds, $taId, $now, 'Global');
    }

    /**
     * Core aggregation: sums achievement over a list of sales IDs.
     * Each record is attributed to exactly one Sales user — no double-count.
     */
    private function aggregateForSalesIds(array $salesIds, ?int $taId, Carbon $now, string $scope): array
    {
        if (empty($salesIds)) {
            return $this->emptyRollup($scope);
        }

        // Kontak = distinct Prospeks created by these Sales in this TA
        $kontak = Prospek::whereIn('sales_id', $salesIds)
            ->when($taId, fn($q) => $q->where('academic_year_id', $taId))
            ->count();

        // Lunas = Prospeks with status LUNAS by these Sales in this TA
        $lunas = Prospek::whereIn('sales_id', $salesIds)
            ->where('status', 'LUNAS')
            ->when($taId, fn($q) => $q->where('academic_year_id', $taId))
            ->count();

        // Formulir = distinct Prospeks with Beli Formulir transaction
        $formulirProspekIds = Prospek::whereIn('sales_id', $salesIds)
            ->when($taId, fn($q) => $q->where('academic_year_id', $taId))
            ->pluck('id');

        $formulir = Transaksi::whereIn('prospek_id', $formulirProspekIds)
            ->where('jenis', 'Beli Formulir')
            ->distinct('prospek_id')
            ->count('prospek_id');

        // Target aggregate (sum of active targets in this TA)
        $targetKontak = Target::whereIn('sales_id', $salesIds)
            ->where('status', 'Aktif')
            ->when($taId, fn($q) => $q->where('academic_year_id', $taId))
            ->sum('target_kontak');

        $targetLunas = Target::whereIn('sales_id', $salesIds)
            ->where('status', 'Aktif')
            ->when($taId, fn($q) => $q->where('academic_year_id', $taId))
            ->sum('target_lunas');

        $pctKontak = $targetKontak > 0 ? round(($kontak / $targetKontak) * 100, 1) : 0;
        $pctLunas  = $targetLunas  > 0 ? round(($lunas  / $targetLunas)  * 100, 1) : 0;

        return [
            'scope'               => $scope,
            'sales_count'         => count($salesIds),
            'target_kontak'       => (int) $targetKontak,
            'target_lunas'        => (int) $targetLunas,
            'achievement_kontak'  => $kontak,
            'achievement_lunas'   => $lunas,
            'achievement_formulir'=> $formulir,
            'deficit_kontak'      => max(0, (int)$targetKontak - $kontak),
            'deficit_lunas'       => max(0, (int)$targetLunas  - $lunas),
            'pct_kontak'          => min(100.0, $pctKontak),
            'pct_lunas'           => min(100.0, $pctLunas),
            'color_status'        => $this->colorStatus($pctLunas),
            'academic_year_id'    => $taId,
        ];
    }

    private function emptyRollup(string $scope): array
    {
        return [
            'scope'               => $scope,
            'sales_count'         => 0,
            'target_kontak'       => 0,
            'target_lunas'        => 0,
            'achievement_kontak'  => 0,
            'achievement_lunas'   => 0,
            'achievement_formulir'=> 0,
            'deficit_kontak'      => 0,
            'deficit_lunas'       => 0,
            'pct_kontak'          => 0.0,
            'pct_lunas'           => 0.0,
            'color_status'        => 'red',
            'academic_year_id'    => null,
        ];
    }

    // ──────────────────────────────────────────────────────────────
    // Achievement Helpers (TA-scoped)
    // ──────────────────────────────────────────────────────────────

    /**
     * Count achievement for a Sales user within a date range, scoped to TA.
     *
     * @return array{kontak: int, lunas: int, formulir: int, followup: int}
     */
    public function getAchievementInRange(User $sales, Target $target, Carbon $from, Carbon $to, ?int $taId = null): array
    {
        $taId = $taId ?? AkademikService::getAktifId();

        // Kontak: new prospects created within range, scoped to TA
        $kontak = Prospek::where('sales_id', $sales->id)
            ->when($taId, fn($q) => $q->where('academic_year_id', $taId))
            ->whereBetween('created_at', [$from, $to])
            ->count();

        // Lunas: prospects that became LUNAS within range (by status update time)
        $lunas = Prospek::where('sales_id', $sales->id)
            ->where('status', 'LUNAS')
            ->when($taId, fn($q) => $q->where('academic_year_id', $taId))
            ->whereBetween('updated_at', [$from, $to])
            ->count();

        // Formulir: Beli Formulir transactions within range
        $formulirProspekIds = Prospek::where('sales_id', $sales->id)
            ->when($taId, fn($q) => $q->where('academic_year_id', $taId))
            ->pluck('id');

        $formulir = Transaksi::whereIn('prospek_id', $formulirProspekIds)
            ->where('jenis', 'Beli Formulir')
            ->whereBetween('tanggal', [$from, $to])
            ->distinct('prospek_id')
            ->count('prospek_id');

        // Follow-up: total follow-ups by this user within range
        $followup = FollowUp::where('user_id', $sales->id)
            ->whereBetween('tanggal', [$from, $to])
            ->count();

        return compact('kontak', 'lunas', 'formulir', 'followup');
    }

    // ──────────────────────────────────────────────────────────────
    // Internal Helpers
    // ──────────────────────────────────────────────────────────────

    /**
     * Determine color status based on percentage achievement.
     *
     * IMPLEMENTOR DECISION:
     * PRD specifies Red/Yellow/Green concept without numeric thresholds.
     * Yellow threshold = YELLOW_THRESHOLD constant (currently 80%).
     */
    public function colorStatus(float $pct): string
    {
        if ($pct >= 100.0) {
            return 'green';
        }
        if ($pct >= self::YELLOW_THRESHOLD) {
            return 'yellow';
        }
        return 'red';
    }

    private function buildFilteredResult(string $filter, Target $target, array $achieved, Carbon $now, ?int $taId): array
    {
        $pctKontak = $target->target_kontak > 0
            ? round(($achieved['kontak'] / $target->target_kontak) * 100, 1)
            : 0.0;

        $pctLunas = $target->target_lunas > 0
            ? round(($achieved['lunas'] / $target->target_lunas) * 100, 1)
            : 0.0;

        return [
            'filter'              => $filter,
            'achievement_kontak'  => $achieved['kontak'],
            'achievement_lunas'   => $achieved['lunas'],
            'achievement_formulir'=> $achieved['formulir'],
            'achievement_followup'=> $achieved['followup'],
            'pct_kontak'          => min(100.0, $pctKontak),
            'pct_lunas'           => min(100.0, $pctLunas),
            'color_status'        => $this->colorStatus($pctLunas),
            'academic_year_id'    => $taId,
            'as_of'               => $now->toDateTimeString(),
        ];
    }
}
