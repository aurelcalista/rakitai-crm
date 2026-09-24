<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wilayah;
use App\Services\WilayahPerformanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class WilayahPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected WilayahPerformanceService $service;
    protected User $adminUser;
    protected User $hmUser;
    protected User $spvUser;
    protected Wilayah $kotaCirebon;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new WilayahPerformanceService();

        $this->kotaCirebon = Wilayah::create([
            'kode' => 'CRB',
            'nama' => 'Kota Cirebon',
            'level' => 'Kota/Kabupaten',
            'status' => 'Aktif',
        ]);

        $this->adminUser = User::create([
            'name' => 'Admin Performance Test',
            'email' => 'adminperf@cic.ac.id',
            'password' => Hash::make('123'),
            'role' => 'Admin',
            'status' => 'Aktif',
        ]);

        $this->hmUser = User::create([
            'name' => 'HM Performance Test',
            'email' => 'hmperf@cic.ac.id',
            'password' => Hash::make('123'),
            'role' => 'HM',
            'status' => 'Aktif',
            'wilayah_id' => $this->kotaCirebon->id,
        ]);

        $this->spvUser = User::create([
            'name' => 'SPV Performance Test',
            'email' => 'spvperf@cic.ac.id',
            'password' => Hash::make('123'),
            'role' => 'SPV',
            'status' => 'Aktif',
            'wilayah_id' => $this->kotaCirebon->id,
        ]);
    }

    /** Test 1: Contact Score Calculations & Zero Target N/A Behavior */
    public function test_01_contact_score_calculations()
    {
        $this->assertEquals(100.0, $this->service->getContactScore(100, 100));
        $this->assertEquals(50.0, $this->service->getContactScore(50, 100));
        $this->assertEquals(150.0, $this->service->getContactScore(150, 100));
        $this->assertEquals(0.0, $this->service->getContactScore(0, 100));
        
        // Final Requirement: Target 0 + Realisasi 0 => null (N/A)
        $this->assertNull($this->service->getContactScore(0, 0));
        $this->assertNull($this->service->getContactScore(5, 0));
    }

    /** Test 2: Closing Score Calculations & Zero Target N/A Behavior */
    public function test_02_closing_score_calculations()
    {
        $this->assertEquals(100.0, $this->service->getClosingScore(100, 100));
        $this->assertEquals(50.0, $this->service->getClosingScore(50, 100));
        $this->assertEquals(150.0, $this->service->getClosingScore(150, 100));
        $this->assertEquals(0.0, $this->service->getClosingScore(0, 100));
        
        // Final Requirement: Target 0 + Realisasi 0 => null (N/A)
        $this->assertNull($this->service->getClosingScore(0, 0));
        $this->assertNull($this->service->getClosingScore(5, 0));
    }

    /** Test 3: Combined Score with N/A and Normal Weights */
    public function test_03_combined_score_calculation()
    {
        // Both valid: Contact 100%, Closing 100% => (100 * 0.4) + (100 * 0.6) = 100%
        $this->assertEquals(100.0, $this->service->getCombinedScore(100.0, 100.0, 40.0, 60.0));

        // Both valid: Contact 100%, Closing 50% => (100 * 0.4) + (50 * 0.6) = 70%
        $score = $this->service->getCombinedScore(100.0, 50.0, 40.0, 60.0);
        $this->assertEquals(70.0, $score);
        $this->assertEquals('C', $this->service->getGrade($score)['code']);

        // N/A Cases: If contact or closing score is null => Combined score is null (N/A)
        $this->assertNull($this->service->getCombinedScore(null, 100.0));
        $this->assertNull($this->service->getCombinedScore(100.0, null));
        $this->assertNull($this->service->getCombinedScore(null, null));
        
        // N/A Grade & Matrix
        $this->assertEquals('N/A', $this->service->getGrade(null)['code']);
        $this->assertEquals('N/A', $this->service->getMatrix(null, 100.0)['code']);
        $this->assertEquals('N/A', $this->service->getMatrix(null, null)['code']);
    }

    /** Test 4: Grade Boundaries */
    public function test_04_grade_boundaries()
    {
        $this->assertEquals('A', $this->service->getGrade(100.0)['code']);
        $this->assertEquals('A', $this->service->getGrade(105.0)['code']);
        $this->assertEquals('B', $this->service->getGrade(99.99)['code']);
        $this->assertEquals('B', $this->service->getGrade(80.0)['code']);
        $this->assertEquals('C', $this->service->getGrade(79.99)['code']);
        $this->assertEquals('C', $this->service->getGrade(60.0)['code']);
        $this->assertEquals('D', $this->service->getGrade(59.99)['code']);
        $this->assertEquals('D', $this->service->getGrade(0.0)['code']);
    }

    /** Test 5: Final Matrix Conditions (100% Threshold: >=100% Tinggi, <100% Rendah) */
    public function test_05_matrix_conditions_final()
    {
        // Contact 100%, Closing 100% => A — Unggul
        $this->assertEquals('A', $this->service->getMatrix(100.0, 100.0)['code']);
        $this->assertEquals('A', $this->service->getMatrix(110.0, 100.01)['code']);

        // Contact 100%, Closing 99.99% => B — Kuantitas oke, follow-up perlu dibenahi
        $matrixB = $this->service->getMatrix(100.0, 99.99);
        $this->assertEquals('B', $matrixB['code']);
        $this->assertStringContainsString('Kuantitas oke', $matrixB['label']);

        // Contact 99.99%, Closing 100% => C — Kualitas bagus, perlu tambah kanal/tenaga
        $matrixC = $this->service->getMatrix(99.99, 100.0);
        $this->assertEquals('C', $matrixC['code']);
        $this->assertStringContainsString('Kualitas bagus', $matrixC['label']);

        // Contact 99.99%, Closing 99.99% => D — Evaluasi total
        $matrixD = $this->service->getMatrix(99.99, 99.99);
        $this->assertEquals('D', $matrixD['code']);
        $this->assertStringContainsString('Evaluasi total', $matrixD['label']);
    }

    /** Test 6: Dynamic Weights Validation (Sum = 100) */
    public function test_06_dynamic_weights_validation()
    {
        // Valid 50:50
        $this->assertTrue($this->service->updateWeights(50.0, 50.0));
        $weights = $this->service->getWeights();
        $this->assertEquals(50.0, $weights['bobot_kontak']);
        $this->assertEquals(50.0, $weights['bobot_closing']);

        // Invalid sum 30:60 = 90 => Throws ValidationException
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->service->updateWeights(30.0, 60.0);
    }

    /** Test 7: Admin vs Non-admin Weight Config Authorization */
    public function test_07_weight_config_authorization()
    {
        // Non-admin (SPV) attempts update -> REJECTED 403
        $responseSpv = $this->actingAs($this->spvUser)->post(route('admin.master-data.update-weights'), [
            'bobot_kontak'  => 50,
            'bobot_closing' => 50,
        ]);
        $responseSpv->assertStatus(403);

        // Admin attempts update -> SUCCESS Redirect
        $responseAdmin = $this->actingAs($this->adminUser)->post(route('admin.master-data.update-weights'), [
            'bobot_kontak'  => 45,
            'bobot_closing' => 55,
        ]);
        $responseAdmin->assertRedirect();
        
        $weights = $this->service->getWeights();
        $this->assertEquals(45.0, $weights['bobot_kontak']);
        $this->assertEquals(55.0, $weights['bobot_closing']);
    }
}
