<?php

namespace Tests\Feature;

use App\Models\FollowUp;
use App\Models\Prodi;
use App\Models\Prospek;
use App\Models\Sekolah;
use App\Models\TahunAkademik;
use App\Models\Target;
use App\Models\Transaksi;
use App\Models\User;
use App\Models\Wilayah;
use App\Services\AkademikService;
use App\Services\SalesTargetService;
use App\Services\TargetMetricsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Phase 7 — Target & Deficit Dashboard Tests (H-X).
 *
 * Naming follows PRD specification:
 *   H. base daily + previous deficit → Target Today correct
 *   I. deficit rollover between days
 *   J. target met → deficit becomes 0
 *   K. over-achievement → deficit does not become negative
 *   L. Sales A does not affect Sales B deficit
 *   M. active TA does not mix with historical TA
 *   N. Sales → SPV roll-up correct
 *   O. SPV → HM roll-up correct
 *   P. HM → Global roll-up correct
 *   Q. no double counting
 *   R. daily filter correct
 *   S. weekly filter correct
 *   T. monthly filter correct
 *   U. annual/academic-year filter correct
 *   V. % achievement correct
 *   W. remaining days correct
 *   X. running daily target correct
 */
class TargetMetricsTest extends TestCase
{
    use DatabaseTransactions;

    private TargetMetricsService $metrics;
    private SalesTargetService   $targetSvc;
    private TahunAkademik        $ta;
    private User                 $sales;
    private User                 $salesB;
    private User                 $spv;
    private User                 $hm;
    private Prodi                $prodi;
    private Target               $target;

    protected function setUp(): void
    {
        parent::setUp();
        AkademikService::flushCache();
        TahunAkademik::query()->update(['status' => 'Non-Aktif']);
        AkademikService::flushCache();

        $this->metrics   = app(TargetMetricsService::class);
        $this->targetSvc = app(SalesTargetService::class);

        $this->ta = TahunAkademik::create(['nama' => '2027/2028-TMT', 'status' => 'Aktif']);
        AkademikService::flushCache();

        $this->prodi = Prodi::first() ?? Prodi::create([
            'kode' => 'TI-' . uniqid(), 'nama' => 'Teknik Informatika', 'jenjang' => 'S1',
            'fakultas' => 'FTIK', 'status' => 'Aktif',
        ]);

        $this->hm    = User::factory()->create(['role' => 'HM']);
        $this->spv   = User::factory()->create(['role' => 'SPV', 'supervisor_id' => null]);
        $this->sales = User::factory()->create(['role' => 'Sales', 'supervisor_id' => $this->spv->id]);
        $this->salesB = User::factory()->create(['role' => 'Sales', 'supervisor_id' => $this->spv->id]);

        // Active monthly target: 30 kontak, 5 lunas, covering today
        $this->target = Target::create([
            'sales_id'         => $this->sales->id,
            'allocated_by'     => $this->spv->id,
            'tipe_periode'     => 'Bulanan',
            'tanggal_mulai'    => Carbon::now()->startOfMonth()->toDateString(),
            'tanggal_selesai'  => Carbon::now()->endOfMonth()->toDateString(),
            'target_kontak'    => 30,
            'target_lunas'     => 5,
            'target_formulir'  => 3,
            'target_followup'  => 20,
            'status'           => 'Aktif',
            'academic_year_id' => $this->ta->id,
        ]);
    }

    /** Helper: create a Prospek for $sales in this TA */
    private function makeProspek(User $sales, string $status = 'BARU', ?TahunAkademik $ta = null): Prospek
    {
        $ta = $ta ?? $this->ta;
        return Prospek::create([
            'name'             => 'P-' . uniqid(),
            'type'             => 'Individu',
            'source'           => 'Website',
            'status'           => $status,
            'sales_id'         => $sales->id,
            'owner_id'         => $sales->id,
            'prodi_id'         => $this->prodi->id,
            'academic_year_id' => $ta->id,
        ]);
    }

    /** Helper: make a target for another Sales */
    private function makeTarget(User $sales, array $overrides = []): Target
    {
        return Target::create(array_merge([
            'sales_id'         => $sales->id,
            'allocated_by'     => $this->spv->id,
            'tipe_periode'     => 'Bulanan',
            'tanggal_mulai'    => Carbon::now()->startOfMonth()->toDateString(),
            'tanggal_selesai'  => Carbon::now()->endOfMonth()->toDateString(),
            'target_kontak'    => 30,
            'target_lunas'     => 5,
            'target_formulir'  => 3,
            'target_followup'  => 20,
            'status'           => 'Aktif',
            'academic_year_id' => $this->ta->id,
        ], $overrides));
    }

    // ──────────────────────────────────────────────────────────────
    // H. Base daily + previous deficit → Target Today correct
    // ──────────────────────────────────────────────────────────────
    public function test_h_target_today_equals_base_daily_plus_previous_deficit(): void
    {
        // Period: 10 days total, target_kontak = 10 → base daily = 1
        $start = Carbon::now()->subDays(2)->startOfDay();
        $end   = $start->copy()->addDays(9); // 10-day period
        $target = Target::create([
            'sales_id'         => $this->sales->id,
            'allocated_by'     => $this->spv->id,
            'tipe_periode'     => 'Bulanan',
            'tanggal_mulai'    => $start->toDateString(),
            'tanggal_selesai'  => $end->toDateString(),
            'target_kontak'    => 10,
            'target_lunas'     => 0,
            'target_formulir'  => 0,
            'target_followup'  => 0,
            'status'           => 'Aktif',
            'academic_year_id' => $this->ta->id,
        ]);

        // Day 1 (yesterday-1): base=1, achievement=0 → deficit from day1 = 1
        // Day 2 (yesterday):   base=1, expected cumulative=2, achievement=0 → deficit=2
        // Today base daily = 1 → Target Today = 1 + 2 = 3

        $now     = Carbon::now();
        $metrics = $this->metrics->computeMetrics($this->sales, $target, $now, $this->ta->id);

        $this->assertEquals(1, $metrics['base_daily_kontak'], 'Base daily harus 1 (10/10)');

        // 2 days elapsed (subDays(2) = start was 2 days ago → elapsed = 2)
        $this->assertEquals(2, $metrics['elapsed_days'], 'Elapsed days harus 2');

        // No achievement → deficit = 2 * 1 = 2
        $this->assertEquals(2, $metrics['deficit_kontak'], 'Deficit harus 2 (2 hari × 1 base, 0 achieved)');

        // Target today = 1 + 2 = 3
        $this->assertEquals(3, $metrics['target_today_kontak'], 'Target Today = 1 (base) + 2 (deficit) = 3');
    }

    // ──────────────────────────────────────────────────────────────
    // I. Deficit rollover between days
    // ──────────────────────────────────────────────────────────────
    public function test_i_deficit_carries_over_from_previous_days(): void
    {
        // target=10, achieved=6 → deficit=4
        // next day base=10 → Target Today = 14
        $start = Carbon::now()->subDays(1)->startOfDay();
        $end   = Carbon::now()->addDays(8);

        $target = Target::create([
            'sales_id'         => $this->sales->id,
            'allocated_by'     => $this->spv->id,
            'tipe_periode'     => 'Harian',   // daily = target_kontak exactly
            'tanggal_mulai'    => $start->toDateString(),
            'tanggal_selesai'  => $end->toDateString(),
            'target_kontak'    => 10,
            'target_lunas'     => 0,
            'target_formulir'  => 0,
            'target_followup'  => 0,
            'status'           => 'Aktif',
            'academic_year_id' => $this->ta->id,
        ]);

        // Create 6 prospects yesterday
        for ($i = 0; $i < 6; $i++) {
            $p = $this->makeProspek($this->sales);
            // Backdate created_at to yesterday
            DB::table('prospeks')->where('id', $p->id)->update([
                'created_at' => $start->copy()->addHours(9 + $i),
                'updated_at' => $start->copy()->addHours(9 + $i),
            ]);
        }

        $now     = Carbon::now();
        $metrics = $this->metrics->computeMetrics($this->sales, $target, $now, $this->ta->id);

        // Harian target: base daily = 10 (as-is, not divided)
        $this->assertEquals(10, $metrics['base_daily_kontak']);
        // 1 day elapsed → expected 10, achieved 6 → deficit 4
        $this->assertEquals(4, $metrics['deficit_kontak'],
            'Deficit harus 4 (10 expected - 6 achieved)');
        // Target today = 10 + 4 = 14
        $this->assertEquals(14, $metrics['target_today_kontak'],
            'Target Today = 10 (base) + 4 (deficit carry-over) = 14');
    }

    // ──────────────────────────────────────────────────────────────
    // J. Achievement meets target → deficit becomes 0
    // ──────────────────────────────────────────────────────────────
    public function test_j_achievement_meets_target_deficit_becomes_zero(): void
    {
        $start = Carbon::now()->subDays(1)->startOfDay();
        $end   = Carbon::now()->addDays(8);

        $target = Target::create([
            'sales_id'         => $this->sales->id,
            'allocated_by'     => $this->spv->id,
            'tipe_periode'     => 'Harian',
            'tanggal_mulai'    => $start->toDateString(),
            'tanggal_selesai'  => $end->toDateString(),
            'target_kontak'    => 10,
            'target_lunas'     => 0,
            'target_formulir'  => 0,
            'target_followup'  => 0,
            'status'           => 'Aktif',
            'academic_year_id' => $this->ta->id,
        ]);

        // Create exactly 10 prospects yesterday → meets target
        for ($i = 0; $i < 10; $i++) {
            $p = $this->makeProspek($this->sales);
            DB::table('prospeks')->where('id', $p->id)->update([
                'created_at' => $start->copy()->addHours(9 + $i),
                'updated_at' => $start->copy()->addHours(9 + $i),
            ]);
        }

        $metrics = $this->metrics->computeMetrics($this->sales, $target, Carbon::now(), $this->ta->id);

        $this->assertEquals(0, $metrics['deficit_kontak'],
            'Deficit harus 0 ketika achievement memenuhi target');
        $this->assertEquals(10, $metrics['target_today_kontak'],
            'Target Today = base (10) + 0 deficit = 10');
    }

    // ──────────────────────────────────────────────────────────────
    // K. Over-achievement → deficit does not become negative
    // ──────────────────────────────────────────────────────────────
    public function test_k_over_achievement_deficit_never_negative(): void
    {
        $start = Carbon::now()->subDays(1)->startOfDay();
        $end   = Carbon::now()->addDays(8);

        $target = Target::create([
            'sales_id'         => $this->sales->id,
            'allocated_by'     => $this->spv->id,
            'tipe_periode'     => 'Harian',
            'tanggal_mulai'    => $start->toDateString(),
            'tanggal_selesai'  => $end->toDateString(),
            'target_kontak'    => 10,
            'target_lunas'     => 0,
            'target_formulir'  => 0,
            'target_followup'  => 0,
            'status'           => 'Aktif',
            'academic_year_id' => $this->ta->id,
        ]);

        // Create 15 prospects yesterday → over-achievement
        for ($i = 0; $i < 15; $i++) {
            $p = $this->makeProspek($this->sales);
            DB::table('prospeks')->where('id', $p->id)->update([
                'created_at' => $start->copy()->addHours(9 + $i),
                'updated_at' => $start->copy()->addHours(9 + $i),
            ]);
        }

        $metrics = $this->metrics->computeMetrics($this->sales, $target, Carbon::now(), $this->ta->id);

        $this->assertEquals(0, $metrics['deficit_kontak'],
            'Deficit harus 0 (tidak boleh negatif ketika over-achievement)');
        $this->assertGreaterThanOrEqual(0, $metrics['deficit_kontak']);
    }

    // ──────────────────────────────────────────────────────────────
    // L. Sales A does not affect Sales B deficit
    // ──────────────────────────────────────────────────────────────
    public function test_l_sales_a_achievement_does_not_affect_sales_b_deficit(): void
    {
        $start = Carbon::now()->subDays(1)->startOfDay();
        $end   = Carbon::now()->addDays(8);

        // Both Sales A and B have same target
        $targetA = Target::create([
            'sales_id' => $this->sales->id, 'allocated_by' => $this->spv->id,
            'tipe_periode' => 'Harian', 'tanggal_mulai' => $start->toDateString(),
            'tanggal_selesai' => $end->toDateString(), 'target_kontak' => 10,
            'target_lunas' => 0, 'target_formulir' => 0, 'target_followup' => 0,
            'status' => 'Aktif', 'academic_year_id' => $this->ta->id,
        ]);

        $targetB = Target::create([
            'sales_id' => $this->salesB->id, 'allocated_by' => $this->spv->id,
            'tipe_periode' => 'Harian', 'tanggal_mulai' => $start->toDateString(),
            'tanggal_selesai' => $end->toDateString(), 'target_kontak' => 10,
            'target_lunas' => 0, 'target_formulir' => 0, 'target_followup' => 0,
            'status' => 'Aktif', 'academic_year_id' => $this->ta->id,
        ]);

        // Sales A achieves 10 (meets target)
        for ($i = 0; $i < 10; $i++) {
            $p = $this->makeProspek($this->sales);
            DB::table('prospeks')->where('id', $p->id)->update([
                'created_at' => $start->copy()->addHours(9 + $i),
                'updated_at' => $start->copy()->addHours(9 + $i),
            ]);
        }
        // Sales B achieves 0

        $metricsA = $this->metrics->computeMetrics($this->sales, $targetA, Carbon::now(), $this->ta->id);
        $metricsB = $this->metrics->computeMetrics($this->salesB, $targetB, Carbon::now(), $this->ta->id);

        $this->assertEquals(0, $metricsA['deficit_kontak'], 'Sales A: deficit harus 0');
        $this->assertEquals(10, $metricsB['deficit_kontak'], 'Sales B: deficit harus 10 (tidak terpengaruh Sales A)');
    }

    // ──────────────────────────────────────────────────────────────
    // M. Active TA does not mix with historical TA
    // ──────────────────────────────────────────────────────────────
    public function test_m_active_ta_not_mixed_with_historical_ta(): void
    {
        // Old TA — create a Prospek with old TA id manually
        $taOld = TahunAkademik::create(['nama' => '2025/2026-TMT', 'status' => 'Non-Aktif']);

        $oldProspek = $this->makeProspek($this->sales, 'BARU', $taOld);
        // Verify old prospek has old TA id
        $this->assertEquals($taOld->id, $oldProspek->academic_year_id);

        // computeMetrics scoped to active TA → should NOT count old TA prospek
        $metrics = $this->metrics->computeMetrics($this->sales, $this->target, Carbon::now(), $this->ta->id);

        // No new prospeks created in active TA → achievement_kontak = 0
        $this->assertEquals(0, $metrics['achievement_kontak'],
            'Achievement harus 0 — prospek dari TA lama tidak boleh ikut terhitung');
    }

    // ──────────────────────────────────────────────────────────────
    // N. Sales → SPV roll-up correct
    // ──────────────────────────────────────────────────────────────
    public function test_n_sales_to_spv_rollup_correct(): void
    {
        // 3 prospects for Sales A (in active TA)
        for ($i = 0; $i < 3; $i++) {
            $this->makeProspek($this->sales, 'BARU');
        }
        // 2 prospects for Sales B (in active TA)
        for ($i = 0; $i < 2; $i++) {
            $this->makeProspek($this->salesB, 'BARU');
        }

        $rollup = $this->metrics->rollUpForSpv($this->spv, null, $this->ta->id);

        $this->assertEquals(5, $rollup['achievement_kontak'],
            'SPV rollup harus menjumlahkan kontak semua Sales di bawahnya: 3+2=5');
    }

    // ──────────────────────────────────────────────────────────────
    // O. SPV → HM roll-up correct
    // ──────────────────────────────────────────────────────────────
    public function test_o_spv_to_hm_rollup_correct(): void
    {
        // SPV1 has Sales A (3 prospects)
        for ($i = 0; $i < 3; $i++) {
            $this->makeProspek($this->sales, 'BARU');
        }

        // Create another SPV with another Sales
        $spv2   = User::factory()->create(['role' => 'SPV']);
        $sales3 = User::factory()->create(['role' => 'Sales', 'supervisor_id' => $spv2->id]);
        for ($i = 0; $i < 4; $i++) {
            $this->makeProspek($sales3, 'BARU');
        }

        // HM rollup = all Sales across all SPVs = 3 + 4 = 7
        // (salesB has 0 prospects, but is counted in the team)
        $rollup = $this->metrics->rollUpForHm($this->hm, null, $this->ta->id);

        $this->assertGreaterThanOrEqual(7, $rollup['achievement_kontak'],
            'HM rollup harus mencakup semua Sales di bawah semua SPV: minimal 3+4=7');
    }

    // ──────────────────────────────────────────────────────────────
    // P. HM → Global roll-up correct
    // ──────────────────────────────────────────────────────────────
    public function test_p_hm_to_global_rollup_correct(): void
    {
        // 5 prospects in active TA
        for ($i = 0; $i < 5; $i++) {
            $this->makeProspek($this->sales, 'BARU');
        }

        $hmRollup     = $this->metrics->rollUpForHm($this->hm, null, $this->ta->id);
        $globalRollup = $this->metrics->rollUpGlobal(null, $this->ta->id);

        // Global >= HM (global = all Sales, HM also all Sales → should be equal)
        $this->assertEquals(
            $hmRollup['achievement_kontak'],
            $globalRollup['achievement_kontak'],
            'Global rollup harus sama dengan HM rollup ketika HM = semua Sales'
        );
        $this->assertGreaterThanOrEqual(5, $globalRollup['achievement_kontak']);
    }

    // ──────────────────────────────────────────────────────────────
    // Q. No double counting
    // ──────────────────────────────────────────────────────────────
    public function test_q_no_double_counting_in_rollup(): void
    {
        // Sales A and Sales B both under same SPV
        for ($i = 0; $i < 3; $i++) {
            $this->makeProspek($this->sales, 'BARU');
        }
        for ($i = 0; $i < 2; $i++) {
            $this->makeProspek($this->salesB, 'BARU');
        }

        $spvRollup    = $this->metrics->rollUpForSpv($this->spv, null, $this->ta->id);
        $globalRollup = $this->metrics->rollUpGlobal(null, $this->ta->id);

        // SPV rollup must = 5 (3 + 2), NOT 10 (double-count)
        $this->assertEquals(5, $spvRollup['achievement_kontak'],
            'SPV rollup = 5, bukan 10 (tidak double-count)');

        // Global must be >= 5 (may include other Sales from previous tests in transaction)
        $this->assertGreaterThanOrEqual(5, $globalRollup['achievement_kontak']);

        // Verify global is exactly sum of unique sales_ids (no duplicate)
        $directCount = Prospek::whereIn('sales_id', User::where('role', 'Sales')->pluck('id'))
            ->where('academic_year_id', $this->ta->id)
            ->count();
        $this->assertEquals($directCount, $globalRollup['achievement_kontak'],
            'Global rollup harus sama dengan direct count (tidak ada double-count)');
    }

    // ──────────────────────────────────────────────────────────────
    // R. Daily filter correct
    // ──────────────────────────────────────────────────────────────
    public function test_r_daily_filter_counts_only_today(): void
    {
        // Create 2 today, 3 yesterday
        for ($i = 0; $i < 2; $i++) {
            $this->makeProspek($this->sales);
            // leave created_at as now()
        }
        for ($i = 0; $i < 3; $i++) {
            $p = $this->makeProspek($this->sales);
            DB::table('prospeks')->where('id', $p->id)->update([
                'created_at' => Carbon::yesterday()->addHours(9),
                'updated_at' => Carbon::yesterday()->addHours(9),
            ]);
        }

        $daily = $this->metrics->computeDaily($this->sales, $this->target, Carbon::now(), $this->ta->id);

        $this->assertEquals(2, $daily['achievement_kontak'],
            'Daily filter harus hanya menghitung prospek hari ini: 2');
    }

    // ──────────────────────────────────────────────────────────────
    // S. Weekly filter correct
    // ──────────────────────────────────────────────────────────────
    public function test_s_weekly_filter_counts_current_week(): void
    {
        // 3 this week, 2 last week
        for ($i = 0; $i < 3; $i++) {
            $this->makeProspek($this->sales);
        }
        for ($i = 0; $i < 2; $i++) {
            $p = $this->makeProspek($this->sales);
            DB::table('prospeks')->where('id', $p->id)->update([
                'created_at' => Carbon::now()->subWeek()->addHours(9),
                'updated_at' => Carbon::now()->subWeek()->addHours(9),
            ]);
        }

        $weekly = $this->metrics->computeWeekly($this->sales, $this->target, Carbon::now(), $this->ta->id);

        $this->assertEquals(3, $weekly['achievement_kontak'],
            'Weekly filter harus hanya menghitung prospek minggu ini: 3');
    }

    // ──────────────────────────────────────────────────────────────
    // T. Monthly filter correct
    // ──────────────────────────────────────────────────────────────
    public function test_t_monthly_filter_uses_target_period(): void
    {
        // 4 in this month, 2 last month
        for ($i = 0; $i < 4; $i++) {
            $this->makeProspek($this->sales);
        }
        for ($i = 0; $i < 2; $i++) {
            $p = $this->makeProspek($this->sales);
            DB::table('prospeks')->where('id', $p->id)->update([
                'created_at' => Carbon::now()->subMonth()->addHours(9),
                'updated_at' => Carbon::now()->subMonth()->addHours(9),
            ]);
        }

        // computeMonthly uses target period (tanggal_mulai = startOfMonth)
        $monthly = $this->metrics->computeMonthly($this->sales, $this->target, Carbon::now(), $this->ta->id);

        $this->assertEquals(4, $monthly['achievement_kontak'],
            'Monthly filter harus hanya menghitung prospek dalam periode target: 4');
    }

    // ──────────────────────────────────────────────────────────────
    // U. Annual/academic-year filter correct
    // ──────────────────────────────────────────────────────────────
    public function test_u_annual_filter_uses_academic_year(): void
    {
        // 5 in active TA
        for ($i = 0; $i < 5; $i++) {
            $this->makeProspek($this->sales);
        }

        // 3 in old TA (manually insert with different academic_year_id)
        $taOld = TahunAkademik::create(['nama' => '2024/2025-TMT', 'status' => 'Non-Aktif']);
        for ($i = 0; $i < 3; $i++) {
            $this->makeProspek($this->sales, 'BARU', $taOld);
        }

        $annual = $this->metrics->computeAnnual($this->sales, $this->ta->id);

        $this->assertEquals(5, $annual['achievement_kontak'],
            'Annual filter harus hanya menghitung prospek dengan active academic_year_id: 5');
    }

    // ──────────────────────────────────────────────────────────────
    // V. % Achievement correct
    // ──────────────────────────────────────────────────────────────
    public function test_v_percentage_achievement_correct(): void
    {
        // target_kontak = 30, create 15 this month → 50%
        for ($i = 0; $i < 15; $i++) {
            $this->makeProspek($this->sales);
        }

        $metrics = $this->metrics->computeMetrics($this->sales, $this->target, Carbon::now(), $this->ta->id);

        $this->assertEquals(50.0, $metrics['pct_kontak'],
            'Percentage achievement harus 50% (15/30)');
        $this->assertEquals('red', $metrics['color_kontak'],
            'Color harus red karena 50% < 80% (YELLOW_THRESHOLD)');
    }

    // ──────────────────────────────────────────────────────────────
    // W. Remaining days correct
    // ──────────────────────────────────────────────────────────────
    public function test_w_remaining_days_correct(): void
    {
        $metrics = $this->metrics->computeMetrics($this->sales, $this->target, Carbon::now(), $this->ta->id);

        $expectedRemaining = (int) Carbon::today()->diffInDays(
            Carbon::now()->endOfMonth()->startOfDay(),
            false
        );
        $expectedRemaining = max(0, $expectedRemaining);

        $this->assertEquals($expectedRemaining, $metrics['remaining_days'],
            'Remaining days harus sesuai dengan hari tersisa sampai akhir periode');
        $this->assertGreaterThanOrEqual(0, $metrics['remaining_days'],
            'Remaining days tidak boleh negatif');
    }

    // ──────────────────────────────────────────────────────────────
    // X. Running daily target correct
    // ──────────────────────────────────────────────────────────────
    public function test_x_running_daily_target_correct(): void
    {
        // Create 10 achievements → remaining = 30-10 = 20
        for ($i = 0; $i < 10; $i++) {
            $this->makeProspek($this->sales);
        }

        $metrics = $this->metrics->computeMetrics($this->sales, $this->target, Carbon::now(), $this->ta->id);

        $remaining      = $metrics['remaining_kontak'];
        $remainingDays  = $metrics['remaining_days'];

        $expectedRunning = $remainingDays > 0
            ? (int) ceil($remaining / $remainingDays)
            : $remaining;

        $this->assertEquals($expectedRunning, $metrics['running_daily_kontak'],
            'Running daily target = sisa target periode / sisa hari (bukan sama dengan base daily)');

        // Running daily target must differ from base_daily if remaining != full target
        if ($remainingDays > 0 && $remaining !== $metrics['target_kontak']) {
            $this->assertNotEquals($metrics['base_daily_kontak'], $metrics['running_daily_kontak'],
                'Running daily target berbeda dari base daily ketika ada achievement');
        }
    }

    // ──────────────────────────────────────────────────────────────
    // Bonus: target_closing bug does not exist
    // ──────────────────────────────────────────────────────────────
    public function test_target_closing_column_does_not_exist_in_db(): void
    {
        $columns = \Illuminate\Support\Facades\Schema::getColumnListing('targets');
        $this->assertNotContains('target_closing', $columns,
            'Kolom target_closing tidak boleh ada di tabel targets');
        $this->assertContains('target_lunas', $columns,
            'Kolom target_lunas harus ada di tabel targets');
    }

    // ──────────────────────────────────────────────────────────────
    // Color status boundaries
    // ──────────────────────────────────────────────────────────────
    public function test_color_status_boundaries(): void
    {
        $this->assertEquals('red',    $this->metrics->colorStatus(0.0));
        $this->assertEquals('red',    $this->metrics->colorStatus(79.9));
        $this->assertEquals('yellow', $this->metrics->colorStatus(80.0));
        $this->assertEquals('yellow', $this->metrics->colorStatus(99.9));
        $this->assertEquals('green',  $this->metrics->colorStatus(100.0));
        $this->assertEquals('green',  $this->metrics->colorStatus(120.0));
    }
}
