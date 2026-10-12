<?php

namespace Tests\Feature;

use App\Models\FollowUp;
use App\Models\Prospek;
use App\Models\ProspekTimeline;
use App\Models\TahunAkademik;
use App\Models\User;
use App\Models\Wilayah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrossRoleFollowUpTest extends TestCase
{
    use RefreshDatabase;

    protected User $salesA;
    protected User $salesB;
    protected User $salesC;
    protected User $spv1;
    protected User $admin;
    protected Wilayah $wilayah1;
    protected Wilayah $wilayah2;
    protected TahunAkademik $activeTa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->activeTa = TahunAkademik::create([
            'nama' => '2026/2027',
            'is_active' => true,
        ]);

        $this->wilayah1 = Wilayah::create(['kode' => 'W1', 'nama' => 'Wilayah 1', 'level' => 'Provinsi']);
        $this->wilayah2 = Wilayah::create(['kode' => 'W2', 'nama' => 'Wilayah 2', 'level' => 'Provinsi']);

        $this->spv1 = User::create([
            'name' => 'SPV 1',
            'email' => 'spv1@cic.ac.id',
            'password' => bcrypt('password'),
            'role' => 'SPV',
            'wilayah_id' => $this->wilayah1->id,
            'status' => 'aktif',
        ]);

        $this->salesA = User::create([
            'name' => 'Sales A',
            'email' => 'salesA@cic.ac.id',
            'password' => bcrypt('password'),
            'role' => 'Sales',
            'supervisor_id' => $this->spv1->id,
            'wilayah_id' => $this->wilayah1->id,
            'status' => 'aktif',
        ]);

        $this->salesB = User::create([
            'name' => 'Sales B',
            'email' => 'salesB@cic.ac.id',
            'password' => bcrypt('password'),
            'role' => 'Sales',
            'supervisor_id' => $this->spv1->id,
            'wilayah_id' => $this->wilayah1->id,
            'status' => 'aktif',
        ]);

        $this->salesC = User::create([
            'name' => 'Sales C',
            'email' => 'salesC@cic.ac.id',
            'password' => bcrypt('password'),
            'role' => 'Sales',
            'supervisor_id' => $this->spv1->id,
            'wilayah_id' => $this->wilayah1->id,
            'status' => 'aktif',
        ]);

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@cic.ac.id',
            'password' => bcrypt('password'),
            'role' => 'Admin',
            'status' => 'aktif',
        ]);
    }

    /**
     * Requirement 1 & 2:
     * Sales A creates a new prospect, data is saved, and appears in Sales A's "Prospek Saya".
     */
    public function test_sales_a_creates_prospect_and_appears_in_own_prospects(): void
    {
        $response = $this->actingAs($this->salesA)->post(route('sales.prospek.store'), [
            'type' => 'Individu',
            'pic' => 'Calon Mahasiswa A',
            'whatsapp' => '081234567890',
            'status' => 'BARU',
            'source' => 'Instagram',
            'notes' => 'Prospek baru dari event sekolah',
        ]);

        $response->assertRedirect(route('sales.prospek.index'));

        $this->assertDatabaseHas('prospeks', [
            'pic' => 'Calon Mahasiswa A',
            'sales_id' => $this->salesA->id,
            'owner_id' => $this->salesA->id,
            'status' => 'BARU',
        ]);

        // Access index page as Sales A
        $indexResponse = $this->actingAs($this->salesA)->get(route('sales.prospek.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee('Calon Mahasiswa A');

        $prospects = $indexResponse->viewData('prospects');
        $found = collect($prospects)->firstWhere('pic', 'Calon Mahasiswa A');
        $this->assertNotNull($found);
        $this->assertTrue($found['is_own']);
        $this->assertFalse($found['is_other']);
    }

    /**
     * Requirement 3:
     * The prospect created by Sales A is discoverable by Sales B in "Follow-Up Sales Lain" / cross-sales view.
     */
    public function test_prospect_created_by_sales_a_is_discovered_by_sales_b(): void
    {
        $prospek = Prospek::create([
            'name' => 'Calon Mahasiswa A',
            'type' => 'Individu',
            'pic' => 'Calon Mahasiswa A',
            'whatsapp' => '081234567890',
            'status' => 'BARU',
            'stage_number' => 1,
            'sales_id' => $this->salesA->id,
            'owner_id' => $this->salesA->id,
            'wilayah_id' => $this->wilayah1->id,
        ]);

        // Sales B visits prospect list
        $response = $this->actingAs($this->salesB)->get(route('sales.prospek.index'));
        $response->assertOk();
        $response->assertSee('Calon Mahasiswa A');

        $prospects = $response->viewData('prospects');
        $found = collect($prospects)->firstWhere('id', $prospek->id);
        $this->assertNotNull($found);
        $this->assertFalse($found['is_own']);
        $this->assertTrue($found['is_other']);
        $this->assertEquals($this->salesA->id, $found['sales_id']);
    }

    /**
     * Requirement 4, 5, 6:
     * New prospect with 0 follow-up history and null next_follow_up appears in
     * "Belum Dihubungi" (uncontacted) and "Follow-Up Sales Lain" (others) queues for Sales B.
     */
    public function test_new_prospect_appears_in_uncontacted_and_others_queue_for_sales_b(): void
    {
        $prospek = Prospek::create([
            'name' => 'Calon Mahasiswa Baru',
            'type' => 'Individu',
            'pic' => 'Calon Mahasiswa Baru',
            'whatsapp' => '081234567891',
            'status' => 'BARU',
            'stage_number' => 1,
            'sales_id' => $this->salesA->id,
            'owner_id' => $this->salesA->id,
            'wilayah_id' => $this->wilayah1->id,
        ]);

        $response = $this->actingAs($this->salesB)->get(route('sales.follow-up.index'));
        $response->assertOk();

        $queues = $response->viewData('prospects');

        // Must be in uncontacted queue
        $inUncontacted = collect($queues['uncontacted'])->firstWhere('id', $prospek->id);
        $this->assertNotNull($inUncontacted, 'Prospect with 0 follow-ups must appear in uncontacted queue');

        // Must be in others queue because owned by Sales A
        $inOthers = collect($queues['others'])->firstWhere('id', $prospek->id);
        $this->assertNotNull($inOthers, 'Prospect owned by Sales A must appear in others queue for Sales B');

        // Must NOT be in done or today
        $inDone = collect($queues['done'])->firstWhere('id', $prospek->id);
        $this->assertNull($inDone);
    }

    /**
     * Requirement 7:
     * Authorization scope: unauthenticated users cannot access sales prospect or follow up queues.
     */
    public function test_unauthenticated_user_cannot_access_sales_queues(): void
    {
        $this->get(route('sales.prospek.index'))->assertRedirect(route('login'));
        $this->get(route('sales.follow-up.index'))->assertRedirect(route('login'));
    }

    /**
     * Requirement 8:
     * Search, filter, and queue categorization accurately finds prospects without dropping eligible data.
     */
    public function test_search_and_queue_categorization_preserves_eligible_prospects(): void
    {
        // Prospect with follow-up history but next_follow_up is null -> unscheduled
        $prospekUnscheduled = Prospek::create([
            'name' => 'Prospek Tanpa Jadwal',
            'type' => 'Individu',
            'pic' => 'PIC Tanpa Jadwal',
            'whatsapp' => '081299990001',
            'status' => 'KONTAK',
            'stage_number' => 2,
            'sales_id' => $this->salesA->id,
            'owner_id' => $this->salesA->id,
        ]);
        FollowUp::create([
            'prospek_id' => $prospekUnscheduled->id,
            'user_id' => $this->salesA->id,
            'metode' => 'WhatsApp',
            'hasil' => 'Tertarik',
            'catatan' => 'Sudah ditelepon',
            'tanggal' => now()->subDays(2),
            'next_follow_up' => null,
        ]);

        // Prospect scheduled for today -> today
        $prospekToday = Prospek::create([
            'name' => 'Prospek Hari Ini',
            'type' => 'Individu',
            'pic' => 'PIC Hari Ini',
            'whatsapp' => '081299990002',
            'status' => 'KONTAK',
            'stage_number' => 2,
            'sales_id' => $this->salesA->id,
            'owner_id' => $this->salesA->id,
        ]);
        FollowUp::create([
            'prospek_id' => $prospekToday->id,
            'user_id' => $this->salesA->id,
            'metode' => 'WhatsApp',
            'hasil' => 'Tertarik',
            'catatan' => 'Follow up terjadwal hari ini',
            'tanggal' => now(),
            'next_follow_up' => now()->toDateString(),
        ]);

        // Prospect overdue -> overdue
        $prospekOverdue = Prospek::create([
            'name' => 'Prospek Terlambat',
            'type' => 'Individu',
            'pic' => 'PIC Terlambat',
            'whatsapp' => '081299990003',
            'status' => 'KONTAK',
            'stage_number' => 2,
            'sales_id' => $this->salesA->id,
            'owner_id' => $this->salesA->id,
        ]);
        FollowUp::create([
            'prospek_id' => $prospekOverdue->id,
            'user_id' => $this->salesA->id,
            'metode' => 'WhatsApp',
            'hasil' => 'Tertarik',
            'catatan' => 'Follow up terlambat',
            'tanggal' => now()->subDays(3),
            'next_follow_up' => now()->subDays(2)->toDateString(),
        ]);

        // Prospect LUNAS -> done
        $prospekDone = Prospek::create([
            'name' => 'Prospek Lunas',
            'type' => 'Individu',
            'pic' => 'PIC Lunas',
            'whatsapp' => '081299990004',
            'status' => 'LUNAS',
            'stage_number' => 7,
            'sales_id' => $this->salesA->id,
            'owner_id' => $this->salesA->id,
        ]);

        $response = $this->actingAs($this->salesB)->get(route('sales.follow-up.index'));
        $response->assertOk();
        $queues = $response->viewData('prospects');

        $this->assertNotNull(collect($queues['unscheduled'])->firstWhere('id', $prospekUnscheduled->id));
        $this->assertNotNull(collect($queues['today'])->firstWhere('id', $prospekToday->id));
        $this->assertNotNull(collect($queues['overdue'])->firstWhere('id', $prospekOverdue->id));
        $this->assertNotNull(collect($queues['done'])->firstWhere('id', $prospekDone->id));

        // Active prospects owned by Sales A must also be in others queue
        $this->assertNotNull(collect($queues['others'])->firstWhere('id', $prospekUnscheduled->id));
        $this->assertNotNull(collect($queues['others'])->firstWhere('id', $prospekToday->id));
        $this->assertNotNull(collect($queues['others'])->firstWhere('id', $prospekOverdue->id));
    }

    /**
     * Requirement 9 & 10:
     * When Sales B helps follow up on Sales A's prospect:
     * - Sales A remains the owner (sales_id and owner_id untouched)
     * - Follow-up actor is recorded as Sales B (follow_ups.user_id = Sales B)
     */
    public function test_sales_b_helps_follow_up_without_changing_ownership(): void
    {
        $prospek = Prospek::create([
            'name' => 'Calon Mahasiswa Mitra',
            'type' => 'Individu',
            'pic' => 'Mitra PIC',
            'whatsapp' => '081234567899',
            'status' => 'BARU',
            'stage_number' => 1,
            'sales_id' => $this->salesA->id,
            'owner_id' => $this->salesA->id,
            'wilayah_id' => $this->wilayah1->id,
        ]);

        $response = $this->actingAs($this->salesB)->post(route('sales.follow-up.store'), [
            'prospek_id' => $prospek->id,
            'metode' => 'Telepon',
            'hasil' => 'Tertarik',
            'catatan' => 'Sales B membantu menghubungi calon mahasiswa',
            'status' => 'BARU', // Non-owner helper cannot change status
            'next_follow_up' => now()->addDays(2)->toDateString(),
        ]);

        $response->assertRedirect();

        // 1. Ownership intact
        $prospek->refresh();
        $this->assertEquals($this->salesA->id, $prospek->sales_id, 'sales_id must remain Sales A');
        $this->assertEquals($this->salesA->id, $prospek->owner_id, 'owner_id must remain Sales A');

        // 2. Follow up actor is Sales B
        $this->assertDatabaseHas('follow_ups', [
            'prospek_id' => $prospek->id,
            'user_id' => $this->salesB->id,
            'metode' => 'Telepon',
            'hasil' => 'Tertarik',
        ]);
    }

    /**
     * Requirement 11:
     * Authorized Sales B helping follow up can update the pipeline status (e.g. BARU -> KONTAK).
     * - Status is successfully updated and saved in the database
     * - Ownership remains Sales A
     * - Follow-up actor is recorded as Sales B
     */
    public function test_authorized_sales_helper_can_update_status_during_follow_up(): void
    {
        $prospek = Prospek::create([
            'name' => 'Calon Mahasiswa Mitra',
            'type' => 'Individu',
            'pic' => 'Mitra PIC',
            'whatsapp' => '081234567899',
            'status' => 'BARU',
            'stage_number' => 1,
            'sales_id' => $this->salesA->id,
            'owner_id' => $this->salesA->id,
            'wilayah_id' => $this->wilayah1->id,
        ]);

        // Sales B updates status via follow-up endpoint
        $response = $this->actingAs($this->salesB)->post(route('sales.follow-up.store'), [
            'prospek_id' => $prospek->id,
            'metode' => 'Telepon',
            'hasil' => 'Tertarik & Diskusi Lanjutan',
            'catatan' => 'Sales B follow up dan memperbarui status prospek ke KONTAK',
            'status' => 'KONTAK', // Different from BARU
            'next_follow_up' => now()->addDays(2)->toDateString(),
        ]);

        $response->assertRedirect();

        $prospek->refresh();
        $this->assertEquals('KONTAK', $prospek->status, 'Status must be updated to KONTAK');
        $this->assertEquals($this->salesA->id, $prospek->sales_id, 'Ownership sales_id must remain Sales A');
        $this->assertEquals($this->salesA->id, $prospek->owner_id, 'Ownership owner_id must remain Sales A');

        // Follow up actor is Sales B
        $this->assertDatabaseHas('follow_ups', [
            'prospek_id' => $prospek->id,
            'user_id' => $this->salesB->id,
            'metode' => 'Telepon',
            'hasil' => 'Tertarik & Diskusi Lanjutan',
        ]);

        // Timeline logs the status update by Sales B
        $this->assertDatabaseHas('prospek_timelines', [
            'prospek_id' => $prospek->id,
            'user_id' => $this->salesB->id,
            'status_before' => 'BARU',
            'status_after' => 'KONTAK',
        ]);
    }

    /**
     * Requirement 12:
     * Non-sales roles (e.g. SPV, Admin) retain their appropriate access rules.
     */
    public function test_non_sales_roles_retain_their_access_rules(): void
    {
        $prospek = Prospek::create([
            'name' => 'Calon Mahasiswa Wilayah 1',
            'type' => 'Individu',
            'pic' => 'PIC W1',
            'whatsapp' => '081234567800',
            'status' => 'BARU',
            'stage_number' => 1,
            'sales_id' => $this->salesA->id,
            'owner_id' => $this->salesA->id,
            'wilayah_id' => $this->wilayah1->id,
        ]);

        // SPV in same wilayah can view
        $responseSpv = $this->actingAs($this->spv1)->get(route('spv.prospek.show', $prospek->id));
        $responseSpv->assertOk();

        // Admin can view shared prospect route
        $responseAdmin = $this->actingAs($this->admin)->get(route('prospek.show', $prospek->id));
        $responseAdmin->assertOk();
    }

    /**
     * Negative Test:
     * Sales cannot view or follow up an unassigned prospect in another territory.
     */
    public function test_sales_cannot_view_or_follow_up_unassigned_prospect_outside_wilayah(): void
    {
        $unassigned = Prospek::create([
            'name' => 'Unassigned Lead Wilayah 2',
            'type' => 'Individu',
            'pic' => 'Unassigned PIC',
            'whatsapp' => '089999999999',
            'status' => 'BARU',
            'stage_number' => 1,
            'sales_id' => null,
            'owner_id' => null,
            'wilayah_id' => $this->wilayah2->id,
        ]);

        // Policy view & followUp must deny Sales A (Wilayah 1)
        $this->assertFalse(\Illuminate\Support\Facades\Gate::forUser($this->salesA)->allows('view', $unassigned));
        $this->assertFalse(\Illuminate\Support\Facades\Gate::forUser($this->salesA)->allows('followUp', $unassigned));

        // HTTP access must be 403 Forbidden
        $this->actingAs($this->salesA)->get(route('sales.prospek.show', $unassigned->id))->assertForbidden();

        // Storing follow-up must be 403 Forbidden
        $this->actingAs($this->salesA)->post(route('sales.follow-up.store'), [
            'prospek_id' => $unassigned->id,
            'metode' => 'WhatsApp',
            'hasil' => 'Tertarik',
            'catatan' => 'Mencoba follow-up unassigned lead',
            'status' => 'BARU',
        ])->assertForbidden();

        // Must NOT appear in Sales A's prospect list
        $indexResponse = $this->actingAs($this->salesA)->get(route('sales.prospek.index'));
        $indexResponse->assertOk();
        $prospects = $indexResponse->viewData('prospects');
        $this->assertNull(collect($prospects)->firstWhere('id', $unassigned->id));
    }

    /**
     * Negative Test:
     * Sales cannot view or follow up a CS-exclusive prospect.
     */
    public function test_sales_cannot_view_or_follow_up_cs_only_prospect(): void
    {
        $csUser = User::create([
            'name' => 'CS Officer',
            'email' => 'cs.officer@cic.ac.id',
            'password' => bcrypt('password'),
            'role' => 'CS',
            'wilayah_id' => $this->wilayah2->id,
            'status' => 'aktif',
        ]);

        $csProspect = Prospek::create([
            'name' => 'CS Only Lead',
            'type' => 'Individu',
            'pic' => 'CS Lead PIC',
            'whatsapp' => '087777777777',
            'status' => 'FORMULIR',
            'stage_number' => 4,
            'sales_id' => null,
            'owner_id' => $csUser->id,
            'cs_id' => $csUser->id,
            'wilayah_id' => $this->wilayah2->id,
        ]);

        // Sales A must be denied view & follow-up
        $this->assertFalse(\Illuminate\Support\Facades\Gate::forUser($this->salesA)->allows('view', $csProspect));
        $this->assertFalse(\Illuminate\Support\Facades\Gate::forUser($this->salesA)->allows('followUp', $csProspect));

        $this->actingAs($this->salesA)->get(route('sales.prospek.show', $csProspect->id))->assertForbidden();

        // Must NOT appear in Sales A's follow-up queues
        $fuResponse = $this->actingAs($this->salesA)->get(route('sales.follow-up.index'));
        $fuResponse->assertOk();
        $queues = $fuResponse->viewData('prospects');
        $this->assertNull(collect($queues['others'])->firstWhere('id', $csProspect->id));
        $this->assertNull(collect($queues['uncontacted'])->firstWhere('id', $csProspect->id));
    }

    /**
     * Canonical Ownership Test:
     * Verifies that when sales_id and owner_id are set to different users,
     * the project canonical ownership is respected without misclassifying into others.
     */
    public function test_canonical_ownership_distinguishes_own_and_other_properly(): void
    {
        // Prospect created by Sales A, but assigned to Sales B as handler
        $prospekAssigned = Prospek::create([
            'name' => 'Dual Ownership Lead',
            'type' => 'Individu',
            'pic' => 'Dual PIC',
            'whatsapp' => '085555555555',
            'status' => 'KONTAK',
            'stage_number' => 2,
            'sales_id' => $this->salesB->id, // Handler: Sales B
            'owner_id' => $this->salesA->id, // Creator: Sales A
            'wilayah_id' => $this->wilayah1->id,
        ]);

        // For Sales A (creator/owner): it is OWN prospect, NOT other
        $resA = $this->actingAs($this->salesA)->get(route('sales.prospek.index'));
        $foundA = collect($resA->viewData('prospects'))->firstWhere('id', $prospekAssigned->id);
        $this->assertNotNull($foundA);
        $this->assertTrue($foundA['is_own']);
        $this->assertFalse($foundA['is_other']);

        // For Sales B (active handler): it is OWN prospect, NOT other
        $resB = $this->actingAs($this->salesB)->get(route('sales.prospek.index'));
        $foundB = collect($resB->viewData('prospects'))->firstWhere('id', $prospekAssigned->id);
        $this->assertNotNull($foundB);
        $this->assertTrue($foundB['is_own']);
        $this->assertFalse($foundB['is_other']);

        // For Sales C (third party): it is OTHER prospect, NOT own
        $resC = $this->actingAs($this->salesC)->get(route('sales.prospek.index'));
        $foundC = collect($resC->viewData('prospects'))->firstWhere('id', $prospekAssigned->id);
        $this->assertNotNull($foundC);
        $this->assertTrue($foundC['is_other']);
    }

    /**
     * Requirement Multi-Account (Sales A, B, C):
     * 1. Sales A creates prospect.
     * 2. Sales B can find and detail prospect A, then perform follow-up.
     * 3. Sales C gets identical access and behavior to Sales B, and can also perform follow-up.
     * 4. Ownership remains Sales A throughout all follow-ups.
     * 5. History and timeline accurately log Sales B and Sales C as respective actors.
     * 6. Helpers (B and C) are forbidden from editing master data or updating pipeline status.
     * 7. Sales A (owner) retains ability to update master data and status.
     */
    public function test_multi_sales_workflow_sales_a_b_c_collaboration_and_security(): void
    {
        // 1. Sales A creates prospect
        $responseCreate = $this->actingAs($this->salesA)->post(route('sales.prospek.store'), [
            'type' => 'Individu',
            'pic' => 'Ahmad Multiclient',
            'whatsapp' => '081211112222',
            'status' => 'BARU',
            'source' => 'Instagram',
            'notes' => 'Prospek kolaborasi multi sales',
        ]);
        $responseCreate->assertRedirect(route('sales.prospek.index'));

        $prospek = Prospek::where('whatsapp', '081211112222')->firstOrFail();
        $this->assertEquals($this->salesA->id, $prospek->sales_id);
        $this->assertEquals($this->salesA->id, $prospek->owner_id);

        // 2. Sales B visits detail page and list page
        $resDetailB = $this->actingAs($this->salesB)->get(route('sales.prospek.show', $prospek->id));
        $resDetailB->assertOk(); // Must not be 403 Forbidden!

        $resListB = $this->actingAs($this->salesB)->get(route('sales.follow-up.index'));
        $resListB->assertOk();
        $queuesB = $resListB->viewData('prospects');
        $this->assertNotNull(collect($queuesB['uncontacted'])->firstWhere('id', $prospek->id));
        $this->assertNotNull(collect($queuesB['others'])->firstWhere('id', $prospek->id));

        // Sales B performs first follow-up via sales.follow-up.store and updates status to KONTAK
        $resFuB = $this->actingAs($this->salesB)->post(route('sales.follow-up.store'), [
            'prospek_id' => $prospek->id,
            'metode' => 'WhatsApp',
            'hasil' => 'Tertarik & Diskusi Lanjutan',
            'catatan' => 'Follow up pertama oleh Sales B (status diubah ke KONTAK)',
            'status' => 'KONTAK',
            'next_follow_up' => now()->addDays(1)->toDateString(),
        ]);
        $resFuB->assertRedirect();

        // Verify status updated to KONTAK, ownership untouched and actor logged as Sales B
        $prospek->refresh();
        $this->assertEquals('KONTAK', $prospek->status, 'Status must be updated to KONTAK by Sales B');
        $this->assertEquals($this->salesA->id, $prospek->sales_id, 'sales_id must remain Sales A');
        $this->assertEquals($this->salesA->id, $prospek->owner_id, 'owner_id must remain Sales A');

        $fuB = FollowUp::where('prospek_id', $prospek->id)->where('user_id', $this->salesB->id)->first();
        $this->assertNotNull($fuB);
        $this->assertEquals('Follow up pertama oleh Sales B (status diubah ke KONTAK)', $fuB->catatan);

        $tlB = ProspekTimeline::where('prospek_id', $prospek->id)->where('user_id', $this->salesB->id)->first();
        $this->assertNotNull($tlB);

        // 3. Sales C (collaborative team helper) visits detail page and list page
        $resDetailC = $this->actingAs($this->salesC)->get(route('sales.prospek.show', $prospek->id));
        $resDetailC->assertOk(); // Must not be 403 Forbidden!

        $resListC = $this->actingAs($this->salesC)->get(route('sales.follow-up.index'));
        $resListC->assertOk();
        $queuesC = $resListC->viewData('prospects');
        $this->assertNotNull(collect($queuesC['others'])->firstWhere('id', $prospek->id));

        // Sales C performs second follow-up via follow-up.store (shared route used by modal) and updates status to BERKAS
        $resFuC = $this->actingAs($this->salesC)->post(route('follow-up.store'), [
            'prospek_id' => $prospek->id,
            'metode' => 'Telepon',
            'hasil' => 'Pemberkasan',
            'catatan' => 'Follow up kedua oleh Sales C (status diubah ke BERKAS)',
            'status' => 'BERKAS',
            'next_follow_up' => now()->addDays(3)->toDateString(),
        ]);
        $resFuC->assertRedirect();

        // 4. Verify status updated to BERKAS, ownership still Sales A, and actor logged as Sales C
        $prospek->refresh();
        $this->assertEquals('BERKAS', $prospek->status, 'Status must be updated to BERKAS by Sales C');
        $this->assertEquals($this->salesA->id, $prospek->sales_id, 'sales_id must remain Sales A after Sales C follow-up');
        $this->assertEquals($this->salesA->id, $prospek->owner_id, 'owner_id must remain Sales A after Sales C follow-up');

        $fuC = FollowUp::where('prospek_id', $prospek->id)->where('user_id', $this->salesC->id)->first();
        $this->assertNotNull($fuC);
        $this->assertEquals('Follow up kedua oleh Sales C (status diubah ke BERKAS)', $fuC->catatan);

        $tlC = ProspekTimeline::where('prospek_id', $prospek->id)->where('user_id', $this->salesC->id)->first();
        $this->assertNotNull($tlC);

        // 5. Total follow-ups count is 2
        $this->assertEquals(2, $prospek->follow_up_count);

        // 6. Security & Authorization checks: Helpers B and C cannot edit master data
        $this->actingAs($this->salesB)->put(route('sales.prospek.update', $prospek->id), [
            'type' => 'Individu',
            'pic' => 'Hacked by Sales B',
            'whatsapp' => '081211112222',
            'status' => 'BERKAS',
        ])->assertForbidden();

        $this->actingAs($this->salesC)->put(route('sales.prospek.update', $prospek->id), [
            'type' => 'Individu',
            'pic' => 'Hacked by Sales C',
            'whatsapp' => '081211112222',
            'status' => 'BERKAS',
        ])->assertForbidden();

        // Sales cannot directly change status to LUNAS via follow-up
        $resLunas = $this->actingAs($this->salesB)->post(route('sales.follow-up.store'), [
            'prospek_id' => $prospek->id,
            'metode' => 'WhatsApp',
            'hasil' => 'Deal',
            'catatan' => 'Mencoba set LUNAS secara langsung',
            'status' => 'LUNAS',
        ]);
        $resLunas->assertSessionHasErrors('status');

        // 7. Owner (Sales A) can update master data and status successfully
        $this->actingAs($this->salesA)->put(route('sales.prospek.update', $prospek->id), [
            'type' => 'Individu',
            'pic' => 'Ahmad Multiclient Updated',
            'whatsapp' => '081211112222',
            'status' => 'BERKAS',
            'source' => 'Instagram',
        ])->assertRedirect();

        $prospek->refresh();
        $this->assertEquals('Ahmad Multiclient Updated', $prospek->pic);
    }

    /**
     * Requirement:
     * Prospect with owner_id = Sales A and null sales_id is discoverable by Sales B and Sales C.
     */
    public function test_prospect_owned_by_sales_with_null_sales_id_is_discoverable_and_updatable(): void
    {
        $prospekNullSales = Prospek::create([
            'name' => 'Prospek Owner Only',
            'type' => 'Individu',
            'pic' => 'PIC Owner Only',
            'whatsapp' => '081288887777',
            'status' => 'BARU',
            'stage_number' => 1,
            'sales_id' => null, // null handler
            'owner_id' => $this->salesA->id, // owned by Sales A
            'wilayah_id' => $this->wilayah1->id,
        ]);

        // Sales B finds it in prospect list and follow-up index
        $resB = $this->actingAs($this->salesB)->get(route('sales.follow-up.index'));
        $resB->assertOk();
        $queuesB = $resB->viewData('prospects');
        $this->assertNotNull(collect($queuesB['uncontacted'])->firstWhere('id', $prospekNullSales->id));
        $this->assertNotNull(collect($queuesB['others'])->firstWhere('id', $prospekNullSales->id));

        // Sales C finds it in prospect list and follow-up index
        $resC = $this->actingAs($this->salesC)->get(route('sales.follow-up.index'));
        $resC->assertOk();
        $queuesC = $resC->viewData('prospects');
        $this->assertNotNull(collect($queuesC['uncontacted'])->firstWhere('id', $prospekNullSales->id));
        $this->assertNotNull(collect($queuesC['others'])->firstWhere('id', $prospekNullSales->id));

        // Sales B can perform follow-up
        $resFu = $this->actingAs($this->salesB)->post(route('follow-up.store'), [
            'prospek_id' => $prospekNullSales->id,
            'metode' => 'WhatsApp',
            'hasil' => 'Tersambung',
            'catatan' => 'Sales B membantu prospek unassigned sales_id',
            'status' => 'BARU',
        ]);
        $resFu->assertRedirect();

        $this->assertDatabaseHas('follow_ups', [
            'prospek_id' => $prospekNullSales->id,
            'user_id' => $this->salesB->id,
        ]);

        $prospekNullSales->refresh();
        $this->assertEquals($this->salesA->id, $prospekNullSales->owner_id);
    }

    /**
     * Requirement:
     * Unauthorized sales user (different team and different territory) is rejected (403).
     */
    public function test_unauthorized_sales_user_is_denied_from_viewing_and_follow_up(): void
    {
        $salesUnauthorized = User::create([
            'name' => 'Sales Outside',
            'email' => 'sales.outside@cic.ac.id',
            'password' => bcrypt('password'),
            'role' => 'Sales',
            'supervisor_id' => null,
            'wilayah_id' => $this->wilayah2->id,
            'status' => 'aktif',
        ]);

        $prospekA = Prospek::create([
            'name' => 'Prospek Wilayah 1',
            'type' => 'Individu',
            'pic' => 'PIC Wilayah 1',
            'whatsapp' => '081233334444',
            'status' => 'BARU',
            'stage_number' => 1,
            'sales_id' => $this->salesA->id,
            'owner_id' => $this->salesA->id,
            'wilayah_id' => $this->wilayah1->id,
        ]);

        // Unauthorized sales cannot view prospect detail (403)
        $this->actingAs($salesUnauthorized)->get(route('sales.prospek.show', $prospekA->id))
            ->assertForbidden();

        // Unauthorized sales cannot follow up (403)
        $this->actingAs($salesUnauthorized)->post(route('sales.follow-up.store'), [
            'prospek_id' => $prospekA->id,
            'metode' => 'WhatsApp',
            'hasil' => 'Tertarik',
            'catatan' => 'Mencoba follow-up tanpa izin',
            'status' => 'BARU',
        ])->assertForbidden();

        // Unauthorized sales cannot follow up via shared route either (403)
        $this->actingAs($salesUnauthorized)->post(route('follow-up.store'), [
            'prospek_id' => $prospekA->id,
            'metode' => 'WhatsApp',
            'hasil' => 'Tertarik',
            'catatan' => 'Mencoba follow-up tanpa izin shared route',
            'status' => 'BARU',
        ])->assertForbidden();

        // Unauthorized sales cannot update status directly via updateStatus endpoint (403)
        $this->actingAs($salesUnauthorized)->patch(route('sales.prospek.updateStatus', $prospekA->id), [
            'status' => 'KONTAK',
        ])->assertForbidden();

        // Unauthorized sales cannot update status via follow-up endpoint (403)
        $this->actingAs($salesUnauthorized)->post(route('sales.follow-up.store'), [
            'prospek_id' => $prospekA->id,
            'metode' => 'WhatsApp',
            'hasil' => 'Tertarik',
            'catatan' => 'Mencoba follow-up sekaligus ubah status',
            'status' => 'KONTAK',
        ])->assertForbidden();
    }
}


