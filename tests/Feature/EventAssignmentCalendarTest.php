<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\MasterData;
use App\Models\TahunAkademik;
use App\Models\User;
use App\Models\Wilayah;
use App\Services\GoogleCalendarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class EventAssignmentCalendarTest extends TestCase
{
    use RefreshDatabase;

    protected User $eo1;
    protected User $eo2;
    protected User $spv1;
    protected User $spv2;
    protected User $sales1;
    protected User $sales2;
    protected User $sales3;
    protected Wilayah $wilayah1;
    protected Wilayah $wilayah2;
    protected TahunAkademik $activeTa;
    protected MasterData $eventType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->activeTa = TahunAkademik::create([
            'nama' => '2026/2027',
            'status' => 'Aktif',
        ]);

        $this->wilayah1 = Wilayah::create(['kode' => 'W1', 'nama' => 'Wilayah 1', 'level' => 'Provinsi']);
        $this->wilayah2 = Wilayah::create(['kode' => 'W2', 'nama' => 'Wilayah 2', 'level' => 'Provinsi']);

        $this->eventType = MasterData::create([
            'type' => 'jenis_event',
            'kode' => 'EXPO',
            'nama' => 'Pameran Expo',
            'status' => 'Aktif',
        ]);

        $this->eo1 = User::create([
            'name' => 'EO One',
            'email' => 'eo1.test@cic.ac.id',
            'password' => bcrypt('password'),
            'role' => 'EO',
            'status' => 'aktif',
            'wilayah_id' => $this->wilayah1->id,
        ]);

        $this->eo2 = User::create([
            'name' => 'EO Two',
            'email' => 'eo2.test@cic.ac.id',
            'password' => bcrypt('password'),
            'role' => 'EO',
            'status' => 'aktif',
            'wilayah_id' => $this->wilayah2->id,
        ]);

        $this->spv1 = User::create([
            'name' => 'SPV One',
            'email' => 'spv1.test@cic.ac.id',
            'password' => bcrypt('password'),
            'role' => 'SPV',
            'status' => 'aktif',
            'wilayah_id' => $this->wilayah1->id,
        ]);

        $this->spv2 = User::create([
            'name' => 'SPV Two',
            'email' => 'spv2.test@cic.ac.id',
            'password' => bcrypt('password'),
            'role' => 'SPV',
            'status' => 'aktif',
            'wilayah_id' => $this->wilayah2->id,
        ]);

        $this->sales1 = User::create([
            'name' => 'Sales One',
            'email' => 'sales1.test@cic.ac.id',
            'password' => bcrypt('password'),
            'role' => 'Sales',
            'status' => 'aktif',
            'supervisor_id' => $this->spv1->id,
            'wilayah_id' => $this->wilayah1->id,
        ]);

        $this->sales2 = User::create([
            'name' => 'Sales Two',
            'email' => 'sales2.test@cic.ac.id',
            'password' => bcrypt('password'),
            'role' => 'Sales',
            'status' => 'aktif',
            'supervisor_id' => $this->spv1->id,
            'wilayah_id' => $this->wilayah1->id,
        ]);

        $this->sales3 = User::create([
            'name' => 'Sales Three',
            'email' => 'sales3.test@cic.ac.id',
            'password' => bcrypt('password'),
            'role' => 'Sales',
            'status' => 'aktif',
            'supervisor_id' => $this->spv2->id,
            'wilayah_id' => $this->wilayah2->id,
        ]);
    }

    /** A. EO dapat membuat event sesuai authorization */
    public function test_skenario_A_eo_dapat_membuat_event_sesuai_authorization()
    {
        $this->assertTrue(Gate::forUser($this->eo1)->allows('create', Event::class));

        $event = Event::create([
            'nama' => 'Edu Fair 2026',
            'type_id' => $this->eventType->id,
            'tanggal_mulai' => now()->addDays(2)->format('Y-m-d 09:00:00'),
            'tanggal_selesai' => now()->addDays(2)->format('Y-m-d 12:00:00'),
            'lokasi' => 'Gedung SICC',
            'deskripsi' => 'Pameran pendidikan tinggi',
            'eo_id' => $this->eo1->id,
            'status' => 'Rencana',
        ]);

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'nama' => 'Edu Fair 2026',
            'eo_id' => $this->eo1->id,
        ]);
    }

    /** B & D. EO dapat assign Sales yang valid dan tersimpan di database */
    public function test_skenario_B_and_D_eo_assign_sales_valid_dan_tersimpan()
    {
        $event = Event::create([
            'nama' => 'Edu Fair 2026',
            'type_id' => $this->eventType->id,
            'tanggal_mulai' => now()->addDays(2)->format('Y-m-d 09:00:00'),
            'tanggal_selesai' => now()->addDays(2)->format('Y-m-d 12:00:00'),
            'lokasi' => 'Gedung SICC',
            'eo_id' => $this->eo1->id,
            'status' => 'Rencana',
        ]);

        $event->sales()->attach($this->sales1->id, ['assigned_by_spv_id' => $this->spv1->id]);

        $this->assertDatabaseHas('event_sales', [
            'event_id' => $event->id,
            'sales_id' => $this->sales1->id,
        ]);
    }

    /** C & N. EO tidak dapat assign Sales di luar kewenangannya / Phase 8 authorization */
    public function test_skenario_C_and_N_eo_tidak_dapat_kelola_event_eo_lain()
    {
        $event2 = Event::create([
            'nama' => 'Event EO 2',
            'type_id' => $this->eventType->id,
            'tanggal_mulai' => now()->addDays(2)->format('Y-m-d 09:00:00'),
            'tanggal_selesai' => now()->addDays(2)->format('Y-m-d 12:00:00'),
            'lokasi' => 'Aula 2',
            'eo_id' => $this->eo2->id,
            'status' => 'Rencana',
        ]);

        // EO 1 cannot update or delete EO 2 event
        $this->assertFalse(Gate::forUser($this->eo1)->allows('update', $event2));
        $this->assertFalse(Gate::forUser($this->eo1)->allows('delete', $event2));
    }

    /** E & F. Multiple Sales assignment tersimpan tanpa duplicate & Calendar sync dipanggil untuk setiap Sales */
    public function test_skenario_E_and_F_multiple_sales_assignment_dan_calendar_sync()
    {
        $event = Event::create([
            'nama' => 'Expo Multi Sales',
            'type_id' => $this->eventType->id,
            'tanggal_mulai' => now()->addDays(3)->format('Y-m-d 10:00:00'),
            'tanggal_selesai' => now()->addDays(3)->format('Y-m-d 15:00:00'),
            'lokasi' => 'Convention Hall',
            'eo_id' => $this->eo1->id,
            'status' => 'Rencana',
        ]);

        $event->sales()->attach($this->sales1->id, ['assigned_by_spv_id' => $this->spv1->id]);
        $event->sales()->attach($this->sales2->id, ['assigned_by_spv_id' => $this->spv1->id]);
        $event->sales()->attach($this->sales3->id, ['assigned_by_spv_id' => $this->spv2->id]);

        $this->assertEquals(3, $event->sales()->count());

        $calendarService = new GoogleCalendarService();
        $results = $calendarService->syncAllAssignedSales($event);

        $this->assertCount(3, $results);
        foreach ($results as $res) {
            $this->assertEquals('fallback_internal', $res['status']); // Unconnected OAuth fallback
            $this->assertFalse($res['synced']);
        }
    }

    /** G & L. Duplicate assignment tidak membuat duplicate calendar event / update idempotent */
    public function test_skenario_G_and_L_idempotent_sync_and_duplicate_prevention()
    {
        $event = Event::create([
            'nama' => 'Event Idempotent',
            'type_id' => $this->eventType->id,
            'tanggal_mulai' => now()->addDays(3)->format('Y-m-d 10:00:00'),
            'tanggal_selesai' => now()->addDays(3)->format('Y-m-d 15:00:00'),
            'lokasi' => 'Hall Utama',
            'eo_id' => $this->eo1->id,
            'status' => 'Rencana',
        ]);

        // First attachment
        $event->sales()->attach($this->sales1->id, ['assigned_by_spv_id' => $this->spv1->id, 'google_event_id' => 'G-EVENT-123']);

        $calendarService = new GoogleCalendarService();

        // Sync twice for same event and sales
        $res1 = $calendarService->syncSalesEvent($event, $this->sales1);
        $res2 = $calendarService->syncSalesEvent($event, $this->sales1);

        // Same google_event_id retained
        $this->assertEquals('G-EVENT-123', $res1['google_event_id']);
        $this->assertEquals('G-EVENT-123', $res2['google_event_id']);
        $this->assertEquals(1, DB::table('event_sales')->where('event_id', $event->id)->where('sales_id', $this->sales1->id)->count());
    }

    /** H, I, J, K. Payload Google Calendar memiliki name, location, date/time, dan EO info */
    public function test_skenario_H_I_J_K_calendar_payload_contains_required_fields()
    {
        $event = Event::create([
            'nama' => 'Pameran Pendidikan 2026',
            'type_id' => $this->eventType->id,
            'tanggal_mulai' => '2026-10-15 08:00:00',
            'tanggal_selesai' => '2026-10-15 12:00:00',
            'lokasi' => 'Hotel Santika Cirebon',
            'deskripsi' => 'Sosialisasi PMB',
            'eo_id' => $this->eo1->id,
            'status' => 'Rencana',
        ]);

        $calendarService = new GoogleCalendarService();
        $payload = $calendarService->buildEventPayload($event);

        $this->assertEquals('Pameran Pendidikan 2026', $payload['summary']);
        $this->assertEquals('Hotel Santika Cirebon', $payload['location']);
        $this->assertStringContainsString('2026-10-15T08:00:00', $payload['start']['dateTime']);
        $this->assertStringContainsString('2026-10-15T12:00:00', $payload['end']['dateTime']);
        $this->assertEquals('EO One', $payload['organizer']['displayName']);
    }

    /** M. Event mengikuti academic year yang benar */
    public function test_skenario_M_event_binds_to_active_academic_year()
    {
        $event = Event::create([
            'nama' => 'Event Academic Year',
            'type_id' => $this->eventType->id,
            'tanggal_mulai' => now()->addDays(5)->format('Y-m-d 08:00:00'),
            'tanggal_selesai' => now()->addDays(5)->format('Y-m-d 10:00:00'),
            'lokasi' => 'Kampus Utama',
            'eo_id' => $this->eo1->id,
            'status' => 'Rencana',
        ]);

        $this->assertEquals($this->activeTa->id, $event->academic_year_id);
    }

    /** FINDING 1: Validasi Sales Assignment berdasarkan Role */
    public function test_finding1_eo_can_assign_sales_user()
    {
        $this->actingAs($this->eo1);

        $response = $this->post(route('eo.events.store'), [
            'name' => 'Event Sales Valid',
            'type_id' => $this->eventType->id,
            'tanggal' => now()->addDays(2)->format('Y-m-d'),
            'waktu_mulai' => '09:00',
            'waktu_selesai' => '12:00',
            'lokasi' => 'Gedung A',
            'spvs' => [$this->spv1->id],
            'sales' => [$this->sales1->id],
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('events', ['nama' => 'Event Sales Valid']);
    }

    public function test_finding1_eo_cannot_assign_non_sales_roles()
    {
        $this->actingAs($this->eo1);

        $admin = User::create([
            'name' => 'Admin Test',
            'email' => 'admin.test@cic.ac.id',
            'password' => bcrypt('password'),
            'role' => 'Admin',
            'status' => 'aktif',
        ]);

        $hm = User::create([
            'name' => 'HM Test',
            'email' => 'hm.test@cic.ac.id',
            'password' => bcrypt('password'),
            'role' => 'HM',
            'status' => 'aktif',
        ]);

        $cs = User::create([
            'name' => 'CS Test',
            'email' => 'cs.test@cic.ac.id',
            'password' => bcrypt('password'),
            'role' => 'CS',
            'status' => 'aktif',
        ]);

        // Attempt Admin as Sales
        $responseAdmin = $this->post(route('eo.events.store'), [
            'name' => 'Event Fail Admin',
            'type_id' => $this->eventType->id,
            'tanggal' => now()->addDays(2)->format('Y-m-d'),
            'waktu_mulai' => '09:00',
            'waktu_selesai' => '12:00',
            'lokasi' => 'Gedung B',
            'spvs' => [$this->spv1->id],
            'sales' => [$admin->id],
        ]);
        $responseAdmin->assertSessionHasErrors(['sales.0']);

        // Attempt HM as Sales
        $responseHm = $this->post(route('eo.events.store'), [
            'name' => 'Event Fail HM',
            'type_id' => $this->eventType->id,
            'tanggal' => now()->addDays(2)->format('Y-m-d'),
            'waktu_mulai' => '09:00',
            'waktu_selesai' => '12:00',
            'lokasi' => 'Gedung B',
            'spvs' => [$this->spv1->id],
            'sales' => [$hm->id],
        ]);
        $responseHm->assertSessionHasErrors(['sales.0']);

        // Attempt SPV as Sales
        $responseSpv = $this->post(route('eo.events.store'), [
            'name' => 'Event Fail SPV',
            'type_id' => $this->eventType->id,
            'tanggal' => now()->addDays(2)->format('Y-m-d'),
            'waktu_mulai' => '09:00',
            'waktu_selesai' => '12:00',
            'lokasi' => 'Gedung B',
            'spvs' => [$this->spv1->id],
            'sales' => [$this->spv1->id],
        ]);
        $responseSpv->assertSessionHasErrors(['sales.0']);

        // Attempt CS as Sales
        $responseCs = $this->post(route('eo.events.store'), [
            'name' => 'Event Fail CS',
            'type_id' => $this->eventType->id,
            'tanggal' => now()->addDays(2)->format('Y-m-d'),
            'waktu_mulai' => '09:00',
            'waktu_selesai' => '12:00',
            'lokasi' => 'Gedung B',
            'spvs' => [$this->spv1->id],
            'sales' => [$cs->id],
        ]);
        $responseCs->assertSessionHasErrors(['sales.0']);

        // Attempt EO as Sales
        $responseEo = $this->post(route('eo.events.store'), [
            'name' => 'Event Fail EO',
            'type_id' => $this->eventType->id,
            'tanggal' => now()->addDays(2)->format('Y-m-d'),
            'waktu_mulai' => '09:00',
            'waktu_selesai' => '12:00',
            'lokasi' => 'Gedung B',
            'spvs' => [$this->spv1->id],
            'sales' => [$this->eo1->id],
        ]);
        $responseEo->assertSessionHasErrors(['sales.0']);
    }

    /** FINDING 2: Google Calendar Integration & Orphan Cleanup Compensation */
    public function test_finding2_google_create_success()
    {
        config(['services.google.client_id' => 'mock_client_id']);
        config(['services.google.client_secret' => 'mock_client_secret']);

        \Illuminate\Support\Facades\Http::fake([
            'https://www.googleapis.com/*' => \Illuminate\Support\Facades\Http::response([
                'id' => 'G-EVENT-NEW-001',
            ], 200),
        ]);

        $this->sales1->update([
            'google_access_token' => 'mock_access_token',
            'google_token_expires_at' => now()->addHour(),
        ]);

        $event = Event::create([
            'nama' => 'Google Create Test',
            'type_id' => $this->eventType->id,
            'tanggal_mulai' => now()->addDays(2)->format('Y-m-d 09:00:00'),
            'tanggal_selesai' => now()->addDays(2)->format('Y-m-d 12:00:00'),
            'lokasi' => 'Hall X',
            'eo_id' => $this->eo1->id,
            'status' => 'Rencana',
        ]);
        $event->sales()->attach($this->sales1->id, ['assigned_by_spv_id' => $this->spv1->id]);

        $calendarService = new GoogleCalendarService();
        $result = $calendarService->syncSalesEvent($event, $this->sales1);

        $this->assertTrue($result['synced']);
        $this->assertEquals('created', $result['status']);
        $this->assertEquals('G-EVENT-NEW-001', $result['google_event_id']);
        $this->assertDatabaseHas('event_sales', [
            'event_id' => $event->id,
            'sales_id' => $this->sales1->id,
            'google_event_id' => 'G-EVENT-NEW-001',
        ]);
    }

    public function test_finding2_google_create_failure_does_not_claim_success()
    {
        config(['services.google.client_id' => 'mock_client_id']);
        config(['services.google.client_secret' => 'mock_client_secret']);

        \Illuminate\Support\Facades\Http::fake([
            'https://www.googleapis.com/*' => \Illuminate\Support\Facades\Http::response([
                'error' => 'Internal Server Error',
            ], 500),
        ]);

        $this->sales1->update([
            'google_access_token' => 'mock_access_token',
            'google_token_expires_at' => now()->addHour(),
        ]);

        $event = Event::create([
            'nama' => 'Google Fail Test',
            'type_id' => $this->eventType->id,
            'tanggal_mulai' => now()->addDays(2)->format('Y-m-d 09:00:00'),
            'tanggal_selesai' => now()->addDays(2)->format('Y-m-d 12:00:00'),
            'lokasi' => 'Hall Y',
            'eo_id' => $this->eo1->id,
            'status' => 'Rencana',
        ]);
        $event->sales()->attach($this->sales1->id, ['assigned_by_spv_id' => $this->spv1->id]);

        $calendarService = new GoogleCalendarService();
        $result = $calendarService->syncSalesEvent($event, $this->sales1);

        $this->assertFalse($result['synced']);
        $this->assertEquals('fallback_internal', $result['status']);
        $this->assertNull(DB::table('event_sales')->where('event_id', $event->id)->where('sales_id', $this->sales1->id)->value('google_event_id'));
    }

    public function test_finding2_db_update_failure_triggers_compensation_delete()
    {
        config(['services.google.client_id' => 'mock_client_id']);
        config(['services.google.client_secret' => 'mock_client_secret']);

        \Illuminate\Support\Facades\Http::fake([
            'https://www.googleapis.com/calendar/v3/calendars/primary/events/G-ORPHAN-999' => \Illuminate\Support\Facades\Http::response([], 204),
            'https://www.googleapis.com/*' => \Illuminate\Support\Facades\Http::response([
                'id' => 'G-ORPHAN-999',
            ], 200),
        ]);

        $this->sales1->update([
            'google_access_token' => 'mock_access_token',
            'google_token_expires_at' => now()->addHour(),
        ]);

        $event = Event::create([
            'nama' => 'Compensation Cleanup Test',
            'type_id' => $this->eventType->id,
            'tanggal_mulai' => now()->addDays(2)->format('Y-m-d 09:00:00'),
            'tanggal_selesai' => now()->addDays(2)->format('Y-m-d 12:00:00'),
            'lokasi' => 'Hall Z',
            'eo_id' => $this->eo1->id,
            'status' => 'Rencana',
        ]);

        // Do NOT attach pivot record so DB update will affect 0 rows
        $calendarService = new GoogleCalendarService();
        $result = $calendarService->syncSalesEvent($event, $this->sales1);

        $this->assertFalse($result['synced']);
        $this->assertEquals('fallback_internal', $result['status']);

        // Assert that HTTP DELETE was called to clean up the orphan Google event
        \Illuminate\Support\Facades\Http::assertSent(function ($request) {
            return $request->method() === 'DELETE' &&
                   str_contains($request->url(), 'G-ORPHAN-999');
        });
    }

    public function test_finding2_existing_google_event_issues_put_update()
    {
        config(['services.google.client_id' => 'mock_client_id']);
        config(['services.google.client_secret' => 'mock_client_secret']);

        \Illuminate\Support\Facades\Http::fake([
            'https://www.googleapis.com/*' => \Illuminate\Support\Facades\Http::response([
                'id' => 'G-EXISTING-123',
            ], 200),
        ]);

        $this->sales1->update([
            'google_access_token' => 'mock_access_token',
            'google_token_expires_at' => now()->addHour(),
        ]);

        $event = Event::create([
            'nama' => 'Existing Event Test',
            'type_id' => $this->eventType->id,
            'tanggal_mulai' => now()->addDays(2)->format('Y-m-d 09:00:00'),
            'tanggal_selesai' => now()->addDays(2)->format('Y-m-d 12:00:00'),
            'lokasi' => 'Hall Update',
            'eo_id' => $this->eo1->id,
            'status' => 'Rencana',
        ]);
        $event->sales()->attach($this->sales1->id, [
            'assigned_by_spv_id' => $this->spv1->id,
            'google_event_id' => 'G-EXISTING-123',
        ]);

        $calendarService = new GoogleCalendarService();
        $result = $calendarService->syncSalesEvent($event, $this->sales1);

        $this->assertTrue($result['synced']);
        $this->assertEquals('updated', $result['status']);
        $this->assertEquals('G-EXISTING-123', $result['google_event_id']);

        \Illuminate\Support\Facades\Http::assertSent(function ($request) {
            return $request->method() === 'PUT' &&
                   str_contains($request->url(), 'G-EXISTING-123');
        });
    }
}
