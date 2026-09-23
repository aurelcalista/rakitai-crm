<?php

namespace Tests\Feature;

use App\Models\Prospek;
use App\Models\User;
use App\Models\Wilayah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MultiWilayahAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $hm;
    protected User $spv;
    protected User $salesA;
    protected User $salesB;
    protected User $csA;
    protected User $csB;
    protected Wilayah $kotaCirebon;
    protected Wilayah $kejaksan;
    protected Wilayah $kesambi;
    protected Wilayah $harjamukti;
    protected Wilayah $kotaBandung;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup Wilayah Hierarchy
        $this->kotaCirebon = Wilayah::create([
            'kode'   => 'W-CRB',
            'nama'   => 'Kota Cirebon',
            'level'  => 'Kota/Kabupaten',
            'status' => 'Aktif',
        ]);

        $this->kejaksan = Wilayah::create([
            'kode'      => 'W-CRB-1',
            'nama'      => 'Kejaksan',
            'level'     => 'Kecamatan',
            'parent_id' => $this->kotaCirebon->id,
            'status'    => 'Aktif',
        ]);

        $this->kesambi = Wilayah::create([
            'kode'      => 'W-CRB-2',
            'nama'      => 'Kesambi',
            'level'     => 'Kecamatan',
            'parent_id' => $this->kotaCirebon->id,
            'status'    => 'Aktif',
        ]);

        $this->harjamukti = Wilayah::create([
            'kode'      => 'W-CRB-3',
            'nama'      => 'Harjamukti',
            'level'     => 'Kecamatan',
            'parent_id' => $this->kotaCirebon->id,
            'status'    => 'Aktif',
        ]);

        $this->kotaBandung = Wilayah::create([
            'kode'   => 'W-BDG',
            'nama'   => 'Kota Bandung',
            'level'  => 'Kota/Kabupaten',
            'status' => 'Aktif',
        ]);

        // Setup Users
        $this->admin = User::factory()->create([
            'name' => 'Admin User',
            'role' => 'Admin',
        ]);

        $this->hm = User::factory()->create([
            'name'       => 'HM Cirebon',
            'role'       => 'HM',
            'wilayah_id' => $this->kotaCirebon->id,
        ]);

        $this->spv = User::factory()->create([
            'name'       => 'SPV Andi',
            'role'       => 'SPV',
            'wilayah_id' => $this->kotaCirebon->id,
        ]);

        $this->salesA = User::factory()->create([
            'name'          => 'Sales A',
            'role'          => 'Sales',
            'supervisor_id' => $this->spv->id,
            'wilayah_id'    => $this->kotaCirebon->id,
        ]);

        $this->salesB = User::factory()->create([
            'name'          => 'Sales B',
            'role'          => 'Sales',
            'supervisor_id' => $this->spv->id,
            'wilayah_id'    => $this->kotaCirebon->id,
        ]);

        $this->csA = User::factory()->create([
            'name'          => 'CS A',
            'role'          => 'CS',
            'supervisor_id' => $this->spv->id,
            'wilayah_id'    => $this->kotaCirebon->id,
        ]);

        $this->csB = User::factory()->create([
            'name'          => 'CS B',
            'role'          => 'CS',
            'supervisor_id' => $this->spv->id,
            'wilayah_id'    => $this->kotaCirebon->id,
        ]);
    }

    public function test_sales_can_have_multiple_active_wilayahs(): void
    {
        $this->actingAs($this->spv);

        // Assign Sales A to Kejaksan
        $res1 = $this->post(route('spv.tim.territory.assign'), [
            'area_id'  => $this->kejaksan->id,
            'sales_id' => $this->salesA->id,
        ]);
        $res1->assertRedirect()->assertSessionHas('success');

        // Assign Sales A to Kesambi
        $res2 = $this->post(route('spv.tim.territory.assign'), [
            'area_id'  => $this->kesambi->id,
            'sales_id' => $this->salesA->id,
        ]);
        $res2->assertRedirect()->assertSessionHas('success');

        // Verify Sales A has 2 active wilayahs in pivot table
        $activeIds = $this->salesA->fresh()->activeWilayahIds();
        $this->assertContains($this->kejaksan->id, $activeIds);
        $this->assertContains($this->kesambi->id, $activeIds);
    }

    public function test_cs_can_have_multiple_active_wilayahs(): void
    {
        $this->actingAs($this->spv);

        // Assign CS A to Kejaksan
        $res1 = $this->post(route('spv.tim.territory.assign'), [
            'area_id' => $this->kejaksan->id,
            'cs_id'   => $this->csA->id,
        ]);
        $res1->assertRedirect()->assertSessionHas('success');

        // Assign CS A to Kesambi
        $res2 = $this->post(route('spv.tim.territory.assign'), [
            'area_id' => $this->kesambi->id,
            'cs_id'   => $this->csA->id,
        ]);
        $res2->assertRedirect()->assertSessionHas('success');

        // Verify CS A has 2 active wilayahs in pivot table
        $activeIds = $this->csA->fresh()->activeWilayahIds();
        $this->assertContains($this->kejaksan->id, $activeIds);
        $this->assertContains($this->kesambi->id, $activeIds);
    }

    public function test_rejects_duplicate_active_sales_for_same_area(): void
    {
        $this->actingAs($this->spv);

        // Assign Sales A to Kesambi
        $this->post(route('spv.tim.territory.assign'), [
            'area_id'  => $this->kesambi->id,
            'sales_id' => $this->salesA->id,
        ]);

        // Try to assign Sales B to Kesambi (should be rejected because Kesambi already has active Sales A)
        $res = $this->post(route('spv.tim.territory.assign'), [
            'area_id'  => $this->kesambi->id,
            'sales_id' => $this->salesB->id,
        ]);

        $res->assertRedirect();
        $res->assertSessionHas('error');
        $this->assertStringContainsString('sudah memiliki Sales aktif', session('error'));
    }

    public function test_rejects_duplicate_active_cs_for_same_area(): void
    {
        $this->actingAs($this->spv);

        // Assign CS A to Kesambi
        $this->post(route('spv.tim.territory.assign'), [
            'area_id' => $this->kesambi->id,
            'cs_id'   => $this->csA->id,
        ]);

        // Try to assign CS B to Kesambi (should be rejected because Kesambi already has active CS A)
        $res = $this->post(route('spv.tim.territory.assign'), [
            'area_id' => $this->kesambi->id,
            'cs_id'   => $this->csB->id,
        ]);

        $res->assertRedirect();
        $res->assertSessionHas('error');
        $this->assertStringContainsString('sudah memiliki CS aktif', session('error'));
    }

    public function test_auto_routing_prospek_to_active_sales_and_cs(): void
    {
        $this->actingAs($this->spv);

        // Assign Kesambi -> Sales A & CS A
        $this->post(route('spv.tim.territory.assign'), [
            'area_id'  => $this->kesambi->id,
            'sales_id' => $this->salesA->id,
            'cs_id'    => $this->csA->id,
        ]);

        // Create Prospek in Kesambi
        $prospek = Prospek::create([
            'name'        => 'Calon Maba Kesambi',
            'type'        => 'Individu',
            'pic'         => 'Budi Kesambi',
            'whatsapp'    => '081234567890',
            'status'      => 'COLD',
            'wilayah_id'  => $this->kesambi->id,
            'sales_id'    => $this->kesambi->activeSalesUser()?->id,
            'cs_id'       => $this->kesambi->activeCsUser()?->id,
            'owner_id'    => $this->kesambi->activeSalesUser()?->id ?? $this->spv->id,
        ]);

        $this->assertEquals($this->salesA->id, $prospek->sales_id);
        $this->assertEquals($this->csA->id, $prospek->cs_id);
    }

    public function test_spv_cross_scope_assignment_returns_403(): void
    {
        $this->actingAs($this->spv);

        // Attempt ID tampering: SPV tries to assign Kota Bandung (outside SPV's scope Kota Cirebon)
        $res = $this->post(route('spv.tim.territory.assign'), [
            'area_id'  => $this->kotaBandung->id,
            'sales_id' => $this->salesA->id,
        ]);

        $res->assertStatus(403);
    }

    public function test_deactivating_territory_preserves_history(): void
    {
        $this->actingAs($this->spv);

        // Assign Sales A to Kesambi
        $this->post(route('spv.tim.territory.assign'), [
            'area_id'  => $this->kesambi->id,
            'sales_id' => $this->salesA->id,
        ]);

        // Deactivate territory assignment
        $res = $this->delete(route('spv.tim.wilayah.deactivate', [
            'user'    => $this->salesA->id,
            'wilayah' => $this->kesambi->id,
        ]));

        $res->assertRedirect()->assertSessionHas('success');

        // History record must still exist in DB with is_active = false
        $record = DB::table('user_wilayah')
            ->where('user_id', $this->salesA->id)
            ->where('wilayah_id', $this->kesambi->id)
            ->first();

        $this->assertNotNull($record);
        $this->assertEquals(0, $record->is_active);
        $this->assertNotNull($record->deactivated_at);
    }

    public function test_cs_can_be_assigned_multiple_areas_in_single_form_submission(): void
    {
        $this->actingAs($this->spv);

        // Assign CS A to Kejaksan, Kesambi, and Harjamukti simultaneously
        $res = $this->post(route('spv.tim.territory.assign'), [
            'sales_id'      => $this->salesA->id,
            'sales_area_id' => $this->kejaksan->id,
            'cs_id'         => $this->csA->id,
            'cs_area_ids'   => [$this->kejaksan->id, $this->kesambi->id, $this->harjamukti->id],
        ]);

        $res->assertRedirect()->assertSessionHas('success');

        $activePivotIds = $this->csA->fresh()->activeWilayahes->pluck('id')->toArray();
        $this->assertCount(3, $activePivotIds);
        $this->assertContains($this->kejaksan->id, $activePivotIds);
        $this->assertContains($this->kesambi->id, $activePivotIds);
        $this->assertContains($this->harjamukti->id, $activePivotIds);

        // Check Sales A active area
        $salesActiveIds = $this->salesA->fresh()->activeWilayahes->pluck('id')->toArray();
        $this->assertContains($this->kejaksan->id, $salesActiveIds);
    }

    public function test_cs_area_unchecking_deactivates_removed_areas(): void
    {
        $this->actingAs($this->spv);

        // Initial assignment: CS A -> Kejaksan, Kesambi, Harjamukti
        $this->post(route('spv.tim.territory.assign'), [
            'cs_id'       => $this->csA->id,
            'cs_area_ids' => [$this->kejaksan->id, $this->kesambi->id, $this->harjamukti->id],
        ]);

        // Resubmit form for CS A with Harjamukti unchecked (only Kejaksan & Kesambi selected)
        $res = $this->post(route('spv.tim.territory.assign'), [
            'cs_id'       => $this->csA->id,
            'cs_area_ids' => [$this->kejaksan->id, $this->kesambi->id],
        ]);

        $res->assertRedirect()->assertSessionHas('success');

        $activePivotIds = $this->csA->fresh()->activeWilayahes->pluck('id')->toArray();
        $this->assertCount(2, $activePivotIds);
        $this->assertContains($this->kejaksan->id, $activePivotIds);
        $this->assertContains($this->kesambi->id, $activePivotIds);
        $this->assertNotContains($this->harjamukti->id, $activePivotIds);

        // Deactivated Harjamukti record still exists in DB
        $record = DB::table('user_wilayah')
            ->where('user_id', $this->csA->id)
            ->where('wilayah_id', $this->harjamukti->id)
            ->first();

        $this->assertNotNull($record);
        $this->assertEquals(0, $record->is_active);
        $this->assertNotNull($record->deactivated_at);
    }
}
