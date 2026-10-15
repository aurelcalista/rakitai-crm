<?php

namespace Tests\Feature;

use App\Models\KategoriSekolah;
use App\Models\Prospek;
use App\Models\ProspekTimeline;
use App\Models\Sekolah;
use App\Models\TahunAkademik;
use App\Models\User;
use App\Models\Wilayah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class PresensiSiswaAndTakeoverTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $salesA;
    protected User $salesB;
    protected User $csA;
    protected User $csB;
    protected Wilayah $wilayahA;
    protected Wilayah $wilayahB;
    protected TahunAkademik $ta;
    protected \App\Models\Prodi $prodi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ta = TahunAkademik::create([
            'nama' => '2026/2027',
            'status' => 'Aktif',
        ]);

        $this->prodi = \App\Models\Prodi::create([
            'kode' => 'IF-01',
            'nama' => 'Teknik Informatika',
            'jenjang' => 'S1',
            'fakultas' => 'Teknologi Informasi',
            'status' => 'Aktif',
        ]);

        $this->wilayahA = Wilayah::create([
            'nama' => 'Wilayah Cirebon',
            'kode' => 'CRB',
            'status' => 'Aktif',
        ]);

        $this->wilayahB = Wilayah::create([
            'nama' => 'Wilayah Bandung',
            'kode' => 'BDG',
            'status' => 'Aktif',
        ]);

        $this->admin = User::factory()->create([
            'name' => 'Admin User',
            'role' => 'Admin',
            'status' => 'Aktif',
        ]);

        $this->salesA = User::factory()->create([
            'name' => 'Sales Cirebon',
            'role' => 'Sales',
            'wilayah_id' => $this->wilayahA->id,
            'status' => 'Aktif',
        ]);

        $this->salesB = User::factory()->create([
            'name' => 'Sales Bandung',
            'role' => 'Sales',
            'wilayah_id' => $this->wilayahB->id,
            'status' => 'Aktif',
        ]);

        $this->csA = User::factory()->create([
            'name' => 'CS Cirebon',
            'role' => 'CS',
            'wilayah_id' => $this->wilayahA->id,
            'status' => 'Aktif',
        ]);

        $this->csB = User::factory()->create([
            'name' => 'CS Bandung',
            'role' => 'CS',
            'wilayah_id' => $this->wilayahB->id,
            'status' => 'Aktif',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SKENARIO FORM PRESENSI SISWA
    // ─────────────────────────────────────────────────────────────────────────

    /** Skenario 1: Siswa mengirim form dengan sekolah yang terdaftar dan lengkap */
    public function test_siswa_submits_form_with_registered_complete_school()
    {
        $sekolah = Sekolah::create([
            'kode' => 'SCH-001',
            'nama' => 'SMK Negeri 1 Cirebon',
            'wilayah_id' => $this->wilayahA->id,
            'kecamatan' => 'Kesambi',
            'status' => 'Aktif',
        ]);

        $payload = [
            'jenis_instansi' => 'Sekolah',
            'sekolah_id' => $sekolah->id,
            'nama' => 'Ahmad Siswa',
            'no_whatsapp' => '081234567890',
            'wa_ortu' => '081234567899',
            'asal_kelas' => 'XII RPL 1',
            'prodi_id' => (string) $this->prodi->id,
            'kelas' => 'Reguler',
        ];

        $response = $this->post('/presensi', $payload);
        $response->assertRedirect(route('kontak-cepat.terima-kasih'));

        $prospek = Prospek::where('whatsapp', '081234567890')->first();
        $this->assertNotNull($prospek);
        $this->assertEquals('Ahmad Siswa', $prospek->pic);
        $this->assertEquals(Prospek::SOURCE_PRESENSI_SISWA, $prospek->source);
        $this->assertEquals($sekolah->id, $prospek->sekolah_id);
        $this->assertEquals($this->wilayahA->id, $prospek->wilayah_id);

        // Owner awal Sistem: owner_id = null, belum ada sales/cs handler
        $this->assertNull($prospek->owner_id);
        $this->assertNull($prospek->sales_id);
        $this->assertNull($prospek->cs_id);
        $this->assertEquals('Belum Di-takeover', $prospek->status_takeover);
        $this->assertNull($prospek->takeover_by_id);
        $this->assertNull($prospek->takeover_at);
    }

    /** Skenario 2: Siswa mengirim form dengan sekolah yang terdaftar tetapi wilayah/kecamatan kosong */
    public function test_siswa_submits_form_with_registered_school_missing_wilayah()
    {
        $sekolah = Sekolah::create([
            'kode' => 'SCH-002',
            'nama' => 'SMA Tanpa Wilayah',
            'wilayah_id' => null,
            'kecamatan' => null,
            'status' => 'Aktif',
        ]);

        $payload = [
            'jenis_instansi' => 'Sekolah',
            'sekolah_id' => $sekolah->id,
            'nama' => 'Bambang Siswa',
            'no_whatsapp' => '082211223344',
            'asal_kelas' => 'XII IPA',
            'prodi_id' => (string) $this->prodi->id,
            'kelas' => 'Reguler',
        ];

        $response = $this->post('/presensi', $payload);
        $response->assertRedirect(route('kontak-cepat.terima-kasih'));

        $prospek = Prospek::where('whatsapp', '082211223344')->first();
        $this->assertNotNull($prospek);
        $this->assertEquals($sekolah->id, $prospek->sekolah_id);
        $this->assertNull($prospek->wilayah_id);
        $this->assertEquals('Belum Di-takeover', $prospek->status_takeover);
        $this->assertNull($prospek->owner_id);
    }

    /** Skenario 3: Siswa mengirim form dengan sekolah yang belum ada di master Admin (otomatis sinkron ke master sekolah) */
    public function test_siswa_submits_form_with_unregistered_school_manual()
    {
        $payload = [
            'jenis_instansi' => 'Sekolah',
            'sekolah_manual' => 'SMA Swasta Pelita Baru',
            'nama' => 'Citra Siswi',
            'no_whatsapp' => '083344556677',
            'asal_kelas' => 'XII IPS 2',
            'prodi_id' => (string) $this->prodi->id,
            'kelas' => 'Reguler',
        ];

        $response = $this->post('/presensi', $payload);
        $response->assertRedirect(route('kontak-cepat.terima-kasih'));

        // Pastikan sekolah baru otomatis tersimpan ke master Sekolah Admin
        $newSchool = Sekolah::where('nama', 'SMA Swasta Pelita Baru')->first();
        $this->assertNotNull($newSchool);
        $this->assertNull($newSchool->wilayah_id, 'Wilayah/kota harus NULL untuk dilengkapi Admin');
        $this->assertEquals('Aktif', $newSchool->status);

        $prospek = Prospek::where('whatsapp', '083344556677')->first();
        $this->assertNotNull($prospek);
        $this->assertEquals('SMA Swasta Pelita Baru', $prospek->name);
        $this->assertEquals('Citra Siswi', $prospek->pic);
        $this->assertEquals($newSchool->id, $prospek->sekolah_id, 'Prospek harus terhubung ke master sekolah yang baru dibuat');
        $this->assertNull($prospek->wilayah_id);
        $this->assertEquals(Prospek::SOURCE_PRESENSI_SISWA, $prospek->source);
    }

    /** Skenario 6 & 7: Tidak ada penugasan otomatis meskipun master sekolah punya sales_id */
    public function test_initial_owner_is_system_without_auto_assignment_even_if_school_has_sales()
    {
        $sekolah = Sekolah::create([
            'kode' => 'SCH-003',
            'nama' => 'SMA Bina Warga Cirebon',
            'wilayah_id' => $this->wilayahA->id,
            'sales_id' => $this->salesA->id, // Sekolah ini dibawahi Sales A
            'status' => 'Aktif',
        ]);

        $payload = [
            'jenis_instansi' => 'Sekolah',
            'sekolah_id' => $sekolah->id,
            'nama' => 'Dodi Siswa',
            'no_whatsapp' => '085566778899',
            'prodi_id' => (string) $this->prodi->id,
            'kelas' => 'Reguler',
        ];

        $response = $this->post('/presensi', $payload);
        $response->assertRedirect(route('kontak-cepat.terima-kasih'));

        $prospek = Prospek::where('whatsapp', '085566778899')->first();
        $this->assertNotNull($prospek);

        // Harus tetap Sistem (owner_id = null) dan TIDAK otomatis ditugaskan ke salesA
        $this->assertNull($prospek->owner_id);
        $this->assertNull($prospek->sales_id);
        $this->assertNull($prospek->cs_id);
        $this->assertEquals('Belum Di-takeover', $prospek->status_takeover);
        $this->assertNull($prospek->takeover_by_id);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SKENARIO FILTER & AKSES LINTAS WILAYAH SALES
    // ─────────────────────────────────────────────────────────────────────────

    /** Skenario 8-11: Filter Prospek Saya, Presensi Siswa, dan User Lain */
    public function test_multirole_prospect_filters()
    {
        // 1. Prospek Sales A
        $pSalesA = Prospek::create([
            'name' => 'Prospek Sales A',
            'pic' => 'PIC A',
            'whatsapp' => '08111111111',
            'type' => 'Sekolah',
            'status' => 'BARU',
            'stage_number' => 1,
            'sales_id' => $this->salesA->id,
            'owner_id' => $this->salesA->id,
            'source' => 'Manual Sales',
            'academic_year_id' => $this->ta->id,
        ]);

        // 2. Prospek Sales B
        $pSalesB = Prospek::create([
            'name' => 'Prospek Sales B',
            'pic' => 'PIC B',
            'whatsapp' => '08222222222',
            'type' => 'Sekolah',
            'status' => 'BARU',
            'stage_number' => 1,
            'sales_id' => $this->salesB->id,
            'owner_id' => $this->salesB->id,
            'source' => 'Manual Sales',
            'academic_year_id' => $this->ta->id,
        ]);

        // 3. Prospek Presensi Siswa (Belum diambil alih)
        $pPresensi = Prospek::create([
            'name' => 'Siswa Presensi',
            'pic' => 'Siswa 1',
            'whatsapp' => '08333333333',
            'type' => 'Sekolah',
            'status' => 'BARU',
            'stage_number' => 1,
            'owner_id' => null,
            'sales_id' => null,
            'source' => Prospek::SOURCE_PRESENSI_SISWA,
            'status_takeover' => 'Belum Di-takeover',
            'academic_year_id' => $this->ta->id,
        ]);

        // Test scope=presensi: Sales A requests scope=presensi
        $resPresensi = $this->actingAs($this->salesA)->get(route('sales.prospek.index', ['scope' => 'presensi']));
        $resPresensi->assertStatus(200);
        $presensiNames = collect($resPresensi->viewData('prospects'))->pluck('name');
        $this->assertTrue($presensiNames->contains('Siswa Presensi'));
        $this->assertFalse($presensiNames->contains('Prospek Sales A'));
        $this->assertFalse($presensiNames->contains('Prospek Sales B'));

        // Test scope=own: Sales A requests scope=own
        $resOwn = $this->actingAs($this->salesA)->get(route('sales.prospek.index', ['scope' => 'own']));
        $resOwn->assertStatus(200);
        $ownNames = collect($resOwn->viewData('prospects'))->pluck('name');
        $this->assertTrue($ownNames->contains('Prospek Sales A'));
        $this->assertFalse($ownNames->contains('Prospek Sales B'));
        $this->assertFalse($ownNames->contains('Siswa Presensi'));
    }

    /** Skenario 12 & 13: Sales dapat melihat Presensi Siswa lintas wilayah */
    public function test_sales_can_see_presensi_siswa_cross_wilayah_and_view_detail()
    {
        // Presensi Siswa dari Wilayah Bandung (B)
        $pPresensiBandung = Prospek::create([
            'name' => 'Siswa Bandung',
            'pic' => 'Eka Siswa',
            'whatsapp' => '089988776655',
            'type' => 'Sekolah',
            'status' => 'BARU',
            'stage_number' => 1,
            'wilayah_id' => $this->wilayahB->id,
            'owner_id' => null,
            'sales_id' => null,
            'source' => Prospek::SOURCE_PRESENSI_SISWA,
            'status_takeover' => 'Belum Di-takeover',
            'academic_year_id' => $this->ta->id,
        ]);

        // Sales A (Wilayah Cirebon) can view Presensi Siswa in Bandung
        $this->assertTrue(Gate::forUser($this->salesA)->allows('view', $pPresensiBandung));

        // Sales A opens detail page
        $res = $this->actingAs($this->salesA)->get(route('sales.prospek.show', $pPresensiBandung));
        $res->assertStatus(200);
        $res->assertSee('Siswa Bandung');
        $res->assertSee('Presensi Siswa');
        $res->assertSee('Sistem');
        $res->assertSee('Takeover Presensi Siswa');
    }

    /** Skenario 14: CS wilayah scoping for Presensi Siswa */
    public function test_cs_wilayah_scoping_for_presensi_siswa()
    {
        // Presensi Siswa di Wilayah B (Bandung)
        $pPresensiBandung = Prospek::create([
            'name' => 'Siswa Bandung 2',
            'pic' => 'Feri Siswa',
            'whatsapp' => '087788990011',
            'type' => 'Sekolah',
            'status' => 'BARU',
            'stage_number' => 1,
            'wilayah_id' => $this->wilayahB->id,
            'owner_id' => null,
            'sales_id' => null,
            'source' => Prospek::SOURCE_PRESENSI_SISWA,
            'status_takeover' => 'Belum Di-takeover',
            'academic_year_id' => $this->ta->id,
        ]);

        // CS B (Bandung) allows view and takeover
        $this->assertTrue(Gate::forUser($this->csB)->allows('view', $pPresensiBandung));
        $this->assertTrue(Gate::forUser($this->csB)->allows('takeover', $pPresensiBandung));

        // CS A (Cirebon) can view Presensi Siswa in Bandung (open view per user requirement), but cannot takeover
        $this->assertTrue(Gate::forUser($this->csA)->allows('view', $pPresensiBandung));
        $this->assertFalse(Gate::forUser($this->csA)->allows('takeover', $pPresensiBandung));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SKENARIO TAKEOVER & PENCEGAHAN TAKEOVER GANDA
    // ─────────────────────────────────────────────────────────────────────────

    /** Skenario 16 & 18: Sales berwenang takeover Presensi Siswa yang belum diambil alih */
    public function test_sales_can_takeover_presensi_siswa()
    {
        $prospek = Prospek::create([
            'name' => 'Siswa Takeover Test',
            'pic' => 'Gilang',
            'whatsapp' => '081299887766',
            'type' => 'Sekolah',
            'status' => 'BARU',
            'stage_number' => 1,
            'wilayah_id' => $this->wilayahB->id,
            'owner_id' => null,
            'sales_id' => null,
            'source' => Prospek::SOURCE_PRESENSI_SISWA,
            'status_takeover' => 'Belum Di-takeover',
            'academic_year_id' => $this->ta->id,
        ]);

        $response = $this->actingAs($this->salesA)->post(route('sales.prospek.takeover', $prospek));
        $response->assertSessionHas('success');

        $prospek->refresh();

        // Presensi Siswa takeover attributes
        $this->assertEquals('Sudah Di-takeover', $prospek->status_takeover);
        $this->assertEquals($this->salesA->id, $prospek->takeover_by_id);
        $this->assertEquals($this->salesA->id, $prospek->sales_id);
        $this->assertNotNull($prospek->takeover_at);

        // Owner awal Sistem tetap tidak ditimpa
        $this->assertNull($prospek->owner_id);

        // Riwayat timeline tercatat
        $this->assertDatabaseHas('prospek_timelines', [
            'prospek_id' => $prospek->id,
            'user_id' => $this->salesA->id,
            'title' => 'Takeover Presensi Siswa',
        ]);
    }

    /** Skenario 19 & 20: Pencegahan takeover ganda secara atomik */
    public function test_double_takeover_is_atomically_prevented()
    {
        $prospek = Prospek::create([
            'name' => 'Siswa Double Takeover Test',
            'pic' => 'Hendra',
            'whatsapp' => '081399887766',
            'type' => 'Sekolah',
            'status' => 'BARU',
            'stage_number' => 1,
            'owner_id' => null,
            'sales_id' => null,
            'source' => Prospek::SOURCE_PRESENSI_SISWA,
            'status_takeover' => 'Belum Di-takeover',
            'academic_year_id' => $this->ta->id,
        ]);

        // Request 1: Sales A takeovers successfully
        $res1 = $this->actingAs($this->salesA)->post(route('sales.prospek.takeover', $prospek));
        $res1->assertSessionHas('success');

        // Request 2: Sales B attempts to takeover the same prospect
        $res2 = $this->actingAs($this->salesB)->post(route('sales.prospek.takeover', $prospek));

        // Must be rejected (either 403 authorization denial or redirect back with error)
        $prospek->refresh();
        $this->assertEquals($this->salesA->id, $prospek->takeover_by_id);
        $this->assertEquals($this->salesA->id, $prospek->sales_id);
        $this->assertNotEquals($this->salesB->id, $prospek->takeover_by_id);

        // Hanya 1 timeline takeover yang tercipta
        $timelineCount = ProspekTimeline::where('prospek_id', $prospek->id)
            ->where('title', 'Takeover Presensi Siswa')
            ->count();
        $this->assertEquals(1, $timelineCount);
    }

    /** Skenario 21: Tampering user ID in takeover request is ignored, auth user always used */
    public function test_takeover_uses_authenticated_user_id_never_tampered_payload()
    {
        $prospek = Prospek::create([
            'name' => 'Siswa Tamper Test',
            'pic' => 'Indra',
            'whatsapp' => '081499887766',
            'type' => 'Sekolah',
            'status' => 'BARU',
            'stage_number' => 1,
            'owner_id' => null,
            'sales_id' => null,
            'source' => Prospek::SOURCE_PRESENSI_SISWA,
            'status_takeover' => 'Belum Di-takeover',
            'academic_year_id' => $this->ta->id,
        ]);

        // Malicious request sending sales_id or takeover_by_id = admin->id
        $this->actingAs($this->salesA)->post(route('sales.prospek.takeover', $prospek), [
            'sales_id' => $this->admin->id,
            'takeover_by_id' => $this->admin->id,
        ]);

        $prospek->refresh();
        $this->assertEquals($this->salesA->id, $prospek->takeover_by_id);
        $this->assertEquals($this->salesA->id, $prospek->sales_id);
        $this->assertNotEquals($this->admin->id, $prospek->takeover_by_id);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SKENARIO REGRESI
    // ─────────────────────────────────────────────────────────────────────────

    /** Skenario 24 & 27: Prospek dengan relasi NULL tetap dapat dirender tanpa error 500 */
    public function test_null_relations_do_not_throw_errors_in_show_view()
    {
        $prospek = Prospek::create([
            'name' => 'Prospek Data Null',
            'pic' => 'Joko',
            'whatsapp' => '081599887766',
            'type' => 'Individu',
            'status' => 'BARU',
            'stage_number' => 1,
            'owner_id' => null,
            'sales_id' => $this->salesA->id,
            'sekolah_id' => null,
            'wilayah_id' => null,
            'source' => 'Manual Input',
            'status_takeover' => null,
            'takeover_by_id' => null,
            'academic_year_id' => $this->ta->id,
        ]);

        $response = $this->actingAs($this->salesA)->get(route('sales.prospek.show', $prospek));
        $response->assertStatus(200);
        $response->assertSee('Prospek Data Null');
    }

    /** Skenario 17: CS takeover dalam wilayah kerja diizinkan */
    public function test_cs_can_takeover_presensi_siswa_in_territory()
    {
        $prospek = Prospek::create([
            'name' => 'Siswa CS Takeover Cirebon',
            'pic' => 'Karina',
            'whatsapp' => '081699887766',
            'type' => 'Sekolah',
            'status' => 'BARU',
            'stage_number' => 1,
            'wilayah_id' => $this->wilayahA->id,
            'owner_id' => null,
            'sales_id' => null,
            'source' => Prospek::SOURCE_PRESENSI_SISWA,
            'status_takeover' => 'Belum Di-takeover',
            'academic_year_id' => $this->ta->id,
        ]);

        $response = $this->actingAs($this->csA)->post(route('prospek.takeover', $prospek->id));
        $response->assertSessionHas('success');

        $prospek->refresh();
        $this->assertEquals($this->csA->id, $prospek->cs_id);
        $this->assertEquals($this->csA->id, $prospek->takeover_by_id);
        $this->assertEquals('Sudah Di-takeover', $prospek->status_takeover);
        $this->assertNull($prospek->owner_id);
    }

    /** Skenario 22: CS dari luar wilayah ditolak melakukan takeover */
    public function test_cs_outside_territory_cannot_takeover_presensi_siswa()
    {
        $prospek = Prospek::create([
            'name' => 'Siswa CS Takeover Bandung',
            'pic' => 'Lukman',
            'whatsapp' => '081799887766',
            'type' => 'Sekolah',
            'status' => 'BARU',
            'stage_number' => 1,
            'wilayah_id' => $this->wilayahB->id, // Wilayah Bandung
            'owner_id' => null,
            'sales_id' => null,
            'source' => Prospek::SOURCE_PRESENSI_SISWA,
            'status_takeover' => 'Belum Di-takeover',
            'academic_year_id' => $this->ta->id,
        ]);

        // CS A (Cirebon) mencoba takeover prospek Bandung
        $response = $this->actingAs($this->csA)->post(route('prospek.takeover', $prospek->id));
        $response->assertStatus(403);

        $prospek->refresh();
        $this->assertNull($prospek->cs_id);
        $this->assertNull($prospek->takeover_by_id);
        $this->assertEquals('Belum Di-takeover', $prospek->status_takeover);
    }

    /** Skenario Form Corporate Presensi */
    public function test_corporate_attendance_form_saves_as_presensi_siswa_source()
    {
        $perusahaan = \App\Models\Perusahaan::create([
            'kode' => 'CORP-01',
            'nama' => 'PT Cirebon Maju Bersama',
            'wilayah_id' => $this->wilayahA->id,
            'status' => 'Aktif',
        ]);

        $payload = [
            'perusahaan_id' => $perusahaan->id,
            'nama' => 'Mario PIC',
            'no_whatsapp' => '081899887766',
            'asal_kelas' => 'Staff IT',
            'prodi_id' => (string) $this->prodi->id,
            'kelas' => 'Karyawan',
        ];

        $response = $this->post('/presensi-corporate', $payload);
        $response->assertRedirect(route('kontak-cepat.terima-kasih-corporate'));

        $prospek = Prospek::where('whatsapp', '081899887766')->first();
        $this->assertNotNull($prospek);
        $this->assertEquals(Prospek::SOURCE_PRESENSI_SISWA, $prospek->source);
        $this->assertEquals('Corporate', $prospek->type);
        $this->assertNull($prospek->owner_id);
        $this->assertNull($prospek->sales_id);
        $this->assertNull($prospek->cs_id);
        $this->assertEquals('Belum Di-takeover', $prospek->status_takeover);
    }

    /** Skenario Filter Konsisten pada Pencarian dan Parameter URL */
    public function test_search_and_filter_parameters_consistency()
    {
        Prospek::create([
            'name' => 'Siswa SMK Khusus',
            'pic' => 'Nadia Khusus',
            'whatsapp' => '081999887766',
            'type' => 'Sekolah',
            'status' => 'BARU',
            'stage_number' => 1,
            'owner_id' => null,
            'sales_id' => null,
            'source' => Prospek::SOURCE_PRESENSI_SISWA,
            'status_takeover' => 'Belum Di-takeover',
            'academic_year_id' => $this->ta->id,
        ]);

        $response = $this->actingAs($this->salesA)->get(route('sales.prospek.index', [
            'scope' => 'presensi',
            'q' => 'Nadia Khusus',
        ]));

        $response->assertStatus(200);
        $prospects = collect($response->viewData('prospects'));
        $this->assertEquals(1, $prospects->count());
        $this->assertEquals('Nadia Khusus', $prospects->first()['pic']);
    }

    /** Halaman form presensi siswa /presensi dapat diakses tanpa Blade parse error */
    public function test_form_presensi_siswa_dapat_dibuka_tanpa_error()
    {
        $response = $this->get('/presensi');
        $response->assertStatus(200);
        $response->assertSee('Nama Lengkap');
        $response->assertSee('Nama Sekolah');
    }
}
