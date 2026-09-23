<?php

namespace Tests\Feature;

use App\Models\Prospek;
use App\Models\TahunAkademik;
use App\Models\Target;
use App\Models\Transaksi;
use App\Models\User;
use App\Models\Wilayah;
use App\Services\SalesTargetService;
use App\Services\SpvPerformanceService;
use App\Services\TargetMetricsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TargetCadenceAndDeficitTest extends TestCase
{
    use RefreshDatabase;

    private User $sales;
    private User $spv;
    private User $hm;
    private TahunAkademik $ta;
    private TargetMetricsService $metricsService;
    private SalesTargetService $salesTargetService;
    private SpvPerformanceService $spvService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ta = TahunAkademik::create([
            'nama' => '2027/2028',
            'is_aktif' => true,
            'status' => 'Aktif',
            'tanggal_mulai' => '2027-09-01',
            'tanggal_selesai' => '2028-08-31',
        ]);

        $wilayah = Wilayah::create([
            'kode' => 'W-CRB',
            'nama' => 'Kota Cirebon',
            'level' => 'Kota/Kabupaten',
            'status' => 'Aktif',
        ]);

        $this->hm = User::factory()->create([
            'role' => 'HM',
            'status' => 'Aktif',
            'wilayah_id' => $wilayah->id,
        ]);

        $this->spv = User::factory()->create([
            'role' => 'SPV',
            'status' => 'Aktif',
            'wilayah_id' => $wilayah->id,
            'supervisor_id' => $this->hm->id,
        ]);

        $this->sales = User::factory()->create([
            'role' => 'Sales',
            'status' => 'Aktif',
            'wilayah_id' => $wilayah->id,
            'supervisor_id' => $this->spv->id,
        ]);

        $this->metricsService = app(TargetMetricsService::class);
        $this->salesTargetService = app(SalesTargetService::class);
        $this->spvService = app(SpvPerformanceService::class);
    }

    private function createProspek(array $attributes = []): Prospek
    {
        $createdAt = $attributes['created_at'] ?? null;
        unset($attributes['created_at']);

        $prospek = Prospek::create(array_merge([
            'sales_id'         => $this->sales->id,
            'academic_year_id' => $this->ta->id,
            'tahun_akademik'   => $this->ta->nama,
            'type'             => 'Individu',
            'status'           => 'BARU',
            'name'             => 'Prospek ' . uniqid(),
        ], $attributes));

        if ($createdAt) {
            $prospek->timestamps = false;
            $prospek->created_at = $createdAt;
            $prospek->save();
            $prospek->timestamps = true;
        }

        return $prospek;
    }

    /**
     * Requirement 2: Target Sales — Kontak Baru
     * - Periode Harian
     * - Defisit dibawa ke hari berikutnya
     * - Defisit kontak berdiri sendiri
     */
    public function test_daily_contact_deficit_carries_over_to_next_day(): void
    {
        $start = Carbon::parse('2027-09-01');
        $end = Carbon::parse('2027-09-30'); // 30 days

        $target = Target::create([
            'sales_id'         => $this->sales->id,
            'allocated_by'     => $this->spv->id,
            'tipe_periode'     => 'Bulanan',
            'tanggal_mulai'    => $start->toDateString(),
            'tanggal_selesai'  => $end->toDateString(),
            'target_kontak'    => 300, // 300 / 30 = 10/day
            'target_formulir'  => 60,
            'target_lunas'     => 30,
            'academic_year_id' => $this->ta->id,
            'tahun_akademik'   => $this->ta->nama,
            'status'           => 'Aktif',
        ]);

        // Day 1: Sales generates only 7 contacts (Target was 10, deficit = 3)
        for ($i = 0; $i < 7; $i++) {
            $this->createProspek([
                'name'       => "Prospek Day 1 #{$i}",
                'created_at' => $start->copy()->addHours(10),
            ]);
        }

        // On Day 2 (as of 2027-09-02)
        $day2 = $start->copy()->addDay()->addHours(8);

        $metrics = $this->metricsService->computeMetrics($this->sales, $target, $day2, $this->ta->id);
        $dailyData = $this->salesTargetService->calculateDailyTarget($this->sales, $day2);

        // Base is 10/day
        $this->assertEquals(10, $metrics['base_daily_kontak']);
        // Deficit from yesterday is 3 (10 - 7)
        $this->assertEquals(3, $metrics['deficit_kontak']);
        $this->assertEquals(3, $dailyData['sisa_akumulasi_kontak']);
        // Target today becomes 10 + 3 = 13
        $this->assertEquals(13, $metrics['target_today_kontak']);
        $this->assertEquals(13, $dailyData['target_hari_ini_kontak']);
    }

    /**
     * Requirement 3: Target Sales — Formulir Terjual
     * - Periode Mingguan
     * - Defisit dibawa ke minggu berikutnya
     * - Defisit formulir berdiri sendiri
     */
    public function test_weekly_formulir_deficit_carries_over_to_next_week(): void
    {
        $start = Carbon::parse('2027-09-06'); // Monday
        $end = Carbon::parse('2027-10-03');   // Sunday (4 weeks)

        $target = Target::create([
            'sales_id'         => $this->sales->id,
            'allocated_by'     => $this->spv->id,
            'tipe_periode'     => 'Bulanan',
            'tanggal_mulai'    => $start->toDateString(),
            'tanggal_selesai'  => $end->toDateString(),
            'target_kontak'    => 280,
            'target_formulir'  => 8, // 8 / 4 = 2/week
            'target_lunas'     => 4,
            'academic_year_id' => $this->ta->id,
            'tahun_akademik'   => $this->ta->nama,
            'status'           => 'Aktif',
        ]);

        // Week 1: Only 1 Formulir transaction (Target was 2, deficit = 1)
        $prospek1 = $this->createProspek([
            'name'       => 'Prospek Week 1',
            'created_at' => $start->copy()->addDays(2),
        ]);

        Transaksi::create([
            'prospek_id' => $prospek1->id,
            'jenis'      => 'Beli Formulir',
            'nominal'    => 250000,
            'tanggal'    => $start->copy()->addDays(2)->toDateTimeString(),
        ]);

        // In Week 2 (e.g. 8 days after start: 2027-09-14)
        $week2Now = $start->copy()->addDays(8);

        $metrics = $this->metricsService->computeMetrics($this->sales, $target, $week2Now, $this->ta->id);
        $weeklyFormulirMetrics = $this->metricsService->computeWeeklyFormulirMetrics($this->sales, $target, $week2Now, $this->ta->id);
        $dailyData = $this->salesTargetService->calculateDailyTarget($this->sales, $week2Now);

        // Base is 2/week
        $this->assertEquals(2, $metrics['base_weekly_formulir']);
        $this->assertEquals(2, $weeklyFormulirMetrics['base_weekly_formulir']);

        // Deficit from Week 1 is 1 (2 - 1)
        $this->assertEquals(1, $metrics['deficit_formulir']);
        $this->assertEquals(1, $weeklyFormulirMetrics['deficit_formulir']);
        $this->assertEquals(1, $dailyData['sisa_akumulasi_formulir']);

        // Target this week becomes 2 + 1 = 3
        $this->assertEquals(3, $metrics['target_this_week_formulir']);
        $this->assertEquals(3, $weeklyFormulirMetrics['target_this_week_formulir']);
        $this->assertEquals(3, $dailyData['target_minggu_ini_formulir']);
    }

    /**
     * Requirement 4: Defisit kontak dan defisit formulir WAJIB independen.
     * Tidak boleh saling memengaruhi sama sekali.
     */
    public function test_contact_and_formulir_deficits_are_strictly_independent(): void
    {
        $start = Carbon::parse('2027-09-06'); // Monday
        $end = Carbon::parse('2027-10-03');   // Sunday (28 days = 4 weeks)

        $target = Target::create([
            'sales_id'         => $this->sales->id,
            'allocated_by'     => $this->spv->id,
            'tipe_periode'     => 'Bulanan',
            'tanggal_mulai'    => $start->toDateString(),
            'tanggal_selesai'  => $end->toDateString(),
            'target_kontak'    => 280, // 10/day
            'target_formulir'  => 8,   // 2/week
            'target_lunas'     => 4,
            'academic_year_id' => $this->ta->id,
            'tahun_akademik'   => $this->ta->nama,
            'status'           => 'Aktif',
        ]);

        // Scenario: Day 1 (2027-09-06)
        // Kontak achieved: 7 (deficit = 3 for tomorrow)
        for ($i = 0; $i < 7; $i++) {
            $this->createProspek([
                'name'       => "Prospek #{$i}",
                'created_at' => $start->copy()->addHours(11),
            ]);
        }

        // Formulir achieved in Day 1: 0 (Week 1 is not elapsed yet, elapsed_weeks = 0)
        $day2 = $start->copy()->addDay()->addHours(9); // 2027-09-07

        $metricsDay2 = $this->metricsService->computeMetrics($this->sales, $target, $day2, $this->ta->id);
        $dailyDataDay2 = $this->salesTargetService->calculateDailyTarget($this->sales, $day2);

        // Contact has deficit 3 -> Target is 13
        $this->assertEquals(3, $metricsDay2['deficit_kontak']);
        $this->assertEquals(13, $metricsDay2['target_today_kontak']);

        // Formulir deficit MUST BE 0 (week 1 is currently in progress, not elapsed)
        $this->assertEquals(0, $metricsDay2['deficit_formulir']);
        $this->assertEquals(2, $metricsDay2['target_this_week_formulir']);
        $this->assertEquals(0, $dailyDataDay2['sisa_akumulasi_formulir']);
        $this->assertEquals(2, $dailyDataDay2['target_minggu_ini_formulir']);

        // Now advance to Week 2 Day 1 (Day 8: 2027-09-14)
        // During Week 1, let's say exactly 1 formulir was bought (deficit = 1 for Week 2)
        $p = Prospek::first();
        Transaksi::create([
            'prospek_id' => $p->id,
            'jenis'      => 'Beli Formulir',
            'nominal'    => 250000,
            'tanggal'    => $start->copy()->addDays(3)->toDateTimeString(),
        ]);

        // And in Week 1, total contacts reached 70 (exactly 10/day for 7 days -> 0 contact deficit on day 8)
        for ($i = 7; $i < 70; $i++) {
            $this->createProspek([
                'name'       => "Prospek W1 #{$i}",
                'created_at' => $start->copy()->addDays((int)floor($i / 10))->addHours(12),
            ]);
        }

        $week2Day1 = $start->copy()->addDays(7)->addHours(8); // Day 8: 2027-09-13
        $metricsWeek2 = $this->metricsService->computeMetrics($this->sales, $target, $week2Day1, $this->ta->id);
        $dailyDataWeek2 = $this->salesTargetService->calculateDailyTarget($this->sales, $week2Day1);

        // Contact deficit is 0 because all 70 expected were reached
        $this->assertEquals(0, $metricsWeek2['deficit_kontak']);
        $this->assertEquals(10, $metricsWeek2['target_today_kontak']);
        $this->assertEquals(0, $dailyDataWeek2['sisa_akumulasi_kontak']);
        $this->assertEquals(10, $dailyDataWeek2['target_hari_ini_kontak']);

        // Formulir deficit MUST BE 1 because only 1 of 2 expected was achieved
        $this->assertEquals(1, $metricsWeek2['deficit_formulir']);
        $this->assertEquals(3, $metricsWeek2['target_this_week_formulir']);
        $this->assertEquals(1, $dailyDataWeek2['sisa_akumulasi_formulir']);
        $this->assertEquals(3, $dailyDataWeek2['target_minggu_ini_formulir']);

        // Assert independence: Formulir deficit 1 DID NOT increase contact target
        $this->assertEquals(10, $metricsWeek2['target_today_kontak']);
    }

    /**
     * Requirement 1: Target LUNAS SPV
     * - Target tahunan
     * - Didistribusikan ke target bulanan (rata atau gelombang)
     * - Total target bulanan HARUS SAMA DENGAN target tahunan
     */
    public function test_spv_annual_to_monthly_target_exact_sum_validation(): void
    {
        $annualTarget = Target::create([
            'spv_id'           => $this->spv->id,
            'allocated_by'     => $this->hm->id,
            'target_type'      => 'Wilayah',
            'wilayah_id'       => $this->spv->wilayah_id,
            'tipe_periode'     => 'Tahunan',
            'tanggal_mulai'    => '2027-09-01',
            'tanggal_selesai'  => '2028-08-31',
            'target_kontak'    => 1200,
            'target_formulir'  => 240,
            'target_lunas'     => 120, // Annual = 120 Maba Lunas
            'academic_year_id' => $this->ta->id,
            'tahun_akademik'   => $this->ta->nama,
            'status'           => 'Aktif',
        ]);

        // 1. Invalid: sum is 115 (< 120)
        $invalidMonthlyUnder = array_fill(0, 11, 10);
        $invalidMonthlyUnder[11] = 5; // Sum = 115 != 120
        $this->expectException(ValidationException::class);
        $this->spvService->distributeAnnualToMonthly($annualTarget, $invalidMonthlyUnder);
    }

    public function test_spv_annual_to_monthly_target_over_sum_rejected(): void
    {
        $annualTarget = Target::create([
            'spv_id'           => $this->spv->id,
            'allocated_by'     => $this->hm->id,
            'target_type'      => 'Wilayah',
            'wilayah_id'       => $this->spv->wilayah_id,
            'tipe_periode'     => 'Tahunan',
            'tanggal_mulai'    => '2027-09-01',
            'tanggal_selesai'  => '2028-08-31',
            'target_kontak'    => 1200,
            'target_formulir'  => 240,
            'target_lunas'     => 120,
            'academic_year_id' => $this->ta->id,
            'tahun_akademik'   => $this->ta->nama,
            'status'           => 'Aktif',
        ]);

        // 2. Invalid: sum is 125 (> 120)
        $invalidMonthlyOver = array_fill(0, 11, 10);
        $invalidMonthlyOver[11] = 15; // Sum = 125 != 120
        $this->expectException(ValidationException::class);
        $this->spvService->distributeAnnualToMonthly($annualTarget, $invalidMonthlyOver);
    }

    public function test_spv_annual_to_monthly_even_distribution_succeeds(): void
    {
        $annualTarget = Target::create([
            'spv_id'           => $this->spv->id,
            'allocated_by'     => $this->hm->id,
            'target_type'      => 'Wilayah',
            'wilayah_id'       => $this->spv->wilayah_id,
            'tipe_periode'     => 'Tahunan',
            'tanggal_mulai'    => '2027-09-01',
            'tanggal_selesai'  => '2028-08-31',
            'target_kontak'    => 1200,
            'target_formulir'  => 240,
            'target_lunas'     => 120,
            'academic_year_id' => $this->ta->id,
            'tahun_akademik'   => $this->ta->nama,
            'status'           => 'Aktif',
        ]);

        // Generate even distribution (12 months @ 10 each = 120)
        $evenAllocations = $this->spvService->generateEvenMonthlyDistribution(120, 12);
        $this->assertCount(12, $evenAllocations);
        $this->assertEquals(120, array_sum($evenAllocations));

        $monthlyTargets = $this->spvService->distributeAnnualToMonthly($annualTarget, $evenAllocations, 'rata');

        $this->assertCount(12, $monthlyTargets);
        $this->assertEquals(120, $monthlyTargets->sum('target_lunas'));

        // Verify parent-child relationship
        foreach ($monthlyTargets as $mTarget) {
            $this->assertEquals($annualTarget->id, $mTarget->parent_id);
            $this->assertEquals('Bulanan', $mTarget->tipe_periode);
            $this->assertEquals(10, $mTarget->target_lunas);
        }

        // Test model relationship
        $this->assertCount(12, $annualTarget->monthlyTargets);
    }

    public function test_spv_annual_to_monthly_wave_pmb_distribution_succeeds(): void
    {
        $annualTarget = Target::create([
            'spv_id'           => $this->spv->id,
            'allocated_by'     => $this->hm->id,
            'target_type'      => 'Wilayah',
            'wilayah_id'       => $this->spv->wilayah_id,
            'tipe_periode'     => 'Tahunan',
            'tanggal_mulai'    => '2027-09-01',
            'tanggal_selesai'  => '2028-08-31',
            'target_kontak'    => 1200,
            'target_formulir'  => 240,
            'target_lunas'     => 120,
            'academic_year_id' => $this->ta->id,
            'tahun_akademik'   => $this->ta->nama,
            'status'           => 'Aktif',
        ]);

        // Gelombang PMB allocation:
        // G1 (Bulan 1-3): 5, 5, 5 (= 15)
        // G2 (Bulan 4-6): 10, 10, 10 (= 30)
        // G3 (Bulan 7-9): 15, 15, 15 (= 45)
        // G4 (Bulan 10-12): 10, 10, 10 (= 30)
        // Total = 15 + 30 + 45 + 30 = 120
        $waveAllocations = [
            0 => 5, 1 => 5, 2 => 5,
            3 => 10, 4 => 10, 5 => 10,
            6 => 15, 7 => 15, 8 => 15,
            9 => 10, 10 => 10, 11 => 10,
        ];
        $this->assertEquals(120, array_sum($waveAllocations));

        $monthlyTargets = $this->spvService->distributeAnnualToMonthly($annualTarget, $waveAllocations, 'gelombang');

        $this->assertCount(12, $monthlyTargets);
        $this->assertEquals(120, $monthlyTargets->sum('target_lunas'));

        // Check specific wave labels
        $this->assertEquals('Gelombang 1', $monthlyTargets[0]->gelombang);
        $this->assertEquals(5, $monthlyTargets[0]->target_lunas);

        $this->assertEquals('Gelombang 2', $monthlyTargets[3]->gelombang);
        $this->assertEquals(10, $monthlyTargets[3]->target_lunas);

        $this->assertEquals('Gelombang 3', $monthlyTargets[6]->gelombang);
        $this->assertEquals(15, $monthlyTargets[6]->target_lunas);
    }
}
