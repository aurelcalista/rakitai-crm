<?php

namespace Tests\Feature;

use App\Models\FollowUp;
use App\Models\MasterData;
use App\Models\Prospek;
use App\Models\TahunAkademik;
use App\Models\User;
use App\Models\Wilayah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CsFollowUpQueueAndScopeTest extends TestCase
{
    use RefreshDatabase;

    protected User $csCirebon;
    protected User $csBandung;
    protected User $salesCirebon;
    protected User $salesBandung;
    protected Wilayah $wilayahCirebon;
    protected Wilayah $wilayahBandung;

    protected function setUp(): void
    {
        parent::setUp();

        TahunAkademik::create([
            'nama' => '2027/2028',
            'is_aktif' => true,
        ]);

        MasterData::create([
            'kode' => 'SRC-01',
            'type' => 'sumber_prospek',
            'nama' => 'Brosur',
            'status' => 'Aktif',
        ]);

        $this->wilayahCirebon = Wilayah::create([
            'nama' => 'Cirebon',
            'kode' => 'CRB-01',
        ]);

        $this->wilayahBandung = Wilayah::create([
            'nama' => 'Bandung',
            'kode' => 'BDG-01',
        ]);

        $this->csCirebon = User::factory()->create([
            'name' => 'CS Cirebon',
            'role' => 'CS',
            'status' => 'Aktif',
            'wilayah_id' => $this->wilayahCirebon->id,
        ]);

        $this->csBandung = User::factory()->create([
            'name' => 'CS Bandung',
            'role' => 'CS',
            'status' => 'Aktif',
            'wilayah_id' => $this->wilayahBandung->id,
        ]);

        $this->salesCirebon = User::factory()->create([
            'name' => 'Sales Cirebon',
            'role' => 'Sales',
            'status' => 'Aktif',
            'wilayah_id' => $this->wilayahCirebon->id,
        ]);

        $this->salesBandung = User::factory()->create([
            'name' => 'Sales Bandung',
            'role' => 'Sales',
            'status' => 'Aktif',
            'wilayah_id' => $this->wilayahBandung->id,
        ]);
    }

    public function test_handover_prospect_appears_in_cs_primary_queue_and_not_in_others()
    {
        // Prospek dibuat oleh Sales Cirebon dan di-handover ke CS Cirebon
        $prospek = Prospek::create([
            'name'        => 'Ahmad Handover',
            'pic'         => 'Ahmad Handover',
            'whatsapp'    => '081234567890',
            'sales_id'    => $this->salesCirebon->id,
            'owner_id'    => $this->salesCirebon->id,
            'cs_id'       => $this->csCirebon->id,
            'wilayah_id'  => $this->wilayahCirebon->id,
            'status'      => 'FORMULIR',
            'type'        => 'Individu',
            'handover_at' => now(),
        ]);

        $response = $this->actingAs($this->csCirebon)->get('/follow-up');
        $response->assertStatus(200);

        $prospects = $response->viewData('prospects');
        $this->assertNotEmpty($prospects);

        // Harus masuk ke need_follow_up (primary task antrean CS)
        $needFollowUpIds = collect($prospects['need_follow_up'])->pluck('id')->toArray();
        $this->assertContains($prospek->id, $needFollowUpIds);

        // TIDAK boleh masuk ke others (Bantuan Sales Lain)
        $othersIds = collect($prospects['others'])->pluck('id')->toArray();
        $this->assertNotContains($prospek->id, $othersIds);

        // is_own harus true
        $matched = collect($prospects['need_follow_up'])->firstWhere('id', $prospek->id);
        $this->assertTrue($matched['is_own']);
        $this->assertFalse($matched['is_other']);
    }

    public function test_cs_does_not_see_unrelated_early_stage_sales_leads()
    {
        // Prospek Sales Bandung berstatus BARU (belum handover, beda wilayah)
        $prospekBandung = Prospek::create([
            'name'       => 'Prospek Bandung Baru',
            'pic'        => 'Rina Bandung',
            'whatsapp'   => '081234567899',
            'sales_id'   => $this->salesBandung->id,
            'owner_id'   => $this->salesBandung->id,
            'wilayah_id' => $this->wilayahBandung->id,
            'status'     => 'BARU',
            'type'       => 'Individu',
        ]);

        $response = $this->actingAs($this->csCirebon)->get('/follow-up');
        $response->assertStatus(200);

        $prospects = $response->viewData('prospects');
        $allIds = collect($prospects['need_follow_up'])
            ->merge($prospects['others'])
            ->merge($prospects['scheduled'])
            ->merge($prospects['done'])
            ->pluck('id')
            ->toArray();

        // Tidak boleh bocor ke antrean CS Cirebon
        $this->assertNotContains($prospekBandung->id, $allIds);
    }

    public function test_cs_can_view_detail_of_assigned_handover_prospect()
    {
        $prospek = Prospek::create([
            'name'        => 'Budi Valid Detail',
            'pic'         => 'Budi Valid Detail',
            'whatsapp'    => '081234567888',
            'sales_id'    => $this->salesCirebon->id,
            'owner_id'    => $this->salesCirebon->id,
            'cs_id'       => $this->csCirebon->id,
            'wilayah_id'  => $this->wilayahCirebon->id,
            'status'      => 'BERKAS',
            'type'        => 'Individu',
            'handover_at' => now(),
        ]);

        $response = $this->actingAs($this->csCirebon)->get('/prospek/' . $prospek->id);
        $response->assertStatus(200);
    }

    public function test_cs_can_view_detail_of_unassigned_prospect_in_different_wilayah()
    {
        $prospek = Prospek::create([
            'name'       => 'Rahasia Bandung',
            'pic'        => 'Rahasia Bandung',
            'whatsapp'   => '081234567777',
            'sales_id'   => $this->salesBandung->id,
            'owner_id'   => $this->salesBandung->id,
            'wilayah_id' => $this->wilayahBandung->id,
            'status'     => 'BARU',
            'type'       => 'Individu',
        ]);

        // CS Cirebon can view prospect from Bandung per policy allowing detail access
        $response = $this->actingAs($this->csCirebon)->get('/prospek/' . $prospek->id);
        $response->assertStatus(200);
    }

    public function test_cs_follow_up_records_actual_cs_actor_and_preserves_sales_owner()
    {
        $prospek = Prospek::create([
            'name'        => 'Cynthia Handover',
            'pic'         => 'Cynthia Handover',
            'whatsapp'    => '081234567666',
            'sales_id'    => $this->salesCirebon->id,
            'owner_id'    => $this->salesCirebon->id,
            'cs_id'       => $this->csCirebon->id,
            'wilayah_id'  => $this->wilayahCirebon->id,
            'status'      => 'FORMULIR',
            'type'        => 'Individu',
            'handover_at' => now(),
        ]);

        // Prior follow-up history by Sales
        FollowUp::create([
            'prospek_id' => $prospek->id,
            'user_id'    => $this->salesCirebon->id,
            'metode'     => 'WhatsApp',
            'hasil'      => 'Tertarik mendaftar',
            'tanggal'    => now()->subDays(2),
            'catatan'    => 'Kontak awal sales',
        ]);

        // CS performs new follow up
        $response = $this->actingAs($this->csCirebon)->post(route('follow-up.store'), [
            'prospek_id' => $prospek->id,
            'metode'     => 'Telepon',
            'hasil'      => 'Sudah mengisi formulir online',
            'catatan'    => 'Menunggu verifikasi berkas',
            'status'     => 'BERKAS',
        ]);

        $response->assertRedirect();

        // 1. New follow up must record CS as user_id
        $this->assertDatabaseHas('follow_ups', [
            'prospek_id' => $prospek->id,
            'user_id'    => $this->csCirebon->id,
            'hasil'      => 'Sudah mengisi formulir online',
        ]);

        // 2. Old follow-up history is preserved
        $this->assertEquals(2, $prospek->fresh()->followUps()->count());

        // 3. Sales owner and creator remain intact
        $prospekFresh = $prospek->fresh();
        $this->assertEquals($this->salesCirebon->id, $prospekFresh->sales_id);
        $this->assertEquals($this->salesCirebon->id, $prospekFresh->owner_id);
        $this->assertEquals($this->csCirebon->id, $prospekFresh->cs_id);
        $this->assertEquals('BERKAS', $prospekFresh->status);
    }

    public function test_sales_creator_can_still_collaborate_and_follow_up_on_handed_over_prospect()
    {
        $prospek = Prospek::create([
            'name'        => 'Dodi Kolaborasi',
            'pic'         => 'Dodi Kolaborasi',
            'whatsapp'    => '081234567555',
            'sales_id'    => $this->salesCirebon->id,
            'owner_id'    => $this->salesCirebon->id,
            'cs_id'       => $this->csCirebon->id,
            'wilayah_id'  => $this->wilayahCirebon->id,
            'status'      => 'FORMULIR',
            'type'        => 'Individu',
            'handover_at' => now(),
        ]);

        // Sales creator assists with follow up
        $response = $this->actingAs($this->salesCirebon)->post(route('follow-up.store'), [
            'prospek_id' => $prospek->id,
            'metode'     => 'WhatsApp',
            'hasil'      => 'Sales follow-up bantuan reminder berkas',
            'catatan'    => 'Bantu ingatkan kirim rapor',
            'status'     => 'FORMULIR',
        ]);

        $response->assertRedirect();

        // Follow up executor is Sales Cirebon
        $this->assertDatabaseHas('follow_ups', [
            'prospek_id' => $prospek->id,
            'user_id'    => $this->salesCirebon->id,
            'hasil'      => 'Sales follow-up bantuan reminder berkas',
        ]);
    }

    public function test_cs_prospects_index_includes_scope_filters_and_cs_identification()
    {
        $cs2 = User::factory()->create([
            'name'       => 'Siti CS Kedua',
            'role'       => 'CS',
            'status'     => 'Aktif',
            'wilayah_id' => $this->wilayahCirebon->id,
        ]);

        $prospekOwn = Prospek::create([
            'name'        => 'Prospek Milik CS Sendiri',
            'pic'         => 'Budi Mandiri',
            'whatsapp'    => '081234567111',
            'sales_id'    => $this->salesCirebon->id,
            'owner_id'    => $this->salesCirebon->id,
            'cs_id'       => $this->csCirebon->id,
            'wilayah_id'  => $this->wilayahCirebon->id,
            'status'      => 'FORMULIR',
            'type'        => 'Individu',
        ]);

        $prospekOther = Prospek::create([
            'name'        => 'Prospek Milik CS Lain',
            'pic'         => 'Rina Kolega',
            'whatsapp'    => '081234567222',
            'sales_id'    => $this->salesCirebon->id,
            'owner_id'    => $this->salesCirebon->id,
            'cs_id'       => $cs2->id,
            'wilayah_id'  => $this->wilayahCirebon->id,
            'status'      => 'BERKAS',
            'type'        => 'Individu',
            'handover_at' => now(),
        ]);

        $response = $this->actingAs($this->csCirebon)->get('/prospek');
        $response->assertStatus(200);
        $response->assertSee('Prospek CS Lain');
        $response->assertSee('Semua Prospek');
        $response->assertSee('Prospek Saya');

        $prospects = $response->viewData('prospects');
        $this->assertNotEmpty($prospects);

        $ownFormatted = collect($prospects)->firstWhere('id', $prospekOwn->id);
        $this->assertNotNull($ownFormatted);
        $this->assertTrue($ownFormatted['is_own']);
        $this->assertFalse($ownFormatted['is_other']);
        $this->assertEquals($this->csCirebon->name, $ownFormatted['cs_name']);

        $otherFormatted = collect($prospects)->firstWhere('id', $prospekOther->id);
        $this->assertNotNull($otherFormatted);
        $this->assertFalse($otherFormatted['is_own']);
        $this->assertTrue($otherFormatted['is_other']);
        $this->assertEquals($cs2->name, $otherFormatted['cs_name']);
    }
}

