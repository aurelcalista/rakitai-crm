<?php

namespace Tests\Feature;

use App\Models\Sekolah;
use App\Models\Perusahaan;
use App\Models\Prodi;
use App\Models\Prospek;
use App\Models\User;
use App\Models\Wilayah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PresensiApiValidationTest extends TestCase
{
    use RefreshDatabase;

    protected $wilayah;
    protected $prodi;
    protected $sales;

    protected function setUp(): void
    {
        parent::setUp();
        $this->wilayah = Wilayah::create([
            'nama' => 'Wilayah Test',
            'kode' => 'WT',
            'status' => 'Aktif',
        ]);
        $this->prodi = Prodi::create([
            'kode' => 'TI',
            'nama' => 'Teknik Informatika',
            'jenjang' => 'S1',
            'fakultas' => 'Teknik',
            'status' => 'Aktif',
        ]);
        $this->sales = User::factory()->create(['role' => 'sales']);
    }

    /** @test */
    public function school_endpoint_invalid_whatsapp_returns_422()
    {
        $payload = [
            'nama' => 'Budi Siswa',
            'no_whatsapp' => 'invalid-number', // not a valid Indonesian WA
            'sekolah_id' => null,
            'sekolah_manual' => 'SMK Test Manual',
            'prodi_id' => (string) $this->prodi->id,
            'kelas' => 'Reguler',
        ];

        $response = $this->actingAs($this->sales)->postJson('/api/v1/presensi/sekolah', $payload);
        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors' => ['no_whatsapp']]);
    }

    /** @test */
    public function corporate_endpoint_invalid_whatsapp_returns_422()
    {
        $payload = [
            'nama' => 'John Corporate',
            'no_whatsapp' => '12345', // invalid
            'perusahaan_id' => null,
            'perusahaan_manual' => 'PT Manual',
            'prodi_id' => (string) $this->prodi->id,
            'kelas' => 'Karyawan',
        ];

        $response = $this->actingAs($this->sales)->postJson('/api/v1/presensi/korporasi', $payload);
        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors' => ['no_whatsapp']]);
    }

    /** @test */
    public function school_endpoint_missing_required_fields_returns_422()
    {
        $payload = [
            // missing 'nama' and 'no_whatsapp'
            'sekolah_manual' => 'SMK Test',
            'prodi_id' => (string) $this->prodi->id,
            'kelas' => 'Reguler',
        ];

        $response = $this->actingAs($this->sales)->postJson('/api/v1/presensi/sekolah', $payload);
        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors' => ['nama', 'no_whatsapp']]);
    }

    /** @test */
    public function corporate_endpoint_missing_required_fields_returns_422()
    {
        $payload = [
            // missing 'nama' and 'no_whatsapp'
            'perusahaan_manual' => 'PT Test',
            'prodi_id' => (string) $this->prodi->id,
            'kelas' => 'Karyawan',
        ];

        $response = $this->actingAs($this->sales)->postJson('/api/v1/presensi/korporasi', $payload);
        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors' => ['nama', 'no_whatsapp']]);
    }

    /** @test */
    public function school_manual_input_creates_new_sekolah_and_prospect()
    {
        $payload = [
            'nama' => 'Budi Siswa',
            'no_whatsapp' => '81234567890',
            'sekolah_manual' => 'SMK Baru Manual',
            'prodi_id' => (string) $this->prodi->id,
            'kelas' => 'Reguler',
        ];

        $response = $this->actingAs($this->sales)->postJson('/api/v1/presensi/sekolah', $payload);
        $response->assertStatus(201);
        // Check school created
        $this->assertDatabaseHas('sekolahs', [
            'nama' => 'SMK Baru Manual',
        ]);
        // Check prospect linked to new school
        $prospect = Prospek::first();
        $this->assertNotNull($prospect);
        $this->assertEquals('Sekolah', $prospect->type);
        $this->assertEquals('SMK Baru Manual', $prospect->name);
    }

    /** @test */
    public function corporate_manual_input_creates_prospect_without_new_company_record()
    {
        $payload = [
            'nama' => 'John Corporate',
            'no_whatsapp' => '81234567890',
            'perusahaan_manual' => 'PT Manual Baru',
            'prodi_id' => (string) $this->prodi->id,
            'kelas' => 'Karyawan',
        ];

        $response = $this->actingAs($this->sales)->postJson('/api/v1/presensi/korporasi', $payload);
        $response->assertStatus(201);
        // No new perusahaan record should be created (manual just notes)
        $this->assertDatabaseMissing('perusahaans', [
            'nama' => 'PT Manual Baru',
        ]);
        // Prospect should have type Corporate and name from payload
        $prospect = Prospek::first();
        $this->assertNotNull($prospect);
        $this->assertEquals('Corporate', $prospect->type);
        $this->assertEquals('John Corporate', $prospect->name);
    }
}
?>
