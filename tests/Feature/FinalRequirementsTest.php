<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\MasterData;
use App\Models\Target;
use App\Models\User;
use App\Models\Wilayah;
use App\Services\EventAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FinalRequirementsTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $hmUser;
    protected User $spvUser;
    protected User $salesUser;
    protected User $csUser;
    protected Wilayah $kotaCirebon;
    protected Wilayah $kecKesambi;
    protected Wilayah $kotaLainnya;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Create Master Wilayahs
        $this->kotaCirebon = Wilayah::create([
            'kode' => 'CRB',
            'nama' => 'Kota Cirebon',
            'level' => 'Kota/Kabupaten',
            'status' => 'Aktif',
        ]);

        $this->kecKesambi = Wilayah::create([
            'kode' => 'CRB-KSB-01',
            'nama' => 'Kesambi',
            'level' => 'Kecamatan',
            'parent_id' => $this->kotaCirebon->id,
            'status' => 'Aktif',
        ]);

        $this->kotaLainnya = Wilayah::create([
            'kode' => 'W-LAIN',
            'nama' => 'Di Kota Lainnya',
            'level' => 'Kota/Kabupaten',
            'status' => 'Aktif',
        ]);

        // 2. Create Users
        $this->adminUser = User::create([
            'name' => 'Admin Test',
            'email' => 'admin@cic.ac.id',
            'password' => Hash::make('123'),
            'role' => 'Admin',
            'status' => 'Aktif',
        ]);

        $this->hmUser = User::create([
            'name' => 'HM Test',
            'email' => 'hm@cic.ac.id',
            'password' => Hash::make('123'),
            'role' => 'HM',
            'status' => 'Aktif',
            'wilayah_id' => $this->kotaCirebon->id,
        ]);

        $this->spvUser = User::create([
            'name' => 'SPV Test',
            'email' => 'spv@cic.ac.id',
            'password' => Hash::make('123'),
            'role' => 'SPV',
            'status' => 'Aktif',
            'wilayah_id' => $this->kotaCirebon->id,
        ]);

        $this->salesUser = User::create([
            'name' => 'Sales Test',
            'email' => 'sales@cic.ac.id',
            'password' => Hash::make('123'),
            'role' => 'Sales',
            'status' => 'Aktif',
            'supervisor_id' => $this->spvUser->id,
        ]);

        $this->csUser = User::create([
            'name' => 'CS Test',
            'email' => 'cs@cic.ac.id',
            'password' => Hash::make('123'),
            'role' => 'CS',
            'status' => 'Aktif',
        ]);
    }

    /** Test 1: Kota Lainnya assignment as operational area is rejected with 403 */
    public function test_01_kota_lainnya_cannot_be_assigned_as_operational_territory()
    {
        // SPV attempts to assign Sales to Kota Lainnya -> REJECTED 403
        $response = $this->actingAs($this->spvUser)->post(route('spv.tim.territory.assign'), [
            'sales_id' => $this->salesUser->id,
            'sales_area_id' => $this->kotaLainnya->id,
        ]);

        $response->assertStatus(403);

        // SPV attempts to store Sales with Kota Lainnya -> REJECTED 403
        $response2 = $this->actingAs($this->spvUser)->post(route('spv.tim.sales.store'), [
            'name' => 'Sales Baru 2',
            'email' => 'salesbaru2@cic.ac.id',
            'area_id' => $this->kotaLainnya->id,
        ]);

        $response2->assertStatus(403);
    }

    /** Test 2: Admin ACC User Baru functionality & non-admin rejection */
    public function test_02_admin_acc_user_baru_flow()
    {
        $pendingUser = User::create([
            'name' => 'User Baru Pending',
            'email' => 'pending@cic.ac.id',
            'password' => Hash::make('123'),
            'role' => 'Sales',
            'status' => 'Pending',
        ]);

        // Non-admin (SPV) attempts to ACC -> REJECTED 403
        $responseSpv = $this->actingAs($this->spvUser)->post(route('admin.users.approve', $pendingUser->id));
        $responseSpv->assertStatus(403);
        $this->assertEquals('Pending', $pendingUser->fresh()->status);

        // Admin approves -> SUCCESS 200/Redirect
        $responseAdmin = $this->actingAs($this->adminUser)->post(route('admin.users.approve', $pendingUser->id));
        $responseAdmin->assertRedirect();
        $this->assertEquals('Aktif', $pendingUser->fresh()->status);

        // Re-approving active user -> Handles gracefully with error message
        $responseReApprove = $this->actingAs($this->adminUser)->post(route('admin.users.approve', $pendingUser->id));
        $responseReApprove->assertSessionHas('error');
    }

    /** Test 3: Event gap validation (gap >= 1 hr PASS, gap < 1 hr FAIL) */
    public function test_03_event_gap_validation()
    {
        $service = new EventAssignmentService();

        // Create initial event 10:00 - 11:00 for Sales
        $event1 = Event::create([
            'name' => 'Event A',
            'nama' => 'Event A',
            'tanggal' => '2026-10-10',
            'waktu_mulai' => '10:00',
            'waktu_selesai' => '11:00',
            'tanggal_mulai' => '2026-10-10 10:00:00',
            'tanggal_selesai' => '2026-10-10 11:00:00',
            'lokasi' => 'Aula UCIC',
            'eo_id' => $this->adminUser->id,
            'status' => 'Scheduled',
        ]);
        $event1->sales()->attach($this->salesUser->id);

        // Case A: Next event 12:00 - 13:00 (Gap = 1 hour / 60 mins) -> PASS
        $service->validateSalesSchedule($this->salesUser->id, '2026-10-10', '12:00', '13:00');
        $this->assertTrue(true);

        // Case B: Next event 11:30 - 12:30 (Gap = 30 mins < 1 hour) -> FAIL ValidationException
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $service->validateSalesSchedule($this->salesUser->id, '2026-10-10', '11:30', '12:30');
    }
}
