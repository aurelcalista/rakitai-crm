<?php

namespace Tests\Feature;

use App\Models\MasterData;
use App\Models\Prodi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProdiFakultasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed essential roles if needed
    }

    public function test_admin_can_view_prodi_page_with_fakultas_card()
    {
        $admin = User::factory()->create(['role' => 'Admin']);

        MasterData::create([
            'type'   => 'fakultas',
            'nama'   => 'Fakultas Teknologi Informasi',
            'kode'   => 'FTI',
            'status' => 'Aktif',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.prodi.index'));

        $response->assertStatus(200);
        $response->assertSee('Data Fakultas Universitas');
        $response->assertSee('Fakultas Teknologi Informasi');
        $response->assertSee('FTI');
        $response->assertSee('Kelola &amp; Tambah Fakultas', false);
    }

    public function test_admin_can_store_new_fakultas()
    {
        $admin = User::factory()->create(['role' => 'Admin']);

        $response = $this->actingAs($admin)->post(route('admin.prodi.fakultas.store'), [
            'nama'   => 'Fakultas Ekonomi dan Bisnis',
            'kode'   => 'FEB',
            'status' => 'Aktif',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('master_data', [
            'type'   => 'fakultas',
            'nama'   => 'Fakultas Ekonomi dan Bisnis',
            'kode'   => 'FEB',
            'status' => 'Aktif',
        ]);
    }

    public function test_admin_can_delete_unused_fakultas()
    {
        $admin = User::factory()->create(['role' => 'Admin']);

        $fakultas = MasterData::create([
            'type'   => 'fakultas',
            'nama'   => 'Fakultas Hukum',
            'kode'   => 'FH',
            'status' => 'Aktif',
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.prodi.fakultas.destroy', $fakultas->id));

        $response->assertRedirect();
        $this->assertDatabaseMissing('master_data', [
            'id' => $fakultas->id,
        ]);
    }

    public function test_admin_cannot_delete_fakultas_used_by_prodi()
    {
        $admin = User::factory()->create(['role' => 'Admin']);

        $fakultas = MasterData::create([
            'type'   => 'fakultas',
            'nama'   => 'Fakultas Ilmu Komputer',
            'kode'   => 'FILKOM',
            'status' => 'Aktif',
        ]);

        Prodi::create([
            'kode'     => 'IF-S1',
            'nama'     => 'Informatika',
            'fakultas' => 'Fakultas Ilmu Komputer',
            'jenjang'  => 'S1',
            'kuota'    => 50,
            'status'   => 'Aktif',
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.prodi.fakultas.destroy', $fakultas->id));

        $response->assertRedirect();
        $this->assertDatabaseHas('master_data', [
            'id' => $fakultas->id,
        ]);
    }
}
