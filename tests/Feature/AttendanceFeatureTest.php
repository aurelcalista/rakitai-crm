<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceLocation;
use App\Models\User;
use App\Models\Wilayah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttendanceFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $hm;
    protected User $spv;
    protected User $sales;
    protected User $eo;
    protected User $cs;
    protected AttendanceLocation $locationActive;
    protected AttendanceLocation $locationInactive;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $wilayah = Wilayah::create([
            'kode'   => 'W-TEST',
            'nama'   => 'Wilayah Uji Coba',
            'level'  => 'Kota/Kabupaten',
            'status' => 'active',
        ]);

        $this->admin = User::create([
            'name'     => 'Admin Test',
            'email'    => 'admin@test.com',
            'password' => bcrypt('password'),
            'role'     => 'Admin',
            'status'   => 'Aktif',
        ]);

        $this->hm = User::create([
            'name'       => 'HM Test',
            'email'      => 'hm@test.com',
            'password'   => bcrypt('password'),
            'role'       => 'HM',
            'status'     => 'Aktif',
            'wilayah_id' => $wilayah->id,
        ]);

        $this->spv = User::create([
            'name'       => 'SPV Test',
            'email'      => 'spv@test.com',
            'password'   => bcrypt('password'),
            'role'       => 'SPV',
            'status'     => 'Aktif',
            'wilayah_id' => $wilayah->id,
        ]);

        $this->sales = User::create([
            'name'          => 'Sales Test',
            'email'         => 'sales@test.com',
            'password'      => bcrypt('password'),
            'role'          => 'Sales',
            'status'        => 'Aktif',
            'supervisor_id' => $this->spv->id,
            'wilayah_id'    => $wilayah->id,
        ]);

        $this->eo = User::create([
            'name'       => 'EO Test',
            'email'      => 'eo@test.com',
            'password'   => bcrypt('password'),
            'role'       => 'EO',
            'status'     => 'Aktif',
            'wilayah_id' => $wilayah->id,
        ]);

        $this->cs = User::create([
            'name'     => 'CS Test',
            'email'    => 'cs@test.com',
            'password' => bcrypt('password'),
            'role'     => 'CS',
            'status'   => 'Aktif',
        ]);

        // Kantor Cirebon: lat -6.726543, lng 108.557654, radius 100 meter
        $this->locationActive = AttendanceLocation::create([
            'name'      => 'Kantor Cirebon',
            'latitude'  => -6.726543,
            'longitude' => 108.557654,
            'radius'    => 100,
            'status'    => 'active',
        ]);

        $this->locationInactive = AttendanceLocation::create([
            'name'      => 'Lokasi Nonaktif',
            'latitude'  => -6.900000,
            'longitude' => 108.600000,
            'radius'    => 50,
            'status'    => 'inactive',
        ]);
    }

    public function test_spv_sales_and_eo_can_access_attendance_page()
    {
        $this->actingAs($this->sales)->get(route('attendance.index'))->assertStatus(200);
        $this->actingAs($this->spv)->get(route('attendance.index'))->assertStatus(200);
        $this->actingAs($this->eo)->get(route('attendance.index'))->assertStatus(200);

        // Role lain seperti CS tidak memiliki akses absensi
        $this->actingAs($this->cs)->get(route('attendance.index'))->assertRedirect();
    }

    public function test_admin_can_manage_attendance_locations()
    {
        $this->actingAs($this->admin)->get(route('admin.attendance-locations.index'))->assertStatus(200);

        $response = $this->actingAs($this->admin)->post(route('admin.attendance-locations.store'), [
            'name'      => 'Titik Kampus 2',
            'latitude'  => -6.982345,
            'longitude' => 108.487654,
            'radius'    => 150,
            'status'    => 'active',
        ]);

        $response->assertRedirect(route('admin.attendance-locations.index'));
        $this->assertDatabaseHas('attendance_locations', [
            'name'     => 'Titik Kampus 2',
            'latitude' => -6.982345,
            'radius'   => 150,
        ]);
    }

    public function test_user_within_radius_can_check_in_successfully()
    {
        // Posisi user: berjarak ~35 meter dari Kantor Cirebon (-6.726543, 108.557654)
        // -6.726250, 108.557650
        $file = UploadedFile::fake()->image('selfie.jpg', 800, 600);

        $response = $this->actingAs($this->sales)->post(route('attendance.store'), [
            'attendance_location_id' => $this->locationActive->id,
            'latitude'               => -6.726500,
            'longitude'              => 108.557650,
            'foto'                   => $file,
            'notes'                  => 'Hadir apel pagi',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('attendances', [
            'user_id'                => $this->sales->id,
            'attendance_location_id' => $this->locationActive->id,
            'status'                 => 'present',
        ]);

        $attendance = Attendance::first();
        $this->assertNotNull($attendance);
        $this->assertLessThanOrEqual(100, $attendance->distance);
        $this->assertEquals(-6.7265, $attendance->latitude);
        Storage::disk('local')->assertExists($attendance->selfie_path);
    }

    public function test_user_outside_radius_is_rejected()
    {
        // Posisi user berjarak jauh (~20 km): lat -6.900000, lng 108.600000
        $file = UploadedFile::fake()->image('selfie.jpg', 640, 480);

        $response = $this->actingAs($this->sales)->post(route('attendance.store'), [
            'attendance_location_id' => $this->locationActive->id,
            'latitude'               => -6.900000,
            'longitude'              => 108.600000,
            'foto'                   => $file,
        ]);

        $response->assertSessionHasErrors(['radius']);
        $this->assertDatabaseMissing('attendances', [
            'user_id' => $this->sales->id,
        ]);
    }

    public function test_inactive_location_cannot_be_selected_for_check_in()
    {
        $file = UploadedFile::fake()->image('selfie.jpg', 640, 480);

        $response = $this->actingAs($this->sales)->post(route('attendance.store'), [
            'attendance_location_id' => $this->locationInactive->id,
            'latitude'               => -6.900000,
            'longitude'              => 108.600000,
            'foto'                   => $file,
        ]);

        $response->assertSessionHasErrors(['attendance_location_id']);
    }

    public function test_duplicate_attendance_prevented_on_same_day()
    {
        $file1 = UploadedFile::fake()->image('selfie1.jpg', 640, 480);
        $this->actingAs($this->sales)->post(route('attendance.store'), [
            'attendance_location_id' => $this->locationActive->id,
            'latitude'               => -6.726540,
            'longitude'              => 108.557650,
            'foto'                   => $file1,
        ]);

        // Coba check-in kedua kali di hari yang sama
        $file2 = UploadedFile::fake()->image('selfie2.jpg', 640, 480);
        $response = $this->actingAs($this->sales)->post(route('attendance.store'), [
            'attendance_location_id' => $this->locationActive->id,
            'latitude'               => -6.726540,
            'longitude'              => 108.557650,
            'foto'                   => $file2,
        ]);

        $response->assertSessionHasErrors(['duplicate']);
        $this->assertEquals(1, Attendance::where('user_id', $this->sales->id)->count());
    }

    public function test_attendance_photo_is_private_and_protected_by_policy()
    {
        $file = UploadedFile::fake()->image('selfie.jpg', 640, 480);
        $this->actingAs($this->sales)->post(route('attendance.store'), [
            'attendance_location_id' => $this->locationActive->id,
            'latitude'               => -6.726540,
            'longitude'              => 108.557650,
            'foto'                   => $file,
        ]);

        $attendance = Attendance::first();

        // 1. Sales pemilik dapat melihat foto
        $this->actingAs($this->sales)->get(route('attendance.photo', $attendance->id))->assertStatus(200);

        // 2. SPV atasannya dapat melihat foto
        $this->actingAs($this->spv)->get(route('attendance.photo', $attendance->id))->assertStatus(200);

        // 3. Admin dapat melihat foto
        $this->actingAs($this->admin)->get(route('attendance.photo', $attendance->id))->assertStatus(200);

        // 4. CS yang tidak terkait ditolak (403)
        $this->actingAs($this->cs)->get(route('attendance.photo', $attendance->id))->assertStatus(403);

        // 5. Tamu tanpa login ditolak
        auth()->logout();
        $this->get(route('attendance.photo', $attendance->id))->assertRedirect(route('login'));
    }

    public function test_monitoring_hierarchy_spv_hm_and_admin()
    {
        $file = UploadedFile::fake()->image('selfie.jpg', 640, 480);
        $this->actingAs($this->sales)->post(route('attendance.store'), [
            'attendance_location_id' => $this->locationActive->id,
            'latitude'               => -6.726540,
            'longitude'              => 108.557650,
            'foto'                   => $file,
        ]);

        // SPV dapat monitoring bawahan Sales
        $responseSpv = $this->actingAs($this->spv)->get(route('attendance.monitoring'));
        $responseSpv->assertStatus(200);
        $responseSpv->assertSee($this->sales->name);

        // HM dapat monitoring
        $responseHm = $this->actingAs($this->hm)->get(route('attendance.monitoring'));
        $responseHm->assertStatus(200);
        $responseHm->assertSee($this->sales->name);

        // Admin dapat monitoring
        $responseAdmin = $this->actingAs($this->admin)->get(route('attendance.monitoring'));
        $responseAdmin->assertStatus(200);
        $responseAdmin->assertSee($this->sales->name);

        // Sales biasa tidak boleh membuka monitoring (di-redirect oleh RoleMiddleware ke dashboard sales)
        $this->actingAs($this->sales)->get(route('attendance.monitoring'))->assertRedirect(route('dashboard.sales'));
        $this->actingAs($this->sales)->getJson(route('attendance.monitoring'))->assertStatus(403);
    }

    public function test_hm_can_monitor_spv_and_eo_attendances()
    {
        $file1 = UploadedFile::fake()->image('selfie_spv.jpg', 640, 480);
        $this->actingAs($this->spv)->post(route('attendance.store'), [
            'attendance_location_id' => $this->locationActive->id,
            'latitude'               => -6.726540,
            'longitude'              => 108.557650,
            'foto'                   => $file1,
        ]);

        $file2 = UploadedFile::fake()->image('selfie_eo.jpg', 640, 480);
        $this->actingAs($this->eo)->post(route('attendance.store'), [
            'attendance_location_id' => $this->locationActive->id,
            'latitude'               => -6.726540,
            'longitude'              => 108.557650,
            'foto'                   => $file2,
        ]);

        // HM membuka halaman monitoring dan dapat melihat absensi SPV dan EO
        $responseHm = $this->actingAs($this->hm)->get(route('attendance.monitoring'));
        $responseHm->assertStatus(200);
        $responseHm->assertSee($this->spv->name);
        $responseHm->assertSee($this->eo->name);

        // SPV membuka monitoring dan dapat melihat absensi dirinya & Sales
        $responseSpv = $this->actingAs($this->spv)->get(route('attendance.monitoring'));
        $responseSpv->assertStatus(200);
        $responseSpv->assertSee($this->spv->name);
    }
}

