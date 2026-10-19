<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\MasterData;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InternalCalendarAndEventAccessTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $eo1;
    protected User $eo2;
    protected User $hm;
    protected User $spv;
    protected User $cs;
    protected User $dataAnalyst;
    protected User $telesales;
    protected User $sales1;
    protected User $sales2;
    protected MasterData $eventType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'name'   => 'Admin User',
            'role'   => 'Admin',
            'status' => 'Aktif',
        ]);

        $this->eo1 = User::factory()->create([
            'name'   => 'EO User 1',
            'role'   => 'EO',
            'status' => 'Aktif',
        ]);

        $this->eo2 = User::factory()->create([
            'name'   => 'EO User 2',
            'role'   => 'EO',
            'status' => 'Aktif',
        ]);

        $this->hm = User::factory()->create([
            'name'   => 'Head of Marketing',
            'role'   => 'HM',
            'status' => 'Aktif',
        ]);

        $this->spv = User::factory()->create([
            'name'   => 'SPV User',
            'role'   => 'SPV',
            'status' => 'Aktif',
        ]);

        $this->cs = User::factory()->create([
            'name'   => 'CS User',
            'role'   => 'CS',
            'status' => 'Aktif',
        ]);

        $this->dataAnalyst = User::factory()->create([
            'name'   => 'Data Analyst User',
            'role'   => 'Data Analyst',
            'status' => 'Aktif',
        ]);

        $this->telesales = User::factory()->create([
            'name'   => 'Telesales User',
            'role'   => 'Telesales',
            'status' => 'Aktif',
        ]);

        $this->sales1 = User::factory()->create([
            'name'   => 'Sales User 1',
            'role'   => 'Sales',
            'status' => 'Aktif',
        ]);

        $this->sales2 = User::factory()->create([
            'name'   => 'Sales User 2',
            'role'   => 'Sales',
            'status' => 'Aktif',
        ]);

        $this->eventType = MasterData::create([
            'type'   => 'jenis_event',
            'kode'   => 'EXPO',
            'nama'   => 'Campus Expo',
            'status' => 'Aktif',
        ]);
    }

    // =========================================================================
    // A. Pengujian Agenda Internal
    // =========================================================================

    public function test_admin_eo_hm_spv_cs_data_analyst_dapat_menambah_agenda()
    {
        $allowedRoles = [
            'Admin'        => $this->admin,
            'EO'           => $this->eo1,
            'HM'           => $this->hm,
            'SPV'          => $this->spv,
            'CS'           => $this->cs,
            'Data Analyst' => $this->dataAnalyst,
        ];

        foreach ($allowedRoles as $roleName => $user) {
            $response = $this->actingAs($user)->post(route('calendar.meetings.store'), [
                'name'          => "Agenda oleh {$roleName}",
                'tanggal'       => '2026-11-05',
                'waktu_mulai'   => '08:00',
                'waktu_selesai' => '09:00',
                'lokasi'        => "Ruang {$roleName}",
                'jenis'         => 'Rapat Internal',
                'deskripsi'     => "Dibuat oleh {$roleName}",
            ]);

            $response->assertRedirect();
            $this->assertDatabaseHas('events', [
                'nama'    => "[Rapat Internal] Agenda oleh {$roleName}",
                'type_id' => null,
                'eo_id'   => $user->id,
            ]);
        }
    }

    public function test_sales_tidak_dapat_menambah_agenda()
    {
        $response = $this->actingAs($this->sales1)->post(route('calendar.meetings.store'), [
            'name'          => 'Agenda Ilegal Sales',
            'tanggal'       => '2026-11-05',
            'waktu_mulai'   => '08:00',
            'waktu_selesai' => '09:00',
            'lokasi'        => 'Ruang Rapat',
            'jenis'         => 'Rapat Internal',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('events', [
            'name' => 'Agenda Ilegal Sales',
        ]);
    }

    public function test_sales_tidak_dapat_mengedit_agenda()
    {
        $agenda = Event::create([
            'name'            => 'Agenda SPV',
            'nama'            => 'Agenda SPV',
            'tanggal'         => '2026-11-06',
            'waktu_mulai'     => '08:00',
            'waktu_selesai'   => '09:00',
            'tanggal_mulai'   => '2026-11-06 08:00:00',
            'tanggal_selesai' => '2026-11-06 09:00:00',
            'lokasi'          => 'Ruang VIP',
            'eo_id'           => $this->spv->id,
            'status'          => 'Scheduled',
            'type_id'         => null,
        ]);

        $response = $this->actingAs($this->sales1)->put(route('calendar.meetings.update', $agenda->id), [
            'name'          => 'Agenda Direvisi Sales',
            'tanggal'       => '2026-11-06',
            'waktu_mulai'   => '08:00',
            'waktu_selesai' => '09:00',
        ]);

        $response->assertStatus(403);
    }

    public function test_sales_tidak_dapat_menghapus_agenda()
    {
        $agenda = Event::create([
            'name'            => 'Agenda SPV',
            'nama'            => 'Agenda SPV',
            'tanggal'         => '2026-11-06',
            'waktu_mulai'     => '08:00',
            'waktu_selesai'   => '09:00',
            'tanggal_mulai'   => '2026-11-06 08:00:00',
            'tanggal_selesai' => '2026-11-06 09:00:00',
            'lokasi'          => 'Ruang VIP',
            'eo_id'           => $this->spv->id,
            'status'          => 'Scheduled',
            'type_id'         => null,
        ]);

        $response = $this->actingAs($this->sales1)->delete(route('calendar.meetings.destroy', $agenda->id));

        $response->assertStatus(403);
        $this->assertDatabaseHas('events', ['id' => $agenda->id]);
    }

    public function test_semua_role_selain_sales_dapat_mengedit_agenda_milik_role_lain()
    {
        // Agenda dibuat oleh EO 1
        $agenda = Event::create([
            'name'            => 'Agenda Koordinasi EO 1',
            'nama'            => 'Agenda Koordinasi EO 1',
            'tanggal'         => '2026-11-07',
            'waktu_mulai'     => '08:00',
            'waktu_selesai'   => '09:00',
            'tanggal_mulai'   => '2026-11-07 08:00:00',
            'tanggal_selesai' => '2026-11-07 09:00:00',
            'lokasi'          => 'Ruang Rapat A',
            'eo_id'           => $this->eo1->id,
            'status'          => 'Scheduled',
            'type_id'         => null,
        ]);

        $editors = [
            'Admin'        => $this->admin,
            'HM'           => $this->hm,
            'SPV'          => $this->spv,
            'CS'           => $this->cs,
            'Data Analyst' => $this->dataAnalyst,
        ];

        foreach ($editors as $roleName => $user) {
            $response = $this->actingAs($user)->put(route('calendar.meetings.update', $agenda->id), [
                'name'          => "Agenda Diubah Oleh {$roleName}",
                'tanggal'       => '2026-11-07',
                'waktu_mulai'   => '10:00',
                'waktu_selesai' => '11:00',
                'lokasi'        => "Ruang {$roleName}",
            ]);

            $response->assertRedirect();
            $this->assertDatabaseHas('events', [
                'id'     => $agenda->id,
                'nama'   => "Agenda Diubah Oleh {$roleName}",
                'lokasi' => "Ruang {$roleName}",
            ]);
        }
    }

    public function test_semua_role_selain_sales_dapat_menghapus_agenda_milik_role_lain()
    {
        $agenda = Event::create([
            'name'            => 'Agenda Dibuat EO',
            'nama'            => 'Agenda Dibuat EO',
            'tanggal'         => '2026-11-08',
            'waktu_mulai'     => '08:00',
            'waktu_selesai'   => '09:00',
            'tanggal_mulai'   => '2026-11-08 08:00:00',
            'tanggal_selesai' => '2026-11-08 09:00:00',
            'lokasi'          => 'Ruang B',
            'eo_id'           => $this->eo1->id,
            'status'          => 'Scheduled',
            'type_id'         => null,
        ]);

        // CS menghapus agenda milik EO
        $response = $this->actingAs($this->cs)->delete(route('calendar.meetings.destroy', $agenda->id));

        $response->assertRedirect(route('calendar.index'));
        $this->assertDatabaseMissing('events', ['id' => $agenda->id]);
    }

    public function test_sales_tetap_dapat_melihat_detail_agenda()
    {
        $agenda = Event::create([
            'name'            => 'Agenda Transparan Tim',
            'nama'            => 'Agenda Transparan Tim',
            'tanggal'         => '2026-11-09',
            'waktu_mulai'     => '13:00',
            'waktu_selesai'   => '15:00',
            'tanggal_mulai'   => '2026-11-09 13:00:00',
            'tanggal_selesai' => '2026-11-09 15:00:00',
            'lokasi'          => 'Auditorium',
            'eo_id'           => $this->admin->id,
            'status'          => 'Scheduled',
            'type_id'         => null,
        ]);

        $response = $this->actingAs($this->sales1)->getJson(route('api.calendar.events'));

        $response->assertOk();
        $this->assertTrue(collect($response->json())->contains('title', 'Agenda Transparan Tim'));
    }

    // =========================================================================
    // B. Pengujian Event EO
    // =========================================================================

    public function test_eo_dapat_membuat_event_eo_baru()
    {
        $response = $this->actingAs($this->eo1)->post(route('eo.events.store'), [
            'name'          => 'Pameran Pendidikan Sukses',
            'type_id'       => $this->eventType->id,
            'tanggal'       => '2026-11-15',
            'waktu_mulai'   => '08:00',
            'waktu_selesai' => '14:00',
            'lokasi'        => 'Gedung Pertemuan Graha',
            'deskripsi'     => 'Acara tahunan.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('events', [
            'name'    => 'Pameran Pendidikan Sukses',
            'type_id' => $this->eventType->id,
            'eo_id'   => $this->eo1->id,
        ]);
    }

    public function test_non_eo_roles_tidak_dapat_membuat_event_eo_baru()
    {
        $nonEoUsers = [
            'Admin'        => $this->admin,
            'HM'           => $this->hm,
            'SPV'          => $this->spv,
            'CS'           => $this->cs,
            'Data Analyst' => $this->dataAnalyst,
            'Telesales'    => $this->telesales,
            'Sales'        => $this->sales1,
        ];

        foreach ($nonEoUsers as $roleName => $user) {
            // Jalur 1: Endpoint EO
            $responseEo = $this->actingAs($user)->post(route('eo.events.store'), [
                'name'          => "Event Ilegal {$roleName}",
                'type_id'       => $this->eventType->id,
                'tanggal'       => '2026-11-15',
                'waktu_mulai'   => '08:00',
                'waktu_selesai' => '14:00',
                'lokasi'        => 'Gedung A',
            ]);
            $responseEo->assertStatus(403);

            // Jalur 2: Endpoint Admin (jika role admin mencoba buat)
            if ($roleName === 'Admin') {
                $responseAdmin = $this->actingAs($user)->post(route('admin.events.store'), [
                    'name'          => "Event Ilegal Admin",
                    'type_id'       => $this->eventType->id,
                    'tanggal'       => '2026-11-15',
                    'waktu_mulai'   => '08:00',
                    'waktu_selesai' => '14:00',
                    'lokasi'        => 'Gedung A',
                ]);
                $responseAdmin->assertStatus(403);
            }
        }
    }

    public function test_semua_role_selain_sales_dapat_mengedit_event_eo_melalui_kalender()
    {
        // Event dibuat oleh EO 1
        $event = Event::create([
            'name'            => 'Event Original EO 1',
            'nama'            => 'Event Original EO 1',
            'type_id'         => $this->eventType->id,
            'tanggal'         => '2026-11-16',
            'waktu_mulai'     => '08:00',
            'waktu_selesai'   => '12:00',
            'tanggal_mulai'   => '2026-11-16 08:00:00',
            'tanggal_selesai' => '2026-11-16 12:00:00',
            'lokasi'          => 'Aula Utama',
            'eo_id'           => $this->eo1->id,
            'status'          => 'Scheduled',
        ]);

        $allowedEditors = [
            'Admin'        => $this->admin,
            'EO2'          => $this->eo2,
            'HM'           => $this->hm,
            'SPV'          => $this->spv,
            'CS'           => $this->cs,
            'Data Analyst' => $this->dataAnalyst,
        ];

        foreach ($allowedEditors as $roleName => $user) {
            $response = $this->actingAs($user)->put(route('calendar.events.update', $event->id), [
                'name'          => "Event Diperbarui Oleh {$roleName}",
                'type_id'       => $this->eventType->id,
                'tanggal'       => '2026-11-16',
                'waktu_mulai'   => '09:00',
                'waktu_selesai' => '13:00',
                'lokasi'        => "Aula {$roleName}",
            ]);

            $response->assertRedirect();
            $this->assertDatabaseHas('events', [
                'id'     => $event->id,
                'name'   => "Event Diperbarui Oleh {$roleName}",
                'lokasi' => "Aula {$roleName}",
            ]);
        }
    }

    public function test_semua_role_selain_sales_dapat_menghapus_event_eo_melalui_kalender()
    {
        $event = Event::create([
            'name'            => 'Event Dibuat EO 1 Akan Dihapus SPV',
            'nama'            => 'Event Dibuat EO 1 Akan Dihapus SPV',
            'type_id'         => $this->eventType->id,
            'tanggal'         => '2026-11-17',
            'waktu_mulai'     => '08:00',
            'waktu_selesai'   => '12:00',
            'tanggal_mulai'   => '2026-11-17 08:00:00',
            'tanggal_selesai' => '2026-11-17 12:00:00',
            'lokasi'          => 'Aula B',
            'eo_id'           => $this->eo1->id,
            'status'          => 'Scheduled',
        ]);

        $eventId = $event->id;

        // SPV menghapus event EO via kalender
        $response = $this->actingAs($this->spv)->delete(route('calendar.events.destroy', $eventId));

        $response->assertRedirect(route('calendar.index'));
        $this->assertDatabaseMissing('events', ['id' => $eventId]);
    }

    public function test_sales_tidak_dapat_mengedit_atau_menghapus_event_eo()
    {
        $event = Event::create([
            'name'            => 'Event Terproteksi Dari Sales',
            'nama'            => 'Event Terproteksi Dari Sales',
            'type_id'         => $this->eventType->id,
            'tanggal'         => '2026-11-18',
            'waktu_mulai'     => '08:00',
            'waktu_selesai'   => '12:00',
            'tanggal_mulai'   => '2026-11-18 08:00:00',
            'tanggal_selesai' => '2026-11-18 12:00:00',
            'lokasi'          => 'Ruang C',
            'eo_id'           => $this->eo1->id,
            'status'          => 'Scheduled',
        ]);

        // Edit ditolak
        $responseUpdate = $this->actingAs($this->sales1)->put(route('calendar.events.update', $event->id), [
            'name'          => 'Coba Edit Oleh Sales',
            'type_id'       => $this->eventType->id,
            'tanggal'       => '2026-11-18',
            'waktu_mulai'   => '08:00',
            'waktu_selesai' => '12:00',
            'lokasi'        => 'Ruang C',
        ]);
        $responseUpdate->assertStatus(403);

        // Hapus ditolak
        $responseDelete = $this->actingAs($this->sales1)->delete(route('calendar.events.destroy', $event->id));
        $responseDelete->assertStatus(403);

        $this->assertDatabaseHas('events', ['id' => $event->id]);
    }

    public function test_mengedit_event_eo_tidak_menciptakan_duplikasi_event()
    {
        $event = Event::create([
            'name'            => 'Event Asli',
            'nama'            => 'Event Asli',
            'type_id'         => $this->eventType->id,
            'tanggal'         => '2026-11-19',
            'waktu_mulai'     => '08:00',
            'waktu_selesai'   => '10:00',
            'tanggal_mulai'   => '2026-11-19 08:00:00',
            'tanggal_selesai' => '2026-11-19 10:00:00',
            'lokasi'          => 'Auditorium',
            'eo_id'           => $this->eo1->id,
            'status'          => 'Scheduled',
        ]);

        $initialCount = Event::count();

        $response = $this->actingAs($this->admin)->put(route('calendar.events.update', $event->id), [
            'name'          => 'Event Diperbarui Tanpa Duplikasi',
            'type_id'       => $this->eventType->id,
            'tanggal'       => '2026-11-19',
            'waktu_mulai'   => '09:00',
            'waktu_selesai' => '11:00',
            'lokasi'        => 'Auditorium Lt 2',
        ]);

        $response->assertRedirect();
        $this->assertEquals($initialCount, Event::count());
        $this->assertDatabaseHas('events', [
            'id'   => $event->id,
            'name' => 'Event Diperbarui Tanpa Duplikasi',
        ]);
    }

    public function test_menghapus_event_eo_tidak_menghapus_event_lain()
    {
        $eventA = Event::create([
            'name'            => 'Event A Tetap Ada',
            'nama'            => 'Event A Tetap Ada',
            'type_id'         => $this->eventType->id,
            'tanggal'         => '2026-11-20',
            'waktu_mulai'     => '08:00',
            'waktu_selesai'   => '10:00',
            'tanggal_mulai'   => '2026-11-20 08:00:00',
            'tanggal_selesai' => '2026-11-20 10:00:00',
            'lokasi'          => 'Lokasi A',
            'eo_id'           => $this->eo1->id,
            'status'          => 'Scheduled',
        ]);

        $eventB = Event::create([
            'name'            => 'Event B Yang Dihapus',
            'nama'            => 'Event B Yang Dihapus',
            'type_id'         => $this->eventType->id,
            'tanggal'         => '2026-11-21',
            'waktu_mulai'     => '08:00',
            'waktu_selesai'   => '10:00',
            'tanggal_mulai'   => '2026-11-21 08:00:00',
            'tanggal_selesai' => '2026-11-21 10:00:00',
            'lokasi'          => 'Lokasi B',
            'eo_id'           => $this->eo2->id,
            'status'          => 'Scheduled',
        ]);

        $response = $this->actingAs($this->hm)->delete(route('calendar.events.destroy', $eventB->id));

        $response->assertRedirect();
        $this->assertDatabaseMissing('events', ['id' => $eventB->id]);
        $this->assertDatabaseHas('events', ['id' => $eventA->id]);
    }

    public function test_data_agenda_dan_event_eo_tetap_terpisah()
    {
        $agenda = Event::create([
            'name'            => 'Agenda Internal Tim',
            'nama'            => 'Agenda Internal Tim',
            'tanggal'         => '2026-11-22',
            'waktu_mulai'     => '08:00',
            'waktu_selesai'   => '10:00',
            'tanggal_mulai'   => '2026-11-22 08:00:00',
            'tanggal_selesai' => '2026-11-22 10:00:00',
            'lokasi'          => 'Ruang Rapat',
            'eo_id'           => $this->spv->id,
            'status'          => 'Scheduled',
            'type_id'         => null,
        ]);

        $event = Event::create([
            'name'            => 'Event EO Akbar',
            'nama'            => 'Event EO Akbar',
            'type_id'         => $this->eventType->id,
            'tanggal'         => '2026-11-22',
            'waktu_mulai'     => '08:00',
            'waktu_selesai'   => '10:00',
            'tanggal_mulai'   => '2026-11-22 08:00:00',
            'tanggal_selesai' => '2026-11-22 10:00:00',
            'lokasi'          => 'Plaza Utama',
            'eo_id'           => $this->eo1->id,
            'status'          => 'Scheduled',
        ]);

        // destroyMeeting tidak boleh menghapus Event EO (redirect dengan error flash atau 400 jika JSON)
        $resp1 = $this->actingAs($this->admin)->delete(route('calendar.meetings.destroy', $event->id));
        $resp1->assertRedirect(route('calendar.index'));
        $resp1->assertSessionHas('error', 'Record ini bukan agenda internal.');
        $this->assertDatabaseHas('events', ['id' => $event->id]);

        $resp1Json = $this->actingAs($this->admin)->deleteJson(route('calendar.meetings.destroy', $event->id));
        $resp1Json->assertStatus(400);

        // destroyEvent tidak boleh menghapus Agenda Internal (harus 404 karena type_id is null)
        $resp2 = $this->actingAs($this->admin)->delete(route('calendar.events.destroy', $agenda->id));
        $resp2->assertStatus(404);
        $this->assertDatabaseHas('events', ['id' => $agenda->id]);
    }

    // =========================================================================
    // C. Pengujian Takeover
    // =========================================================================

    public function test_sales_yang_berhak_dapat_melakukan_takeover()
    {
        $event = Event::create([
            'name'            => 'Event Siap Di-Takeover',
            'nama'            => 'Event Siap Di-Takeover',
            'type_id'         => $this->eventType->id,
            'tanggal'         => '2026-11-25',
            'waktu_mulai'     => '08:00',
            'waktu_selesai'   => '12:00',
            'tanggal_mulai'   => '2026-11-25 08:00:00',
            'tanggal_selesai' => '2026-11-25 12:00:00',
            'lokasi'          => 'Hall Expo',
            'eo_id'           => $this->eo1->id,
            'status'          => 'Scheduled',
        ]);

        $response = $this->actingAs($this->sales1)->postJson(route('calendar.events.takeover', $event->id));

        $response->assertOk();
        $this->assertDatabaseHas('events', [
            'id'             => $event->id,
            'takeover_by_id' => $this->sales1->id,
        ]);
        $this->assertNotNull(Event::find($event->id)->takeover_at);
    }

    public function test_takeover_hanya_bisa_dilakukan_oleh_satu_pengguna()
    {
        $event = Event::create([
            'name'            => 'Event Sudah Di-Takeover',
            'nama'            => 'Event Sudah Di-Takeover',
            'type_id'         => $this->eventType->id,
            'tanggal'         => '2026-11-26',
            'waktu_mulai'     => '08:00',
            'waktu_selesai'   => '12:00',
            'tanggal_mulai'   => '2026-11-26 08:00:00',
            'tanggal_selesai' => '2026-11-26 12:00:00',
            'lokasi'          => 'Hall Expo',
            'eo_id'           => $this->eo1->id,
            'status'          => 'Scheduled',
            'takeover_by_id'  => $this->sales1->id,
            'takeover_at'     => now(),
        ]);

        // Sales 2 mencoba takeover event yang sudah di-takeover Sales 1
        $response = $this->actingAs($this->sales2)->postJson(route('calendar.events.takeover', $event->id));

        $response->assertStatus(422);
        $this->assertEquals($this->sales1->id, Event::find($event->id)->takeover_by_id);
    }

    public function test_sales_tidak_memperoleh_hak_edit_atau_hapus_setelah_takeover()
    {
        $event = Event::create([
            'name'            => 'Event Di-Takeover Sales 1',
            'nama'            => 'Event Di-Takeover Sales 1',
            'type_id'         => $this->eventType->id,
            'tanggal'         => '2026-11-27',
            'waktu_mulai'     => '08:00',
            'waktu_selesai'   => '12:00',
            'tanggal_mulai'   => '2026-11-27 08:00:00',
            'tanggal_selesai' => '2026-11-27 12:00:00',
            'lokasi'          => 'Hall Expo',
            'eo_id'           => $this->eo1->id,
            'status'          => 'Scheduled',
            'takeover_by_id'  => $this->sales1->id,
            'takeover_at'     => now(),
        ]);

        // Sales 1 yang telah takeover tetap ditolak saat mencoba update
        $responseUpdate = $this->actingAs($this->sales1)->put(route('calendar.events.update', $event->id), [
            'name'          => 'Event Diedit Sales Yang Takeover',
            'type_id'       => $this->eventType->id,
            'tanggal'       => '2026-11-27',
            'waktu_mulai'   => '08:00',
            'waktu_selesai' => '12:00',
            'lokasi'        => 'Hall Expo',
        ]);
        $responseUpdate->assertStatus(403);

        // Sales 1 yang telah takeover tetap ditolak saat mencoba delete
        $responseDelete = $this->actingAs($this->sales1)->delete(route('calendar.events.destroy', $event->id));
        $responseDelete->assertStatus(403);
    }

    public function test_edit_event_eo_tidak_menghilangkan_data_takeover()
    {
        $takeoverTime = now()->subDay()->startOfMinute();

        $event = Event::create([
            'name'            => 'Event Ter-Takeover Sebelum Edit',
            'nama'            => 'Event Ter-Takeover Sebelum Edit',
            'type_id'         => $this->eventType->id,
            'tanggal'         => '2026-11-28',
            'waktu_mulai'     => '08:00',
            'waktu_selesai'   => '12:00',
            'tanggal_mulai'   => '2026-11-28 08:00:00',
            'tanggal_selesai' => '2026-11-28 12:00:00',
            'lokasi'          => 'Hall 1',
            'eo_id'           => $this->eo1->id,
            'status'          => 'Scheduled',
            'takeover_by_id'  => $this->sales1->id,
            'takeover_at'     => $takeoverTime,
        ]);

        // Admin mengedit event melalui Kalender Internal
        $response = $this->actingAs($this->admin)->put(route('calendar.events.update', $event->id), [
            'name'          => 'Event Ter-Takeover Setelah Edit Admin',
            'type_id'       => $this->eventType->id,
            'tanggal'       => '2026-11-28',
            'waktu_mulai'   => '09:00',
            'waktu_selesai' => '13:00',
            'lokasi'        => 'Hall 2 (Pindah)',
        ]);

        $response->assertRedirect();

        $event->refresh();
        $this->assertEquals('Event Ter-Takeover Setelah Edit Admin', $event->name);
        // Data takeover TETAP utuh
        $this->assertEquals($this->sales1->id, $event->takeover_by_id);
        $this->assertNotNull($event->takeover_at);
        $this->assertEquals(
            $takeoverTime->format('Y-m-d H:i'),
            \Carbon\Carbon::parse($event->takeover_at)->format('Y-m-d H:i')
        );
    }

    public function test_calendar_view_renders_edit_event_modal_with_submit_button()
    {
        $response = $this->actingAs($this->eo1)->get(route('calendar.index'));

        $response->assertStatus(200);
        $response->assertSee('modalEditEvent', false);
        $response->assertSee('Simpan Perubahan', false);
        $response->assertSee('type="submit"', false);
    }

    public function test_admin_events_view_renders_edit_modal_with_submit_button()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.events.index'));

        $response->assertStatus(200);
        $response->assertSee('modalEditEvent', false);
        $response->assertSee('Simpan Perubahan', false);
        $response->assertSee('type="submit"', false);
    }
}


