<?php

namespace Tests\Feature;

use App\Models\Prospek;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PmbIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $hmUser;
    protected User $csUser;
    protected User $salesUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create(['role' => 'Admin']);
        $this->hmUser = User::factory()->create(['role' => 'HM']);
        $this->csUser = User::factory()->create(['role' => 'CS']);
        $this->salesUser = User::factory()->create(['role' => 'Sales']);
    }

    public function test_admin_can_access_all_pmb_routes()
    {
        $this->actingAs($this->adminUser);

        $pendaftar = Prospek::create([
            'name' => 'Calon Mahasiswa Test',
            'email' => 'calon@test.com',
            'whatsapp' => '081234567890',
            'status' => 'BARU',
            'gelombang' => 'Gelombang 1',
            'status_formulir' => 'Sudah Isi',
            'status_berkas' => 'Sudah Upload',
            'status_ujian' => 'Sudah Mengerjakan',
            'nilai_ujian' => 88.5,
        ]);

        $this->get(route('pmb.dashboard'))->assertOk();
        $this->get(route('pmb.pendaftar.index'))->assertOk();
        $this->get(route('pmb.pendaftar.show', $pendaftar->id))->assertOk();
        $this->get(route('pmb.pembayaran.index'))->assertOk();
        $this->get(route('pmb.admin.index'))->assertOk();
    }

    public function test_hm_can_access_all_pmb_routes()
    {
        $this->actingAs($this->hmUser);

        $this->get(route('pmb.dashboard'))->assertOk();
        $this->get(route('pmb.pendaftar.index'))->assertOk();
        $this->get(route('pmb.pembayaran.index'))->assertOk();
        $this->get(route('pmb.admin.index'))->assertOk();
    }

    public function test_cs_can_access_pmb_dashboard_pendaftar_pembayaran_but_forbidden_from_admin()
    {
        $this->actingAs($this->csUser);

        $this->get(route('pmb.dashboard'))->assertOk();
        $this->get(route('pmb.pendaftar.index'))->assertOk();
        $this->get(route('pmb.pembayaran.index'))->assertOk();

        // CS is forbidden from Data Admin: GET is redirected to CS dashboard, POST/DELETE gets 403 Forbidden
        $this->get(route('pmb.admin.index'))->assertRedirect(route('dashboard.cs'));
        $this->post(route('pmb.admin.store'), [
            'name' => 'Illegal Admin',
            'email' => 'illegal@test.com',
            'role' => 'Admin',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertForbidden();
    }

    public function test_unauthorized_role_sales_cannot_access_pmb()
    {
        $this->actingAs($this->salesUser);

        $this->get(route('pmb.dashboard'))->assertRedirect(route('dashboard.sales'));
        $this->get(route('pmb.pendaftar.index'))->assertRedirect(route('dashboard.sales'));
        $this->get(route('pmb.pembayaran.index'))->assertRedirect(route('dashboard.sales'));
        $this->get(route('pmb.admin.index'))->assertRedirect(route('dashboard.sales'));

        // Non-GET requests should abort with 403 Forbidden
        $this->post(route('pmb.pembayaran.verify', 999))->assertForbidden();
    }

    public function test_cs_cannot_delete_pendaftar()
    {
        $this->actingAs($this->csUser);

        $pendaftar = Prospek::create([
            'name' => 'Hapus Target',
            'whatsapp' => '081234567891',
            'status' => 'BARU',
        ]);

        $this->delete(route('pmb.pendaftar.destroy', $pendaftar->id))->assertForbidden();
        $this->assertDatabaseHas('prospeks', ['id' => $pendaftar->id]);
    }

    public function test_reset_password_pendaftar_creates_timeline()
    {
        $this->actingAs($this->adminUser);

        $pendaftar = Prospek::create([
            'name' => 'Salsa Test',
            'email' => 'salsa@test.com',
            'whatsapp' => '081234567892',
            'status' => 'BARU',
        ]);

        $response = $this->post(route('pmb.pendaftar.reset-password', $pendaftar->id));
        $response->assertSessionHas('success');
        
        $this->assertDatabaseHas('prospek_timelines', [
            'prospek_id' => $pendaftar->id,
            'title' => 'Reset Password PMB',
        ]);
    }

    public function test_verify_and_reject_pembayaran()
    {
        $this->actingAs($this->adminUser);

        $pendaftar = Prospek::create([
            'name' => 'Bayar Test',
            'whatsapp' => '081234567893',
            'status' => 'BARU',
        ]);

        $trx = Transaksi::create([
            'prospek_id' => $pendaftar->id,
            'user_id' => $this->adminUser->id,
            'nominal' => 300000,
            'jenis' => 'Pendaftaran PMB',
            'detail_pembayaran' => '• Biaya Pendaftaran PMB',
            'metode_pembayaran' => 'TRANSFER BRI',
            'payment_status' => Transaksi::STATUS_PENDING,
            'tanggal' => now(),
        ]);

        // Verify
        $resVerify = $this->post(route('pmb.pembayaran.verify', $trx->id));
        $resVerify->assertSessionHas('success');
        $this->assertEquals(Transaksi::STATUS_VERIFIED, $trx->fresh()->payment_status);

        // Reject
        $resReject = $this->post(route('pmb.pembayaran.reject', $trx->id), [
            'rejection_reason' => 'Bukti pembayaran tidak terbaca',
        ]);
        $resReject->assertSessionHas('error');
        $this->assertEquals(Transaksi::STATUS_REJECTED, $trx->fresh()->payment_status);
    }

    public function test_export_pendaftar_and_pembayaran_streams_csv()
    {
        $this->actingAs($this->adminUser);

        $resPendaftar = $this->get(route('pmb.pendaftar.export'));
        $resPendaftar->assertOk();
        $this->assertStringContainsString('text/csv', $resPendaftar->headers->get('content-type'));

        $resPembayaran = $this->get(route('pmb.pembayaran.export'));
        $resPembayaran->assertOk();
        $this->assertStringContainsString('text/csv', $resPembayaran->headers->get('content-type'));
    }

    public function test_admin_cannot_delete_self()
    {
        $this->actingAs($this->adminUser);

        $response = $this->delete(route('pmb.admin.destroy', $this->adminUser->id));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $this->adminUser->id]);
    }
}
