<?php

namespace Tests\Feature;

use App\Models\FollowUp;
use App\Models\Prospek;
use App\Models\User;
use App\Models\Wilayah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FollowUpQueueSimplificationTest extends TestCase
{
    use RefreshDatabase;

    private User $salesA;
    private User $salesB;
    private User $spv;
    private Wilayah $wilayah;

    protected function setUp(): void
    {
        parent::setUp();

        $this->wilayah = Wilayah::create([
            'nama' => 'Wilayah Cirebon',
            'kode' => 'CRB',
            'target_mahasiswa' => 100,
        ]);

        $this->spv = User::create([
            'name' => 'Supervisor Tim',
            'email' => 'spv@cic.ac.id',
            'password' => bcrypt('password'),
            'role' => 'SPV',
            'wilayah_id' => $this->wilayah->id,
            'status' => 'aktif',
        ]);

        $this->salesA = User::create([
            'name' => 'Sales Aurel',
            'email' => 'aurel@cic.ac.id',
            'password' => bcrypt('password'),
            'role' => 'Sales',
            'supervisor_id' => $this->spv->id,
            'wilayah_id' => $this->wilayah->id,
            'status' => 'aktif',
        ]);

        $this->salesB = User::create([
            'name' => 'Sales Budi',
            'email' => 'budi@cic.ac.id',
            'password' => bcrypt('password'),
            'role' => 'Sales',
            'supervisor_id' => $this->spv->id,
            'wilayah_id' => $this->wilayah->id,
            'status' => 'aktif',
        ]);
    }

    /**
     * Requirement 1:
     * Navigasi hanya menampilkan empat tab utama:
     * Perlu Follow-Up, Bantuan Sales Lain, Terjadwal, Selesai.
     */
    public function test_navigation_displays_only_four_primary_tabs(): void
    {
        $response = $this->actingAs($this->salesA)->get(route('sales.follow-up.index'));
        $response->assertOk();

        // 4 Main tab headers must be present in view
        $response->assertSee('Perlu Follow-Up');
        $response->assertSee('Bantuan Sales Lain');
        $response->assertSee('Terjadwal');
        $response->assertSee('Selesai');

        $content = $response->getContent();
        // The old 7 navigation tabs should no longer be rendered as tab buttons
        $this->assertStringNotContainsString("activeTab = 'uncontacted'", $content);
        $this->assertStringNotContainsString("activeTab = 'today'", $content);
        $this->assertStringNotContainsString("activeTab = 'overdue'", $content);
        $this->assertStringNotContainsString("activeTab = 'unscheduled'", $content);
        $this->assertStringNotContainsString("activeTab = 'upcoming'", $content);
    }

    /**
     * Requirement 2:
     * Prospek baru milik Sales sendiri masuk Perlu Follow-Up (priority 3: Belum Dihubungi).
     */
    public function test_new_own_prospect_enters_need_follow_up(): void
    {
        $prospek = Prospek::create([
            'name' => 'Prospek Baru Mandiri',
            'type' => 'Individu',
            'pic' => 'PIC Baru',
            'whatsapp' => '081111111111',
            'status' => 'BARU',
            'stage_number' => 1,
            'sales_id' => $this->salesA->id,
            'owner_id' => $this->salesA->id,
        ]);

        $response = $this->actingAs($this->salesA)->get(route('sales.follow-up.index'));
        $response->assertOk();
        $queues = $response->viewData('prospects');

        $found = collect($queues['need_follow_up'])->firstWhere('id', $prospek->id);
        $this->assertNotNull($found, 'New prospect must enter need_follow_up');
        $this->assertEquals('uncontacted', $found['timing_category']);
        $this->assertEquals('Belum Dihubungi', $found['priority_label']);
        $this->assertEquals(3, $found['need_fu_priority']);

        // Must not appear in scheduled or others
        $this->assertNull(collect($queues['scheduled'])->firstWhere('id', $prospek->id));
        $this->assertNull(collect($queues['others'])->firstWhere('id', $prospek->id));
    }

    /**
     * Requirement 3:
     * Prospek dengan jadwal hari ini masuk Perlu Follow-Up (priority 2: Jadwal Hari Ini).
     */
    public function test_own_prospect_scheduled_today_enters_need_follow_up(): void
    {
        $prospek = Prospek::create([
            'name' => 'Prospek Jadwal Hari Ini',
            'type' => 'Individu',
            'pic' => 'PIC Hari Ini',
            'whatsapp' => '081111111112',
            'status' => 'KONTAK',
            'stage_number' => 2,
            'sales_id' => $this->salesA->id,
            'owner_id' => $this->salesA->id,
        ]);

        FollowUp::create([
            'prospek_id' => $prospek->id,
            'user_id' => $this->salesA->id,
            'metode' => 'WhatsApp',
            'hasil' => 'Konfirmasi',
            'catatan' => 'Follow-up hari ini',
            'tanggal' => now()->subDay(),
            'next_follow_up' => now()->format('Y-m-d 14:00:00'),
        ]);

        $response = $this->actingAs($this->salesA)->get(route('sales.follow-up.index'));
        $queues = $response->viewData('prospects');

        $found = collect($queues['need_follow_up'])->firstWhere('id', $prospek->id);
        $this->assertNotNull($found, 'Prospect scheduled today must enter need_follow_up');
        $this->assertEquals('today', $found['timing_category']);
        $this->assertEquals('Jadwal Hari Ini', $found['priority_label']);
        $this->assertEquals(2, $found['need_fu_priority']);

        $this->assertNull(collect($queues['scheduled'])->firstWhere('id', $prospek->id));
    }

    /**
     * Requirement 4:
     * Prospek yang terlambat masuk Perlu Follow-Up (priority 1: Terlambat).
     */
    public function test_overdue_own_prospect_enters_need_follow_up(): void
    {
        $prospek = Prospek::create([
            'name' => 'Prospek Terlambat FU',
            'type' => 'Individu',
            'pic' => 'PIC Overdue',
            'whatsapp' => '081111111113',
            'status' => 'KONTAK',
            'stage_number' => 2,
            'sales_id' => $this->salesA->id,
            'owner_id' => $this->salesA->id,
        ]);

        FollowUp::create([
            'prospek_id' => $prospek->id,
            'user_id' => $this->salesA->id,
            'metode' => 'Telepon',
            'hasil' => 'Tertarik',
            'catatan' => 'Jadwal lewat',
            'tanggal' => now()->subDays(4),
            'next_follow_up' => now()->subDays(2)->format('Y-m-d 10:00:00'),
        ]);

        $response = $this->actingAs($this->salesA)->get(route('sales.follow-up.index'));
        $queues = $response->viewData('prospects');

        $found = collect($queues['need_follow_up'])->firstWhere('id', $prospek->id);
        $this->assertNotNull($found, 'Overdue prospect must enter need_follow_up');
        $this->assertEquals('overdue', $found['timing_category']);
        $this->assertEquals('Terlambat', $found['priority_label']);
        $this->assertEquals(1, $found['need_fu_priority']);

        $this->assertNull(collect($queues['scheduled'])->firstWhere('id', $prospek->id));
    }

    /**
     * Requirement 5:
     * Prospek yang sudah pernah di-FU tetapi tidak mempunyai jadwal berikutnya tetap muncul di Perlu Follow-Up.
     */
    public function test_contacted_prospect_without_next_schedule_enters_need_follow_up(): void
    {
        $prospek = Prospek::create([
            'name' => 'Prospek Tanpa Jadwal Lanjutan',
            'type' => 'Individu',
            'pic' => 'PIC Unscheduled',
            'whatsapp' => '081111111114',
            'status' => 'KONTAK',
            'stage_number' => 2,
            'sales_id' => $this->salesA->id,
            'owner_id' => $this->salesA->id,
        ]);

        FollowUp::create([
            'prospek_id' => $prospek->id,
            'user_id' => $this->salesA->id,
            'metode' => 'Meeting',
            'hasil' => 'Sudah presentasi',
            'catatan' => 'Belum menentukan tanggal berikutnya',
            'tanggal' => now()->subDays(1),
            'next_follow_up' => null,
        ]);

        $response = $this->actingAs($this->salesA)->get(route('sales.follow-up.index'));
        $queues = $response->viewData('prospects');

        $found = collect($queues['need_follow_up'])->firstWhere('id', $prospek->id);
        $this->assertNotNull($found, 'Prospect with previous FU but null next_follow_up must be in need_follow_up');
        $this->assertEquals('unscheduled', $found['timing_category']);
        $this->assertEquals('Belum Dijadwalkan', $found['priority_label']);
        $this->assertEquals(4, $found['need_fu_priority']);
    }

    /**
     * Requirement 6:
     * Prospek milik sendiri dengan jadwal mendatang masuk Terjadwal, bukan Perlu Follow-Up.
     */
    public function test_own_prospect_with_future_schedule_enters_scheduled_not_need_follow_up(): void
    {
        $prospek = Prospek::create([
            'name' => 'Prospek Terjadwal Mendatang',
            'type' => 'Individu',
            'pic' => 'PIC Mendatang',
            'whatsapp' => '081111111115',
            'status' => 'KONTAK',
            'stage_number' => 2,
            'sales_id' => $this->salesA->id,
            'owner_id' => $this->salesA->id,
        ]);

        FollowUp::create([
            'prospek_id' => $prospek->id,
            'user_id' => $this->salesA->id,
            'metode' => 'WhatsApp',
            'hasil' => 'Janjian minggu depan',
            'catatan' => 'Audiensi lanjutan',
            'tanggal' => now(),
            'next_follow_up' => now()->addDays(5)->format('Y-m-d 09:00:00'),
        ]);

        $response = $this->actingAs($this->salesA)->get(route('sales.follow-up.index'));
        $queues = $response->viewData('prospects');

        $inScheduled = collect($queues['scheduled'])->firstWhere('id', $prospek->id);
        $this->assertNotNull($inScheduled, 'Upcoming prospect must enter scheduled');
        $this->assertEquals('upcoming', $inScheduled['timing_category']);

        // Strictly forbidden from need_follow_up
        $inNeedFollowUp = collect($queues['need_follow_up'])->firstWhere('id', $prospek->id);
        $this->assertNull($inNeedFollowUp, 'Upcoming prospect must NOT enter need_follow_up');
    }

    /**
     * Requirement 7:
     * Bantuan Sales Lain menampilkan seluruh prospek aktif rekan yang memang boleh dibantu,
     * termasuk yang jadwalnya masih mendatang.
     */
    public function test_bantuan_sales_lain_shows_all_active_other_sales_prospects_including_upcoming(): void
    {
        // Prospect owned by Sales A with upcoming schedule
        $prospekUpcoming = Prospek::create([
            'name' => 'Prospek A Mendatang',
            'type' => 'Individu',
            'pic' => 'PIC A',
            'whatsapp' => '081111111116',
            'status' => 'KONTAK',
            'stage_number' => 2,
            'sales_id' => $this->salesA->id,
            'owner_id' => $this->salesA->id,
        ]);
        FollowUp::create([
            'prospek_id' => $prospekUpcoming->id,
            'user_id' => $this->salesA->id,
            'metode' => 'WhatsApp',
            'hasil' => 'Jadwal mendatang',
            'catatan' => 'Audiensi mendatang',
            'tanggal' => now(),
            'next_follow_up' => now()->addDays(3)->format('Y-m-d 10:00:00'),
        ]);

        // Prospect owned by Sales A that is overdue
        $prospekOverdue = Prospek::create([
            'name' => 'Prospek A Terlambat',
            'type' => 'Individu',
            'pic' => 'PIC A Overdue',
            'whatsapp' => '081111111117',
            'status' => 'KONTAK',
            'stage_number' => 2,
            'sales_id' => $this->salesA->id,
            'owner_id' => $this->salesA->id,
        ]);
        FollowUp::create([
            'prospek_id' => $prospekOverdue->id,
            'user_id' => $this->salesA->id,
            'metode' => 'WhatsApp',
            'hasil' => 'Jadwal lewat',
            'catatan' => 'Perlu follow-up',
            'tanggal' => now()->subDays(3),
            'next_follow_up' => now()->subDays(1)->format('Y-m-d 10:00:00'),
        ]);

        // Sales B logs in
        $response = $this->actingAs($this->salesB)->get(route('sales.follow-up.index'));
        $queues = $response->viewData('prospects');

        // Both active prospects of Sales A appear in Bantuan Sales Lain (others)
        $inOthersUpcoming = collect($queues['others'])->firstWhere('id', $prospekUpcoming->id);
        $inOthersOverdue  = collect($queues['others'])->firstWhere('id', $prospekOverdue->id);

        $this->assertNotNull($inOthersUpcoming, 'Upcoming prospect of Sales A must be visible in Sales B Bantuan queue');
        $this->assertNotNull($inOthersOverdue, 'Overdue prospect of Sales A must be visible in Sales B Bantuan queue');

        // Neither can appear in Sales B's own queues
        $this->assertNull(collect($queues['need_follow_up'])->firstWhere('id', $prospekUpcoming->id));
        $this->assertNull(collect($queues['scheduled'])->firstWhere('id', $prospekUpcoming->id));
    }

    /**
     * Requirement 8:
     * Urutan Bantuan Sales Lain mengikuti prioritas yang ditentukan:
     * 1. Terlambat
     * 2. Jadwal hari ini
     * 3. Belum pernah dihubungi
     * 4. Belum memiliki jadwal berikutnya setelah pernah di-follow-up
     * 5. Jadwal mendatang, dengan jadwal paling dekat berada paling atas.
     */
    public function test_bantuan_sales_lain_priority_ordering(): void
    {
        // 1. Terlambat
        $pOverdue = Prospek::create([
            'name' => 'Order 1: Terlambat',
            'type' => 'Individu',
            'pic' => 'PIC 1',
            'whatsapp' => '081200000001',
            'status' => 'KONTAK',
            'sales_id' => $this->salesA->id,
            'owner_id' => $this->salesA->id,
        ]);
        FollowUp::create([
            'prospek_id' => $pOverdue->id,
            'user_id' => $this->salesA->id,
            'metode' => 'WhatsApp',
            'hasil' => 'Hasil',
            'catatan' => 'Catatan',
            'tanggal' => now()->subDays(5),
            'next_follow_up' => now()->subDays(2)->format('Y-m-d 10:00:00'),
        ]);

        // 2. Hari Ini
        $pToday = Prospek::create([
            'name' => 'Order 2: Hari Ini',
            'type' => 'Individu',
            'pic' => 'PIC 2',
            'whatsapp' => '081200000002',
            'status' => 'KONTAK',
            'sales_id' => $this->salesA->id,
            'owner_id' => $this->salesA->id,
        ]);
        FollowUp::create([
            'prospek_id' => $pToday->id,
            'user_id' => $this->salesA->id,
            'metode' => 'WhatsApp',
            'hasil' => 'Hasil',
            'catatan' => 'Catatan',
            'tanggal' => now()->subDays(1),
            'next_follow_up' => now()->format('Y-m-d 11:00:00'),
        ]);

        // 3. Belum pernah dihubungi
        $pUncontacted = Prospek::create([
            'name' => 'Order 3: Belum Pernah Dihubungi',
            'type' => 'Individu',
            'pic' => 'PIC 3',
            'whatsapp' => '081200000003',
            'status' => 'BARU',
            'sales_id' => $this->salesA->id,
            'owner_id' => $this->salesA->id,
        ]);

        // 4. Belum memiliki jadwal berikutnya setelah pernah di-FU
        $pUnscheduled = Prospek::create([
            'name' => 'Order 4: Belum Memiliki Jadwal',
            'type' => 'Individu',
            'pic' => 'PIC 4',
            'whatsapp' => '081200000004',
            'status' => 'KONTAK',
            'sales_id' => $this->salesA->id,
            'owner_id' => $this->salesA->id,
        ]);
        FollowUp::create([
            'prospek_id' => $pUnscheduled->id,
            'user_id' => $this->salesA->id,
            'metode' => 'WhatsApp',
            'hasil' => 'Hasil',
            'catatan' => 'Catatan',
            'tanggal' => now()->subDays(1),
            'next_follow_up' => null,
        ]);

        // 5a. Mendatang (Besok / Lebih Dekat)
        $pUpcomingNear = Prospek::create([
            'name' => 'Order 5a: Mendatang Dekat',
            'type' => 'Individu',
            'pic' => 'PIC 5a',
            'whatsapp' => '081200000005',
            'status' => 'KONTAK',
            'sales_id' => $this->salesA->id,
            'owner_id' => $this->salesA->id,
        ]);
        FollowUp::create([
            'prospek_id' => $pUpcomingNear->id,
            'user_id' => $this->salesA->id,
            'metode' => 'WhatsApp',
            'hasil' => 'Hasil',
            'catatan' => 'Catatan',
            'tanggal' => now(),
            'next_follow_up' => now()->addDays(1)->format('Y-m-d 09:00:00'),
        ]);

        // 5b. Mendatang (Minggu Depan / Lebih Jauh)
        $pUpcomingFar = Prospek::create([
            'name' => 'Order 5b: Mendatang Jauh',
            'type' => 'Individu',
            'pic' => 'PIC 5b',
            'whatsapp' => '081200000006',
            'status' => 'KONTAK',
            'sales_id' => $this->salesA->id,
            'owner_id' => $this->salesA->id,
        ]);
        FollowUp::create([
            'prospek_id' => $pUpcomingFar->id,
            'user_id' => $this->salesA->id,
            'metode' => 'WhatsApp',
            'hasil' => 'Hasil',
            'catatan' => 'Catatan',
            'tanggal' => now(),
            'next_follow_up' => now()->addDays(7)->format('Y-m-d 09:00:00'),
        ]);

        $response = $this->actingAs($this->salesB)->get(route('sales.follow-up.index'));
        $queues = $response->viewData('prospects');
        $others = $queues['others'];

        $orderedIds = array_column($others, 'id');

        $posOverdue       = array_search($pOverdue->id, $orderedIds);
        $posToday         = array_search($pToday->id, $orderedIds);
        $posUncontacted   = array_search($pUncontacted->id, $orderedIds);
        $posUnscheduled   = array_search($pUnscheduled->id, $orderedIds);
        $posUpcomingNear  = array_search($pUpcomingNear->id, $orderedIds);
        $posUpcomingFar   = array_search($pUpcomingFar->id, $orderedIds);

        $this->assertNotFalse($posOverdue);
        $this->assertNotFalse($posToday);
        $this->assertNotFalse($posUncontacted);
        $this->assertNotFalse($posUnscheduled);
        $this->assertNotFalse($posUpcomingNear);
        $this->assertNotFalse($posUpcomingFar);

        // Assert priority ordering: 1 < 2 < 3 < 4 < 5a < 5b
        $this->assertLessThan($posToday, $posOverdue, 'Overdue must be ahead of Today');
        $this->assertLessThan($posUncontacted, $posToday, 'Today must be ahead of Uncontacted');
        $this->assertLessThan($posUnscheduled, $posUncontacted, 'Uncontacted must be ahead of Unscheduled');
        $this->assertLessThan($posUpcomingNear, $posUnscheduled, 'Unscheduled must be ahead of Upcoming');
        $this->assertLessThan($posUpcomingFar, $posUpcomingNear, 'Nearer upcoming date must be ahead of farther upcoming date');
    }

    /**
     * Requirement 9:
     * Prospek LUNAS dan DINGIN tidak masuk antrean aktif.
     * Hanya masuk tab Selesai milik Sales sendiri.
     */
    public function test_lunas_and_dingin_do_not_enter_active_queues(): void
    {
        $pLunas = Prospek::create([
            'name' => 'Prospek Lunas A',
            'type' => 'Individu',
            'pic' => 'PIC Lunas',
            'whatsapp' => '081300000001',
            'status' => 'LUNAS',
            'sales_id' => $this->salesA->id,
            'owner_id' => $this->salesA->id,
        ]);

        $pDingin = Prospek::create([
            'name' => 'Prospek Dingin A',
            'type' => 'Individu',
            'pic' => 'PIC Dingin',
            'whatsapp' => '081300000002',
            'status' => 'DINGIN',
            'sales_id' => $this->salesA->id,
            'owner_id' => $this->salesA->id,
        ]);

        // For Sales A (owner):
        $resA = $this->actingAs($this->salesA)->get(route('sales.follow-up.index'));
        $queuesA = $resA->viewData('prospects');

        $this->assertNotNull(collect($queuesA['done'])->firstWhere('id', $pLunas->id));
        $this->assertNotNull(collect($queuesA['done'])->firstWhere('id', $pDingin->id));
        $this->assertNull(collect($queuesA['need_follow_up'])->firstWhere('id', $pLunas->id));
        $this->assertNull(collect($queuesA['need_follow_up'])->firstWhere('id', $pDingin->id));
        $this->assertNull(collect($queuesA['scheduled'])->firstWhere('id', $pLunas->id));

        // For Sales B (other Sales):
        $resB = $this->actingAs($this->salesB)->get(route('sales.follow-up.index'));
        $queuesB = $resB->viewData('prospects');

        // Finished prospects of Sales A MUST NOT appear in Sales B's others or done
        $this->assertNull(collect($queuesB['others'])->firstWhere('id', $pLunas->id));
        $this->assertNull(collect($queuesB['others'])->firstWhere('id', $pDingin->id));
        $this->assertNull(collect($queuesB['done'])->firstWhere('id', $pLunas->id));
        $this->assertNull(collect($queuesB['done'])->firstWhere('id', $pDingin->id));
    }

    /**
     * Requirement 10 & 11:
     * Bantuan follow-up tidak mengubah pemilik awal.
     * Identitas pelaksana dan seluruh riwayat FU tetap tersimpan.
     */
    public function test_assistance_follow_up_preserves_owner_and_records_executor(): void
    {
        $prospek = Prospek::create([
            'name' => 'Prospek Kolaborasi',
            'type' => 'Individu',
            'pic' => 'PIC Kolaborasi',
            'whatsapp' => '081300000003',
            'status' => 'BARU',
            'sales_id' => $this->salesA->id,
            'owner_id' => $this->salesA->id,
        ]);

        // First follow-up by Sales A
        FollowUp::create([
            'prospek_id' => $prospek->id,
            'user_id' => $this->salesA->id,
            'metode' => 'WhatsApp',
            'hasil' => 'Kontak awal',
            'catatan' => 'Follow up pertama oleh Sales A',
            'tanggal' => now()->subDays(2),
            'next_follow_up' => now()->toDateString(),
        ]);

        // Sales B assists with follow-up via sales.follow-up.store
        $storeResponse = $this->actingAs($this->salesB)->post(route('sales.follow-up.store'), [
            'prospek_id' => $prospek->id,
            'metode' => 'Telepon',
            'hasil' => 'Berminat D3 AI',
            'catatan' => 'Sales B membantu follow up saat Sales A dinas',
            'status' => 'KONTAK',
            'next_follow_up' => now()->addDays(2)->format('Y-m-d'),
        ]);

        $storeResponse->assertRedirect();

        // 10. Owner remains Sales A
        $prospek->refresh();
        $this->assertEquals($this->salesA->id, $prospek->sales_id, 'sales_id must remain Sales A');
        $this->assertEquals($this->salesA->id, $prospek->owner_id, 'owner_id must remain Sales A');

        // 11. Follow-up history records Sales B
        $this->assertEquals(2, $prospek->followUps()->count());
        $latest = $prospek->latestFollowUp;
        $this->assertEquals($this->salesB->id, $latest->user_id, 'Latest follow-up user_id must record Sales B');

        // Sales A can see the assistance record
        $resA = $this->actingAs($this->salesA)->get(route('sales.follow-up.index'));
        $queuesA = $resA->viewData('prospects');
        $foundA = collect($queuesA['scheduled'])->firstWhere('id', $prospek->id);
        $this->assertNotNull($foundA);
        $this->assertTrue($foundA['is_assisted_by_other']);
        $this->assertEquals('Sales Budi', $foundA['latest_follow_up_user']);
    }

    /**
     * Requirement 12:
     * Hak akses dan pembatasan role tidak melemah:
     * Prospek eksklusif CS tidak dapat diintervensi oleh Sales.
     */
    public function test_cs_exclusive_prospect_not_accessible_to_sales(): void
    {
        $csUser = User::create([
            'name' => 'CS Dina',
            'email' => 'dina@cic.ac.id',
            'password' => bcrypt('password'),
            'role' => 'CS',
            'status' => 'aktif',
        ]);

        $csProspect = Prospek::create([
            'name' => 'Lead CS Eksklusif',
            'type' => 'Individu',
            'pic' => 'PIC CS Lead',
            'whatsapp' => '081399999999',
            'status' => 'FORMULIR',
            'cs_id' => $csUser->id,
            'owner_id' => $csUser->id,
            'sales_id' => null,
        ]);

        $res = $this->actingAs($this->salesA)->get(route('sales.follow-up.index'));
        $queues = $res->viewData('prospects');

        // Must NOT appear in Sales A queues
        $this->assertNull(collect($queues['need_follow_up'])->firstWhere('id', $csProspect->id));
        $this->assertNull(collect($queues['others'])->firstWhere('id', $csProspect->id));
    }

    /**
     * Requirement 13 & 14:
     * Jumlah badge dan data sesuai hasil query.
     * Tidak ada duplikasi prospek dalam satu tab.
     */
    public function test_no_prospect_duplication_within_tabs_and_counts_match(): void
    {
        // Create 3 diverse prospects for Sales A
        $p1 = Prospek::create([
            'name' => 'Lead Overdue',
            'type' => 'Individu',
            'pic' => 'PIC 1',
            'whatsapp' => '081400000001',
            'status' => 'KONTAK',
            'sales_id' => $this->salesA->id,
            'owner_id' => $this->salesA->id,
        ]);
        FollowUp::create([
            'prospek_id' => $p1->id,
            'user_id' => $this->salesA->id,
            'metode' => 'WhatsApp',
            'hasil' => 'Hasil',
            'catatan' => 'Catatan',
            'tanggal' => now()->subDays(3),
            'next_follow_up' => now()->subDay()->toDateString(),
        ]);

        $p2 = Prospek::create([
            'name' => 'Lead Today',
            'type' => 'Individu',
            'pic' => 'PIC 2',
            'whatsapp' => '081400000002',
            'status' => 'KONTAK',
            'sales_id' => $this->salesA->id,
            'owner_id' => $this->salesA->id,
        ]);
        FollowUp::create([
            'prospek_id' => $p2->id,
            'user_id' => $this->salesA->id,
            'metode' => 'WhatsApp',
            'hasil' => 'Hasil',
            'catatan' => 'Catatan',
            'tanggal' => now(),
            'next_follow_up' => now()->toDateString(),
        ]);

        $p3 = Prospek::create([
            'name' => 'Lead Upcoming',
            'type' => 'Individu',
            'pic' => 'PIC 3',
            'whatsapp' => '081400000003',
            'status' => 'KONTAK',
            'sales_id' => $this->salesA->id,
            'owner_id' => $this->salesA->id,
        ]);
        FollowUp::create([
            'prospek_id' => $p3->id,
            'user_id' => $this->salesA->id,
            'metode' => 'WhatsApp',
            'hasil' => 'Hasil',
            'catatan' => 'Catatan',
            'tanggal' => now(),
            'next_follow_up' => now()->addDays(2)->toDateString(),
        ]);

        $response = $this->actingAs($this->salesA)->get(route('sales.follow-up.index'));
        $queues = $response->viewData('prospects');

        // Check need_follow_up
        $needIds = array_column($queues['need_follow_up'], 'id');
        $this->assertEquals(count($needIds), count(array_unique($needIds)), 'No duplicates in need_follow_up');
        $this->assertContains($p1->id, $needIds);
        $this->assertContains($p2->id, $needIds);
        $this->assertNotContains($p3->id, $needIds);

        // Check scheduled
        $schedIds = array_column($queues['scheduled'], 'id');
        $this->assertEquals(count($schedIds), count(array_unique($schedIds)), 'No duplicates in scheduled');
        $this->assertContains($p3->id, $schedIds);
        $this->assertNotContains($p1->id, $schedIds);
        $this->assertNotContains($p2->id, $schedIds);
    }

    /**
     * Requirement 15:
     * Responsiveness & Action Buttons:
     * - Catat Follow Up for own prospects
     * - Bantu Follow Up for others' prospects
     */
    public function test_ui_renders_catat_vs_bantu_follow_up_buttons_and_metadata(): void
    {
        // Own prospect
        Prospek::create([
            'name' => 'Prospek Sendiri',
            'type' => 'Individu',
            'pic' => 'PIC Sendiri',
            'whatsapp' => '081500000001',
            'status' => 'BARU',
            'sales_id' => $this->salesA->id,
            'owner_id' => $this->salesA->id,
        ]);

        // Other prospect
        Prospek::create([
            'name' => 'Prospek Teman',
            'type' => 'Individu',
            'pic' => 'PIC Teman',
            'whatsapp' => '081500000002',
            'status' => 'KONTAK',
            'sales_id' => $this->salesB->id,
            'owner_id' => $this->salesB->id,
        ]);

        $response = $this->actingAs($this->salesA)->get(route('sales.follow-up.index'));
        $response->assertOk();

        $content = $response->getContent();
        $this->assertStringContainsString('Catat Follow Up', $content);
        $this->assertStringContainsString('Bantu Follow Up', $content);
        $this->assertStringContainsString('Sales Pemilik', $content);
        $this->assertStringContainsString('Riwayat FU', $content);
        $this->assertStringContainsString('Last Contact', $content);
        $this->assertStringContainsString('Pelaksana Terakhir', $content);
    }
}
