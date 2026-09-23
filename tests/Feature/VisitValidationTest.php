<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Sekolah;
use App\Models\Perusahaan;
use App\Models\Prodi;
use App\Models\Kunjungan;
use App\Models\Event;
use App\Models\MasterData;
use App\Services\GeoLocationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VisitValidationTest extends TestCase
{
    use RefreshDatabase;

    protected User $sales;
    protected User $dosen;
    protected Prodi $prodi;
    protected Sekolah $sekolah;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->sales = User::factory()->create(['role' => 'Sales']);
        $this->dosen = User::factory()->create(['role' => 'Admin', 'name' => 'Dr. Budi Utomo']);

        $this->prodi = Prodi::first() ?? Prodi::create([
            'kode' => 'TI',
            'nama' => 'Teknik Informatika',
            'jenjang' => 'S1',
            'fakultas' => 'FTIK',
            'status' => 'Aktif',
        ]);

        $this->sekolah = Sekolah::create([
            'kode'        => 'SCH-001',
            'nama'        => 'SMA Negeri 1 Cirebon',
            'tier'        => 'A',
            'alamat'      => 'Jl. Dr. Wahidin Sudirohusodo',
            'pic_name'    => 'Bapak Kepala Sekolah',
            'pic_phone'   => '08123456789',
            'status'      => 'Aktif',
            'lat'         => -6.72620000,
            'lng'         => 108.55740000,
        ]);
    }

    protected function getValidVisitPayload(): array
    {
        return [
            'jenis'          => 'Sekolah',
            'sekolah_id'     => $this->sekolah->id,
            'prodi_id'       => $this->prodi->id,
            'tanggal'        => '2026-10-01',
            'waktu'          => '09:00',
            'pic_name'       => 'Bpk Hartono',
            'pic_whatsapp'   => '081234567890',
            'catatan'        => 'Audiensi kerjasama pendaftaran siswa.',
            'foto'           => UploadedFile::fake()->image('visit.jpg'),
            'lat'            => -6.72625000, // ~5.5 meters from school
            'lng'            => 108.55740000,
        ];
    }

    /**
     * Test A: Visit dengan koordinat valid → jarak dihitung dari koordinat aktual.
     */
    public function test_a_visit_with_valid_coordinates_calculates_actual_distance()
    {
        $payload = $this->getValidVisitPayload();

        $response = $this->actingAs($this->sales)->post(route('sales.kunjungan.store'), $payload);

        $response->assertRedirect();
        $visit = Kunjungan::where('sales_id', $this->sales->id)->latest('id')->first();
        $this->assertNotNull($visit);
        $this->assertEquals('Valid', $visit->status_lokasi);
        $this->assertEquals('Valid', $visit->status_verifikasi);
        $this->assertEquals('Selesai', $visit->status);
        $this->assertTrue($visit->is_verified);
        $this->assertFalse($visit->is_outside_radius);
        $this->assertGreaterThanOrEqual(0, $visit->jarak_meter);
    }

    /**
     * Test B: Visit di luar radius → Perlu Verifikasi.
     */
    public function test_b_visit_outside_radius_marks_perlu_verifikasi()
    {
        $payload = $this->getValidVisitPayload();
        // Location ~3 km away from the school
        $payload['lat'] = -6.75000000;
        $payload['lng'] = 108.58000000;

        $response = $this->actingAs($this->sales)->post(route('sales.kunjungan.store'), $payload);

        $response->assertRedirect();
        $visit = Kunjungan::where('sales_id', $this->sales->id)->latest('id')->first();
        $this->assertNotNull($visit);
        $this->assertEquals('Perlu Verifikasi', $visit->status);
        $this->assertEquals('Perlu Verifikasi', $visit->status_lokasi);
        $this->assertEquals('Perlu Verifikasi', $visit->status_verifikasi);
        $this->assertTrue($visit->is_outside_radius);
        $this->assertFalse($visit->is_verified);
        $this->assertGreaterThan(100, $visit->jarak_meter);
    }

    /**
     * Test C: Pastikan tidak ada lagi hardcode jarak_meter = 0 sebagai hasil normal.
     */
    public function test_c_no_hardcoded_zero_distance_when_coordinates_differ()
    {
        $payload = $this->getValidVisitPayload();
        // ~500m away
        $payload['lat'] = -6.73000000;
        $payload['lng'] = 108.55740000;

        $response = $this->actingAs($this->sales)->post(route('sales.kunjungan.store'), $payload);

        $response->assertRedirect();
        $visit = Kunjungan::where('sales_id', $this->sales->id)->latest('id')->first();
        $this->assertNotNull($visit);
        $this->assertNotEquals(0, $visit->jarak_meter);
        $this->assertGreaterThan(400, $visit->jarak_meter);
    }

    /**
     * Test D: Visit tanpa foto → gagal.
     */
    public function test_d_visit_without_photo_fails()
    {
        $payload = $this->getValidVisitPayload();
        unset($payload['foto']);

        $response = $this->actingAs($this->sales)->post(route('sales.kunjungan.store'), $payload);
        $response->assertSessionHasErrors('foto');
    }

    /**
     * Test E: Foto valid → visit dapat disimpan.
     */
    public function test_e_valid_photo_saves_visit_and_file()
    {
        $payload = $this->getValidVisitPayload();

        $response = $this->actingAs($this->sales)->post(route('sales.kunjungan.store'), $payload);
        $response->assertRedirect();

        $visit = Kunjungan::where('sales_id', $this->sales->id)->latest('id')->first();
        $this->assertNotNull($visit);
        $this->assertNotNull($visit->foto_path);
        Storage::disk('public')->assertExists($visit->foto_path);
    }

    /**
     * Test F: Training tanpa Dosen Pemateri → gagal.
     */
    public function test_f_training_without_dosen_fails()
    {
        $payload = $this->getValidVisitPayload();
        $payload['kesediaan_training_ai'] = 1;
        unset($payload['dosen_id']);
        unset($payload['dosen_pemateri']);

        $response = $this->actingAs($this->sales)->post(route('sales.kunjungan.store'), $payload);
        $response->assertSessionHasErrors('dosen_id');
    }

    /**
     * Test G: Training dengan Dosen Pemateri → berhasil.
     */
    public function test_g_training_with_dosen_succeeds()
    {
        $payload = $this->getValidVisitPayload();
        $payload['kesediaan_training_ai'] = 1;
        $payload['dosen_id'] = $this->dosen->id;

        $response = $this->actingAs($this->sales)->post(route('sales.kunjungan.store'), $payload);
        $response->assertSessionHasNoErrors();

        $visit = Kunjungan::where('sales_id', $this->sales->id)->latest('id')->first();
        $this->assertNotNull($visit);
        $this->assertEquals($this->dosen->id, $visit->dosen_id);
    }

    /**
     * Test H: Prodi kosong → gagal.
     */
    public function test_h_empty_prodi_fails()
    {
        $payload = $this->getValidVisitPayload();
        unset($payload['prodi_id']);

        $response = $this->actingAs($this->sales)->post(route('sales.kunjungan.store'), $payload);
        $response->assertSessionHasErrors('prodi_id');
    }

    /**
     * Test I: QR/session uniqueness tervalidasi.
     */
    public function test_i_qr_session_uniqueness()
    {
        $payload1 = $this->getValidVisitPayload();
        $this->actingAs($this->sales)->post(route('sales.kunjungan.store'), $payload1);
        $visit1 = Kunjungan::where('sales_id', $this->sales->id)->latest('id')->first();

        $payload2 = $this->getValidVisitPayload();
        $this->actingAs($this->sales)->post(route('sales.kunjungan.store'), $payload2);
        $visit2 = Kunjungan::where('sales_id', $this->sales->id)->latest('id')->first();

        $this->assertNotNull($visit1->qr_code);
        $this->assertNotNull($visit2->qr_code);
        $this->assertNotEquals($visit1->qr_code, $visit2->qr_code);
    }

    /**
     * Test J: Tier A/B/C dan budget menggunakan source/config yang benar.
     */
    public function test_j_tier_and_budget_from_config()
    {
        $this->sekolah->update(['tier' => 'A']);
        $payloadA = $this->getValidVisitPayload();
        $this->actingAs($this->sales)->post(route('sales.kunjungan.store'), $payloadA);
        $visitA = Kunjungan::where('sales_id', $this->sales->id)->latest('id')->first();

        $this->assertEquals('A', $visitA->tier);
        $this->assertEquals(10000000, (float) $visitA->budget_maksimum);

        $this->sekolah->update(['tier' => 'C']);
        $payloadC = $this->getValidVisitPayload();
        $this->actingAs($this->sales)->post(route('sales.kunjungan.store'), $payloadC);
        $visitC = Kunjungan::where('sales_id', $this->sales->id)->latest('id')->first();

        $this->assertEquals('C', $visitC->tier);
        $this->assertEquals(2500000, (float) $visitC->budget_maksimum);
    }

    /**
     * Test API: Endpoint /api/v1/kunjungan enforces Sanctum auth, geo calculation, and validation.
     */
    public function test_api_visit_endpoint_requires_auth_and_validates_geo()
    {
        // 1. Unauthenticated request rejected
        $response = $this->postJson('/api/v1/kunjungan', []);
        $response->assertStatus(401);

        // 2. Authenticated request with geo calculation
        Sanctum::actingAs($this->sales);

        $apiPayload = [
            'sekolah_id'   => $this->sekolah->id,
            'prodi_id'     => $this->prodi->id,
            'tanggal'      => '2026-10-01',
            'tujuan'       => 'SMA Negeri 1 Cirebon',
            'foto'         => UploadedFile::fake()->image('api_visit.jpg'),
            'lat'          => -6.72625000,
            'lng'          => 108.55740000,
        ];

        $response = $this->postJson('/api/v1/kunjungan', $apiPayload);
        $response->assertStatus(201);
        $response->assertJsonPath('data.status_lokasi', 'Valid');
        $response->assertJsonPath('data.is_outside_radius', false);
    }

    /**
     * Test EO: Training event without Dosen Pemateri fails validation.
     */
    public function test_eo_event_training_requires_dosen()
    {
        $eo = User::factory()->create(['role' => 'EO']);
        $spv = User::factory()->create(['role' => 'SPV']);
        $eventType = MasterData::firstOrCreate(
            ['type' => 'jenis_event', 'nama' => 'Training AI & Coding'],
            ['kode' => 'EV-TR', 'deskripsi' => 'Training', 'status' => 'Aktif']
        );

        $payload = [
            'name'           => 'Training AI for Teachers',
            'type_id'        => $eventType->id,
            'tanggal'        => '2026-10-15',
            'waktu_mulai'    => '08:00',
            'waktu_selesai'  => '12:00',
            'lokasi'         => 'Aula SMA 1',
            'spvs'           => [$spv->id],
            'sekolah_id'     => $this->sekolah->id,
            'prodi_id'       => $this->prodi->id,
        ];

        $response = $this->actingAs($eo)->post(route('eo.events.store'), $payload);
        $response->assertSessionHasErrors('dosen_id');
    }

    /**
     * Test EO: Training event with Dosen Pemateri succeeds and associates school & prodi.
     */
    public function test_eo_event_training_with_dosen_succeeds()
    {
        $eo = User::factory()->create(['role' => 'EO']);
        $spv = User::factory()->create(['role' => 'SPV']);
        $eventType = MasterData::firstOrCreate(
            ['type' => 'jenis_event', 'nama' => 'Training AI & Coding'],
            ['kode' => 'EV-TR', 'deskripsi' => 'Training', 'status' => 'Aktif']
        );

        $payload = [
            'name'           => 'Training AI for Teachers',
            'type_id'        => $eventType->id,
            'tanggal'        => '2026-10-15',
            'waktu_mulai'    => '08:00',
            'waktu_selesai'  => '12:00',
            'lokasi'         => 'Aula SMA 1',
            'spvs'           => [$spv->id],
            'dosen_id'       => $this->dosen->id,
            'sekolah_id'     => $this->sekolah->id,
            'prodi_id'       => $this->prodi->id,
        ];

        $response = $this->actingAs($eo)->post(route('eo.events.store'), $payload);
        $response->assertSessionHasNoErrors();

        $event = Event::where('eo_id', $eo->id)->latest('id')->first();
        $this->assertNotNull($event);
        $this->assertEquals($this->dosen->id, $event->dosen_id);
        $this->assertEquals($this->sekolah->id, $event->sekolah_id);
        $this->assertEquals($this->prodi->id, $event->prodi_id);
        $this->assertNotNull($event->qr_code);
    }
}
