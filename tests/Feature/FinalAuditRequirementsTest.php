<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wilayah;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinalAuditRequirementsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /** @test */
    public function test_sales_self_registration_creates_pending_user_without_code()
    {
        $response = $this->post(route('register'), [
            'name' => 'Sales Self Reg',
            'email' => 'salesself@cic.ac.id',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseHas('users', [
            'email' => 'salesself@cic.ac.id',
            'role' => 'Sales',
            'status' => 'Pending',
            'kode' => null,
        ]);
    }

    /** @test */
    public function test_pending_sales_cannot_login()
    {
        $user = User::factory()->create([
            'email' => 'pendinguser@cic.ac.id',
            'password' => bcrypt('password123'),
            'role' => 'Sales',
            'status' => 'Pending',
        ]);

        $response = $this->post(route('login'), [
            'email' => 'pendinguser@cic.ac.id',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /** @test */
    public function test_admin_can_approve_pending_sales_and_generate_code()
    {
        $admin = User::where('role', 'Admin')->first();
        $user = User::factory()->create([
            'email' => 'pendinguser2@cic.ac.id',
            'role' => 'Sales',
            'status' => 'Pending',
            'kode' => null,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.users.approve', $user->id));

        $response->assertRedirect();
        $user->refresh();

        $this->assertEquals('Aktif', $user->status);
        $this->assertNotNull($user->kode);
        $expectedPrefix = date('ym') . 'S';
        $this->assertStringStartsWith($expectedPrefix, $user->kode);
    }

    /** @test */
    public function test_admin_can_reject_pending_sales()
    {
        $admin = User::where('role', 'Admin')->first();
        $user = User::factory()->create([
            'email' => 'pendinguser3@cic.ac.id',
            'role' => 'Sales',
            'status' => 'Pending',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.users.reject', $user->id));

        $response->assertRedirect();
        $user->refresh();

        $this->assertEquals('Nonaktif', $user->status);
    }

    /** @test */
    public function test_meeting_creation_enforces_one_hour_gap()
    {
        $spv = User::where('role', 'SPV')->first();

        // Create initial meeting 09:00 - 10:00
        Event::create([
            'nama' => 'Morning Meeting',
            'tipe_kegiatan' => 'meeting',
            'tanggal_mulai' => '2026-10-10 09:00:00',
            'tanggal_selesai' => '2026-10-10 10:00:00',
            'tanggal' => '2026-10-10',
            'jam_mulai' => '09:00:00',
            'jam_selesai' => '10:00:00',
            'lokasi' => 'Ruang SPV',
            'eo_id' => $spv->id,
            'created_by' => $spv->id,
            'status' => 'Direncanakan',
        ]);

        // Attempting to create meeting starting at 10:00 should fail (needs 1-hour gap, so min 11:00)
        $response1 = $this->actingAs($spv)->post(route('calendar.meetings.store'), [
            'name' => 'Conflict Meeting',
            'tanggal' => '2026-10-10',
            'waktu_mulai' => '10:00',
            'waktu_selesai' => '10:30',
        ]);

        $response1->assertSessionHasErrors('waktu_mulai');

        // Creating meeting starting at 11:00 should succeed
        $response2 = $this->actingAs($spv)->post(route('calendar.meetings.store'), [
            'name' => 'Valid Gap Meeting',
            'tanggal' => '2026-10-10',
            'waktu_mulai' => '11:00',
            'waktu_selesai' => '12:00',
        ]);

        $response2->assertRedirect();
        $this->assertDatabaseHas('events', [
            'nama' => 'Valid Gap Meeting',
        ]);
    }
}
