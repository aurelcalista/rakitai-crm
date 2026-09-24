<?php

namespace Tests\Feature;

use App\Models\TahunAkademik;
use App\Models\Target;
use App\Models\User;
use App\Models\Wilayah;
use App\Services\AkademikService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessStructureTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $hm;
    protected User $spv;
    protected User $salesInScope;
    protected User $salesInScope2;
    protected User $salesOutScope;
    protected User $csInScope;
    
    protected Wilayah $kotaCirebon;
    protected Wilayah $kecKesambi;
    protected Wilayah $kecHarjamukti;
    protected Wilayah $kotaLain;
    
    protected TahunAkademik $ta;

    protected function setUp(): void
    {
        parent::setUp();

        // TA
        $this->ta = TahunAkademik::create([
            'nama'   => '2026/2027',
            'status' => 'Aktif',
        ]);
        AkademikService::flushCache();

        // Wilayah
        $this->kotaCirebon = Wilayah::create(['kode' => 'W-CRB', 'nama' => 'Kota Cirebon', 'level' => 'Kota/Kabupaten', 'status' => 'Aktif']);
        $this->kecKesambi = Wilayah::create(['kode' => 'W-KSB', 'nama' => 'Kesambi', 'level' => 'Kecamatan', 'parent_id' => $this->kotaCirebon->id, 'status' => 'Aktif']);
        $this->kecHarjamukti = Wilayah::create(['kode' => 'W-HJM', 'nama' => 'Harjamukti', 'level' => 'Kecamatan', 'parent_id' => $this->kotaCirebon->id, 'status' => 'Aktif']);
        $this->kotaLain = Wilayah::create(['kode' => 'W-X', 'nama' => 'Kota Lain', 'level' => 'Kota/Kabupaten', 'status' => 'Aktif']);

        // Users
        $this->admin = User::factory()->create(['role' => 'Admin']);
        $this->hm = User::factory()->create(['role' => 'HM', 'wilayah_id' => $this->kotaCirebon->id]);
        $this->spv = User::factory()->create(['role' => 'SPV', 'wilayah_id' => $this->kotaCirebon->id]);
        
        $this->salesInScope = User::factory()->create(['role' => 'Sales', 'wilayah_id' => $this->kecKesambi->id]);
        $this->salesInScope2 = User::factory()->create(['role' => 'Sales', 'wilayah_id' => $this->kecHarjamukti->id]);
        $this->csInScope = User::factory()->create(['role' => 'CS', 'wilayah_id' => $this->kecHarjamukti->id]);
        $this->salesOutScope = User::factory()->create(['role' => 'Sales', 'wilayah_id' => $this->kotaLain->id]);
    }

    public function test_A_admin_membuat_wilayah()
    {
        $this->actingAs($this->admin);
        $res = $this->post(route('admin.wilayah.store'), [
            'kode'   => 'W-KNG',
            'nama'   => 'Kuningan',
            'status' => 'Aktif',
        ]);
        $res->assertRedirect();
        $this->assertDatabaseHas('wilayahs', ['kode' => 'W-KNG']);
    }

    public function test_B_hm_assign_spv()
    {
        $this->actingAs($this->hm);
        
        $res = $this->post(route('hm.wilayah.assignSpv', $this->kotaCirebon->id), [
            'spv_id' => $this->spv->id,
        ]);
        
        $res->assertRedirect();
        $this->assertDatabaseHas('wilayahs', [
            'id' => $this->kotaCirebon->id,
        ]);
        
        // Cek SPV sekarang ditugaskan di wilayah HM
        $this->spv->refresh();
        $this->assertEquals($this->kotaCirebon->id, $this->spv->wilayah_id);
    }

    public function test_C_hm_set_target()
    {
        $this->actingAs($this->hm);
        $res = $this->post(route('hm.wilayah.setTarget', $this->kotaCirebon->id), [
            'spv_id'          => $this->spv->id,
            'tipe_periode'    => 'Mingguan',
            'tanggal_mulai'   => now()->startOfWeek()->toDateString(),
            'tanggal_selesai' => now()->endOfWeek()->toDateString(),
            'target_kontak'   => 100,
            'target_formulir' => 50,
            'target_lunas'    => 20,
        ]);
        
        $res->assertRedirect();
        
        $this->assertDatabaseHas('targets', [
            'target_type'   => 'Wilayah',
            'spv_id'        => $this->spv->id,
            'target_kontak' => 100,
        ]);
    }

    public function test_D_hm_tidak_mengelola_sales_cs()
    {
        $this->actingAs($this->hm);
        
        // HM mencoba assign Sales melalui endpoint SPV -> harus gagal (redirect/403)
        $res = $this->post(route('spv.tim.assign'), [
            'user_id' => $this->salesInScope->id,
            'area_id' => $this->kecKesambi->id,
        ]);
        
        // RoleMiddleware mengembalikan 403 Forbidden karena non-GET request & not authorized sbg SPV
        $res->assertStatus(403);
        
        // SPV juga punya endpoint alokasi target, HM harusnya tidak bisa
        $res2 = $this->post(route('spv.performa.alokasi'), [
            'sales_id' => $this->salesInScope->id,
        ]);
        $res2->assertStatus(403);
    }

    public function test_E_spv_assign_sales()
    {
        $this->actingAs($this->spv);
        $res = $this->post(route('spv.tim.assign'), [
            'user_id' => $this->salesInScope->id,
            'area_id' => $this->kecKesambi->id,
        ]);
        
        $res->assertRedirect();
        $this->salesInScope->refresh();
        $this->assertEquals($this->spv->id, $this->salesInScope->supervisor_id);
    }

    public function test_F_spv_assign_cs_returns_403()
    {
        $this->actingAs($this->spv);
        $res = $this->post(route('spv.tim.assign'), [
            'user_id' => $this->csInScope->id,
            'area_id' => $this->kecHarjamukti->id,
        ]);
        
        $res->assertStatus(403);
    }

    public function test_G_descendant_scope()
    {
        // Secara logic policy/model
        // $this->salesInScope->wilayah_id adalah kecKesambi. kecKesambi isDescendantOf kotaCirebon.
        $this->assertTrue($this->salesInScope->isWithinWilayahScope($this->kotaCirebon->id));
        
        // Verifikasi dengan route
        $this->actingAs($this->spv);
        $res = $this->post(route('spv.tim.assign'), [
            'user_id' => $this->salesInScope->id,
            'area_id' => $this->kecKesambi->id,
        ]);
        $res->assertRedirect(); // Pass
    }

    public function test_H_outside_scope()
    {
        $this->actingAs($this->spv);
        
        // SPV Cirebon mencoba assign Sales di Kota Lain
        $res = $this->post(route('spv.tim.assign'), [
            'user_id' => $this->salesOutScope->id,
            'area_id' => $this->kotaLain->id,
        ]);
        
        // Harus 403 Forbidden
        $res->assertStatus(403);
    }

    public function test_I_target_allocation()
    {
        // 1. HM set Target Wilayah = 100
        $this->actingAs($this->hm);
        $this->post(route('hm.wilayah.setTarget', $this->kotaCirebon->id), [
            'spv_id'          => $this->spv->id,
            'tipe_periode'    => 'Mingguan',
            'tanggal_mulai'   => now()->startOfWeek()->toDateString(),
            'tanggal_selesai' => now()->endOfWeek()->toDateString(),
            'target_kontak'   => 100,
            'target_formulir' => 50,
            'target_lunas'    => 20,
        ]);
        
        // 2. Sales in scope harus supervisor_id = SPV
        $this->salesInScope->supervisor_id = $this->spv->id;
        $this->salesInScope->save();
        $this->salesInScope2->supervisor_id = $this->spv->id;
        $this->salesInScope2->save();
        
        // 3. SPV alokasi ke sales
        $this->actingAs($this->spv);
        
        // Sales A = 30
        $res1 = $this->post(route('spv.performa.alokasi'), [
            'sales_id' => $this->salesInScope->id,
            'tipe_periode' => 'Mingguan',
            'tanggal_mulai' => now()->startOfWeek()->toDateString(),
            'tanggal_selesai' => now()->endOfWeek()->toDateString(),
            'target_kontak' => 30,
            'target_formulir' => 15,
            'target_lunas' => 5,
        ]);
        $res1->assertSessionHasNoErrors();
        
        // Sales B = 70 (Total 100 -> valid)
        $res2 = $this->post(route('spv.performa.alokasi'), [
            'sales_id' => $this->salesInScope2->id,
            'tipe_periode' => 'Mingguan',
            'tanggal_mulai' => now()->startOfWeek()->toDateString(),
            'tanggal_selesai' => now()->endOfWeek()->toDateString(),
            'target_kontak' => 70,
            'target_formulir' => 35,
            'target_lunas' => 15,
        ]);
        $res2->assertSessionHasNoErrors();
    }

    public function test_J_over_allocation()
    {
        // 1. HM set Target Wilayah = 100
        $this->actingAs($this->hm);
        $this->post(route('hm.wilayah.setTarget', $this->kotaCirebon->id), [
            'spv_id'          => $this->spv->id,
            'tipe_periode'    => 'Mingguan',
            'tanggal_mulai'   => now()->startOfWeek()->toDateString(),
            'tanggal_selesai' => now()->endOfWeek()->toDateString(),
            'target_kontak'   => 100,
            'target_formulir' => 50,
            'target_lunas'    => 20,
        ]);
        
        $this->salesInScope->supervisor_id = $this->spv->id;
        $this->salesInScope->save();
        $this->salesInScope2->supervisor_id = $this->spv->id;
        $this->salesInScope2->save();
        
        $this->actingAs($this->spv);
        
        // Sales A = 80
        $res1 = $this->post(route('spv.performa.alokasi'), [
            'sales_id' => $this->salesInScope->id,
            'tipe_periode' => 'Mingguan',
            'tanggal_mulai' => now()->startOfWeek()->toDateString(),
            'tanggal_selesai' => now()->endOfWeek()->toDateString(),
            'target_kontak' => 80,
            'target_formulir' => 40,
            'target_lunas' => 10,
        ]);
        $res1->assertSessionHasNoErrors();
        
        // Sales B = 30 (Total 110 > 100 -> INVALID)
        $res2 = $this->post(route('spv.performa.alokasi'), [
            'sales_id' => $this->salesInScope2->id,
            'tipe_periode' => 'Mingguan',
            'tanggal_mulai' => now()->startOfWeek()->toDateString(),
            'tanggal_selesai' => now()->endOfWeek()->toDateString(),
            'target_kontak' => 30,
            'target_formulir' => 20,
            'target_lunas' => 15,
        ]);
        
        $res2->assertSessionHasErrors(['target_lunas']);
    }

    public function test_K_achievement_boleh_melebihi_target()
    {
        $this->salesInScope->update(['supervisor_id' => $this->spv->id]);

        // Buat Target Individual 30 untuk sales
        $target = Target::create([
            'target_type'     => 'Individual',
            'sales_id'        => $this->salesInScope->id,
            'spv_id'          => $this->spv->id,
            'wilayah_id'      => $this->salesInScope->wilayah_id,
            'academic_year_id'=> $this->ta->id,
            'tahun_akademik'  => $this->ta->nama,
            'tipe_periode'    => 'Mingguan',
            'tanggal_mulai'   => now()->startOfWeek()->toDateString(),
            'tanggal_selesai' => now()->endOfWeek()->toDateString(),
            'target_kontak'   => 30,
            'target_formulir' => 15,
            'target_lunas'    => 5,
            'status'          => 'Aktif',
            'allocated_by'    => $this->spv->id,
        ]);

        // Buat 40 prospek LUNAS untuk Sales -> melebihi target 30
        for ($i = 0; $i < 40; $i++) {
            \App\Models\Prospek::create([
                'name'     => 'Test ' . $i,
                'type'     => 'B2C',
                'whatsapp' => '08000' . $i,
                'source'   => 'WhatsApp',
                'sales_id' => $this->salesInScope->id,
                'status'   => 'LUNAS',
            ]);
        }

        // Achievement dihitung dari jumlah Prospek LUNAS Sales (bukan dari field di Target)
        $achievement = \App\Models\Prospek::where('sales_id', $this->salesInScope->id)
            ->where('status', 'LUNAS')
            ->count();

        // Target yang ditetapkan = 5 (target_lunas)
        $targetLunas = $target->target_lunas;

        // Achievement 40 > target 5 — ini VALID (tidak ada validasi yang memblokir achievement)
        $this->assertEquals(40, $achievement);
        $this->assertEquals(5, $targetLunas);
        $this->assertTrue($achievement > $targetLunas, 'Achievement boleh melebihi target tanpa error');

        // Pastikan over-achievement TIDAK mengakibatkan error di SPV alokasi target baru
        // (Karena validasi hanya berlaku untuk alokasi, bukan realisasi)
        $this->actingAs($this->spv);
        $res = $this->post(route('spv.performa.alokasi'), [
            'sales_id'        => $this->salesInScope->id,
            'tipe_periode'    => 'Bulanan',
            'tanggal_mulai'   => now()->startOfMonth()->toDateString(),
            'tanggal_selesai' => now()->endOfMonth()->toDateString(),
            'target_kontak'   => 10,
            'target_formulir' => 5,
            'target_lunas'    => 3,
        ]);
        // Tidak ada error session -> alokasi baru tetap diterima meski achievement sebelumnya melebihi target
        $res->assertSessionHasNoErrors();
    }

    public function test_L_hm_can_edit_existing_target_wilayah_and_change_spv()
    {
        $this->actingAs($this->hm);

        // 1. Initial Target creation
        $res = $this->post(route('hm.wilayah.setTarget', $this->kotaCirebon->id), [
            'spv_id'          => $this->spv->id,
            'tipe_periode'    => 'Bulanan',
            'tanggal_mulai'   => '2026-09-01',
            'tanggal_selesai' => '2026-09-30',
            'target_kontak'   => 500,
            'target_formulir' => 200,
            'target_lunas'    => 50,
        ]);
        $res->assertRedirect();

        $initialTarget = \App\Models\Target::where('target_type', 'Wilayah')
            ->where('wilayah_id', $this->kotaCirebon->id)
            ->latest('id')
            ->first();
        $this->assertNotNull($initialTarget);
        $this->assertEquals($this->spv->id, $initialTarget->spv_id);
        $this->assertEquals(500, $initialTarget->target_kontak);

        // 2. Create another SPV candidate within scope
        $spv2 = \App\Models\User::factory()->create([
            'role' => 'SPV',
            'status' => 'Aktif',
            'wilayah_id' => $this->kotaCirebon->id,
        ]);

        // 3. Edit the Target Wilayah with target_id and change SPV to spv2
        $res2 = $this->post(route('hm.wilayah.setTarget', $this->kotaCirebon->id), [
            'target_id'       => $initialTarget->id,
            'spv_id'          => $spv2->id,
            'tipe_periode'    => 'Bulanan',
            'tanggal_mulai'   => '2026-09-01',
            'tanggal_selesai' => '2026-09-30',
            'target_kontak'   => 1200,
            'target_formulir' => 400,
            'target_lunas'    => 150,
        ]);
        $res2->assertRedirect();

        $initialTarget->refresh();
        $this->assertEquals($spv2->id, $initialTarget->spv_id);
        $this->assertEquals(1200, $initialTarget->target_kontak);
        $this->assertEquals(400, $initialTarget->target_formulir);
        $this->assertEquals(150, $initialTarget->target_lunas);

        $spv2->refresh();
        $this->assertEquals($this->kotaCirebon->id, $spv2->wilayah_id);
    }
}
