<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wilayah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceRoleRestrictionTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $hm;
    protected User $spv;
    protected User $sales;
    protected User $cs;
    protected User $eo;
    protected Wilayah $wilayah;

    protected function setUp(): void
    {
        parent::setUp();

        $this->wilayah = Wilayah::create([
            'kode' => 'W1',
            'nama' => 'Kota Cirebon',
            'level' => 'Kota/Kabupaten',
        ]);

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin.test@cic.ac.id',
            'password' => bcrypt('password'),
            'role' => 'Admin',
            'status' => 'aktif',
        ]);

        $this->hm = User::create([
            'name' => 'HM User',
            'email' => 'hm.test@cic.ac.id',
            'password' => bcrypt('password'),
            'role' => 'HM',
            'status' => 'aktif',
            'wilayah_id' => $this->wilayah->id,
        ]);

        $this->spv = User::create([
            'name' => 'SPV User',
            'email' => 'spv.test@cic.ac.id',
            'password' => bcrypt('password'),
            'role' => 'SPV',
            'status' => 'aktif',
            'wilayah_id' => $this->wilayah->id,
        ]);

        $this->sales = User::create([
            'name' => 'Sales User',
            'email' => 'sales.test@cic.ac.id',
            'password' => bcrypt('password'),
            'role' => 'Sales',
            'status' => 'aktif',
            'wilayah_id' => $this->wilayah->id,
            'supervisor_id' => $this->spv->id,
        ]);

        $this->cs = User::create([
            'name' => 'CS User',
            'email' => 'cs.test@cic.ac.id',
            'password' => bcrypt('password'),
            'role' => 'CS',
            'status' => 'aktif',
        ]);

        $this->eo = User::create([
            'name' => 'EO User',
            'email' => 'eo.test@cic.ac.id',
            'password' => bcrypt('password'),
            'role' => 'EO',
            'status' => 'aktif',
        ]);
    }

    /**
     * 1. Admin tidak melihat menu Input Absen maupun Kelola Absen di sidebar.
     */
    public function test_admin_sidebar_does_not_contain_input_absen_and_does_not_contain_kelola_absen(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.users.index'));
        $response->assertStatus(200);
        $response->assertDontSee('Input Absensi');
        $response->assertDontSee('Kelola Absensi');
    }

    /**
     * 2. HM tidak melihat menu Input Absen di sidebar, tetapi melihat Kelola Absen.
     */
    public function test_hm_sidebar_does_not_contain_input_absen_but_has_kelola_absen(): void
    {
        $response = $this->actingAs($this->hm)->get(route('attendance.index'));
        $response->assertStatus(200);
        $response->assertDontSee('Input Absensi');
        $response->assertSee('Kelola Absensi');
    }

    /**
     * 3. Role lain yang berhak (Sales, CS, EO, SPV) tetap melihat Input Absen di sidebar.
     */
    public function test_authorized_roles_see_input_absen_in_sidebar(): void
    {
        $response = $this->actingAs($this->sales)->get(route('attendance.create'));
        $response->assertStatus(200);
        $response->assertSee('Input Absensi');

        $response = $this->actingAs($this->spv)->get(route('attendance.create'));
        $response->assertStatus(200);
        $response->assertSee('Input Absensi');
        $response->assertSee('Kelola Absensi');

        $response = $this->actingAs($this->cs)->get(route('attendance.create'));
        $response->assertStatus(200);
        $response->assertSee('Input Absensi');

        $response = $this->actingAs($this->eo)->get(route('attendance.create'));
        $response->assertStatus(200);
        $response->assertSee('Input Absensi');
    }

    /**
     * 4. Admin dan HM tidak dapat mengakses Input Absen via URL langsung (GET).
     */
    public function test_admin_and_hm_cannot_access_input_absen_via_url(): void
    {
        // Admin GET /absensi/input -> blocked by RoleMiddleware and redirected to Admin dashboard with error
        $responseAdmin = $this->actingAs($this->admin)->get(route('attendance.create'));
        $responseAdmin->assertRedirect(route('dashboard.admin'));
        $responseAdmin->assertSessionHas('error', 'Anda tidak memiliki akses ke halaman tersebut.');

        // HM GET /absensi/input -> blocked by RoleMiddleware and redirected to HM dashboard with error
        $responseHm = $this->actingAs($this->hm)->get(route('attendance.create'));
        $responseHm->assertRedirect(route('dashboard.hm'));
        $responseHm->assertSessionHas('error', 'Anda tidak memiliki akses ke halaman tersebut.');
    }

    /**
     * 5. Admin dan HM tidak dapat mengirim absensi via URL langsung (POST).
     */
    public function test_admin_and_hm_cannot_post_to_input_absen(): void
    {
        $responseAdmin = $this->actingAs($this->admin)->post(route('attendance.store'), [
            'status' => 'Hadir',
        ]);
        $responseAdmin->assertStatus(403);

        $responseHm = $this->actingAs($this->hm)->post(route('attendance.store'), [
            'status' => 'Hadir',
        ]);
        $responseHm->assertStatus(403);
    }

    /**
     * 6. Admin tidak dapat mengakses Kelola Absen via URL langsung (GET), sedangkan HM dan SPV tetap dapat mengakses.
     */
    public function test_admin_cannot_access_kelola_absen_while_hm_and_spv_can(): void
    {
        // Admin GET /absensi/kelola -> blocked by RoleMiddleware and redirected to Admin dashboard with error
        $responseAdmin = $this->actingAs($this->admin)->get(route('attendance.index'));
        $responseAdmin->assertRedirect(route('dashboard.admin'));
        $responseAdmin->assertSessionHas('error', 'Anda tidak memiliki akses ke halaman tersebut.');

        // HM GET /absensi/kelola -> success (200)
        $responseHm = $this->actingAs($this->hm)->get(route('attendance.index'));
        $responseHm->assertStatus(200);
        $responseHm->assertSee('Kelola Absensi');

        // SPV GET /absensi/kelola -> success (200)
        $responseSpv = $this->actingAs($this->spv)->get(route('attendance.index'));
        $responseSpv->assertStatus(200);
        $responseSpv->assertSee('Kelola Absensi');
    }

    /**
     * 7. Halaman Kelola Pengguna Admin memuat tombol filter Data Analyst.
     */
    public function test_admin_kelola_pengguna_has_data_analyst_role_filter(): void
    {
        User::create([
            'name' => 'Analyst User',
            'email' => 'analyst.test@cic.ac.id',
            'password' => bcrypt('password'),
            'role' => 'Data Analyst',
            'status' => 'Aktif',
            'phone' => '08123456789',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.users.index'));
        $response->assertStatus(200);
        $response->assertSee('Data Analyst');
        $response->assertSee('analyst.test@cic.ac.id');
    }
}
