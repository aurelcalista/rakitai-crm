<?php

namespace Tests\Feature;

use App\Models\TahunAkademik;
use App\Models\User;
use App\Models\Wilayah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminHmWilayahTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $hm;
    private User $spv;
    private Wilayah $kotaCirebon;
    private Wilayah $kabCirebon;

    protected function setUp(): void
    {
        parent::setUp();

        TahunAkademik::create([
            'nama' => '2027/2028',
            'is_active' => true,
            'status' => 'Aktif',
            'tanggal_mulai' => '2027-09-01',
            'tanggal_selesai' => '2028-08-31',
        ]);

        $this->admin = User::factory()->create([
            'role'   => 'Admin',
            'status' => 'Aktif',
        ]);

        $this->hm = User::factory()->create([
            'role'   => 'HM',
            'status' => 'Aktif',
            'wilayah_id' => null,
        ]);

        $this->spv = User::factory()->create([
            'role'   => 'SPV',
            'status' => 'Aktif',
        ]);

        $this->kotaCirebon = Wilayah::create([
            'kode'   => 'W-CRB',
            'nama'   => 'Kota Cirebon',
            'level'  => 'Kota/Kabupaten',
            'status' => 'Aktif',
            'parent_id' => null,
        ]);

        $this->kabCirebon = Wilayah::create([
            'kode'   => 'W-KAB-CRB',
            'nama'   => 'Kabupaten Cirebon',
            'level'  => 'Kota/Kabupaten',
            'status' => 'Aktif',
            'parent_id' => null,
        ]);
    }

    public function test_admin_can_view_hm_wilayah_index_page()
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('admin.hm-wilayah.index'));

        $response->assertStatus(200);
        $response->assertSee('Penugasan Wilayah Head Marketing (HM)');
        $response->assertSee('Kota Cirebon');
        $response->assertSee('Kabupaten Cirebon');
    }

    public function test_non_admin_cannot_access_admin_hm_wilayah_page()
    {
        $this->actingAs($this->hm);

        $response = $this->get(route('admin.hm-wilayah.index'));

        // Non-admin is gracefully redirected to their dashboard by RoleMiddleware
        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_admin_can_assign_hm_to_kota_wilayah()
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('admin.hm-wilayah.assign'), [
            'hm_id'      => $this->hm->id,
            'wilayah_id' => $this->kotaCirebon->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->hm->refresh();
        $this->assertEquals($this->kotaCirebon->id, $this->hm->wilayah_id);
    }

    public function test_admin_can_unassign_hm_from_wilayah()
    {
        $this->actingAs($this->admin);

        $this->hm->update(['wilayah_id' => $this->kotaCirebon->id]);

        $response = $this->post(route('admin.hm-wilayah.unassign', $this->hm->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->hm->refresh();
        $this->assertNull($this->hm->wilayah_id);
    }

    public function test_admin_reassigning_hm_updates_correctly()
    {
        $this->actingAs($this->admin);

        $this->hm->update(['wilayah_id' => $this->kotaCirebon->id]);

        // Reassign to Kabupaten Cirebon
        $response = $this->post(route('admin.hm-wilayah.assign'), [
            'hm_id'      => $this->hm->id,
            'wilayah_id' => $this->kabCirebon->id,
        ]);

        $response->assertRedirect();

        $this->hm->refresh();
        $this->assertEquals($this->kabCirebon->id, $this->hm->wilayah_id);
    }
}
