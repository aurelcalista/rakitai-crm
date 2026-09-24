<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wilayah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class HmCsManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed basic wilayah structure
        $this->kotaCirebon = Wilayah::create([
            'kode' => 'CRB-01',
            'nama' => 'Kota Cirebon',
            'level' => 'Kota/Kabupaten',
            'status' => 'Aktif',
        ]);

        $this->kecKesambi = Wilayah::create([
            'kode' => 'CRB-KSB-01',
            'nama' => 'Kesambi',
            'level' => 'Kecamatan',
            'parent_id' => $this->kotaCirebon->id,
            'status' => 'Aktif',
        ]);

        $this->kecKejaksan = Wilayah::create([
            'kode' => 'CRB-KJS-01',
            'nama' => 'Kejaksan',
            'level' => 'Kecamatan',
            'parent_id' => $this->kotaCirebon->id,
            'status' => 'Aktif',
        ]);

        // Outside Wilayah Scope
        $this->kotaKuningan = Wilayah::create([
            'kode' => 'KNG-01',
            'nama' => 'Kabupaten Kuningan',
            'level' => 'Kota/Kabupaten',
            'status' => 'Aktif',
        ]);

        $this->kecCilimus = Wilayah::create([
            'kode' => 'KNG-CLM-01',
            'nama' => 'Cilimus',
            'level' => 'Kecamatan',
            'parent_id' => $this->kotaKuningan->id,
            'status' => 'Aktif',
        ]);

        // Users
        $this->hmUser = User::create([
            'name' => 'HM Andi',
            'email' => 'hm.andi@cic.ac.id',
            'phone' => '08111111111',
            'password' => Hash::make('password'),
            'role' => 'HM',
            'status' => 'Aktif',
            'wilayah_id' => $this->kotaCirebon->id,
        ]);

        $this->spvUser = User::create([
            'name' => 'SPV Budi',
            'email' => 'spv.budi@cic.ac.id',
            'phone' => '08222222222',
            'password' => Hash::make('password'),
            'role' => 'SPV',
            'status' => 'Aktif',
            'wilayah_id' => $this->kotaCirebon->id,
        ]);

        $this->salesUser = User::create([
            'name' => 'Sales Rina',
            'email' => 'sales.rina@cic.ac.id',
            'phone' => '08333333333',
            'password' => Hash::make('password'),
            'role' => 'Sales',
            'status' => 'Aktif',
            'supervisor_id' => $this->spvUser->id,
            'wilayah_id' => $this->kecKesambi->id,
        ]);

        $this->csUser = User::create([
            'name' => 'CS Siti',
            'email' => 'cs.siti@cic.ac.id',
            'phone' => '08444444444',
            'password' => Hash::make('password'),
            'role' => 'CS',
            'status' => 'Aktif',
            'wilayah_id' => $this->kotaCirebon->id,
        ]);
    }

    public function test_hm_can_access_kelola_cs_page_and_see_cs_list()
    {
        $response = $this->actingAs($this->hmUser)->get(route('hm.cs.index'));

        $response->assertStatus(200);
        $response->assertSee('Kelola CS (Customer Service)');
        $response->assertSee('CS Siti');
    }

    public function test_hm_can_create_cs_with_auto_role_cs_jabatan_cs_and_cic_email()
    {
        $response = $this->actingAs($this->hmUser)->post(route('hm.cs.store'), [
            'name' => 'CS Dewi',
            'email_username' => 'dewi.cs',
            'phone' => '08555555555',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'cs_area_ids' => [$this->kecKesambi->id, $this->kecKejaksan->id],
        ]);

        $response->assertRedirect(route('hm.cs.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'name' => 'CS Dewi',
            'email' => 'dewi.cs@cic.ac.id',
            'role' => 'CS',
            'jabatan' => 'CS',
            'status' => 'Aktif',
        ]);

        $createdCs = User::where('email', 'dewi.cs@cic.ac.id')->first();
        $this->assertNotNull($createdCs);

        $activeAreas = $createdCs->activeWilayahes->pluck('id')->toArray();
        $this->assertContains($this->kecKesambi->id, $activeAreas);
        $this->assertContains($this->kecKejaksan->id, $activeAreas);
    }

    public function test_hm_cs_creation_rejects_non_cic_email_domain()
    {
        $response = $this->actingAs($this->hmUser)->post(route('hm.cs.store'), [
            'name' => 'CS Invalid',
            'email' => 'cs.invalid@gmail.com',
            'phone' => '08555555555',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_hm_cannot_assign_cs_to_area_outside_hm_scope_returns_403()
    {
        $response = $this->actingAs($this->hmUser)->post(route('hm.cs.store'), [
            'name' => 'CS Out',
            'email_username' => 'cs.out',
            'phone' => '08555555555',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'cs_area_ids' => [$this->kecCilimus->id], // Cilimus is under Kuningan (Outside HM Andi's scope Kota Cirebon)
        ]);

        $response->assertStatus(403);
    }

    public function test_hm_can_update_cs_multi_wilayah_assignment()
    {
        $response = $this->actingAs($this->hmUser)->post(route('hm.cs.territory.assign'), [
            'cs_id' => $this->csUser->id,
            'cs_area_ids' => [$this->kecKesambi->id, $this->kecKejaksan->id],
        ]);

        $response->assertRedirect(route('hm.cs.index'));
        $response->assertSessionHas('success');

        $activeAreas = $this->csUser->fresh()->activeWilayahes->pluck('id')->toArray();
        $this->assertContains($this->kecKesambi->id, $activeAreas);
        $this->assertContains($this->kecKejaksan->id, $activeAreas);
    }

    public function test_spv_cannot_create_cs_and_returns_403()
    {
        $response = $this->actingAs($this->spvUser)->post(route('hm.cs.store'), [
            'name' => 'CS Fake',
            'email_username' => 'cs.fake',
            'phone' => '08999999999',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertStatus(403);
    }

    public function test_spv_cannot_assign_cs_territory_and_returns_403()
    {
        $response = $this->actingAs($this->spvUser)->post(route('spv.tim.territory.assign'), [
            'cs_id' => $this->csUser->id,
            'cs_area_ids' => [$this->kecKesambi->id],
        ]);

        $response->assertStatus(403);
    }

    public function test_spv_team_directory_displays_sales_only_excluding_cs()
    {
        $response = $this->actingAs($this->spvUser)->get(route('spv.tim.index'));

        $response->assertStatus(200);
        $response->assertSee('Sales Rina');
        $response->assertDontSee('CS Siti');
    }
}
