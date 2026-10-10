<?php

namespace Tests\Feature;

use App\Models\Prodi;
use App\Models\Prospek;
use App\Models\Sekolah;
use App\Models\TahunAkademik;
use App\Models\Transaksi;
use App\Models\User;
use App\Models\Wilayah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class DataAnalystRoleTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $analyst;
    protected User $spv;
    protected User $sales;
    protected User $cs;
    protected Wilayah $wilayah1;
    protected Wilayah $wilayah2;
    protected TahunAkademik $ta;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ta = TahunAkademik::create([
            'nama'   => '2026/2027',
            'status' => 'Aktif',
        ]);

        $this->wilayah1 = Wilayah::create([
            'kode'   => 'KOTA-CRB',
            'nama'   => 'Kota Cirebon',
            'level'  => 'Kota/Kabupaten',
            'status' => 'Aktif',
        ]);

        $this->wilayah2 = Wilayah::create([
            'kode'   => 'KAB-TGL',
            'nama'   => 'Kabupaten Tegal',
            'level'  => 'Kota/Kabupaten',
            'status' => 'Aktif',
        ]);

        $this->admin = User::create([
            'name'     => 'Super Admin',
            'email'    => 'admin@cic.ac.id',
            'password' => bcrypt('password'),
            'role'     => 'Admin',
            'status'   => 'Aktif',
        ]);

        $this->analyst = User::create([
            'name'     => 'Analyst User',
            'email'    => 'analyst@cic.ac.id',
            'password' => bcrypt('password'),
            'role'     => 'Data Analyst',
            'status'   => 'Aktif',
        ]);

        $this->spv = User::create([
            'name'       => 'Supervisor Satu',
            'email'      => 'spv@cic.ac.id',
            'password'   => bcrypt('password'),
            'role'       => 'SPV',
            'status'     => 'Aktif',
            'wilayah_id' => $this->wilayah1->id,
        ]);

        $this->sales = User::create([
            'name'          => 'Sales Satu',
            'email'         => 'sales@cic.ac.id',
            'password'      => bcrypt('password'),
            'role'          => 'Sales',
            'status'        => 'Aktif',
            'supervisor_id' => $this->spv->id,
            'wilayah_id'    => $this->wilayah1->id,
        ]);

        $this->cs = User::create([
            'name'       => 'CS Satu',
            'email'      => 'cs@cic.ac.id',
            'password'   => bcrypt('password'),
            'role'       => 'CS',
            'status'     => 'Aktif',
            'wilayah_id' => $this->wilayah1->id,
        ]);
    }

    /** @test */
    public function test_data_analyst_user_creation_and_code_generation()
    {
        $ymPrefix = date('ym');
        $this->assertStringStartsWith("{$ymPrefix}D", $this->analyst->kode);

        // Create second analyst to verify sequence increment
        $analyst2 = User::create([
            'name'     => 'Analyst Dua',
            'email'    => 'analyst2@cic.ac.id',
            'password' => bcrypt('password'),
            'role'     => 'Data Analyst',
            'status'   => 'Aktif',
        ]);

        $this->assertEquals("{$ymPrefix}D002", $analyst2->kode);
    }

    /** @test */
    public function test_data_analyst_can_access_dashboard_and_is_redirected_from_generic_dashboard()
    {
        // Accessing generic /dashboard should redirect to /dashboard/data-analyst
        $response = $this->actingAs($this->analyst)->get('/dashboard');
        $response->assertRedirect(route('dashboard.data-analyst'));

        // Accessing /dashboard/data-analyst directly should return 200 OK
        $dashboardResponse = $this->actingAs($this->analyst)->get('/dashboard/data-analyst');
        $dashboardResponse->assertStatus(200);
        $dashboardResponse->assertSee('Dashboard PMB');
    }

    /** @test */
    public function test_data_analyst_has_global_read_access_across_regions_and_teams()
    {
        // Prospect 1 in Wilayah 1 (assigned to Sales 1)
        $prospek1 = Prospek::create([
            'name'        => 'Calon Cirebon',
            'whatsapp'    => '081234567890',
            'status'      => 'BARU',
            'type'        => 'Mahasiswa',
            'source'      => 'Sekolah',
            'wilayah_id'  => $this->wilayah1->id,
            'sales_id'    => $this->sales->id,
        ]);

        // Prospect 2 in Wilayah 2 (unassigned)
        $prospek2 = Prospek::create([
            'name'        => 'Calon Tegal',
            'whatsapp'    => '089876543210',
            'status'      => 'FORMULIR',
            'type'        => 'Mahasiswa',
            'source'      => 'Website CIC',
            'wilayah_id'  => $this->wilayah2->id,
        ]);

        // Policies allow Data Analyst to view any prospect across all wilayahs
        $this->assertTrue(Gate::forUser($this->analyst)->allows('view', $prospek1));
        $this->assertTrue(Gate::forUser($this->analyst)->allows('view', $prospek2));

        // Prospect Explorer page displays both prospects
        $response = $this->actingAs($this->analyst)->get(route('analyst.prospek.index'));
        $response->assertStatus(200);
        $response->assertSee('Calon Cirebon');
        $response->assertSee('Calon Tegal');
    }

    /** @test */
    public function test_data_analyst_cannot_perform_write_or_operational_actions()
    {
        $prospek = Prospek::create([
            'name'       => 'Calon Tes',
            'whatsapp'   => '08111222333',
            'status'     => 'BARU',
            'type'       => 'Mahasiswa',
            'source'     => 'Sekolah',
            'wilayah_id' => $this->wilayah1->id,
            'sales_id'   => $this->sales->id,
        ]);

        // 1. Cannot create prospect via operational route
        $createRes = $this->actingAs($this->analyst)->post('/prospek', [
            'name'     => 'Hacked Lead',
            'whatsapp' => '08999999999',
            'source'   => 'Sekolah',
        ]);
        $this->assertTrue(in_array($createRes->getStatusCode(), [403, 302]));
        if ($createRes->getStatusCode() === 302) {
            $createRes->assertRedirect(route('dashboard.data-analyst'));
        }

        // 2. Policy blocks Data Analyst from create, update, delete
        $this->assertFalse(Gate::forUser($this->analyst)->allows('create', Prospek::class));
        $this->assertFalse(Gate::forUser($this->analyst)->allows('update', $prospek));
        $this->assertFalse(Gate::forUser($this->analyst)->allows('delete', $prospek));
        $this->assertFalse(Gate::forUser($this->analyst)->allows('followUp', $prospek));
        $this->assertFalse(Gate::forUser($this->analyst)->allows('takeover', $prospek));
        $this->assertFalse(Gate::forUser($this->analyst)->allows('transaction', $prospek));
        $this->assertFalse(Gate::forUser($this->analyst)->allows('markLost', $prospek));

        // 3. Cannot access Admin user management
        $adminRes = $this->actingAs($this->analyst)->get('/admin/users');
        $this->assertTrue(in_array($adminRes->getStatusCode(), [403, 302]));

        // 4. Cannot access CS payment verification write action
        $transaksi = Transaksi::create([
            'prospek_id'        => $prospek->id,
            'jenis'             => 'Pembayaran Termin 1',
            'nominal'           => 1500000,
            'metode_pembayaran' => 'bank_transfer',
            'payment_status'    => 'pending',
            'tanggal'           => now(),
        ]);
        $verifyRes = $this->actingAs($this->analyst)->post("/cs/verifikasi/{$transaksi->id}/verify");
        $verifyRes->assertStatus(403);
    }

    /** @test */
    public function test_other_operational_roles_cannot_access_analyst_workspace()
    {
        // Sales cannot access analyst dashboard
        $salesRes = $this->actingAs($this->sales)->get('/analyst/dashboard');
        $salesRes->assertRedirect(route('dashboard.sales'));

        // CS cannot access analyst dashboard
        $csRes = $this->actingAs($this->cs)->get('/analyst/dashboard');
        $csRes->assertRedirect(route('dashboard.cs'));

        // SPV cannot access analyst dashboard
        $spvRes = $this->actingAs($this->spv)->get('/analyst/dashboard');
        $spvRes->assertRedirect(route('dashboard.spv'));
    }

    /** @test */
    public function test_data_analyst_export_generates_valid_csv_with_utf8_bom()
    {
        Prospek::create([
            'name'       => 'Calon Mahasiswa Export',
            'whatsapp'   => '0812999888',
            'status'     => 'BARU',
            'type'       => 'Mahasiswa',
            'source'     => 'Sekolah',
            'wilayah_id' => $this->wilayah1->id,
        ]);

        $response = $this->actingAs($this->analyst)->get(route('analyst.export', ['type' => 'prospek']));
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        // Capture streamed response content
        $content = $response->streamedContent();

        // Check UTF-8 BOM
        $bom = chr(0xEF) . chr(0xBB) . chr(0xBF);
        $this->assertStringStartsWith($bom, $content);
        $this->assertStringContainsString('Calon Mahasiswa Export', $content);
        $this->assertStringContainsString('Kota Cirebon', $content);
    }

    /** @test */
    public function test_existing_roles_maintain_authorization_without_regressions()
    {
        // Admin has global access to both prospects
        $prospek1 = Prospek::create([
            'name'       => 'Prospect W1',
            'whatsapp'   => '081234111',
            'status'     => 'BARU',
            'type'       => 'Mahasiswa',
            'source'     => 'Sekolah',
            'wilayah_id' => $this->wilayah1->id,
            'sales_id'   => $this->sales->id,
        ]);
        $prospek2 = Prospek::create([
            'name'       => 'Prospect W2',
            'whatsapp'   => '081234222',
            'status'     => 'BARU',
            'type'       => 'Mahasiswa',
            'source'     => 'Sekolah',
            'wilayah_id' => $this->wilayah2->id,
        ]);

        $this->assertTrue(Gate::forUser($this->admin)->allows('view', $prospek1));
        $this->assertTrue(Gate::forUser($this->admin)->allows('view', $prospek2));

        // Sales 1 can view own prospect (prospek1) but not prospek2
        $this->assertTrue(Gate::forUser($this->sales)->allows('view', $prospek1));
        $this->assertFalse(Gate::forUser($this->sales)->allows('view', $prospek2));
    }

    /** @test */
    public function test_data_analyst_can_login_via_post_login_and_is_redirected_to_analyst_dashboard()
    {
        // 1. Login with email
        $res = $this->post('/login', [
            'email'    => 'analyst@cic.ac.id',
            'password' => 'password',
        ]);

        $res->assertRedirect(route('dashboard.data-analyst'));
        $this->assertAuthenticatedAs($this->analyst);

        // Logout
        $this->post('/logout');
        $this->assertGuest();

        // 2. Login with employee code (kode)
        $resKode = $this->post('/login', [
            'email'    => $this->analyst->kode,
            'password' => 'password',
        ]);

        $resKode->assertRedirect(route('dashboard.data-analyst'));
        $this->assertAuthenticatedAs($this->analyst);
    }

    /** @test */
    public function test_data_analyst_cannot_post_to_calendar_meetings()
    {
        $response = $this->actingAs($this->analyst)->post('/calendar/meetings', [
            'name'          => 'Rapat Analisis PMB',
            'tanggal'       => date('Y-m-d'),
            'waktu_mulai'   => '09:00',
            'waktu_selesai' => '10:00',
            'jenis'         => 'Internal',
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function test_data_analyst_export_follow_up_timeline_and_kualitas_data()
    {
        $prospek = Prospek::create([
            'name'       => 'Calon Ekspor Lengkap',
            'whatsapp'   => '0812345678',
            'status'     => 'PROSPEK',
            'type'       => 'Mahasiswa',
            'source'     => 'Sekolah',
            'wilayah_id' => $this->wilayah1->id,
            'sales_id'   => $this->sales->id,
        ]);

        \App\Models\FollowUp::create([
            'prospek_id' => $prospek->id,
            'user_id'    => $this->sales->id,
            'tanggal'    => now(),
            'metode'     => 'WhatsApp',
            'catatan'    => 'Interaksi pertama via WA',
        ]);

        \App\Models\ProspekTimeline::create([
            'prospek_id' => $prospek->id,
            'user_id'    => $this->sales->id,
            'title'      => 'Status Updated to PROSPEK',
            'notes'      => 'Lead updated by sales',
            'time'       => now(),
        ]);

        $bom = chr(0xEF) . chr(0xBB) . chr(0xBF);

        // 1. Follow-up export
        $resFollowUp = $this->actingAs($this->analyst)->get(route('analyst.export', ['type' => 'follow_up']));
        $resFollowUp->assertStatus(200);
        $resFollowUp->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $contentFollowUp = $resFollowUp->streamedContent();
        $this->assertStringStartsWith($bom, $contentFollowUp);
        $this->assertStringContainsString('ID Follow-Up', $contentFollowUp);
        $this->assertStringContainsString('Calon Ekspor Lengkap', $contentFollowUp);

        // 2. Timeline export
        $resTimeline = $this->actingAs($this->analyst)->get(route('analyst.export', ['type' => 'timeline']));
        $resTimeline->assertStatus(200);
        $contentTimeline = $resTimeline->streamedContent();
        $this->assertStringStartsWith($bom, $contentTimeline);
        $this->assertStringContainsString('ID Log Timeline', $contentTimeline);
        $this->assertStringContainsString('Calon Ekspor Lengkap', $contentTimeline);

        // 3. Kualitas Data export
        $resKualitas = $this->actingAs($this->analyst)->get(route('analyst.export', ['type' => 'kualitas_data']));
        $resKualitas->assertStatus(200);
        $contentKualitas = $resKualitas->streamedContent();
        $this->assertStringStartsWith($bom, $contentKualitas);
        $this->assertStringContainsString('Kategori Temuan Audit', $contentKualitas);
    }

    /** @test */
    public function test_canonical_prd_status_mapping_preserves_pipeline()
    {
        $this->assertEquals('Baru', \App\Services\AnalystExportService::mapToPrdStatus('BARU'));
        $this->assertEquals('Kontak', \App\Services\AnalystExportService::mapToPrdStatus('KONTAK'));
        $this->assertEquals('Hangat', \App\Services\AnalystExportService::mapToPrdStatus('PROSPEK'));
        $this->assertEquals('Hangat', \App\Services\AnalystExportService::mapToPrdStatus('HANGAT'));
        $this->assertEquals('Panas', \App\Services\AnalystExportService::mapToPrdStatus('HOT PROSPEK'));
        $this->assertEquals('Panas', \App\Services\AnalystExportService::mapToPrdStatus('PANAS'));
        $this->assertEquals('Formulir', \App\Services\AnalystExportService::mapToPrdStatus('FORMULIR'));
        $this->assertEquals('Berkas', \App\Services\AnalystExportService::mapToPrdStatus('BERKAS'));
        $this->assertEquals('Lunas', \App\Services\AnalystExportService::mapToPrdStatus('LUNAS'));
        $this->assertEquals('Dingin', \App\Services\AnalystExportService::mapToPrdStatus('DINGIN'));
        $this->assertEquals('Dingin', \App\Services\AnalystExportService::mapToPrdStatus('NO RESPON'));
    }

    /** @test */
    public function test_data_analyst_sidebar_displays_simplified_standard_menu_names()
    {
        $response = $this->actingAs($this->analyst)->get(route('dashboard.data-analyst'));
        $response->assertStatus(200);

        // Standard simplified menu labels (matching other roles)
        $response->assertSee('CRM Inbound');
        $response->assertSee('Data Prospek');
        $response->assertSee('Potensi Wilayah');
        $response->assertSee('Performance');
        $response->assertSee('Laporan');

        // Verify weird/complex labels are gone from sidebar
        $response->assertDontSee('Intelijen & Analitik');
        $response->assertDontSee('title="Dashboard Analitik"');
        $response->assertDontSee('Data Prospek Global');
        $response->assertDontSee('title="Ekspor Data Excel"');
    }

    /** @test */
    public function test_data_analyst_can_access_laporan_and_potensi_wilayah()
    {
        $resLaporan = $this->actingAs($this->analyst)->get(route('analyst.laporan.index'));
        $resLaporan->assertStatus(200);

        $resPotensi = $this->actingAs($this->analyst)->get(route('potensi-wilayah.index'));
        $resPotensi->assertStatus(200);
    }
}
