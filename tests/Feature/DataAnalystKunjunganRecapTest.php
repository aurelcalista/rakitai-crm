<?php

namespace Tests\Feature;

use App\Models\Kunjungan;
use App\Models\Perusahaan;
use App\Models\Sekolah;
use App\Models\TahunAkademik;
use App\Models\User;
use App\Models\Wilayah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataAnalystKunjunganRecapTest extends TestCase
{
    use RefreshDatabase;

    protected User $analyst;
    protected User $sales;
    protected User $spv;
    protected Wilayah $wilayah;
    protected TahunAkademik $ta;
    protected Sekolah $sekolah;
    protected Perusahaan $perusahaan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ta = TahunAkademik::create([
            'nama'   => '2026/2027',
            'status' => 'Aktif',
        ]);

        $this->wilayah = Wilayah::create([
            'kode'   => 'KOTA-CRB',
            'nama'   => 'Kota Cirebon',
            'level'  => 'Kota/Kabupaten',
            'status' => 'Aktif',
        ]);

        $this->analyst = User::create([
            'name'     => 'Analyst User Test',
            'email'    => 'analyst.kunjungan@cic.ac.id',
            'password' => bcrypt('password'),
            'role'     => 'Data Analyst',
            'status'   => 'Aktif',
        ]);

        $this->spv = User::create([
            'name'       => 'SPV Budi Santoso',
            'email'      => 'spv.budi@cic.ac.id',
            'password'   => bcrypt('password'),
            'role'       => 'SPV',
            'status'     => 'Aktif',
            'wilayah_id' => $this->wilayah->id,
        ]);

        $this->sales = User::create([
            'name'          => 'Sales Ani Wijaya',
            'email'         => 'sales.ani@cic.ac.id',
            'password'      => bcrypt('password'),
            'role'          => 'Sales',
            'status'        => 'Aktif',
            'supervisor_id' => $this->spv->id,
            'wilayah_id'    => $this->wilayah->id,
        ]);

        $this->sekolah = Sekolah::create([
            'kode'       => 'SKL-001',
            'nama'       => 'SMK Negeri 1 Cirebon',
            'wilayah_id' => $this->wilayah->id,
            'status'     => 'Aktif',
        ]);

        $this->perusahaan = Perusahaan::create([
            'kode'       => 'PRU-001',
            'nama'       => 'PT Cirebon Teknologi Utama',
            'wilayah_id' => $this->wilayah->id,
            'status'     => 'Aktif',
        ]);
    }

    /** @test */
    public function test_data_analyst_dashboard_renders_rekap_kunjungan_from_all_roles()
    {
        // 1. Visit by Sales to Sekolah
        Kunjungan::create([
            'nomor'            => 'KNJ-TEST-001',
            'tanggal'          => now()->toDateString(),
            'waktu'            => '09:00:00',
            'sales_id'         => $this->sales->id,
            'jenis'            => 'Sekolah',
            'tujuan_id'        => $this->sekolah->id,
            'nama_institusi'   => 'SMK Negeri 1 Cirebon',
            'tujuan_kunjungan' => 'Sosialisasi PMB',
            'hasil'            => '150 siswa hadir menyimak',
            'status'           => 'Selesai',
        ]);

        // 2. Visit by SPV to Perusahaan
        Kunjungan::create([
            'nomor'            => 'KNJ-TEST-002',
            'tanggal'          => now()->toDateString(),
            'waktu'            => '14:00:00',
            'sales_id'         => $this->spv->id,
            'jenis'            => 'Perusahaan',
            'tujuan_id'        => $this->perusahaan->id,
            'nama_institusi'   => 'PT Cirebon Teknologi Utama',
            'tujuan_kunjungan' => 'Audiensi Kerjasama MoU Kelas Karyawan',
            'hasil'            => 'MoU disepakati',
            'status'           => 'Terverifikasi',
            'status_verifikasi'=> 'Terverifikasi',
        ]);

        // Access Dashboard as Data Analyst
        $response = $this->actingAs($this->analyst)->get(route('analyst.dashboard'));
        $response->assertStatus(200);

        // Assert blade contains the recap title and institutions
        $response->assertSee('Rekap Data Kunjungan Sekolah & Perusahaan', false);
        $response->assertSee('SMK Negeri 1 Cirebon');
        $response->assertSee('PT Cirebon Teknologi Utama');
        $response->assertSee('Sales Ani Wijaya');
        $response->assertSee('SPV Budi Santoso');
    }

    /** @test */
    public function test_data_analyst_dashboard_ajax_contains_rekap_kunjungan_payload()
    {
        // Create visit by Sales
        Kunjungan::create([
            'nomor'            => 'KNJ-TEST-003',
            'tanggal'          => now()->toDateString(),
            'waktu'            => '10:00:00',
            'sales_id'         => $this->sales->id,
            'jenis'            => 'Sekolah',
            'tujuan_id'        => $this->sekolah->id,
            'nama_institusi'   => 'SMK Negeri 1 Cirebon',
            'status'           => 'Selesai',
        ]);

        // Create visit by SPV
        Kunjungan::create([
            'nomor'            => 'KNJ-TEST-004',
            'tanggal'          => now()->toDateString(),
            'waktu'            => '11:00:00',
            'sales_id'         => $this->spv->id,
            'jenis'            => 'Perusahaan',
            'tujuan_id'        => $this->perusahaan->id,
            'nama_institusi'   => 'PT Cirebon Teknologi Utama',
            'status'           => 'Terverifikasi',
            'status_verifikasi'=> 'Terverifikasi',
        ]);

        $response = $this->actingAs($this->analyst)
            ->getJson(route('dashboard.data-analyst', ['ajax' => '1']));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'rekapKunjungan' => [
                'total_kunjungan',
                'total_sekolah',
                'total_perusahaan',
                'total_selesai',
                'per_role',
                'list',
            ],
        ]);

        $data = $response->json('rekapKunjungan');
        $this->assertEquals(2, $data['total_kunjungan']);
        $this->assertEquals(1, $data['total_sekolah']);
        $this->assertEquals(1, $data['total_perusahaan']);
        $this->assertEquals(2, $data['total_selesai']);

        // Check roles included
        $roles = collect($data['per_role'])->pluck('role')->toArray();
        $this->assertContains('Sales', $roles);
        $this->assertContains('SPV', $roles);
    }

    /** @test */
    public function test_data_analyst_can_export_kunjungan_excel()
    {
        Kunjungan::create([
            'nomor'            => 'KNJ-TEST-005',
            'tanggal'          => now()->toDateString(),
            'waktu'            => '08:30:00',
            'sales_id'         => $this->sales->id,
            'jenis'            => 'Sekolah',
            'tujuan_id'        => $this->sekolah->id,
            'nama_institusi'   => 'SMK Negeri 1 Cirebon',
            'status'           => 'Selesai',
        ]);

        $res = $this->actingAs($this->analyst)->get(route('analyst.export', ['type' => 'kunjungan']));
        $res->assertStatus(200);
        $res->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }
}
