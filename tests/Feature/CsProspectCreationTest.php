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

        $created = Prospek::where('whatsapp', '08987654321')->first();
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

        // 2. In CS Mei Fie's verification page: Transaksi DOES NOT appear
        $resMeiFie = $this->actingAs($csMeiFie)->get(route('cs.verifikasi.index'));
        $resMeiFie->assertOk();
        $resMeiFie->assertDontSee('Camaba Yuda');

        // 3. CS Mei Fie trying to verify CS Yuda's transaction directly is rejected with 403
        $resTamper = $this->actingAs($csMeiFie)->post(route('cs.verifikasi.verify', $trx->id));
        $resTamper->assertStatus(403);

        // 4. In CS Mei Fie's /prospek (Data Kontak), CS Yuda's prospect DOES NOT appear
        $resProspekMeiFie = $this->actingAs($csMeiFie)->get(route('prospek.index'));
        $resProspekMeiFie->assertOk();
        $resProspekMeiFie->assertDontSee('Camaba Yuda');

        // 5. In CS Yuda's /prospek, Camaba Yuda DOES appear
        $resProspekYuda = $this->actingAs($csYuda)->get(route('prospek.index'));
        $resProspekYuda->assertOk();
        $resProspekYuda->assertSee('Camaba Yuda');

        // 6. Direct detail access to CS Yuda's prospect by CS Mei Fie is 403 Forbidden
        $resShowTamper = $this->actingAs($csMeiFie)->get(route('prospek.show', $prospekYuda->id));
        $resShowTamper->assertStatus(403);
    }
}
