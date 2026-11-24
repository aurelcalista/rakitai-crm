<?php

namespace Tests\Feature;

use App\Models\Kunjungan;
use App\Models\Perusahaan;
use App\Models\Prodi;
use App\Models\Sekolah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CsCollaborativeVisitTest extends TestCase
{
    use RefreshDatabase;

    protected User $adinda;
    protected User $arel;
    protected User $bima;
    protected User $unrelatedCs;
    protected User $sales;
    protected User $spv;
    protected Sekolah $sekolah;
    protected Prodi $prodi;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        // Create CS users
        $this->adinda = User::factory()->create([
            'name'   => 'Adinda CS',
            'email'  => 'adinda@cic.ac.id',
            'role'   => 'CS',
            'status' => 'Aktif',
        ]);

        $this->arel = User::factory()->create([
            'name'   => 'Arel CS',
            'email'  => 'arel@cic.ac.id',
            'role'   => 'CS',
            'status' => 'Aktif',
        ]);

        $this->bima = User::factory()->create([
            'name'   => 'Bima CS',
            'email'  => 'bima@cic.ac.id',
            'role'   => 'CS',
            'status' => 'Aktif',
        ]);

        $this->unrelatedCs = User::factory()->create([
            'name'   => 'Unrelated CS',
            'email'  => 'unrelated@cic.ac.id',
            'role'   => 'CS',
            'status' => 'Aktif',
        ]);

        // Create SPV and Sales users
        $this->spv = User::factory()->create([
            'name'   => 'Supervisor Hendra',
            'email'  => 'spv@cic.ac.id',
            'role'   => 'SPV',
            'status' => 'Aktif',
        ]);

        $this->sales = User::factory()->create([
            'name'          => 'Sales Rizky',
            'email'         => 'sales@cic.ac.id',
            'role'          => 'Sales',
            'status'        => 'Aktif',
            'supervisor_id' => $this->spv->id,
        ]);

        // Create Master Data
        $this->prodi = Prodi::create([
            'kode'     => 'IF',
            'nama'     => 'Informatika',
            'jenjang'  => 'S1',
            'fakultas' => 'FTIK',
            'status'   => 'Aktif',
        ]);

        $this->sekolah = Sekolah::create([
            'kode'      => 'SCH-CS-001',
            'nama'      => 'SMA Negeri 1 Cirebon',
            'tier'      => 'A',
            'alamat'    => 'Jl. Wahidin Cirebon',
            'pic_name'  => 'Pak Guru BK',
            'pic_phone' => '+6281234567890',
            'status'    => 'Aktif',
            'lat'       => -6.72620000,
            'lng'       => 108.55740000,
        ]);
    }

    protected function getValidVisitPayload(array $overrides = []): array
    {
        return array_merge([
            'jenis'          => 'Sekolah',
            'sekolah_id'     => $this->sekolah->id,
            'prodi_id'       => $this->prodi->id,
            'tanggal'        => '2026-11-23',
            'waktu'          => '09:00',
            'pic_name'       => 'Pak Guru BK',
            'pic_whatsapp'   => '81234567890',
            'catatan'        => 'Kunjungan kolaborasi sosialisasi kampus.',
            'foto'           => UploadedFile::fake()->image('visit.jpg'),
            'lat'            => -6.72625000,
            'lng'            => 108.55740000,
        ], $overrides);
    }

    /**
     * Scenario 1: CS Adinda creates a visit without tagging anyone. The report works normally.
     */
    public function test_1_cs_creates_visit_without_tagging_anyone_works_normally(): void
    {
        $payload = $this->getValidVisitPayload();

        $response = $this->actingAs($this->adinda)->post(route('kunjungan.store'), $payload);

        $response->assertRedirect();
        $this->assertDatabaseHas('kunjungans', [
            'sales_id'       => $this->adinda->id,
            'nama_institusi' => 'SMA Negeri 1 Cirebon',
        ]);

        $visit = Kunjungan::where('sales_id', $this->adinda->id)->first();
        $this->assertNotNull($visit);
        $this->assertCount(0, $visit->participants);

        // Appears in Adinda's history
        $historyResponse = $this->actingAs($this->adinda)->get(route('kunjungan.index'));
        $historyResponse->assertOk();
        $historyResponse->assertSee('SMA Negeri 1 Cirebon');
    }

    /**
     * Scenario 2: CS Adinda tags CS Arel. The report appears in both histories.
     */
    public function test_2_cs_adinda_tags_cs_arel_report_appears_in_both_histories(): void
    {
        $payload = $this->getValidVisitPayload([
            'participant_ids' => [$this->arel->id],
        ]);

        $response = $this->actingAs($this->adinda)->post(route('kunjungan.store'), $payload);

        $response->assertRedirect();

        // Exactly ONE record in kunjungans table
        $this->assertEquals(1, Kunjungan::count());
        $visit = Kunjungan::first();

        // Primary creator is Adinda
        $this->assertEquals($this->adinda->id, $visit->sales_id);

        // Relational pivot table contains Arel
        $this->assertDatabaseHas('kunjungan_users', [
            'kunjungan_id' => $visit->id,
            'user_id'      => $this->arel->id,
        ]);
        $this->assertTrue($visit->hasParticipant($this->arel->id));

        // Visit appears in Adinda's history
        $adindaHistory = $this->actingAs($this->adinda)->get(route('kunjungan.index'));
        $adindaHistory->assertOk();
        $adindaHistory->assertSee('SMA Negeri 1 Cirebon');

        // Visit appears in Arel's history automatically
        $arelHistory = $this->actingAs($this->arel)->get(route('kunjungan.index'));
        $arelHistory->assertOk();
        $arelHistory->assertSee('SMA Negeri 1 Cirebon');
    }

    /**
     * Scenario 3: CS Adinda tags multiple CS members. The same report appears in every tagged member's history.
     */
    public function test_3_cs_adinda_tags_multiple_cs_members_appears_in_all_histories_without_duplicates(): void
    {
        $payload = $this->getValidVisitPayload([
            'participant_ids' => [$this->arel->id, $this->bima->id],
        ]);

        $response = $this->actingAs($this->adinda)->post(route('kunjungan.store'), $payload);

        $response->assertRedirect();

        // Only ONE record created
        $this->assertEquals(1, Kunjungan::count());
        $visit = Kunjungan::first();

        // Check pivot table
        $this->assertEquals(2, $visit->participants()->count());
        $this->assertTrue($visit->hasParticipant($this->arel->id));
        $this->assertTrue($visit->hasParticipant($this->bima->id));

        // Appears in Adinda, Arel, and Bima's histories
        $this->actingAs($this->adinda)->get(route('kunjungan.index'))->assertSee('SMA Negeri 1 Cirebon');
        $this->actingAs($this->arel)->get(route('kunjungan.index'))->assertSee('SMA Negeri 1 Cirebon');
        $this->actingAs($this->bima)->get(route('kunjungan.index'))->assertSee('SMA Negeri 1 Cirebon');
    }

    /**
     * Scenario 4: Arel does not need to submit another report.
     */
    public function test_4_tagged_cs_does_not_need_to_submit_another_report(): void
    {
        $payload = $this->getValidVisitPayload([
            'participant_ids' => [$this->arel->id],
        ]);

        $this->actingAs($this->adinda)->post(route('kunjungan.store'), $payload);

        // Arel never called store, yet the visit exists in Arel's history
        $this->assertEquals(0, Kunjungan::where('sales_id', $this->arel->id)->count());
        $this->assertEquals(1, $this->arel->sharedKunjungans()->count());

        $arelHistory = $this->actingAs($this->arel)->get(route('kunjungan.index'));
        $arelHistory->assertOk();
        $arelHistory->assertSee('SMA Negeri 1 Cirebon');
    }

    /**
     * Scenario 5: Updating the original report updates the shared report for all participants.
     */
    public function test_5_updating_original_report_updates_shared_report_for_all_participants(): void
    {
        $payload = $this->getValidVisitPayload([
            'participant_ids' => [$this->arel->id],
        ]);

        $this->actingAs($this->adinda)->post(route('kunjungan.store'), $payload);
        $visit = Kunjungan::first();

        // Update the report notes
        $updatePayload = [
            'tanggal'          => '2026-11-23',
            'waktu'            => '10:30',
            'nama_institusi'   => 'SMA Negeri 1 Cirebon (Updated)',
            'tujuan_kunjungan' => 'SMA Negeri 1 Cirebon (Updated)',
            'catatan'          => 'Catatan hasil telah diperbarui oleh Adinda.',
            'hasil'            => 'Audiensi sukses menghasilkan MoU.',
            'pic_name'         => 'Pak Guru BK Updated',
            'pic_whatsapp'     => '81234567890',
        ];

        $updateResponse = $this->actingAs($this->adinda)->put(route('kunjungan.update', $visit->id), $updatePayload);
        $updateResponse->assertRedirect();

        $visit->refresh();
        $this->assertEquals('SMA Negeri 1 Cirebon (Updated)', $visit->nama_institusi);
        $this->assertEquals('Audiensi sukses menghasilkan MoU.', $visit->hasil);

        // Arel sees the updated data
        $arelHistory = $this->actingAs($this->arel)->get(route('kunjungan.index'));
        $arelVisits = $arelHistory->viewData('visits');
        $this->assertNotEmpty($arelVisits);
        $this->assertEquals('SMA Negeri 1 Cirebon (Updated)', $arelVisits[0]['name']);
        $this->assertEquals('Catatan hasil telah diperbarui oleh Adinda.', $arelVisits[0]['notes']);

        $arelShow = $this->actingAs($this->arel)->get(route('kunjungan.show', $visit->id));
        $arelShow->assertOk();
        $arelShow->assertSee('SMA Negeri 1 Cirebon (Updated)');
        $arelShow->assertSee('Catatan hasil telah diperbarui oleh Adinda.');
    }

    /**
     * Scenario 6: A non-CS user cannot be selected through the frontend (controller passes only CS users).
     */
    public function test_6_frontend_only_provides_active_cs_users_excluding_current_user(): void
    {
        $response = $this->actingAs($this->adinda)->get(route('kunjungan.create'));
        $response->assertOk();

        // View data has csUsers containing Arel, Bima, and Unrelated CS, but NOT Adinda (self), and NOT Sales or SPV
        $csUsers = $response->viewData('csUsers');
        $this->assertNotNull($csUsers);

        $csUserIds = $csUsers->pluck('id')->toArray();
        $this->assertContains($this->arel->id, $csUserIds);
        $this->assertContains($this->bima->id, $csUserIds);
        $this->assertContains($this->unrelatedCs->id, $csUserIds);
        $this->assertNotContains($this->adinda->id, $csUserIds); // Excludes self
        $this->assertNotContains($this->sales->id, $csUserIds);  // Excludes Sales
        $this->assertNotContains($this->spv->id, $csUserIds);    // Excludes SPV
    }

    /**
     * Scenario 7: A manually submitted non-CS user ID is rejected by the backend.
     */
    public function test_7_manually_submitted_non_cs_user_id_is_rejected_by_backend(): void
    {
        // Try submitting Sales user ID as participant
        $payload = $this->getValidVisitPayload([
            'participant_ids' => [$this->sales->id],
        ]);

        $response = $this->actingAs($this->adinda)->post(route('kunjungan.store'), $payload);

        $response->assertSessionHasErrors('participant_ids');
        $this->assertEquals(0, Kunjungan::count());

        // Try submitting SPV user ID as participant
        $payloadSpv = $this->getValidVisitPayload([
            'participant_ids' => [$this->spv->id],
        ]);

        $responseSpv = $this->actingAs($this->adinda)->post(route('kunjungan.store'), $payloadSpv);
        $responseSpv->assertSessionHasErrors('participant_ids');
        $this->assertEquals(0, Kunjungan::count());
    }

    /**
     * Scenario 7b: A non-CS user (e.g. Sales) submitting participants is rejected by the backend.
     */
    public function test_7b_non_cs_user_submitting_participants_is_rejected_by_backend(): void
    {
        $payload = $this->getValidVisitPayload([
            'participant_ids' => [$this->arel->id],
        ]);

        $response = $this->actingAs($this->sales)->post(route('sales.kunjungan.store'), $payload);

        $response->assertSessionHasErrors('participant_ids');
        $this->assertEquals(0, Kunjungan::count());
    }

    /**
     * Scenario 7c: CS user tagging themselves is rejected by the backend.
     */
    public function test_7c_cs_tagging_self_is_rejected_by_backend(): void
    {
        $payload = $this->getValidVisitPayload([
            'participant_ids' => [$this->adinda->id],
        ]);

        $response = $this->actingAs($this->adinda)->post(route('kunjungan.store'), $payload);

        $response->assertSessionHasErrors('participant_ids');
        $this->assertEquals(0, Kunjungan::count());
    }

    /**
     * Scenario 8: An unrelated CS user cannot access a report they are not associated with.
     */
    public function test_8_unrelated_cs_user_cannot_access_report_they_are_not_associated_with(): void
    {
        $payload = $this->getValidVisitPayload([
            'participant_ids' => [$this->arel->id],
        ]);

        $this->actingAs($this->adinda)->post(route('kunjungan.store'), $payload);
        $visit = Kunjungan::first();

        // Adinda can access show
        $this->actingAs($this->adinda)->get(route('kunjungan.show', $visit->id))->assertOk();

        // Arel (participant) can access show
        $this->actingAs($this->arel)->get(route('kunjungan.show', $visit->id))->assertOk();

        // Unrelated CS user CANNOT access show (403 Forbidden)
        $unrelatedShow = $this->actingAs($this->unrelatedCs)->get(route('kunjungan.show', $visit->id));
        $unrelatedShow->assertForbidden();

        // Unrelated CS history does NOT include the visit in its dataset
        $unrelatedHistory = $this->actingAs($this->unrelatedCs)->get(route('kunjungan.index'));
        $unrelatedVisits = $unrelatedHistory->viewData('visits');
        $this->assertEmpty($unrelatedVisits);
        $unrelatedHistory->assertDontSee($visit->nomor);
    }

    /**
     * Scenario 9: GPS validation and SPV verification continue to work.
     */
    public function test_9_gps_validation_and_spv_verification_continue_to_work(): void
    {
        // Visit with coordinates outside 100m radius
        $payload = $this->getValidVisitPayload([
            'participant_ids' => [$this->arel->id],
            'lat'             => -6.75000000, // ~2.6 km away
            'lng'             => 108.55740000,
        ]);

        $this->actingAs($this->adinda)->post(route('kunjungan.store'), $payload);
        $visit = Kunjungan::first();

        $this->assertTrue($visit->is_outside_radius);
        $this->assertEquals('Perlu Verifikasi', $visit->status_verifikasi);

        // SPV can verify visit of sales in their team
        $salesVisit = Kunjungan::create([
            'nomor'             => 'KNJ-TEST-SPV-01',
            'tanggal'           => '2026-11-23',
            'waktu'             => '09:00:00',
            'sales_id'          => $this->sales->id,
            'prodi_id'          => $this->prodi->id,
            'jenis'             => 'Sekolah',
            'tujuan_id'         => $this->sekolah->id,
            'tujuan_kunjungan'  => $this->sekolah->nama,
            'nama_institusi'    => $this->sekolah->nama,
            'hasil'             => 'Hasil kunjungan sales',
            'status'            => 'Perlu Verifikasi',
            'status_verifikasi' => 'Perlu Verifikasi',
            'status_lokasi'     => 'Perlu Verifikasi',
            'is_outside_radius' => true,
            'is_verified'       => false,
            'lat'               => -6.75000000,
            'lng'               => 108.55740000,
        ]);

        $spvVerify = $this->actingAs($this->spv)->post(route('spv.kunjungan.verifikasi', $salesVisit->id));
        $spvVerify->assertRedirect();

        $salesVisit->refresh();
        $this->assertEquals('Terverifikasi', $salesVisit->status_verifikasi);
        $this->assertEquals('Selesai', $salesVisit->status);
    }

    /**
     * Scenario 10: Existing Sales visit workflows remain unchanged.
     */
    public function test_10_existing_sales_visit_workflows_remain_unchanged(): void
    {
        $payload = $this->getValidVisitPayload();

        $response = $this->actingAs($this->sales)->post(route('sales.kunjungan.store'), $payload);
        $response->assertRedirect();

        $this->assertDatabaseHas('kunjungans', [
            'sales_id'       => $this->sales->id,
            'nama_institusi' => 'SMA Negeri 1 Cirebon',
        ]);

        // Sales history shows the visit
        $salesHistory = $this->actingAs($this->sales)->get(route('sales.kunjungan.index'));
        $salesHistory->assertOk();
        $salesHistory->assertSee('SMA Negeri 1 Cirebon');
    }

    /**
     * Scenario 11: Mobile visit creation and visit history continue to work.
     */
    public function test_11_mobile_visit_creation_and_history_work_for_cs(): void
    {
        $payload = [
            'jenis'           => 'Sekolah',
            'sekolah_id'      => $this->sekolah->id,
            'nama_institusi'  => $this->sekolah->nama,
            'tanggal'         => '2026-11-23',
            'pic_name'        => 'Pak BK Mobile',
            'pic_whatsapp'    => '81234567890',
            'foto'            => UploadedFile::fake()->image('mobile_visit.jpg'),
            'lat'             => -6.72625000,
            'lng'             => 108.55740000,
            'participant_ids' => [$this->arel->id],
        ];

        $response = $this->actingAs($this->adinda)->post(route('mobile.kunjungan.store'), $payload);
        $response->assertRedirect(route('mobile.sukses', [
            'type'    => 'kunjungan',
            'title'   => 'Laporan Kunjungan Berhasil Terkirim!',
            'message' => 'Laporan kunjungan ke ' . $this->sekolah->nama . ' telah tersimpan dengan nomor ' . Kunjungan::latest()->first()->nomor,
        ]));

        $visit = Kunjungan::latest()->first();
        $this->assertEquals($this->adinda->id, $visit->sales_id);
        $this->assertTrue($visit->hasParticipant($this->arel->id));

        // Arel sees mobile-created visit in web history
        $this->actingAs($this->arel)->get(route('kunjungan.index'))->assertSee($this->sekolah->nama);
    }
}
