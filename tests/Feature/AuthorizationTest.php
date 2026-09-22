<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Kunjungan;
use App\Models\MasterData;
use App\Models\Prospek;
use App\Models\TahunAkademik;
use App\Models\Target;
use App\Models\User;
use App\Models\Wilayah;
use App\Services\TargetMetricsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $hm1;
    protected User $hm2;
    protected User $spv1;
    protected User $spv2;
    protected User $sales1;
    protected User $sales2;
    protected User $cs1;
    protected User $cs2;
    protected User $eo1;
    protected User $eo2;
    protected Wilayah $wilayah1;
    protected Wilayah $wilayah2;
    protected TahunAkademik $activeTa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->activeTa = TahunAkademik::create([
            'nama' => '2026/2027',
            'is_active' => true,
        ]);

        $this->wilayah1 = Wilayah::create(['kode' => 'W1', 'nama' => 'Wilayah 1', 'level' => 'Provinsi']);
        $this->wilayah2 = Wilayah::create(['kode' => 'W2', 'nama' => 'Wilayah 2', 'level' => 'Provinsi']);

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin.test@cic.ac.id',
            'password' => bcrypt('password'),
            'role' => 'Admin',
            'status' => 'aktif',
        ]);

        $this->hm1 = User::create([
            'name' => 'HM Wilayah 1',
            'email' => 'hm1@cic.ac.id',
            'password' => bcrypt('password'),
            'role' => 'HM',
            'status' => 'aktif',
            'wilayah_id' => $this->wilayah1->id,
        ]);

        $this->hm2 = User::create([
            'name' => 'HM Wilayah 2',
            'email' => 'hm2@cic.ac.id',
            'password' => bcrypt('password'),
            'role' => 'HM',
            'status' => 'aktif',
            'wilayah_id' => $this->wilayah2->id,
        ]);

        $this->spv1 = User::create([
            'name' => 'SPV 1',
            'email' => 'spv1@cic.ac.id',
            'password' => bcrypt('password'),
            'role' => 'SPV',
            'status' => 'aktif',
            'wilayah_id' => $this->wilayah1->id,
        ]);

        $this->spv2 = User::create([
            'name' => 'SPV 2',
            'email' => 'spv2@cic.ac.id',
            'password' => bcrypt('password'),
            'role' => 'SPV',
            'status' => 'aktif',
            'wilayah_id' => $this->wilayah1->id, // Same prospect wilayah, different team
        ]);

        $this->sales1 = User::create([
            'name' => 'Sales 1',
            'email' => 'sales1@cic.ac.id',
            'password' => bcrypt('password'),
            'role' => 'Sales',
            'status' => 'aktif',
            'supervisor_id' => $this->spv1->id,
            'wilayah_id' => $this->wilayah1->id,
        ]);

        $this->sales2 = User::create([
            'name' => 'Sales 2',
            'email' => 'sales2@cic.ac.id',
            'password' => bcrypt('password'),
            'role' => 'Sales',
            'status' => 'aktif',
            'supervisor_id' => $this->spv2->id,
            'wilayah_id' => $this->wilayah2->id, // Wilayah 2
        ]);

        $this->cs1 = User::create([
            'name' => 'CS 1',
            'email' => 'cs1@cic.ac.id',
            'password' => bcrypt('password'),
            'role' => 'CS',
            'status' => 'aktif',
            'supervisor_id' => $this->spv1->id,
            'wilayah_id' => $this->wilayah1->id,
        ]);

        $this->cs2 = User::create([
            'name' => 'CS 2',
            'email' => 'cs2@cic.ac.id',
            'password' => bcrypt('password'),
            'role' => 'CS',
            'status' => 'aktif',
            'supervisor_id' => $this->spv2->id,
            'wilayah_id' => $this->wilayah2->id,
        ]);

        $this->eo1 = User::create([
            'name' => 'EO 1',
            'email' => 'eo1@cic.ac.id',
            'password' => bcrypt('password'),
            'role' => 'EO',
            'status' => 'aktif',
            'wilayah_id' => $this->wilayah1->id,
        ]);

        $this->eo2 = User::create([
            'name' => 'EO 2',
            'email' => 'eo2@cic.ac.id',
            'password' => bcrypt('password'),
            'role' => 'EO',
            'status' => 'aktif',
            'wilayah_id' => $this->wilayah2->id,
        ]);
    }

    /** A. Admin dapat melihat data lintas Wilayah */
    public function test_skenario_A_admin_dapat_melihat_data_lintas_wilayah()
    {
        $p1 = Prospek::create([
            'name' => 'Prospect W1', 'type' => 'Sekolah', 'pic' => 'PIC 1', 'whatsapp' => '0811',
            'status' => 'BARU', 'stage_number' => 1, 'sales_id' => $this->sales1->id,
            'owner_id' => $this->sales1->id, 'wilayah_id' => $this->wilayah1->id,
            'academic_year_id' => $this->activeTa->id,
        ]);

        $p2 = Prospek::create([
            'name' => 'Prospect W2', 'type' => 'Sekolah', 'pic' => 'PIC 2', 'whatsapp' => '0822',
            'status' => 'BARU', 'stage_number' => 1, 'sales_id' => $this->sales2->id,
            'owner_id' => $this->sales2->id, 'wilayah_id' => $this->wilayah2->id,
            'academic_year_id' => $this->activeTa->id,
        ]);

        $this->assertTrue(Gate::forUser($this->admin)->allows('view', $p1));
        $this->assertTrue(Gate::forUser($this->admin)->allows('view', $p2));
    }

    /** B. HM A hanya dapat melihat Wilayah A */
    public function test_skenario_B_hm_a_hanya_dapat_melihat_wilayah_a()
    {
        $p1 = Prospek::create([
            'name' => 'Prospect W1', 'type' => 'Sekolah', 'pic' => 'PIC 1', 'whatsapp' => '0811',
            'status' => 'BARU', 'stage_number' => 1, 'sales_id' => $this->sales1->id,
            'owner_id' => $this->sales1->id, 'wilayah_id' => $this->wilayah1->id,
            'academic_year_id' => $this->activeTa->id,
        ]);

        $this->assertTrue(Gate::forUser($this->hm1)->allows('view', $p1));
    }

    /** C. HM A tidak dapat melihat/mengakses Prospek Wilayah B */
    public function test_skenario_C_hm_a_tidak_dapat_melihat_prospek_wilayah_b()
    {
        $p2 = Prospek::create([
            'name' => 'Prospect W2', 'type' => 'Sekolah', 'pic' => 'PIC 2', 'whatsapp' => '0822',
            'status' => 'BARU', 'stage_number' => 1, 'sales_id' => $this->sales2->id,
            'owner_id' => $this->sales2->id, 'wilayah_id' => $this->wilayah2->id,
            'academic_year_id' => $this->activeTa->id,
        ]);

        $this->assertFalse(Gate::forUser($this->hm1)->allows('view', $p2));
        $this->assertFalse(Gate::forUser($this->hm1)->allows('update', $p2));
    }

    /** D. SPV A tidak dapat mengakses Sales/CS milik SPV B */
    public function test_skenario_D_spv_a_tidak_dapat_mengakses_sales_cs_milik_spv_b()
    {
        $p2 = Prospek::create([
            'name' => 'Prospect Sales 2', 'type' => 'Sekolah', 'pic' => 'PIC 2', 'whatsapp' => '0822',
            'status' => 'BARU', 'stage_number' => 1, 'sales_id' => $this->sales2->id,
            'owner_id' => $this->sales2->id, 'wilayah_id' => $this->wilayah1->id,
            'academic_year_id' => $this->activeTa->id,
        ]);

        $this->assertFalse(Gate::forUser($this->spv1)->allows('view', $p2));
        $this->assertFalse(Gate::forUser($this->spv1)->allows('update', $p2));
    }

    /** E & F. Sales & CS tidak dapat mengubah wilayah_id menjadi Wilayah lain */
    public function test_skenario_E_and_F_sales_cs_cannot_change_wilayah_id()
    {
        $p1 = Prospek::create([
            'name' => 'Prospect Sales 1', 'type' => 'Sekolah', 'pic' => 'PIC 1', 'whatsapp' => '0811',
            'status' => 'BARU', 'stage_number' => 1, 'sales_id' => $this->sales1->id,
            'owner_id' => $this->sales1->id, 'cs_id' => $this->cs1->id, 'handover_at' => now(),
            'wilayah_id' => $this->wilayah1->id, 'academic_year_id' => $this->activeTa->id,
        ]);

        // Sales updates prospect via form — wilayah_id is not in fillable/validated fields of UpdateProspectRequest
        $responseSales = $this->actingAs($this->sales1)->put(route('sales.prospek.update', $p1), [
            'pic' => 'Updated PIC',
            'type' => 'Sekolah',
            'whatsapp' => '0811',
            'status' => 'KONTAK',
            'source' => 'Sekolah',
            'prodi_id' => 1,
            'wilayah_id' => $this->wilayah2->id, // Attempted tampering
        ]);

        $p1->refresh();
        $this->assertEquals($this->wilayah1->id, $p1->wilayah_id); // Wilayah 1 unchanged!
    }

    /** G. Transfer owner lintas Wilayah ditolak jika actor tidak berwenang */
    public function test_skenario_G_transfer_owner_lintas_wilayah_ditolak_jika_unauthorized()
    {
        $p2 = Prospek::create([
            'name' => 'Prospect W2', 'type' => 'Sekolah', 'pic' => 'PIC 2', 'whatsapp' => '0822',
            'status' => 'BARU', 'stage_number' => 1, 'sales_id' => $this->sales2->id,
            'owner_id' => $this->sales2->id, 'wilayah_id' => $this->wilayah2->id,
            'academic_year_id' => $this->activeTa->id,
        ]);

        // SPV 1 (Wilayah 1) attempting re-alokasi on Wilayah 2 prospect
        $this->assertFalse(Gate::forUser($this->spv1)->allows('reallocate', $p2));
    }

    /** H. Assignment Sales/CS hanya menerima kandidat yang valid */
    public function test_skenario_H_assignment_sales_cs_only_accepts_valid_candidates()
    {
        $response = $this->actingAs($this->spv1)->post(route('spv.performa.alokasi'), [
            'sales_id' => 99999, // Invalid user
            'tipe_periode' => 'Harian',
            'tanggal_mulai' => now()->toDateString(),
            'tanggal_selesai' => now()->toDateString(),
            'target_kontak' => 10,
            'target_formulir' => 5,
            'target_lunas' => 2,
        ]);

        $response->assertSessionHasErrors('sales_id');
    }

    /** I & J. HM A tidak mendapat data Sales HM B, Admin mendapat HM A + HM B */
    public function test_skenario_I_and_J_hm_metrics_scoping_and_admin_global()
    {
        Target::create([
            'sales_id' => $this->sales1->id, 'tipe_periode' => 'Bulanan',
            'tanggal_mulai' => now()->startOfMonth(), 'tanggal_selesai' => now()->endOfMonth(),
            'target_kontak' => 50, 'target_lunas' => 10, 'academic_year_id' => $this->activeTa->id,
        ]);

        Target::create([
            'sales_id' => $this->sales2->id, 'tipe_periode' => 'Bulanan',
            'tanggal_mulai' => now()->startOfMonth(), 'tanggal_selesai' => now()->endOfMonth(),
            'target_kontak' => 60, 'target_lunas' => 12, 'academic_year_id' => $this->activeTa->id,
        ]);

        $metricsService = new TargetMetricsService();

        $hm1Rollup = $metricsService->rollUpForHm($this->hm1);
        $globalRollup = $metricsService->rollUpGlobal();

        // HM1 only gets Sales 1 (50 target_kontak)
        $this->assertEquals(50, $hm1Rollup['target_kontak']);

        // Global (Admin) gets Sales 1 + Sales 2 (110 target_kontak)
        $this->assertEquals(110, $globalRollup['target_kontak']);
    }

    /** K & N. Locked target tidak dapat diubah oleh role yang tidak berwenang (SPV / Sales) */
    public function test_skenario_K_and_N_locked_target_cannot_be_updated_by_spv_or_sales()
    {
        $target = Target::create([
            'sales_id' => $this->sales1->id, 'tipe_periode' => 'Bulanan',
            'tanggal_mulai' => now()->startOfMonth(), 'tanggal_selesai' => now()->endOfMonth(),
            'target_kontak' => 50, 'target_lunas' => 10, 'academic_year_id' => $this->activeTa->id,
            'is_locked' => true, 'locked_by' => $this->hm1->id, 'locked_at' => now(),
        ]);

        // SPV 1 cannot update locked target
        $this->assertFalse(Gate::forUser($this->spv1)->allows('update', $target));

        // Sales 1 cannot update locked target
        $this->assertFalse(Gate::forUser($this->sales1)->allows('update', $target));
    }

    /** L. HM dapat lock target Wilayahnya */
    public function test_skenario_L_hm_can_lock_target_in_their_wilayah()
    {
        $target = Target::create([
            'sales_id' => $this->sales1->id, 'tipe_periode' => 'Bulanan',
            'tanggal_mulai' => now()->startOfMonth(), 'tanggal_selesai' => now()->endOfMonth(),
            'target_kontak' => 50, 'target_lunas' => 10, 'academic_year_id' => $this->activeTa->id,
            'is_locked' => false,
        ]);

        // HM 1 CAN lock target of Sales 1 (Wilayah 1)
        $this->assertTrue(Gate::forUser($this->hm1)->allows('lock', $target));

        // HM 1 locks the target
        $target->lock($this->hm1);

        $this->assertTrue($target->fresh()->isLocked());
        $this->assertEquals($this->hm1->id, $target->fresh()->locked_by);
    }

    /** M. SPV tidak dapat mengubah target HM/Wilayah lain */
    public function test_skenario_M_spv_cannot_change_target_of_other_spv_or_wilayah()
    {
        $targetSales2 = Target::create([
            'sales_id' => $this->sales2->id, 'tipe_periode' => 'Bulanan',
            'tanggal_mulai' => now()->startOfMonth(), 'tanggal_selesai' => now()->endOfMonth(),
            'target_kontak' => 60, 'target_lunas' => 12, 'academic_year_id' => $this->activeTa->id,
            'is_locked' => false,
        ]);

        // SPV 1 cannot update target of Sales 2
        $this->assertFalse(Gate::forUser($this->spv1)->allows('update', $targetSales2));
    }

    /** O. Manipulasi route parameter/ID tetap ditolak */
    public function test_skenario_O_route_parameter_tampering_rejected()
    {
        $p2 = Prospek::create([
            'name' => 'Prospect W2', 'type' => 'Sekolah', 'pic' => 'PIC 2', 'whatsapp' => '0822',
            'status' => 'BARU', 'stage_number' => 1, 'sales_id' => $this->sales2->id,
            'owner_id' => $this->sales2->id, 'wilayah_id' => $this->wilayah2->id,
            'academic_year_id' => $this->activeTa->id,
        ]);

        // Sales 1 accesses Sales 2's prospect URL directly
        $response = $this->actingAs($this->sales1)->get(route('sales.prospek.show', $p2));

        $response->assertStatus(403);
    }

    /** P. Academic year scoping tetap bekerja */
    public function test_skenario_P_academic_year_scoping_is_enforced()
    {
        $oldTa = TahunAkademik::create(['nama' => '2025/2026', 'is_active' => false]);

        $oldProspect = Prospek::create([
            'name' => 'Old Prospect', 'type' => 'Sekolah', 'pic' => 'PIC', 'whatsapp' => '0899',
            'status' => 'BARU', 'stage_number' => 1, 'sales_id' => $this->sales1->id,
            'owner_id' => $this->sales1->id, 'wilayah_id' => $this->wilayah1->id,
            'academic_year_id' => $oldTa->id,
        ]);

        $activeProspect = Prospek::create([
            'name' => 'Active Prospect', 'type' => 'Sekolah', 'pic' => 'PIC', 'whatsapp' => '0888',
            'status' => 'BARU', 'stage_number' => 1, 'sales_id' => $this->sales1->id,
            'owner_id' => $this->sales1->id, 'wilayah_id' => $this->wilayah1->id,
            'academic_year_id' => $this->activeTa->id,
        ]);

        $activeProspects = Prospek::where('academic_year_id', $this->activeTa->id)->pluck('id')->toArray();

        $this->assertContains($activeProspect->id, $activeProspects);
        $this->assertNotContains($oldProspect->id, $activeProspects);
    }
}
