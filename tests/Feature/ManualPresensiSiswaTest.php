<?php

namespace Tests\Feature;

use App\Models\Prodi;
use App\Models\Prospek;
use App\Models\ProspekTimeline;
use App\Models\Sekolah;
use App\Models\TahunAkademik;
use App\Models\User;    
use App\Models\Wilayah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManualPresensiSiswaTest extends TestCase
{
    use RefreshDatabase;

    protected User $cs;
    protected User $sales;
    protected Wilayah $wilayah;
    protected Sekolah $sekolah;
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
            'kode'     => 'IF',
            'nama'     => 'Teknik Informatika',
            'jenjang'  => 'S1',
            'fakultas' => 'Teknologi Informasi',
            'status'   => 'Aktif',
        ]);

        $this->wilayah = Wilayah::create([
            'nama'   => 'Cirebon',
            'kode'   => 'CRB',
            'status' => 'Aktif',
        ]);

        $this->sekolah = Sekolah::create([
            'kode'       => 'SCH-001',
            'nama'       => 'SMK Negeri 1 Cirebon',
            'wilayah_id' => $this->wilayah->id,
            'status'     => 'Aktif',
        ]);

        $this->cs = User::factory()->create([
            'name'       => 'CS Dina',
            'email'      => 'cs.dina@cic.ac.id',
            'role'       => 'CS',
            'wilayah_id' => $this->wilayah->id,
        ]);

        $this->sales = User::factory()->create([
            'name'       => 'Sales Aurel',
            'email'      => 'sales.aurel@cic.ac.id',
            'role'       => 'Sales',
            'wilayah_id' => $this->wilayah->id,
        ]);
    }

    public function test_cs_can_view_tambah_presensi_siswa_button_on_prospek_page()
    {
        $response = $this->actingAs($this->cs)->get('/prospek');

        $response->assertStatus(200);
        $response->assertSee('Tambah Presensi Siswa');
        $response->assertSee('modalPresensiManual');
        $response->assertSee('Tambah Presensi Siswa (Langsung)');
    }

    public function test_cs_can_manually_add_student_attendance_with_master_school()
    {
        $response = $this->actingAs($this->cs)->post(route('prospek.presensi-manual'), [
            'nama'        => 'Budi Santoso',
            'sekolah_id'  => $this->sekolah->id,
            'asal_kelas'  => '12 RPL 2',
            'no_whatsapp' => '81234567890',
            'wa_ortu'     => '81298765432',
            'prodi_id'    => $this->prodi->id,
            'kelas'       => 'Reguler',
            'notes'       => 'Hadir langsung di counter CS kampus utama',
        ]);

        $response->assertRedirect(route('prospek.index', ['scope' => 'presensi']));
        $response->assertSessionHas('success');

        $prospek = Prospek::where('pic', 'Budi Santoso')->first();
        $this->assertNotNull($prospek);
        $this->assertEquals(Prospek::SOURCE_PRESENSI_SISWA, $prospek->source);
        $this->assertNull($prospek->owner_id, 'Owner awal harus NULL (Sistem)');
        $this->assertNull($prospek->sales_id, 'Sales ID harus NULL');
        $this->assertNull($prospek->cs_id, 'CS ID harus NULL');
        $this->assertEquals('Belum Di-takeover', $prospek->status_takeover);
        $this->assertEquals('BARU', $prospek->status);
        $this->assertEquals('12 RPL 2', $prospek->asal_kelas);
        $this->assertEquals('+6281234567890', $prospek->whatsapp);
        $this->assertEquals('+6281298765432', $prospek->wa_ortu);
        $this->assertEquals($this->sekolah->id, $prospek->sekolah_id);
        $this->assertEquals($this->wilayah->id, $prospek->wilayah_id);
        $this->assertStringContainsString('Presensi siswa langsung dicatat manual oleh CS Dina (CS)', $prospek->notes);

        $timeline = ProspekTimeline::where('prospek_id', $prospek->id)->first();
        $this->assertNotNull($timeline);
        $this->assertEquals('Presensi Siswa Langsung Dicatat', $timeline->title);
        $this->assertEquals($this->cs->id, $timeline->user_id);
    }

    public function test_cs_can_manually_add_student_attendance_with_manual_school()
    {
        $response = $this->actingAs($this->cs)->post(route('prospek.presensi-manual'), [
            'nama'           => 'Siti Nurhaliza',
            'sekolah_manual' => 'SMA Swasta Harapan Bangsa',
            'asal_kelas'     => '12 IPS 1',
            'no_whatsapp'    => '81399887766',
            'prodi_id'       => 'lainnya',
            'prodi_lainnya'  => 'Digital Marketing & AI',
            'kelas'          => 'Karyawan',
        ]);

        $response->assertRedirect(route('prospek.index', ['scope' => 'presensi']));
        $response->assertSessionHas('success');

        $prospek = Prospek::where('pic', 'Siti Nurhaliza')->first();
        $this->assertNotNull($prospek);
        $this->assertEquals(Prospek::SOURCE_PRESENSI_SISWA, $prospek->source);

        // Sekolah baru otomatis tersimpan ke master Sekolah Admin dengan wilayah_id NULL (belum diverifikasi)
        $createdSchool = Sekolah::where('nama', 'SMA Swasta Harapan Bangsa')->first();
        $this->assertNotNull($createdSchool);
        $this->assertNull($createdSchool->wilayah_id);
        $this->assertEquals($createdSchool->id, $prospek->sekolah_id);
        $this->assertEquals('SMA Swasta Harapan Bangsa', $prospek->name);
        $this->assertEquals('Digital Marketing & AI', $prospek->prodi_lainnya);
        $this->assertEquals('Karyawan', $prospek->kelas);
        $this->assertStringContainsString('Sekolah baru otomatis ditambahkan ke Master Data: SMA Swasta Harapan Bangsa', $prospek->notes);
    }

    public function test_manual_attendance_requires_school_selection_or_manual_input()
    {
        $response = $this->actingAs($this->cs)->from(route('prospek.index'))->post(route('prospek.presensi-manual'), [
            'nama'        => 'Ahmad Fauzan',
            'asal_kelas'  => '12 IPA',
            'no_whatsapp' => '81122334455',
            'prodi_id'    => $this->prodi->id,
            'kelas'       => 'Reguler',
        ]);

        $response->assertRedirect(route('prospek.index'));
        $response->assertSessionHasErrors(['sekolah_id']);
        $this->assertDatabaseMissing('prospeks', ['pic' => 'Ahmad Fauzan']);
    }

    public function test_manual_attendance_saves_and_displays_when_optional_fields_are_empty()
    {
        // Leaving all optional fields (no_registrasi, gelombang, wa_ortu, notes) empty
        $response = $this->actingAs($this->cs)->post(route('prospek.presensi-manual'), [
            'nama'          => 'Rian Pratama',
            'no_registrasi' => '',
            'gelombang'     => '',
            'sekolah_id'    => $this->sekolah->id,
            'asal_kelas'    => '12 TKJ 1',
            'no_whatsapp'   => '87711223344',
            'wa_ortu'       => '',
            'prodi_id'      => $this->prodi->id,
            'kelas'         => 'Reguler',
            'notes'         => '',
        ]);

        $response->assertRedirect(route('prospek.index', ['scope' => 'presensi']));
        $response->assertSessionHas('success');

        // Verify record in database
        $prospek = Prospek::where('pic', 'Rian Pratama')->first();
        $this->assertNotNull($prospek, 'Data presensi siswa harus tersimpan ke database');
        $this->assertNull($prospek->no_registrasi);
        $this->assertNull($prospek->gelombang);
        $this->assertNull($prospek->wa_ortu);
        $this->assertEquals('+6287711223344', $prospek->whatsapp);
        $this->assertEquals('12 TKJ 1', $prospek->asal_kelas);
        $this->assertEquals(Prospek::SOURCE_PRESENSI_SISWA, $prospek->source);

        // Verify record appears in page view
        $viewResponse = $this->actingAs($this->cs)->get(route('prospek.index', ['scope' => 'presensi']));
        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('Rian Pratama');
        $viewResponse->assertSee('12 TKJ 1');

        // Also visible in "Semua Prospek"
        $allResponse = $this->actingAs($this->cs)->get(route('prospek.index'));
        $allResponse->assertStatus(200);
        $allResponse->assertSee('Rian Pratama');
    }

    public function test_manual_attendance_gracefully_normalizes_whatsapp_with_08_prefix()
    {
        $response = $this->actingAs($this->cs)->post(route('prospek.presensi-manual'), [
            'nama'        => 'Dewi Anggraeni',
            'sekolah_id'  => $this->sekolah->id,
            'asal_kelas'  => '12 Akuntansi',
            'no_whatsapp' => '08987654321', // Starts with 08
            'wa_ortu'     => '08123456789', // Starts with 08
            'prodi_id'    => $this->prodi->id,
            'kelas'       => 'Reguler',
        ]);

        $response->assertRedirect(route('prospek.index', ['scope' => 'presensi']));
        $response->assertSessionHas('success');

        $prospek = Prospek::where('pic', 'Dewi Anggraeni')->first();
        $this->assertNotNull($prospek);
        $this->assertEquals('+628987654321', $prospek->whatsapp);
        $this->assertEquals('+628123456789', $prospek->wa_ortu);
    }

    public function test_tambah_prospek_baru_with_empty_optional_fields_successfully_saved_and_displayed()
    {
        $response = $this->actingAs($this->cs)->post(route('prospek.store'), [
            'type'          => 'Sekolah',
            'sekolah_id'    => $this->sekolah->id,
            'kelas'         => 'Reguler',
            'prodi_id'      => '', // Kosong
            'prodi_lainnya' => '', // Kosong
            'status'        => 'BARU',
            'pic'           => 'Surya Kencana',
            'asal_kelas'    => '', // Kosong
            'whatsapp'      => '85544332211',
            'wa_ortu'       => '', // Kosong
            'source'        => 'Website UCIC',
            'no_registrasi' => '', // Kosong
            'gelombang'     => '', // Kosong
            'category'      => '', // Kosong
            'notes'         => '', // Kosong
        ]);

        $response->assertSessionHas('success');

        $prospek = Prospek::where('pic', 'Surya Kencana')->first();
        $this->assertNotNull($prospek, 'Prospek baru dengan opsional kosong harus tersimpan');
        $this->assertNull($prospek->no_registrasi);
        $this->assertNull($prospek->gelombang);
        $this->assertNull($prospek->wa_ortu);
        $this->assertNull($prospek->asal_kelas);
        $this->assertNull($prospek->notes);
        $this->assertNull($prospek->prodi_id);
        $this->assertEquals('+6285544332211', $prospek->whatsapp);

        // Verify it appears in Data Prospek view
        $viewResponse = $this->actingAs($this->cs)->get(route('prospek.index'));
        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('Surya Kencana');
    }

    public function test_tambah_prospek_baru_normalizes_whatsapp_with_08_prefix()
    {
        $response = $this->actingAs($this->cs)->post(route('prospek.store'), [
            'type'          => 'Sekolah',
            'sekolah_id'    => $this->sekolah->id,
            'kelas'         => 'Reguler',
            'status'        => 'BARU',
            'pic'           => 'Bima Arya',
            'whatsapp'      => '081299887766', // 08 prefix
            'wa_ortu'       => '081399887766', // 08 prefix
            'source'        => 'Website UCIC',
        ]);

        $response->assertSessionHas('success');

        $prospek = Prospek::where('pic', 'Bima Arya')->first();
        $this->assertNotNull($prospek);
        $this->assertEquals('+6281299887766', $prospek->whatsapp);
        $this->assertEquals('+6281399887766', $prospek->wa_ortu);
    }
}
