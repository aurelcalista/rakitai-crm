<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmRoutesTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $sales;
    protected $spv;
    protected $cs;
    protected $hm;

    protected function setUp(): void
    {
        parent::setUp();
        \App\Models\TahunAkademik::updateOrCreate(['nama' => '2027/2028'], ['status' => 'Aktif']);
        $this->admin = User::where('role', 'Admin')->first() ?? User::factory()->create(['role' => 'Admin']);
        $this->sales = User::where('role', 'Sales')->first() ?? User::factory()->create(['role' => 'Sales']);
        $this->spv = User::where('role', 'SPV')->first() ?? User::factory()->create(['role' => 'SPV']);
        $this->cs = User::where('role', 'CS')->first() ?? User::factory()->create(['role' => 'CS']);
        $this->hm = User::where('role', 'HM')->first() ?? User::factory()->create(['role' => 'HM']);
    }

    public function test_root_redirects_to_dashboard(): void
    {
        $response = $this->actingAs($this->sales)->get('/');
        $response->assertRedirect(route('dashboard'));
    }

    public function test_login_page_is_accessible(): void
    {
        $response = $this->get(route('login'));
        $response->assertStatus(200);
    }

    public function test_all_dashboards_are_accessible(): void
    {
        $this->actingAs($this->sales)->get(route('dashboard.sales'))->assertStatus(200);
        $this->actingAs($this->cs)->get(route('dashboard.cs'))->assertStatus(200);
        $this->actingAs($this->spv)->get(route('dashboard.spv'))->assertStatus(200);
        $this->actingAs($this->hm)->get(route('dashboard.hm'))->assertStatus(200);
        $this->actingAs($this->admin)->get(route('dashboard.admin'))->assertStatus(200);
    }

    public function test_admin_modules_are_accessible(): void
    {
        $this->actingAs($this->admin)->get(route('admin.users.index'))->assertStatus(200);
        $this->actingAs($this->admin)->get(route('admin.master-data.index'))->assertStatus(200);
        $this->actingAs($this->admin)->get(route('admin.audit-logs.index'))->assertStatus(200);
        $this->actingAs($this->admin)->get(route('admin.settings.index'))->assertStatus(200);
    }

    public function test_crm_core_modules_are_accessible(): void
    {
        $prospek = \App\Models\Prospek::first();

        $this->actingAs($this->sales)->get(route('prospek.index'))->assertStatus(200);
        if ($prospek) {
            $this->actingAs($this->sales)->get(route('prospek.show', $prospek->id))->assertStatus(200);
        }
        $this->actingAs($this->sales)->get(route('kunjungan.index'))->assertStatus(200);
        $this->actingAs($this->sales)->get(route('follow-up.index'))->assertStatus(200);
        $this->actingAs($this->sales)->get(route('pipeline.index'))->assertStatus(200);
        $this->actingAs($this->sales)->get(route('performa.index'))->assertStatus(200);
        $this->actingAs($this->sales)->get(route('laporan.index'))->assertStatus(200);
        $this->actingAs($this->sales)->get(route('profil.index'))->assertStatus(200);
    }
}
