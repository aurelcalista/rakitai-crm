<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\MasterData;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EoAgendaAndEventManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $eo1;
    protected User $eo2;
    protected User $spv;
    protected User $sales;
    protected User $cs;
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

        $this->spv = User::factory()->create([
            'name'   => 'SPV User',
            'role'   => 'SPV',
            'status' => 'Aktif',
        ]);

        $this->sales = User::factory()->create([
            'name'   => 'Sales User',
            'role'   => 'Sales',
            'status' => 'Aktif',
        ]);

        $this->cs = User::factory()->create([
            'name'   => 'CS User',
            'role'   => 'CS',
            'status' => 'Aktif',
        ]);

        $this->eventType = MasterData::create([
            'type'   => 'jenis_event',
            'kode'   => 'EXPO',
            'nama'   => 'Education Expo',
            'status' => 'Aktif',
        ]);
    }

    /**
     * A1 & A3: EO dapat mengedit agenda internal yang dibuat oleh pengguna lain, dan memperbarui record yang sama.
     */
    public function test_eo_dapat_mengedit_agenda_yang_dibuat_pengguna_lain()
    {
        // Sales membuat agenda
        $agenda = Event::create([
            'name'            => '[Rapat Internal] Koordinasi Lapangan',
            'nama'            => '[Rapat Internal] Koordinasi Lapangan',
            'tanggal'         => '2026-11-05',
            'waktu_mulai'     => '09:00',
            'waktu_selesai'   => '10:00',
            'tanggal_mulai'   => '2026-11-05 09:00:00',
            'tanggal_selesai' => '2026-11-05 10:00:00',
            'lokasi'          => 'Ruang Rapat 1',
            'deskripsi'       => 'Briefing pagi.',
            'eo_id'           => $this->sales->id,
            'status'          => 'Scheduled',
            'type_id'         => null,
        ]);

        $initialId = $agenda->id;

        // EO mengedit agenda
        $response = $this->actingAs($this->eo1)->put(route('calendar.meetings.update', $agenda->id), [
            'name'          => 'Koordinasi Lapangan Updated',
            'tanggal'       => '2026-11-06',
            'waktu_mulai'   => '10:00',
            'waktu_selesai' => '12:00',
            'lokasi'        => 'Ruang Rapat 2',
            'deskripsi'     => 'Briefing pagi yang direvisi.',
            'jenis'         => 'Rapat Internal',
        ]);

        $response->assertRedirect(route('calendar.index'));

        $agenda->refresh();

        $this->assertEquals($initialId, $agenda->id);
        $this->assertEquals('[Rapat Internal] Koordinasi Lapangan Updated', $agenda->nama);
        $this->assertEquals('2026-11-06', $agenda->tanggal->format('Y-m-d'));
        $this->assertEquals('Ruang Rapat 2', $agenda->lokasi);
    }

    /**
     * A2 & A4: EO dapat menghapus agenda internal yang dibuat oleh pengguna lain tanpa merusak relasi.
     */
    public function test_eo_dapat_menghapus_agenda_yang_dibuat_pengguna_lain()
    {
        $agenda = Event::create([
            'name'            => 'Agenda Evaluasi Tim',
            'nama'            => 'Agenda Evaluasi Tim',
            'tanggal'         => '2026-11-08',
            'waktu_mulai'     => '14:00',
            'waktu_selesai'   => '16:00',
            'tanggal_mulai'   => '2026-11-08 14:00:00',
            'tanggal_selesai' => '2026-11-08 16:00:00',
            'lokasi'          => 'Lobby Utama',
            'eo_id'           => $this->spv->id,
            'status'          => 'Scheduled',
            'type_id'         => null,
        ]);

        $agendaId = $agenda->id;

        $response = $this->actingAs($this->eo1)->delete(route('calendar.meetings.destroy', $agendaId));

        $response->assertRedirect(route('calendar.index'));
        $this->assertDatabaseMissing('events', ['id' => $agendaId]);
    }

    /**
     * A5: Sales tidak dapat edit atau hapus agenda, sedangkan role selain Sales (seperti CS) dapat mengedit/menghapus.
     */
    public function test_sales_tidak_dapat_edit_atau_hapus_agenda_tetapi_role_lain_dapat()
    {
        $agenda = Event::create([
            'name'            => 'Agenda Khusus SPV',
            'nama'            => 'Agenda Khusus SPV',
            'tanggal'         => '2026-11-09',
            'waktu_mulai'     => '08:00',
            'waktu_selesai'   => '09:00',
            'tanggal_mulai'   => '2026-11-09 08:00:00',
            'tanggal_selesai' => '2026-11-09 09:00:00',
            'lokasi'          => 'Ruang VIP',
            'eo_id'           => $this->spv->id,
            'status'          => 'Scheduled',
            'type_id'         => null,
        ]);

        // 1. Sales dilarang mengedit agenda (403)
        $responseUpdateSales = $this->actingAs($this->sales)->put(route('calendar.meetings.update', $agenda->id), [
            'name'          => 'Agenda Diubah Sales',
            'tanggal'       => '2026-11-09',
            'waktu_mulai'   => '08:00',
            'waktu_selesai' => '09:00',
        ]);
        $responseUpdateSales->assertStatus(403);

        // 2. Sales dilarang menghapus agenda (403)
        $responseDeleteSales = $this->actingAs($this->sales)->delete(route('calendar.meetings.destroy', $agenda->id));
        $responseDeleteSales->assertStatus(403);

        // 3. CS (role selain Sales) dapat mengedit agenda milik SPV
        $responseUpdateCS = $this->actingAs($this->cs)->put(route('calendar.meetings.update', $agenda->id), [
            'name'          => 'Agenda Diubah CS',
            'tanggal'       => '2026-11-09',
            'waktu_mulai'   => '08:00',
            'waktu_selesai' => '09:00',
        ]);
        $responseUpdateCS->assertRedirect();
        $this->assertDatabaseHas('events', [
            'id'   => $agenda->id,
            'nama' => 'Agenda Diubah CS',
        ]);

        // 4. CS (role selain Sales) dapat menghapus agenda
        $responseDeleteCS = $this->actingAs($this->cs)->delete(route('calendar.meetings.destroy', $agenda->id));
        $responseDeleteCS->assertRedirect(route('calendar.index'));
        $this->assertDatabaseMissing('events', ['id' => $agenda->id]);
    }

    /**
     * B6, B7, B8, B9, B10: EO dapat mengedit dan menghapus event pada menu Kelola Event meskipun dibuat pengguna lain (misal Admin atau EO lain),
     * mempertahankan record yang sama dan tidak merusak data Takeover.
     */
    public function test_eo_dapat_mengedit_event_dibuat_pengguna_lain_dan_mempertahankan_takeover()
    {
        // Event dibuat oleh Admin dan sudah di-takeover oleh Sales
        $event = Event::create([
            'name'            => 'Expo Akbar Kampus 2026',
            'nama'            => 'Expo Akbar Kampus 2026',
            'type_id'         => $this->eventType->id,
            'tanggal'         => '2026-11-20',
            'waktu_mulai'     => '09:00',
            'waktu_selesai'   => '16:00',
            'tanggal_mulai'   => '2026-11-20 09:00:00',
            'tanggal_selesai' => '2026-11-20 16:00:00',
            'lokasi'          => 'Convention Hall',
            'deskripsi'       => 'Event besar.',
            'eo_id'           => $this->admin->id,
            'status'          => 'Scheduled',
            'takeover_by_id'  => $this->sales->id,
            'takeover_at'     => now()->subHours(2),
        ]);

        $eventId = $event->id;
        $takeoverTime = $event->takeover_at;

        // EO 1 mengedit event yang dibuat oleh Admin
        $response = $this->actingAs($this->eo1)->put(route('eo.events.update', $eventId), [
            'name'          => 'Expo Akbar Kampus 2026 (Revisi EO)',
            'type_id'       => $this->eventType->id,
            'tanggal'       => '2026-11-21',
            'waktu_mulai'   => '10:00',
            'waktu_selesai' => '17:00',
            'lokasi'        => 'Convention Hall Lt 2',
            'spvs'          => [$this->spv->id],
            'deskripsi'     => 'Event besar yang direvisi EO.',
        ]);

        $response->assertRedirect(route('eo.events.index'));

        $event->refresh();

        // Tetap record yang sama
        $this->assertEquals($eventId, $event->id);
        $this->assertEquals('Expo Akbar Kampus 2026 (Revisi EO)', $event->name);
        // Data takeover TETAP terjaga
        $this->assertEquals($this->sales->id, $event->takeover_by_id);
        $this->assertNotNull($event->takeover_at);
        $this->assertEquals($takeoverTime->toDateTimeString(), $event->takeover_at->toDateTimeString());
    }

    /**
     * B7 & B10: EO dapat menghapus event yang dibuat oleh pengguna lain.
     */
    public function test_eo_dapat_menghapus_event_yang_dibuat_pengguna_lain()
    {
        $event = Event::create([
            'name'            => 'Event Dibuat EO 2',
            'nama'            => 'Event Dibuat EO 2',
            'type_id'         => $this->eventType->id,
            'tanggal'         => '2026-11-22',
            'waktu_mulai'     => '08:00',
            'waktu_selesai'   => '12:00',
            'tanggal_mulai'   => '2026-11-22 08:00:00',
            'tanggal_selesai' => '2026-11-22 12:00:00',
            'lokasi'          => 'Aula SMA 1',
            'eo_id'           => $this->eo2->id,
            'status'          => 'Scheduled',
        ]);

        $eventId = $event->id;

        $response = $this->actingAs($this->eo1)->delete(route('eo.events.destroy', $eventId));

        $response->assertRedirect(route('eo.events.index'));
        $this->assertDatabaseMissing('events', ['id' => $eventId]);
    }

    /**
     * C11 - C19: Bug event hilang:
     * EO membuat event tanpa memilih SPV.
     * Event berhasil tersimpan ke DB.
     * Event muncul di Kalender Internal, Kelola Event, dan terhitung di Dashboard EO.
     */
    public function test_event_dibuat_tanpa_spv_tetap_muncul_di_kelola_event_kalender_dan_dashboard()
    {
        // EO 1 membuat event dengan SPV
        $response = $this->actingAs($this->eo1)->post(route('eo.events.store'), [
            'name'          => 'Job Fair & Edufest 2026',
            'type_id'       => $this->eventType->id,
            'tanggal'       => '2026-11-25',
            'waktu_mulai'   => '08:00',
            'waktu_selesai' => '15:00',
            'lokasi'        => 'Stadion Olahraga',
            'spvs'          => [$this->spv->id],
            'deskripsi'     => 'Event edukasi dan bursa karir.',
        ]);

        $response->assertRedirect(route('eo.events.index'));

        $this->assertDatabaseHas('events', [
            'name'    => 'Job Fair & Edufest 2026',
            'type_id' => $this->eventType->id,
            'lokasi'  => 'Stadion Olahraga',
        ]);

        $event = Event::where('name', 'Job Fair & Edufest 2026')->first();
        $this->assertNotNull($event);
        $this->assertCount(1, $event->spvs);

        // 1. Muncul di Kalender Internal
        $calendarResponse = $this->actingAs($this->eo1)->getJson(route('api.calendar.events'));
        $calendarResponse->assertOk();
        $calendarItems = collect($calendarResponse->json());
        $this->assertTrue($calendarItems->contains('id', $event->id));

        // 2. Muncul di menu Kelola Event (bahkan saat dilihat oleh EO 2 yang bukan pembuat)
        $eo2KelolaResponse = $this->actingAs($this->eo2)->get(route('eo.events.index'));
        $eo2KelolaResponse->assertOk();
        $eo2KelolaResponse->assertSee('Job Fair & Edufest 2026');

        // 3. Terhitung di dashboard EO
        $dashboardResponse = $this->actingAs($this->eo2)->get(route('dashboard.eo'));
        $dashboardResponse->assertOk();
        $this->assertEquals(1, $dashboardResponse->viewData('totalEvents'));
    }

    /**
     * D: Double submit protection: form redirects cleanly and creates exactly one event.
     */
    public function test_submit_form_membuat_satu_event_dan_redirect_ke_index()
    {
        $response = $this->actingAs($this->eo1)->post(route('eo.events.store'), [
            'name'          => 'Single Submission Event',
            'type_id'       => $this->eventType->id,
            'tanggal'       => '2026-11-28',
            'waktu_mulai'   => '09:00',
            'waktu_selesai' => '11:00',
            'lokasi'        => 'Gedung Serbaguna',
            'spvs'          => [$this->spv->id],
        ]);

        $response->assertRedirect(route('eo.events.index'));
        $response->assertSessionHas('success');

        $this->assertEquals(1, Event::where('name', 'Single Submission Event')->count());
    }

    public function test_eo_events_view_renders_edit_modal_with_submit_button()
    {
        $response = $this->actingAs($this->eo1)->get(route('eo.events.index'));

        $response->assertOk();
        $response->assertSee('modalEditEvent', false);
        $response->assertSee('Simpan Perubahan', false);
        $response->assertSee('type="submit"', false);
    }

    public function test_eo_dapat_menugaskan_event_ke_spv()
    {
        \Illuminate\Support\Facades\Notification::fake();

        $response = $this->actingAs($this->eo1)->post(route('eo.events.store'), [
            'name'          => 'Workshop Digital Marketing',
            'type_id'       => $this->eventType->id,
            'tanggal'       => '2026-11-20',
            'waktu_mulai'   => '09:00',
            'waktu_selesai' => '12:00',
            'lokasi'        => 'Auditorium Kampus',
            'assign_type'   => 'spv',
            'spv_id'        => $this->spv->id,
        ]);

        $response->assertRedirect(route('eo.events.index'));
        $event = Event::where('name', 'Workshop Digital Marketing')->first();
        $this->assertNotNull($event);
        $this->assertTrue($event->spvs->contains('id', $this->spv->id));

        \Illuminate\Support\Facades\Notification::assertSentTo(
            $this->spv,
            \App\Notifications\EventNotification::class
        );
    }

    public function test_eo_dapat_menugaskan_event_ke_sesama_eo()
    {
        \Illuminate\Support\Facades\Notification::fake();

        $response = $this->actingAs($this->eo1)->post(route('eo.events.store'), [
            'name'          => 'Expo Kolaborasi Kampus',
            'type_id'       => $this->eventType->id,
            'tanggal'       => '2026-11-21',
            'waktu_mulai'   => '10:00',
            'waktu_selesai' => '15:00',
            'lokasi'        => 'Hall Utama',
            'assign_type'   => 'eo',
            'eo_partner_id' => $this->eo2->id,
        ]);

        $response->assertRedirect(route('eo.events.index'));
        $event = Event::where('name', 'Expo Kolaborasi Kampus')->first();
        $this->assertNotNull($event);
        $this->assertTrue($event->spvs->contains('id', $this->eo2->id));

        \Illuminate\Support\Facades\Notification::assertSentTo(
            $this->eo2,
            \App\Notifications\CrmActivityNotification::class,
            function ($notif) {
                return $notif->action === 'event_assigned_eo';
            }
        );
    }

    public function test_eo_dapat_menugaskan_event_ke_diri_sendiri()
    {
        \Illuminate\Support\Facades\Notification::fake();

        $response = $this->actingAs($this->eo1)->post(route('eo.events.store'), [
            'name'          => 'Event Mandiri EO',
            'type_id'       => $this->eventType->id,
            'tanggal'       => '2026-11-22',
            'waktu_mulai'   => '13:00',
            'waktu_selesai' => '16:00',
            'lokasi'        => 'Ruang Kreatif',
            'assign_type'   => 'self',
        ]);

        $response->assertRedirect(route('eo.events.index'));
        $event = Event::where('name', 'Event Mandiri EO')->first();
        $this->assertNotNull($event);
        $this->assertTrue($event->spvs->contains('id', $this->eo1->id));

        // Self assignment mengirim notifikasi ke diri sendiri
        \Illuminate\Support\Facades\Notification::assertSentTo(
            $this->eo1,
            \App\Notifications\CrmActivityNotification::class,
            function ($notif) {
                return $notif->action === 'event_assigned_self';
            }
        );
    }

    public function test_eo_dapat_mengubah_penugasan_event_dari_spv_ke_rekan_eo()
    {
        \Illuminate\Support\Facades\Notification::fake();

        $event = Event::create([
            'name'            => 'Event Awal SPV',
            'nama'            => 'Event Awal SPV',
            'type_id'         => $this->eventType->id,
            'tanggal'         => '2026-11-23',
            'waktu_mulai'     => '08:00',
            'waktu_selesai'   => '11:00',
            'tanggal_mulai'   => '2026-11-23 08:00:00',
            'tanggal_selesai' => '2026-11-23 11:00:00',
            'lokasi'          => 'Ruang Serba Guna',
            'eo_id'           => $this->eo1->id,
            'status'          => 'Scheduled',
        ]);
        $event->spvs()->sync([$this->spv->id]);

        $response = $this->actingAs($this->eo1)->put(route('eo.events.update', $event->id), [
            'name'          => 'Event Awal SPV Dialihkan',
            'type_id'       => $this->eventType->id,
            'tanggal'       => '2026-11-23',
            'waktu_mulai'   => '08:00',
            'waktu_selesai' => '11:00',
            'lokasi'        => 'Ruang Serba Guna',
            'assign_type'   => 'eo',
            'eo_partner_id' => $this->eo2->id,
        ]);

        $response->assertRedirect(route('eo.events.index'));
        $event->refresh();
        $this->assertTrue($event->spvs->contains('id', $this->eo2->id));
        $this->assertFalse($event->spvs->contains('id', $this->spv->id));

        \Illuminate\Support\Facades\Notification::assertSentTo(
            $this->eo2,
            \App\Notifications\CrmActivityNotification::class
        );
    }

    public function test_view_kelola_event_menampilkan_opsi_penugasan_dan_plugin_search()
    {
        $response = $this->actingAs($this->eo1)->get(route('eo.events.index'));

        $response->assertOk();
        $response->assertSee('Tugaskan ke SPV', false);
        $response->assertSee('Tugaskan ke Sesama EO', false);
        $response->assertSee('Pegang Sendiri (EO)', false);
        $response->assertSee('window.eoSpvList', false);
        $response->assertSee('window.eoOtherEoList', false);
        $response->assertSee('Ketik untuk cari nama SPV...', false);
        $response->assertSee('Ketik untuk cari nama EO...', false);
    }
}


