<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\MasterData;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CalendarTakeoverTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $dataAnalyst;
    protected User $eo;
    protected User $spv;
    protected User $sales;
    protected User $sales2;
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

        $this->dataAnalyst = User::factory()->create([
            'name'   => 'Data Analyst User',
            'role'   => 'Data Analyst',
            'status' => 'Aktif',
        ]);

        $this->eo = User::factory()->create([
            'name'   => 'EO User',
            'role'   => 'EO',
            'status' => 'Aktif',
        ]);

        $this->spv = User::factory()->create([
            'name'   => 'SPV User',
            'role'   => 'SPV',
            'status' => 'Aktif',
        ]);

        $this->sales = User::factory()->create([
            'name'   => 'Sales User 1',
            'role'   => 'Sales',
            'status' => 'Aktif',
        ]);

        $this->sales2 = User::factory()->create([
            'name'   => 'Sales User 2',
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
            'kode'   => 'WRKSHP',
            'nama'   => 'Workshop Edukasi',
            'status' => 'Aktif',
        ]);
    }

    /**
     * Requirement 1: Agenda baru dapat dibuat tanpa assigned_users[].
     */
    public function test_1_agenda_baru_dapat_dibuat_tanpa_assigned_users()
    {
        $response = $this->actingAs($this->sales)->post(route('calendar.meetings.store'), [
            'name'          => 'Kunjungan Lapangan Bersama',
            'tanggal'       => '2026-11-01',
            'waktu_mulai'   => '09:00',
            'waktu_selesai' => '11:00',
            'lokasi'        => 'SMK Informatika',
            'jenis'         => 'Kunjungan Sekolah',
            'deskripsi'     => 'Sosialisasi prodi.',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('events', [
            'name'     => '[Kunjungan Sekolah] Kunjungan Lapangan Bersama',
            'type_id'  => null,
            'eo_id'    => $this->sales->id,
            'lokasi'   => 'SMK Informatika',
        ]);

        // Verifikasi tidak ada selective assignment di tabel pivot event_sales
        $event = Event::where('name', '[Kunjungan Sekolah] Kunjungan Lapangan Bersama')->first();
        $this->assertCount(0, $event->sales);
    }

    /**
     * Requirement 2: Agenda tetap terlihat oleh pengguna yang memiliki akses.
     */
    public function test_2_agenda_tetap_terlihat_oleh_pengguna_yang_memiliki_akses()
    {
        Event::create([
            'name'            => 'Rapat Divisi Bulanan',
            'nama'            => 'Rapat Divisi Bulanan',
            'tanggal'         => '2026-11-02',
            'waktu_mulai'     => '10:00',
            'waktu_selesai'   => '12:00',
            'tanggal_mulai'   => '2026-11-02 10:00:00',
            'tanggal_selesai' => '2026-11-02 12:00:00',
            'lokasi'          => 'Ruang Rapat Utama',
            'eo_id'           => $this->spv->id,
            'status'          => 'Scheduled',
        ]);

        foreach ([$this->admin, $this->sales, $this->dataAnalyst, $this->cs] as $viewer) {
            $response = $this->actingAs($viewer)->getJson(route('api.calendar.events'));
            $response->assertOk();
            $this->assertTrue(collect($response->json())->contains('title', 'Rapat Divisi Bulanan'));
        }
    }

    /**
     * Requirement 3: Event baru dapat dibuat tanpa memilih SPV.
     */
    public function test_3_event_baru_dapat_dibuat_tanpa_memilih_spv()
    {
        $response = $this->actingAs($this->eo)->post(route('eo.events.store'), [
            'name'          => 'Campus Expo Cirebon 2026',
            'type_id'       => $this->eventType->id,
            'tanggal'       => '2026-11-10',
            'waktu_mulai'   => '08:00',
            'waktu_selesai' => '15:00',
            'lokasi'        => 'Gedung Kesenian Cirebon',
            'deskripsi'     => 'Pameran pendidikan akbar.',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('events', [
            'name'           => 'Campus Expo Cirebon 2026',
            'type_id'        => $this->eventType->id,
            'eo_id'          => $this->eo->id,
            'takeover_by_id' => null,
            'takeover_at'    => null,
        ]);

        $event = Event::where('name', 'Campus Expo Cirebon 2026')->first();
        $this->assertCount(0, $event->spvs);
    }

    /**
     * Requirement 4: Event baru dapat dilihat melalui Kalender Internal.
     */
    public function test_4_event_baru_dapat_dilihat_melalui_kalender_internal()
    {
        $event = Event::create([
            'name'            => 'Festival Inovasi Kampus',
            'nama'            => 'Festival Inovasi Kampus',
            'type_id'         => $this->eventType->id,
            'tanggal'         => '2026-11-15',
            'waktu_mulai'     => '09:00',
            'waktu_selesai'   => '16:00',
            'tanggal_mulai'   => '2026-11-15 09:00:00',
            'tanggal_selesai' => '2026-11-15 16:00:00',
            'lokasi'          => 'Auditorium UCIC',
            'eo_id'           => $this->eo->id,
            'status'          => 'Scheduled',
        ]);

        $response = $this->actingAs($this->sales)->getJson(route('api.calendar.events'));
        $response->assertOk();

        $eventItem = collect($response->json())->firstWhere('id', $event->id);
        $this->assertNotNull($eventItem);
        $this->assertTrue($eventItem['is_eo_event']);
        $this->assertEquals('Festival Inovasi Kampus', $eventItem['title']);
    }

    /**
     * Requirement 5: Role yang diizinkan dapat melakukan Takeover.
     */
    public function test_5_role_yang_diizinkan_dapat_melakukan_takeover()
    {
        $event = Event::create([
            'name'            => 'Roadshow SMK Taruna',
            'type_id'         => $this->eventType->id,
            'tanggal'         => '2026-11-20',
            'eo_id'           => $this->eo->id,
            'status'          => 'Scheduled',
        ]);

        $response = $this->actingAs($this->sales)->postJson(route('calendar.events.takeover', $event->id));
        $response->assertOk();
        $response->assertJson([
            'success' => true,
        ]);

        $event->refresh();
        $this->assertEquals($this->sales->id, $event->takeover_by_id);
        $this->assertNotNull($event->takeover_at);
    }

    /**
     * Requirement 6: Admin tidak dapat melakukan Takeover.
     */
    public function test_6_admin_tidak_dapat_melakukan_takeover()
    {
        $event = Event::create([
            'name'            => 'Seminar Karir Mahasiswa',
            'type_id'         => $this->eventType->id,
            'tanggal'         => '2026-11-21',
            'eo_id'           => $this->eo->id,
            'status'          => 'Scheduled',
        ]);

        $response = $this->actingAs($this->admin)->postJson(route('calendar.events.takeover', $event->id));
        $response->assertStatus(403);
        $response->assertJsonFragment([
            'message' => 'Role Anda tidak diizinkan melakukan Takeover.',
        ]);

        $event->refresh();
        $this->assertNull($event->takeover_by_id);
    }

    /**
     * Requirement 7: Data Analyst tidak dapat melakukan Takeover.
     */
    public function test_7_data_analyst_tidak_dapat_melakukan_takeover()
    {
        $event = Event::create([
            'name'            => 'Bedah Kampus SMA 2',
            'type_id'         => $this->eventType->id,
            'tanggal'         => '2026-11-22',
            'eo_id'           => $this->eo->id,
            'status'          => 'Scheduled',
        ]);

        $response = $this->actingAs($this->dataAnalyst)->postJson(route('calendar.events.takeover', $event->id));
        $response->assertStatus(403);
        $response->assertJsonFragment([
            'message' => 'Role Anda tidak diizinkan melakukan Takeover.',
        ]);

        $event->refresh();
        $this->assertNull($event->takeover_by_id);
    }

    /**
     * Requirement 8: Pengguna yang belum login tidak dapat melakukan Takeover.
     */
    public function test_8_pengguna_belum_login_tidak_dapat_melakukan_takeover()
    {
        $event = Event::create([
            'name'            => 'Webinar Nasional',
            'type_id'         => $this->eventType->id,
            'tanggal'         => '2026-11-23',
            'eo_id'           => $this->eo->id,
            'status'          => 'Scheduled',
        ]);

        $response = $this->postJson(route('calendar.events.takeover', $event->id));
        $response->assertStatus(401);

        $event->refresh();
        $this->assertNull($event->takeover_by_id);
    }

    /**
     * Requirement 9: Pengguna kedua tidak dapat melakukan Takeover jika event sudah diambil pengguna pertama.
     */
    public function test_9_pengguna_kedua_tidak_dapat_melakukan_takeover_jika_sudah_diambil()
    {
        $event = Event::create([
            'name'            => 'Kunjungan Industri PT ABC',
            'type_id'         => $this->eventType->id,
            'tanggal'         => '2026-11-24',
            'eo_id'           => $this->eo->id,
            'status'          => 'Scheduled',
        ]);

        // User 1 mengambil alih event
        $res1 = $this->actingAs($this->sales)->postJson(route('calendar.events.takeover', $event->id));
        $res1->assertOk();

        // User 2 mencoba mengambil alih event yang sama
        $res2 = $this->actingAs($this->sales2)->postJson(route('calendar.events.takeover', $event->id));
        $res2->assertStatus(422);
        $res2->assertJsonFragment([
            'message' => 'Event sudah memiliki penanggung jawab.',
        ]);

        $event->refresh();
        // Tetap dimiliki oleh user 1
        $this->assertEquals($this->sales->id, $event->takeover_by_id);
    }

    /**
     * Requirement 10: Dua request Takeover bersamaan tidak menyebabkan dua penanggung jawab (atomic locking test).
     */
    public function test_10_dua_request_takeover_bersamaan_tidak_menyebabkan_dua_penanggung_jawab()
    {
        $event = Event::create([
            'name'            => 'Job Fair Terbuka',
            'type_id'         => $this->eventType->id,
            'tanggal'         => '2026-11-25',
            'eo_id'           => $this->eo->id,
            'status'          => 'Scheduled',
        ]);

        // Simulasikan transaksi bersamaan: transaksi 1 berhasil, transaksi 2 mengecek kondisi terkunci
        DB::transaction(function () use ($event) {
            $locked = Event::where('id', $event->id)->lockForUpdate()->first();
            $locked->update([
                'takeover_by_id' => $this->sales->id,
                'takeover_at'    => now(),
            ]);
        });

        // Request kedua setelah status berubah
        $response = $this->actingAs($this->sales2)->postJson(route('calendar.events.takeover', $event->id));
        $response->assertStatus(422);

        $event->refresh();
        $this->assertEquals($this->sales->id, $event->takeover_by_id);
    }

    /**
     * Requirement 11: Identitas penanggung jawab berasal dari pengguna yang login.
     */
    public function test_11_identitas_penanggung_jawab_berasal_dari_pengguna_yang_login()
    {
        $event = Event::create([
            'name'            => 'Workshop Robotika',
            'type_id'         => $this->eventType->id,
            'tanggal'         => '2026-11-26',
            'eo_id'           => $this->eo->id,
            'status'          => 'Scheduled',
        ]);

        // Mengirimkan user_id atau takeover_by_id palsu di payload request
        $response = $this->actingAs($this->sales)->postJson(route('calendar.events.takeover', $event->id), [
            'user_id'        => $this->admin->id,
            'takeover_by_id' => $this->sales2->id,
        ]);

        $response->assertOk();

        $event->refresh();
        // Sistem harus tetap menggunakan auth()->id() bukan dari body payload
        $this->assertEquals($this->sales->id, $event->takeover_by_id);
    }

    /**
     * Requirement 12: Event yang belum diambil menampilkan status yang benar di API.
     */
    public function test_12_event_yang_belum_diambil_menampilkan_status_yang_benar_di_api()
    {
        $event = Event::create([
            'name'            => 'Try Out Akbar SBMPTN',
            'type_id'         => $this->eventType->id,
            'tanggal'         => '2026-11-27',
            'eo_id'           => $this->eo->id,
            'status'          => 'Scheduled',
        ]);

        $response = $this->actingAs($this->sales)->getJson(route('api.calendar.events'));
        $response->assertOk();

        $item = collect($response->json())->firstWhere('id', $event->id);
        $this->assertNotNull($item);
        $this->assertNull($item['takeover_by_id']);
        $this->assertNull($item['takeover_by']);
        $this->assertNull($item['takeover_at']);
    }

    /**
     * Requirement 13: Event yang sudah diambil menampilkan penanggung jawab yang benar di API.
     */
    public function test_13_event_yang_sudah_diambil_menampilkan_penanggung_jawab_yang_benar_di_api()
    {
        $event = Event::create([
            'name'            => 'Seminar AI & Data Science',
            'type_id'         => $this->eventType->id,
            'tanggal'         => '2026-11-28',
            'eo_id'           => $this->eo->id,
            'takeover_by_id'  => $this->spv->id,
            'takeover_at'     => now(),
            'status'          => 'Scheduled',
        ]);

        $response = $this->actingAs($this->sales)->getJson(route('api.calendar.events'));
        $response->assertOk();

        $item = collect($response->json())->firstWhere('id', $event->id);
        $this->assertNotNull($item);
        $this->assertEquals($this->spv->id, $item['takeover_by_id']);
        $this->assertNotNull($item['takeover_by']);
        $this->assertEquals($this->spv->name, $item['takeover_by']['name']);
        $this->assertEquals('SPV', $item['takeover_by']['role']);
        $this->assertNotNull($item['takeover_at']);
    }

    /**
     * Requirement 14: Fitur SPV Assignment saat pembuatan event tetap berjalan jika SPV dipilih secara opsional.
     */
    public function test_14_fitur_spv_assignment_tetap_berjalan_jika_spv_diberikan()
    {
        $response = $this->actingAs($this->eo)->post(route('eo.events.store'), [
            'name'          => 'Pelatihan Desain Grafis',
            'type_id'       => $this->eventType->id,
            'tanggal'       => '2026-11-29',
            'waktu_mulai'   => '08:00',
            'waktu_selesai' => '12:00',
            'lokasi'        => 'Lab Komputer 3',
            'spvs'          => [$this->spv->id],
        ]);

        $response->assertRedirect();

        $event = Event::where('name', 'Pelatihan Desain Grafis')->first();
        $this->assertNotNull($event);
        $this->assertTrue($event->spvs->contains($this->spv->id));
    }
}
