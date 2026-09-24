<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wilayah;
use App\Models\Sekolah;
use App\Http\Controllers\WilayahController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MasterDataWilayahSekolahTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /** @test Test 1: Kota Cirebon memiliki kecamatan yang benar dari database */
    public function test_01_kota_cirebon_has_correct_kecamatans()
    {
        $kota = Wilayah::where('nama', 'Kota Cirebon')->where('level', 'Kota/Kabupaten')->first();
        $this->assertNotNull($kota, 'Kota Cirebon must exist in database.');

        $kecamatanNames = $kota->children()->pluck('nama')->toArray();
        $expected = ['Kejaksan', 'Kesambi', 'Pekalipan', 'Lemahwungkuk', 'Harjamukti'];

        foreach ($expected as $kec) {
            $this->assertContains($kec, $kecamatanNames, "Kecamatan {$kec} must belong to Kota Cirebon.");
        }
    }

    /** @test Test 2: Endpoint kecamatan by kota only returns kecamatan belonging to that city */
    public function test_02_endpoint_returns_only_kecamatans_for_selected_city()
    {
        $user = User::where('role', 'Admin')->first();
        $kotaCrb = Wilayah::where('nama', 'Kota Cirebon')->where('level', 'Kota/Kabupaten')->first();
        $kotaKng = Wilayah::where('nama', 'Kabupaten Kuningan')->where('level', 'Kota/Kabupaten')->first();

        $response = $this->actingAs($user)->getJson(route('api.wilayah.kecamatan', $kotaCrb->id));

        $response->assertStatus(200);
        $data = $response->json();
        $names = array_column($data, 'nama');

        $this->assertContains('Kesambi', $names);
        $this->assertContains('Kejaksan', $names);
        // Kecamatan from Kuningan should NOT be in Kota Cirebon response
        $this->assertNotContains('Cilimus', $names);
        $this->assertNotContains('Luragung', $names);
    }

    /** @test Test 3: Endpoint sekolah by kecamatan returns schools from selected kecamatan */
    public function test_03_endpoint_returns_schools_for_selected_kecamatan()
    {
        $user = User::where('role', 'Admin')->first();
        $kotaCrb = Wilayah::where('nama', 'Kota Cirebon')->first();
        $kesambi = Wilayah::where('parent_id', $kotaCrb->id)->where('nama', 'Kesambi')->first();

        $response = $this->actingAs($user)->getJson(route('api.kecamatan.sekolah', $kesambi->id));

        $response->assertStatus(200);
        $data = $response->json();
        $schoolNames = array_column($data, 'nama');

        // Schools in Kesambi
        $this->assertContains('SMA Negeri 2 Cirebon', $schoolNames);
        $this->assertContains('SMK Negeri 1 Cirebon', $schoolNames);
    }

    /** @test Test 4: Schools from other kecamatan do not appear when selecting Kesambi */
    public function test_04_schools_from_other_kecamatan_do_not_appear()
    {
        $user = User::where('role', 'Admin')->first();
        $kotaCrb = Wilayah::where('nama', 'Kota Cirebon')->first();
        $kesambi = Wilayah::where('parent_id', $kotaCrb->id)->where('nama', 'Kesambi')->first();

        $response = $this->actingAs($user)->getJson(route('api.kecamatan.sekolah', $kesambi->id));

        $response->assertStatus(200);
        $data = $response->json();
        $schoolNames = array_column($data, 'nama');

        // Schools from Kejaksan should NOT appear in Kesambi response
        $this->assertNotContains('SMA Negeri 1 Cirebon', $schoolNames);
        $this->assertNotContains('SMK Informatika Al-Irsyad', $schoolNames);
    }

    /** @test Test 5: Security validation rejects kecamatan from another city */
    public function test_05_security_rejects_kecamatan_from_different_city()
    {
        $kotaCrb = Wilayah::where('nama', 'Kota Cirebon')->first();
        $kotaKng = Wilayah::where('nama', 'Kabupaten Kuningan')->first();
        $kecKuningan = Wilayah::where('parent_id', $kotaKng->id)->where('nama', 'Cilimus')->first();

        $this->expectException(ValidationException::class);
        WilayahController::validateDistrictBelongsToCity($kotaCrb->id, $kecKuningan->id);
    }

    /** @test Test 6: Security validation rejects school from different kecamatan */
    public function test_06_security_rejects_school_from_different_kecamatan()
    {
        $kotaCrb = Wilayah::where('nama', 'Kota Cirebon')->first();
        $kesambi = Wilayah::where('parent_id', $kotaCrb->id)->where('nama', 'Kesambi')->first();
        $sma1Kejaksan = Sekolah::where('nama', 'SMA Negeri 1 Cirebon')->first();

        $this->expectException(ValidationException::class);
        WilayahController::validateSchoolBelongsToDistrict($kesambi->id, $sma1Kejaksan->id);
    }

    /** @test Test 7: Kota Lainnya is not present in master data */
    public function test_07_kota_lainnya_does_not_exist_in_master_data()
    {
        $kotaLainnya = Wilayah::where('kode', 'W-LAIN')
            ->orWhere('nama', 'like', '%Kota Lainnya%')
            ->orWhere('nama', 'like', '%Di Kota Lainnya%')
            ->get();

        $this->assertCount(0, $kotaLainnya, 'Kota Lainnya must not exist in database.');
    }

    /** @test Test 8: Seeder is idempotent and does not produce duplicate records */
    public function test_08_seeder_is_idempotent()
    {
        $initialWilayahCount = Wilayah::count();
        $initialSekolahCount = Sekolah::count();

        // Run seeders again
        $this->seed();

        $this->assertEquals($initialWilayahCount, Wilayah::count(), 'Re-running seeder must not create duplicate Wilayah.');
        $this->assertEquals($initialSekolahCount, Sekolah::count(), 'Re-running seeder must not create duplicate Sekolah.');
    }
}
