<?php

namespace Tests\Feature;

use App\Models\Prospek;
use App\Models\TahunAkademik;
use App\Models\User;
use App\Models\Wilayah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiroleDataProspekMenuTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $sales;
    protected User $spv;
    protected User $cs;
    protected User $hm;
    protected User $eo;
    protected User $analyst;
    protected Wilayah $wilayah;
    protected TahunAkademik $ta;
    protected Prospek $prospek;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ta = TahunAkademik::updateOrCreate(
            ['nama' => '2026/2027'],
            ['status' => 'Aktif']
        );

        $this->wilayah = Wilayah::create([
            'kode'  => 'W1',
            'nama'  => 'Jawa Barat',
            'level' => 'Provinsi',
        ]);

        $this->admin = User::create([
            'name'       => 'Admin User',
            'email'      => 'admin.menu@cic.ac.id',
            'password'   => bcrypt('password'),
            'role'       => 'Admin',
            'status'     => 'aktif',
            'wilayah_id' => $this->wilayah->id,
        ]);

        $this->hm = User::create([
            'name'       => 'HM User',
            'email'      => 'hm.menu@cic.ac.id',
            'password'   => bcrypt('password'),
            'role'       => 'HM',
            'status'     => 'aktif',
            'wilayah_id' => $this->wilayah->id,
        ]);

        $this->spv = User::create([
            'name'       => 'SPV User',
            'email'      => 'spv.menu@cic.ac.id',
            'password'   => bcrypt('password'),
            'role'       => 'SPV',
            'status'     => 'aktif',
            'wilayah_id' => $this->wilayah->id,
        ]);

        $this->sales = User::create([
            'name'          => 'Sales User',
            'email'         => 'sales.menu@cic.ac.id',
            'password'      => bcrypt('password'),
            'role'          => 'Sales',
            'status'        => 'aktif',
            'wilayah_id'    => $this->wilayah->id,
            'supervisor_id' => $this->spv->id,
        ]);

        $this->cs = User::create([
            'name'       => 'CS User',
            'email'      => 'cs.menu@cic.ac.id',
            'password'   => bcrypt('password'),
            'role'       => 'CS',
            'status'     => 'aktif',
            'wilayah_id' => $this->wilayah->id,
        ]);

        $this->eo = User::create([
            'name'       => 'EO User',
            'email'      => 'eo.menu@cic.ac.id',
            'password'   => bcrypt('password'),
            'role'       => 'EO',
            'status'     => 'aktif',
            'wilayah_id' => $this->wilayah->id,
        ]);

        $this->analyst = User::create([
            'name'     => 'Data Analyst User',
            'email'    => 'analyst.menu@cic.ac.id',
            'password' => bcrypt('password'),
            'role'     => 'Data Analyst',
            'status'   => 'aktif',
        ]);

        $this->prospek = Prospek::create([
            'name'              => 'Prospek Uji Coba Menu',
            'type'              => 'Sekolah',
            'pic'               => 'Siswa A',
            'whatsapp'          => '081234567890',
            'status'            => 'BARU',
            'stage_number'      => 1,
            'source'            => 'Presensi Siswa',
            'wilayah_id'        => $this->wilayah->id,
            'sales_id'          => $this->sales->id,
            'academic_year_id'  => $this->ta->id,
        ]);
    }

    public function test_eo_dapat_melihat_menu_data_prospek_dan_mengakses_halamannya(): void
    {
        $response = $this->actingAs($this->eo)->get(route('prospek.index'));
        $response->assertOk();
        $response->assertSee('Data Prospek');
        $response->assertSee(route('prospek.index'));
    }

    public function test_eo_dapat_melihat_detail_prospek(): void
    {
        $response = $this->actingAs($this->eo)->get(route('prospek.show', $this->prospek->id));
        $response->assertOk();
        $response->assertSee('Prospek Uji Coba Menu');
    }

    public function test_admin_memiliki_menu_data_prospek_dan_bisa_mengaksesnya(): void
    {
        $response = $this->actingAs($this->admin)->get(route('prospek.index'));
        $response->assertOk();
        $response->assertSee('Data Prospek');
        $response->assertSee(route('prospek.index'));
    }

    public function test_sales_memiliki_menu_data_prospek(): void
    {
        $response = $this->actingAs($this->sales)->get(route('sales.prospek.index'));
        $response->assertOk();
        $response->assertSee('Data Prospek');
    }

    public function test_cs_memiliki_menu_data_prospek(): void
    {
        $response = $this->actingAs($this->cs)->get(route('prospek.index'));
        $response->assertOk();
        $response->assertSee('Data Prospek');
    }

    public function test_spv_memiliki_menu_data_prospek(): void
    {
        $response = $this->actingAs($this->spv)->get(route('spv.prospek.index'));
        $response->assertOk();
        $response->assertSee('Data Prospek');
    }

    public function test_hm_memiliki_menu_data_prospek(): void
    {
        $response = $this->actingAs($this->hm)->get(route('prospek.index'));
        $response->assertOk();
        $response->assertSee('Data Prospek');
    }

    public function test_data_analyst_memiliki_menu_data_prospek(): void
    {
        $response = $this->actingAs($this->analyst)->get(route('analyst.prospek.index'));
        $response->assertOk();
        $response->assertSee('Data Prospek');
    }

    public function test_semua_role_dapat_melihat_presensi_siswa_di_filter_presensi(): void
    {
        $presensiBaru = Prospek::create([
            'name'             => 'Calon Mahasiswa Presensi Baru',
            'type'             => 'Sekolah',
            'pic'              => 'Budi Siswa',
            'whatsapp'         => '089988776655',
            'status'           => 'BARU',
            'stage_number'     => 1,
            'source'           => Prospek::SOURCE_PRESENSI_SISWA,
            'owner_id'         => null,
            'sales_id'         => null,
            'wilayah_id'       => $this->wilayah->id,
            'academic_year_id' => $this->ta->id,
        ]);

        // 1. Admin
        $resAdmin = $this->actingAs($this->admin)->get(route('prospek.index', ['scope' => 'presensi']));
        $resAdmin->assertOk();
        $resAdmin->assertSee('Calon Mahasiswa Presensi Baru');
        $resAdmin->assertSee('Presensi Siswa');

        // 2. Sales
        $resSales = $this->actingAs($this->sales)->get(route('sales.prospek.index', ['scope' => 'presensi']));
        $resSales->assertOk();
        $resSales->assertSee('Calon Mahasiswa Presensi Baru');
        $resSales->assertSee('Presensi Siswa');

        // 3. CS
        $resCs = $this->actingAs($this->cs)->get(route('prospek.index', ['scope' => 'presensi']));
        $resCs->assertOk();
        $resCs->assertSee('Calon Mahasiswa Presensi Baru');
        $resCs->assertSee('Presensi Siswa');

        // 4. SPV
        $resSpv = $this->actingAs($this->spv)->get(route('spv.prospek.index', ['scope' => 'presensi']));
        $resSpv->assertOk();
        $resSpv->assertSee('Calon Mahasiswa Presensi Baru');
        $resSpv->assertSee('Presensi Siswa');

        // 5. HM
        $resHm = $this->actingAs($this->hm)->get(route('prospek.index', ['scope' => 'presensi']));
        $resHm->assertOk();
        $resHm->assertSee('Calon Mahasiswa Presensi Baru');
        $resHm->assertSee('Presensi Siswa');

        // 6. EO
        $resEo = $this->actingAs($this->eo)->get(route('prospek.index', ['scope' => 'presensi']));
        $resEo->assertOk();
        $resEo->assertSee('Calon Mahasiswa Presensi Baru');
        $resEo->assertSee('Presensi Siswa');

        // 7. Data Analyst
        $resAnalyst = $this->actingAs($this->analyst)->get(route('analyst.prospek.index', ['scope' => 'presensi']));
        $resAnalyst->assertOk();
        $resAnalyst->assertSee('Calon Mahasiswa Presensi Baru');
        $resAnalyst->assertSee('Presensi Siswa');
    }

    public function test_badge_dan_tab_filter_presensi_siswa_muncul_di_view_semua_role(): void
    {
        // 1. Admin default view contains tab Presensi Siswa
        $resAdmin = $this->actingAs($this->admin)->get(route('prospek.index'));
        $resAdmin->assertOk();
        $resAdmin->assertSee('Presensi Siswa');

        // 2. Sales default view contains tab Presensi Siswa
        $resSales = $this->actingAs($this->sales)->get(route('sales.prospek.index'));
        $resSales->assertOk();
        $resSales->assertSee('Presensi Siswa');

        // 3. CS default view contains tab Presensi Siswa
        $resCs = $this->actingAs($this->cs)->get(route('prospek.index'));
        $resCs->assertOk();
        $resCs->assertSee('Presensi Siswa');

        // 4. SPV default view contains tab Presensi Siswa
        $resSpv = $this->actingAs($this->spv)->get(route('spv.prospek.index'));
        $resSpv->assertOk();
        $resSpv->assertSee('Presensi Siswa');

        // 5. HM default view contains tab Presensi Siswa
        $resHm = $this->actingAs($this->hm)->get(route('prospek.index'));
        $resHm->assertOk();
        $resHm->assertSee('Presensi Siswa');

        // 6. EO default view contains tab Presensi Siswa
        $resEo = $this->actingAs($this->eo)->get(route('prospek.index'));
        $resEo->assertOk();
        $resEo->assertSee('Presensi Siswa');

        // 7. Data Analyst default view contains Presensi Siswa filter
        $resAnalyst = $this->actingAs($this->analyst)->get(route('analyst.prospek.index'));
        $resAnalyst->assertOk();
        $resAnalyst->assertSee('Presensi Siswa');
    }
}
