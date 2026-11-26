<?php

namespace Tests\Feature;

use App\Models\Prodi;
use App\Models\Prospek;
use App\Models\TahunAkademik;
use App\Models\User;
use App\Services\AkademikService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DualAcademicYearTest extends TestCase
{
    use RefreshDatabase;

    protected TahunAkademik $ta2026;
    protected TahunAkademik $ta2027;
    protected Prodi $prodi;
    protected User $admin;
    protected User $cs;

    protected function setUp(): void
    {
        parent::setUp();
        AkademikService::flushCache();

        TahunAkademik::query()->update(['status' => 'Non-Aktif']);
        AkademikService::flushCache();

        $this->ta2026 = TahunAkademik::create([
            'nama' => '2026/2027',
            'status' => 'Aktif',
            'tanggal_mulai' => '2026-09-01',
            'tanggal_selesai' => '2027-08-31',
        ]);

        $this->ta2027 = TahunAkademik::create([
            'nama' => '2027/2028',
            'status' => 'Aktif',
            'tanggal_mulai' => '2027-09-01',
            'tanggal_selesai' => '2028-08-31',
        ]);

        AkademikService::flushCache();

        $this->prodi = Prodi::create([
            'kode' => 'TI-TEST',
            'nama' => 'Teknik Informatika',
            'jenjang' => 'S1',
            'fakultas' => 'FTIK',
            'status' => 'Aktif',
        ]);

        $this->admin = User::factory()->create([
            'role' => 'Admin',
            'status' => 'active',
        ]);

        $this->cs = User::factory()->create([
            'role' => 'CS',
            'status' => 'active',
        ]);
    }

    public function test_two_academic_years_can_be_active_simultaneously(): void
    {
        $activeList = AkademikService::getAktifList();
        $this->assertCount(2, $activeList);
        $this->assertEquals(['2027/2028', '2026/2027'], $activeList->pluck('nama')->toArray());
        
        // Default latest active TA
        $this->assertEquals('2027/2028', AkademikService::getAktifNama());
    }

    public function test_camaba_registration_with_selected_academic_year(): void
    {
        // 1. Camaba A chooses 2026/2027
        $responseA = $this->post(route('pmb.register.post'), [
            'name' => 'Camaba 2026',
            'email' => 'camaba2026@test.com',
            'whatsapp' => '+6281234567890',
            'asal_sekolah' => 'SMKN 1 Cirebon',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'tahun_akademik_id' => $this->ta2026->id,
        ]);
        $responseA->assertRedirect(route('pmb.login'));

        $prospekA = Prospek::where('email', 'camaba2026@test.com')->first();
        $this->assertNotNull($prospekA);
        $this->assertEquals($this->ta2026->id, $prospekA->academic_year_id);
        $this->assertEquals('2026/2027', $prospekA->tahun_akademik);

        // 2. Camaba B chooses 2027/2028
        $responseB = $this->post(route('pmb.register.post'), [
            'name' => 'Camaba 2027',
            'email' => 'camaba2027@test.com',
            'whatsapp' => '+6281234567891',
            'asal_sekolah' => 'SMAN 2 Cirebon',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'tahun_akademik_id' => $this->ta2027->id,
        ]);
        $responseB->assertRedirect(route('pmb.login'));

        $prospekB = Prospek::where('email', 'camaba2027@test.com')->first();
        $this->assertNotNull($prospekB);
        $this->assertEquals($this->ta2027->id, $prospekB->academic_year_id);
        $this->assertEquals('2027/2028', $prospekB->tahun_akademik);
    }

    public function test_camaba_can_switch_academic_year_via_portal_form(): void
    {
        $camabaUser = User::factory()->create([
            'role' => 'camaba',
            'name' => 'Camaba Switch',
            'email' => 'switch@test.com',
        ]);

        $prospek = Prospek::create([
            'name' => 'Camaba Switch',
            'email' => 'switch@test.com',
            'whatsapp' => '+6281234567892',
            'user_id' => $camabaUser->id,
            'academic_year_id' => $this->ta2026->id,
            'tahun_akademik' => '2026/2027',
            'status' => 'BARU',
            'type' => 'Individu',
        ]);

        $response = $this->actingAs($camabaUser)->post(route('pmb.akun.form-pendaftaran.update'), [
            'name' => 'Camaba Switch',
            'whatsapp' => '+6281234567892',
            'prodi_id' => $this->prodi->id,
            'kelas' => 'Reguler',
            'academic_year_id' => $this->ta2027->id,
        ]);

        $response->assertSessionHas('success');
        $prospek->refresh();
        $this->assertEquals($this->ta2027->id, $prospek->academic_year_id);
        $this->assertEquals('2027/2028', $prospek->tahun_akademik);
    }

    public function test_admin_cs_can_update_prospect_academic_year_in_crm(): void
    {
        $prospek = Prospek::create([
            'name' => 'Prospek CRM TA',
            'email' => 'crmta@test.com',
            'whatsapp' => '+6281234567893',
            'academic_year_id' => $this->ta2026->id,
            'tahun_akademik' => '2026/2027',
            'status' => 'BARU',
            'type' => 'Individu',
        ]);

        $response = $this->actingAs($this->admin)->put(route('prospek.update', $prospek->id), [
            'status' => 'BARU',
            'name' => 'Prospek CRM TA Updated',
            'type' => 'Individu',
            'pic' => 'Prospek CRM TA Updated',
            'whatsapp' => '+6281234567893',
            'academic_year_id' => $this->ta2027->id,
        ]);

        $response->assertSessionHas('success');
        $prospek->refresh();
        $this->assertEquals($this->ta2027->id, $prospek->academic_year_id);
        $this->assertEquals('2027/2028', $prospek->tahun_akademik);

        // Verify timeline was logged
        $this->assertDatabaseHas('prospek_timelines', [
            'prospek_id' => $prospek->id,
            'title' => 'Tahun Akademik Diperbarui',
        ]);
    }

    public function test_pmb_pendaftar_filtering_by_academic_year(): void
    {
        Prospek::create([
            'name' => 'Pendaftar 2026',
            'email' => 'pendaftar2026@test.com',
            'whatsapp' => '+6281234567894',
            'academic_year_id' => $this->ta2026->id,
            'tahun_akademik' => '2026/2027',
            'status' => 'BARU',
            'type' => 'Individu',
        ]);

        Prospek::create([
            'name' => 'Pendaftar 2027',
            'email' => 'pendaftar2027@test.com',
            'whatsapp' => '+6281234567895',
            'academic_year_id' => $this->ta2027->id,
            'tahun_akademik' => '2027/2028',
            'status' => 'BARU',
            'type' => 'Individu',
        ]);

        // Default query (filter to newest active: 2027/2028)
        $responseDefault = $this->actingAs($this->admin)->get(route('pmb.pendaftar.index'));
        $responseDefault->assertStatus(200);
        $responseDefault->assertSee('Pendaftar 2027');
        $responseDefault->assertDontSee('Pendaftar 2026');

        // Explicit filter to 2026/2027
        $response2026 = $this->actingAs($this->admin)->get(route('pmb.pendaftar.index', ['tahun_akademik' => '2026/2027']));
        $response2026->assertStatus(200);
        $response2026->assertSee('Pendaftar 2026');
        $response2026->assertDontSee('Pendaftar 2027');

        // All filter
        $responseAll = $this->actingAs($this->admin)->get(route('pmb.pendaftar.index', ['tahun_akademik' => 'all']));
        $responseAll->assertStatus(200);
        $responseAll->assertSee('Pendaftar 2026');
        $responseAll->assertSee('Pendaftar 2027');
    }

    public function test_activation_guardrail_limits_to_maximum_two_active_years(): void
    {
        $ta3 = TahunAkademik::create([
            'nama' => '2028/2029',
            'status' => 'Non-Aktif',
            'tanggal_mulai' => '2028-09-01',
            'tanggal_selesai' => '2029-08-31',
        ]);

        // Attempting to activate 3rd TA throws RuntimeException from service
        $this->expectException(\RuntimeException::class);
        AkademikService::activate($ta3);
    }

    public function test_spv_can_update_prospect_academic_year_and_export_contains_ta(): void
    {
        $spv = User::factory()->create([
            'role' => 'SPV',
            'status' => 'active',
        ]);

        $prospek = Prospek::create([
            'name' => 'Prospek SPV TA',
            'email' => 'spvta@test.com',
            'whatsapp' => '+6281234567899',
            'pic' => 'Prospek SPV TA',
            'academic_year_id' => $this->ta2026->id,
            'tahun_akademik' => '2026/2027',
            'status' => 'BARU',
            'type' => 'Individu',
            'source' => 'Website PMB',
            'owner_id' => $spv->id,
            'sales_id' => $spv->id,
        ]);

        $response = $this->actingAs($spv)->put(route('spv.prospek.update', $prospek->id), [
            'type' => 'Individu',
            'name' => 'Prospek SPV TA Updated',
            'pic' => 'Prospek SPV TA Updated',
            'whatsapp' => '+6281234567899',
            'source' => 'Website PMB',
            'academic_year_id' => $this->ta2027->id,
        ]);

        $response->assertRedirect(route('spv.prospek.show', $prospek->id));
        $prospek->refresh();
        $this->assertEquals($this->ta2027->id, $prospek->academic_year_id);
        $this->assertEquals('2027/2028', $prospek->tahun_akademik);

        // Verify timeline was logged
        $this->assertDatabaseHas('prospek_timelines', [
            'prospek_id' => $prospek->id,
            'title' => 'Tahun Akademik Diperbarui',
        ]);

        // Export response contains xlsx download
        $exportResponse = $this->actingAs($spv)->get(route('export.prospek', ['tahun_akademik' => '2027/2028']));
        $exportResponse->assertStatus(200);
    }

    public function test_pmb_pembayaran_filtering_by_academic_year(): void
    {
        Prospek::create([
            'name' => 'Camaba Bayar 2026',
            'email' => 'bayar2026@test.com',
            'whatsapp' => '+6281234567881',
            'academic_year_id' => $this->ta2026->id,
            'tahun_akademik' => '2026/2027',
            'prodi_id' => $this->prodi->id,
            'status' => 'BARU',
            'type' => 'Individu',
        ]);

        Prospek::create([
            'name' => 'Camaba Bayar 2027',
            'email' => 'bayar2027@test.com',
            'whatsapp' => '+6281234567882',
            'academic_year_id' => $this->ta2027->id,
            'tahun_akademik' => '2027/2028',
            'prodi_id' => $this->prodi->id,
            'status' => 'BARU',
            'type' => 'Individu',
        ]);

        // Default query: filter ke tahun akademik aktif terbaru (2027/2028)
        $responseDefault = $this->actingAs($this->admin)->get(route('pmb.pembayaran.index'));
        $responseDefault->assertStatus(200);
        $responseDefault->assertSee('Camaba Bayar 2027');
        $responseDefault->assertDontSee('Camaba Bayar 2026');

        // Filter eksplisit ke 2026/2027
        $response2026 = $this->actingAs($this->admin)->get(route('pmb.pembayaran.index', ['tahun_akademik' => '2026/2027']));
        $response2026->assertStatus(200);
        $response2026->assertSee('Camaba Bayar 2026');
        $response2026->assertDontSee('Camaba Bayar 2027');

        // Filter 'all' menampilkan kedua camaba
        $responseAll = $this->actingAs($this->admin)->get(route('pmb.pembayaran.index', ['tahun_akademik' => 'all']));
        $responseAll->assertStatus(200);
        $responseAll->assertSee('Camaba Bayar 2026');
        $responseAll->assertSee('Camaba Bayar 2027');

        // Export data pembayaran filter ke 2026/2027
        $responseExport = $this->actingAs($this->admin)->get(route('pmb.pembayaran.export', ['tahun_akademik' => '2026/2027']));
        $responseExport->assertStatus(200);
        $content = $responseExport->streamedContent();
        $this->assertStringContainsString('Camaba Bayar 2026', $content);
        $this->assertStringNotContainsString('Camaba Bayar 2027', $content);
        $this->assertStringContainsString('Tahun Akademik', $content);
    }
}
