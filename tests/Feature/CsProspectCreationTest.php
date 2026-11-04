<?php

namespace Tests\Feature;

use App\Models\Prospek;
use App\Models\TahunAkademik;
use App\Models\User;
use App\Models\Wilayah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class CsProspectCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cs_can_create_prospect_and_immediately_access_and_follow_up()
    {
        $wilayah = Wilayah::create(['nama' => 'Wilayah 1', 'kode' => 'W1', 'status' => 'Aktif']);
        $ta = TahunAkademik::create(['nama' => '2026/2027', 'is_active' => true]);

        $cs = User::factory()->create([
            'name' => 'Mei Fie',
            'role' => 'CS',
            'wilayah_id' => $wilayah->id,
            'status' => 'Aktif',
        ]);

        $prospek = Prospek::create([
            'name' => 'SMA Test',
            'pic' => 'Budi',
            'whatsapp' => '081234567890',
            'type' => 'Sekolah',
            'status' => 'BARU',
            'stage_number' => 1,
            'owner_id' => $cs->id,
            'cs_id' => $cs->id,
            'sales_id' => null,
            'wilayah_id' => $wilayah->id,
            'academic_year_id' => $ta->id,
        ]);

        // CS must be able to view, update, follow up, update status
        $this->assertTrue(Gate::forUser($cs)->allows('view', $prospek));
        $this->assertTrue(Gate::forUser($cs)->allows('update', $prospek));
        $this->assertTrue(Gate::forUser($cs)->allows('followUp', $prospek));
        $this->assertTrue(Gate::forUser($cs)->allows('updateStatus', $prospek));

        // CS cannot delete
        $this->assertFalse(Gate::forUser($cs)->allows('delete', $prospek));

        // Active handler label
        $this->assertEquals('CS — Mei Fie', $prospek->activeHandlerLabel());
    }

    public function test_cs_submitting_prospek_store_assigns_cs_id_and_not_sales_id()
    {
        $wilayah = Wilayah::create(['nama' => 'Wilayah 1', 'kode' => 'W1', 'status' => 'Aktif']);
        $ta = TahunAkademik::create(['nama' => '2026/2027', 'is_active' => true]);

        $cs = User::factory()->create([
            'name' => 'Mei Fie',
            'role' => 'CS',
            'wilayah_id' => $wilayah->id,
            'status' => 'Aktif',
        ]);

        $response = $this->actingAs($cs)->post(route('prospek.store'), [
            'type' => 'Individu',
            'name' => 'Calon Mahasiswa CS',
            'pic' => 'Calon Mahasiswa CS',
            'whatsapp' => '08987654321',
            'status' => 'BARU',
            'cs_id' => $cs->id,
        ]);

        $response->assertSessionHasNoErrors();

        $created = Prospek::where('whatsapp', '+628987654321')->orWhere('whatsapp', '08987654321')->first();
        $this->assertNotNull($created);
        $this->assertEquals($cs->id, $created->cs_id);
        $this->assertNull($created->sales_id);
        $this->assertEquals('CS — Mei Fie', $created->activeHandlerLabel());
    }

    public function test_pending_payment_assigned_to_one_cs_does_not_leak_to_another_cs()
    {
        $wilayah = Wilayah::create(['nama' => 'Wilayah 1', 'kode' => 'W1', 'status' => 'Aktif']);
        $ta = TahunAkademik::create(['nama' => '2026/2027', 'is_active' => true]);

        $csYuda = User::factory()->create([
            'name' => 'Yuda',
            'role' => 'CS',
            'wilayah_id' => $wilayah->id,
            'status' => 'Aktif',
        ]);

        $csMeiFie = User::factory()->create([
            'name' => 'Mei Fie',
            'role' => 'CS',
            'wilayah_id' => $wilayah->id,
            'status' => 'Aktif',
        ]);

        $sales = User::factory()->create([
            'name' => 'Sales Lapangan',
            'role' => 'Sales',
            'wilayah_id' => $wilayah->id,
            'status' => 'Aktif',
        ]);

        // Prospect assigned to CS Yuda
        $prospekYuda = Prospek::create([
            'name' => 'Camaba Yuda',
            'pic' => 'Camaba Yuda',
            'whatsapp' => '08111111111',
            'type' => 'Individu',
            'status' => 'FORMULIR',
            'stage_number' => 5,
            'sales_id' => $sales->id,
            'owner_id' => $sales->id,
            'cs_id' => $csYuda->id,
            'wilayah_id' => $wilayah->id,
            'academic_year_id' => $ta->id,
        ]);

        // Sales inputs payment for Prospek Yuda
        $trx = \App\Models\Transaksi::create([
            'prospek_id' => $prospekYuda->id,
            'user_id' => $sales->id,
            'jenis' => 'Pembayaran Termin 1',
            'nominal' => 1500000,
            'tanggal' => now(),
            'payment_status' => \App\Models\Transaksi::STATUS_PENDING,
            'metode_pembayaran' => 'bank_transfer',
        ]);

        // 1. In CS Yuda's verification page: Transaksi appears
        $resYuda = $this->actingAs($csYuda)->get(route('cs.verifikasi.index'));
        $resYuda->assertOk();
        $this->assertEquals($csYuda->id, $prospekYuda->fresh()->cs_id);
        $resYuda->assertSee('Camaba Yuda');

        // 2. Section O: CS Mei Fie can also view shared CS transactions
        $resMeiFie = $this->actingAs($csMeiFie)->get(route('cs.verifikasi.index'));
        $resMeiFie->assertOk();
        $resMeiFie->assertSee('Camaba Yuda');

        // 3. Section O: CS Mei Fie can verify the transaction without 403
        $resVerify = $this->actingAs($csMeiFie)->post(route('cs.verifikasi.verify', $trx->id));
        $resVerify->assertRedirect();
        $this->assertEquals(\App\Models\Transaksi::STATUS_VERIFIED, $trx->fresh()->payment_status);

        // 4. In CS Mei Fie's /prospek, CS Yuda's prospect appears
        $resProspekMeiFie = $this->actingAs($csMeiFie)->get(route('prospek.index'));
        $resProspekMeiFie->assertOk();
        $resProspekMeiFie->assertSee('Camaba Yuda');

        // 5. In CS Yuda's /prospek, Camaba Yuda also appears
        $resProspekYuda = $this->actingAs($csYuda)->get(route('prospek.index'));
        $resProspekYuda->assertOk();
        $resProspekYuda->assertSee('Camaba Yuda');

        // 6. Section O: Direct detail access to CS Yuda's prospect by CS Mei Fie is allowed (not 403)
        $resShowMeiFie = $this->actingAs($csMeiFie)->get(route('prospek.show', $prospekYuda->id));
        $resShowMeiFie->assertOk();
    }

    public function test_cs_b_visibility_of_cs_a_prospects_across_tabs_and_pages()
    {
        $wilayah = Wilayah::create(['nama' => 'Wilayah 1', 'kode' => 'W1', 'status' => 'Aktif']);
        $ta = TahunAkademik::create(['nama' => '2026/2027', 'is_active' => true]);

        $csA = User::factory()->create([
            'name' => 'CS Alpha',
            'role' => 'CS',
            'wilayah_id' => $wilayah->id,
            'status' => 'Aktif',
        ]);

        $csB = User::factory()->create([
            'name' => 'CS Beta',
            'role' => 'CS',
            'wilayah_id' => $wilayah->id,
            'status' => 'Aktif',
        ]);

        // CS A inputs a prospect via prospek.store (status BARU)
        $resStore = $this->actingAs($csA)->post(route('prospek.store'), [
            'type' => 'Individu',
            'name' => 'Prospek Dari CS Alpha',
            'pic' => 'Prospek Dari CS Alpha',
            'whatsapp' => '81299990001',
            'status' => 'BARU',
            'cs_id' => $csA->id,
        ]);
        $resStore->assertSessionHasNoErrors();

        // 1. Check in CS A's prospek.index
        $resCsA = $this->actingAs($csA)->get(route('prospek.index'));
        $resCsA->assertOk()->assertSee('Prospek Dari CS Alpha');

        // 2. Check in CS B's prospek.index (Semua Prospek / default)
        $resCsB = $this->actingAs($csB)->get(route('prospek.index'));
        $resCsB->assertOk()->assertSee('Prospek Dari CS Alpha');

        // 3. Check in CS B's scope=own (Should NOT be in Prospek Saya CS B!)
        $resCsBOwn = $this->actingAs($csB)->get(route('prospek.index', ['scope' => 'own']));
        $resCsBOwn->assertOk()->assertDontSee('Prospek Dari CS Alpha');

        // 4. Check in CS B's scope=others (SHOULD be in Prospek CS Lain!)
        $resCsBOthers = $this->actingAs($csB)->get(route('prospek.index', ['scope' => 'others']));
        $resCsBOthers->assertOk()->assertSee('Prospek Dari CS Alpha');

        // 5. Check in CS B's follow-up page
        $resCsBFu = $this->actingAs($csB)->get(route('follow-up.index'));
        $resCsBFu->assertOk()->assertSee('Prospek Dari CS Alpha');

        // Test Scenario 2: Prospek dibuat oleh Sales di tahap awal (BARU, cs_id = null, source = Sosmed)
        $sales = User::factory()->create([
            'name' => 'Sales Lapangan 2',
            'role' => 'Sales',
            'wilayah_id' => $wilayah->id,
            'status' => 'Aktif',
        ]);

        $prospekSales = Prospek::create([
            'name' => 'Prospek Sales Awal',
            'pic' => 'Prospek Sales Awal',
            'whatsapp' => '+6281299990002',
            'type' => 'Individu',
            'status' => 'BARU',
            'stage_number' => 1,
            'source' => 'Sosmed',
            'sales_id' => $sales->id,
            'owner_id' => $sales->id,
            'cs_id' => null,
            'wilayah_id' => $wilayah->id,
            'academic_year_id' => $ta->id,
        ]);

        // Check: Can CS A see it?
        $resCsASales = $this->actingAs($csA)->get(route('prospek.index'));
        // Check: Can CS B see it?
        $resCsBSales = $this->actingAs($csB)->get(route('prospek.index'));
        $this->assertFalse(str_contains($resCsASales->getContent(), 'Prospek Sales Awal'), 'CS A should NOT see early sales prospect before handover or presensi');
        $this->assertFalse(str_contains($resCsBSales->getContent(), 'Prospek Sales Awal'), 'CS B should NOT see early sales prospect before handover or presensi');

        // Now CS A takes over or CS A is assigned cs_id
        $prospekSales->update(['cs_id' => $csA->id, 'takeover_by_id' => $csA->id, 'status_takeover' => 'Sudah Di-takeover']);

        // Check again: CS A sees it
        $resCsASalesAfter = $this->actingAs($csA)->get(route('prospek.index'));
        $this->assertTrue(str_contains($resCsASalesAfter->getContent(), 'Prospek Sales Awal'));

        // Check again: CS B sees it in prospek.index (Semua Prospek)
        $resCsBSalesAfter = $this->actingAs($csB)->get(route('prospek.index'));
        $this->assertTrue(str_contains($resCsBSalesAfter->getContent(), 'Prospek Sales Awal'));
    }
}
