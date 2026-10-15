<?php

namespace Tests\Feature;

use App\Models\Prodi;
use App\Models\Prospek;
use App\Models\Sekolah;
use App\Models\TahunAkademik;
use App\Models\User;
use App\Models\Wilayah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolNameWarningAndSyncTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $cs;
    protected User $sales;
    protected Wilayah $wilayah;
    protected Prodi $prodi;
    protected TahunAkademik $ta;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ta = TahunAkademik::create([
            'nama'   => '2026/2027',
            'status' => 'Aktif',
        ]);

        $this->prodi = Prodi::create([
            'kode'     => 'IF-01',
            'nama'     => 'Teknik Informatika',
            'jenjang'  => 'S1',
            'fakultas' => 'Teknologi Informasi',
            'status'   => 'Aktif',
        ]);

        $this->wilayah = Wilayah::create([
            'nama'   => 'Kota Cirebon',
            'kode'   => 'KCRB',
            'status' => 'Aktif',
        ]);

        $this->admin = User::factory()->create([
            'name'       => 'Admin CRM',
            'role'       => 'Admin',
            'status'     => 'Aktif',
            'wilayah_id' => $this->wilayah->id,
        ]);

        $this->cs = User::factory()->create([
            'name'       => 'CS Front Office',
            'role'       => 'CS',
            'status'     => 'Aktif',
            'wilayah_id' => $this->wilayah->id,
        ]);

        $this->sales = User::factory()->create([
            'name'       => 'Sales Field',
            'role'       => 'Sales',
            'status'     => 'Aktif',
            'wilayah_id' => $this->wilayah->id,
        ]);
    }

    /**
     * Presensi Form:
     * Tampilan bersih dan rapi (tanpa kotak peringatan besar yang memenuhi layar),
     * serta terintegrasi dengan pengecekan duplikat sekolah saat submit (checkSchoolDuplicate).
     */
    public function test_presensi_form_has_clean_school_input_with_duplicate_detection_support()
    {
        $response = $this->get('/presensi');

        $response->assertStatus(200);
        $response->assertSee('checkSchoolDuplicate');
        $response->assertSee('sekolahsList');
        $response->assertSee('Contoh: SMK NEGERI 1 CIREBON');
        $response->assertSee('+ Sekolah belum terdaftar? Ketik Manual');
    }

    /**
     * Presensi Corporate Form:
     * Halaman kontak corporate bersih dan tidak menampilkan instruksi nama sekolah yang salah sasaran.
     */
    public function test_presensi_corporate_form_loads_cleanly()
    {
        $response = $this->get('/presensi-corporate');

        $response->assertStatus(200);
        $response->assertSee('Nama Perusahaan / PT');
        $response->assertSee('PT MAJU SUKSES MANDIRI');
    }

    /**
     * Modal Tambah Presensi Siswa (Langsung) di /prospek:
     * Tampilan modal rapi & lapang, terintegrasi dengan checkSchoolDuplicate pada saat submit.
     */
    public function test_manual_presensi_modal_on_prospek_has_clean_input_with_duplicate_detection()
    {
        $response = $this->actingAs($this->cs)->get('/prospek');

        $response->assertStatus(200);
        $response->assertSee('Tambah Presensi Siswa (Langsung)');
        $response->assertSee('checkSchoolDuplicate');
        $response->assertSee('sekolahsList');
        $response->assertSee('Contoh: SMK NEGERI 1 CIREBON');
    }

    /**
     * Halaman Admin Master Data Sekolah (/admin/sekolah) bersih dan fungsional.
     */
    public function test_admin_master_sekolah_page_loads_cleanly()
    {
        $response = $this->actingAs($this->admin)->get('/admin/sekolah');

        $response->assertStatus(200);
        $response->assertSee('Data Sekolah');
    }

    /**
     * Form input kunjungan sekolah (/kunjungan/create) bersih dan fungsional.
     */
    public function test_kunjungan_create_page_loads_cleanly()
    {
        $response = $this->actingAs($this->sales)->get('/kunjungan/create');

        $response->assertStatus(200);
        $response->assertSee('Tambah Kunjungan');
    }

    /**
     * Acceptance Criteria 7, 8, 9, 10:
     * Input manual sekolah baru di form /presensi otomatis tersimpan ke Master Sekolah
     * dengan wilayah/kota kosong (NULL) untuk diverifikasi/dilengkapi Admin.
     */
    public function test_manual_school_input_syncs_to_master_sekolah_with_null_location()
    {
        $payload = [
            'jenis_instansi' => 'Sekolah',
            'sekolah_manual' => 'SMA SWASTA TELADAN BANGSA',
            'nama'           => 'Raditya Pratama',
            'no_whatsapp'    => '082111222333',
            'asal_kelas'     => '12 MIPA 1',
            'prodi_id'       => (string) $this->prodi->id,
            'kelas'          => 'Reguler',
        ];

        $response = $this->post('/presensi', $payload);
        $response->assertRedirect(route('kontak-cepat.terima-kasih'));

        // 1. Record baru masuk ke master data sekolah
        $newSchool = Sekolah::where('nama', 'SMA SWASTA TELADAN BANGSA')->first();
        $this->assertNotNull($newSchool, 'Sekolah baru harus tersimpan di master Sekolah');
        $this->assertEquals('Aktif', $newSchool->status);
        $this->assertNull($newSchool->wilayah_id, 'Kota/kabupaten harus NULL menunggu kelengkapan Admin');

        // 2. Prospek terhubung ke record sekolah baru
        $prospek = Prospek::where('whatsapp', '082111222333')->first();
        $this->assertNotNull($prospek);
        $this->assertEquals($newSchool->id, $prospek->sekolah_id);
        $this->assertEquals(Prospek::SOURCE_PRESENSI_SISWA, $prospek->source);
        $this->assertEquals('SMA SWASTA TELADAN BANGSA', $prospek->name);

        // 3. Admin dapat melengkapi kota/kabupaten sekolah baru tersebut
        $kategori = \App\Models\MasterData::create([
            'kode'   => 'KAT-SMA',
            'type'   => 'kategori_sekolah',
            'nama'   => 'SMA',
            'status' => 'Aktif',
        ]);

        $updateResponse = $this->actingAs($this->admin)->from('/admin/sekolah')->put('/admin/sekolah/' . $newSchool->id, [
            'nama'        => $newSchool->nama,
            'kategori_id' => $kategori->id,
            'wilayah_id'  => $this->wilayah->id,
            'status'      => 'Aktif',
        ]);
        $updateResponse->assertRedirect('/admin/sekolah');
        $updateResponse->assertSessionHas('success');

        $newSchool->refresh();
        $this->assertEquals($this->wilayah->id, $newSchool->wilayah_id, 'Admin berhasil melengkapi wilayah sekolah');
        $this->assertEquals($kategori->id, $newSchool->kategori_id);
    }

    /**
     * Acceptance Criteria 7:
     * Tidak membuat record duplikat jika sekolah dengan nama yang sama sudah ada di master.
     */
    public function test_no_duplicate_school_created_if_exact_match_exists()
    {
        $existingSchool = Sekolah::create([
            'kode'       => 'SCH-EXACT01',
            'nama'       => 'SMK NEGERI 1 CIREBON',
            'wilayah_id' => $this->wilayah->id,
            'status'     => 'Aktif',
        ]);

        $initialCount = Sekolah::count();

        // Input manual dengan case berbeda
        $payload = [
            'jenis_instansi' => 'Sekolah',
            'sekolah_manual' => 'smk negeri 1 cirebon',
            'nama'           => 'Ahmad Zaki',
            'no_whatsapp'    => '082223334444',
            'asal_kelas'     => '12 TKJ 1',
            'prodi_id'       => (string) $this->prodi->id,
            'kelas'          => 'Reguler',
        ];

        $response = $this->post('/presensi', $payload);
        $response->assertRedirect(route('kontak-cepat.terima-kasih'));

        // Tidak ada duplicate record sekolah
        $this->assertEquals($initialCount, Sekolah::count());

        $prospek = Prospek::where('whatsapp', '082223334444')->first();
        $this->assertNotNull($prospek);
        $this->assertEquals($existingSchool->id, $prospek->sekolah_id);
    }
}
