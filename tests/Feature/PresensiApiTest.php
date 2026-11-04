<?php

namespace Tests\Feature;

use App\Models\Sekolah;
use App\Models\Perusahaan;
use App\Models\Prodi;
use App\Models\Prospek;
use App\Models\User;
use App\Models\Wilayah;
use App\Models\MasterData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PresensiApiTest extends TestCase
{
    use RefreshDatabase;

    protected $wilayah;
    protected $kategoriSekolah;
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
        $this->kategoriSekolah = MasterData::create([
            'kode' => 'KAT-SMTA',
            'type' => 'kategori_sekolah',
            'nama' => 'SMTA',
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

    public function test_presensi_sekolah_endpoint_creates_prospect()
    {
        $sekolah = Sekolah::create([
            'nama' => 'SMK Test',
            'kode' => 'SCH-TEST',
            'kategori_id' => $this->kategoriSekolah->id,
            'wilayah_id' => $this->wilayah->id,
            'status' => 'Aktif',
        ]);

        $payload = [
            'nama' => 'Budi Siswa',
            'no_whatsapp' => '81234567890',
            'wa_ortu' => '81211112222',
            'sekolah_id' => $sekolah->id,
            'prodi_id' => (string) $this->prodi->id,
            'kelas' => 'Reguler',
        ];

        $response = $this->actingAs($this->sales)->postJson('/api/v1/presensi/sekolah', $payload);
        $response->assertStatus(201)
            ->assertJsonStructure(['message', 'data' => ['id']]);

        $this->assertDatabaseHas('prospeks', [
            'name' => $sekolah->nama,
            'whatsapp' => '+6281234567890',
            'type' => 'Sekolah',
            'source' => Prospek::SOURCE_PRESENSI_SISWA,
        ]);
    }

    public function test_presensi_korporasi_endpoint_creates_prospect()
    {
        $perusahaan = Perusahaan::create([
            'nama' => 'PT Test',
            'kode' => 'PT-TEST',
            'status' => 'Aktif',
            'wilayah_id' => $this->wilayah->id,
        ]);

        $payload = [
            'nama' => 'John Corporate',
            'no_whatsapp' => '81234567890',
            'wa_ortu' => null,
            'perusahaan_id' => $perusahaan->id,
            'prodi_id' => (string) $this->prodi->id,
            'kelas' => 'Karyawan',
        ];

        $response = $this->actingAs($this->sales)->postJson('/api/v1/presensi/korporasi', $payload);
        $response->assertStatus(201)
            ->assertJsonStructure(['message', 'data' => ['id']]);

        $this->assertDatabaseHas('prospeks', [
            'name' => $perusahaan->nama,
            'whatsapp' => '+6281234567890',
            'type' => 'Corporate',
            'source' => Prospek::SOURCE_PRESENSI_SISWA,
        ]);
    }
}
?>
