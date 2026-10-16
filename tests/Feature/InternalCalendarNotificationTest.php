<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InternalCalendarNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $spv;
    protected User $sales;
    protected User $cs;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'name'   => 'Admin Test',
            'role'   => 'Admin',
            'status' => 'Aktif',
        ]);

        $this->spv = User::factory()->create([
            'name'   => 'SPV Test',
            'role'   => 'SPV',
            'status' => 'Aktif',
        ]);

        $this->sales = User::factory()->create([
            'name'   => 'Sales Test',
            'role'   => 'Sales',
            'status' => 'Aktif',
        ]);

        $this->cs = User::factory()->create([
            'name'   => 'CS Test',
            'role'   => 'CS',
            'status' => 'Aktif',
        ]);
    }

    public function test_when_sales_creates_agenda_it_appears_in_other_roles_calendar_and_dispatches_notifications()
    {
        $response = $this->actingAs($this->sales)->post(route('calendar.meetings.store'), [
            'name'          => 'Presentasi Kampus di SMA 1',
            'tanggal'       => '2026-10-20',
            'waktu_mulai'   => '08:00',
            'waktu_selesai' => '10:00',
            'lokasi'        => 'Aula SMA 1',
            'jenis'         => 'Kunjungan Sekolah',
            'deskripsi'     => 'Presentasi program beasiswa.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('events', [
            'nama'   => '[Kunjungan Sekolah] Presentasi Kampus di SMA 1',
            'lokasi' => 'Aula SMA 1',
            'eo_id'  => $this->sales->id,
        ]);

        // Check fetchEvents as Admin
        $adminEvents = $this->actingAs($this->admin)->getJson(route('api.calendar.events'));
        $adminEvents->assertOk();
        $this->assertTrue(collect($adminEvents->json())->contains(function ($e) {
            return str_contains($e['title'], 'Presentasi Kampus di SMA 1');
        }));

        // Check fetchEvents as SPV
        $spvEvents = $this->actingAs($this->spv)->getJson(route('api.calendar.events'));
        $spvEvents->assertOk();
        $this->assertTrue(collect($spvEvents->json())->contains(function ($e) {
            return str_contains($e['title'], 'Presentasi Kampus di SMA 1');
        }));

        // Check fetchEvents as CS
        $csEvents = $this->actingAs($this->cs)->getJson(route('api.calendar.events'));
        $csEvents->assertOk();
        $this->assertTrue(collect($csEvents->json())->contains(function ($e) {
            return str_contains($e['title'], 'Presentasi Kampus di SMA 1');
        }));

        // Check notifications: admin, spv, cs must have received notification
        $this->assertCount(1, $this->admin->notifications);
        $this->assertCount(1, $this->spv->notifications);
        $this->assertCount(1, $this->cs->notifications);

        $adminNotif = $this->admin->notifications->first();
        $this->assertEquals('Agenda Kalender Baru', $adminNotif->data['title']);
        $this->assertStringContainsString('Presentasi Kampus di SMA 1', $adminNotif->data['message']);
        $this->assertStringContainsString('Sales Test', $adminNotif->data['message']);

        // Creator should not receive self-notification
        $this->assertCount(0, $this->sales->notifications);
    }

    public function test_when_agenda_created_all_active_members_receive_agenda_notification_without_selective_assignment()
    {
        $response = $this->actingAs($this->spv)->post(route('calendar.meetings.store'), [
            'name'          => 'Rapat Koordinasi Mingguan',
            'tanggal'       => '2026-10-21',
            'waktu_mulai'   => '13:00',
            'waktu_selesai' => '14:30',
            'lokasi'        => 'Meeting Room 2',
            'jenis'         => 'Rapat Internal',
        ]);

        $response->assertRedirect();

        // Agenda is shared across all active colleagues, all receive 'Agenda Kalender Baru'
        $this->assertCount(1, $this->sales->notifications);
        $salesNotif = $this->sales->notifications->first();
        $this->assertEquals('Agenda Kalender Baru', $salesNotif->data['title']);
        $this->assertStringContainsString('Rapat Koordinasi Mingguan', $salesNotif->data['message']);

        // Admin & CS also receive new agenda notification
        $this->assertCount(1, $this->admin->notifications);
        $adminNotif = $this->admin->notifications->first();
        $this->assertEquals('Agenda Kalender Baru', $adminNotif->data['title']);

        $this->assertCount(1, $this->cs->notifications);
        $csNotif = $this->cs->notifications->first();
        $this->assertEquals('Agenda Kalender Baru', $csNotif->data['title']);
    }
}
