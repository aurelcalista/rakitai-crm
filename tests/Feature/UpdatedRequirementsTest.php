<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wilayah;
use App\Models\Target;
use App\Models\Prospek;
use App\Models\Prodi;
use App\Models\Sekolah;
use App\Models\Perusahaan;
use App\Models\TahunAkademik;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UpdatedRequirementsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        TahunAkademik::create([
            'nama' => '2026/2027',
            'tahun_mulai' => 2026,
            'tahun_selesai' => 2027,
            'is_active' => true,
        ]);
    }

    public function test_auto_generate_user_code_is_unique_and_formatted_correctly()
    {
        $code1 = User::generateUserCode();
        $user1 = User::factory()->create([
            'kode' => $code1,
            'role' => 'SPV',
        ]);

        $code2 = User::generateUserCode();
        $this->assertNotEquals($code1, $code2);
        $this->assertMatchesRegularExpression('/^\d{4}[a-zA-Z]\d{3}$/', $code1);
        $this->assertMatchesRegularExpression('/^\d{4}[a-zA-Z]\d{3}$/', $code2);
    }

    public function test_hm_can_create_spv_cs_eo_and_admin()
    {
        $hm = User::factory()->create(['role' => 'HM', 'status' => 'Aktif']);

        $responseSpv = $this->actingAs($hm)->post(route('admin.users.store'), [
            'name' => 'SPV Test',
            'email' => 'spvtest@example.com',
            'phone' => '081234567891',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'SPV',
            'status' => 'Aktif',
        ]);
        $responseSpv->assertRedirect();
        $this->assertDatabaseHas('users', ['email' => 'spvtest@example.com', 'role' => 'SPV']);

        $responseCs = $this->actingAs($hm)->post(route('admin.users.store'), [
            'name' => 'CS Test',
            'email' => 'cstest@example.com',
            'phone' => '081234567892',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'CS',
            'status' => 'Aktif',
        ]);
        $responseCs->assertRedirect();
        $this->assertDatabaseHas('users', ['email' => 'cstest@example.com', 'role' => 'CS']);
    }

    public function test_wilayah_kecamatan_kode_is_auto_generated_sequentially()
    {
        $kota = Wilayah::create([
            'kode' => '32.74',
            'nama' => 'Kota Cirebon',
            'tipe' => 'Kota',
            'is_active' => true,
        ]);

        $code1 = Wilayah::generateKecamatanKode($kota, 'Kesambi');
        $this->assertEquals('CRB-KSB-01', $code1);

        $kec1 = Wilayah::create([
            'parent_id' => $kota->id,
            'kode' => $code1,
            'nama' => 'Kesambi',
            'tipe' => 'Kecamatan',
            'is_active' => true,
        ]);

        $code2 = Wilayah::generateKecamatanKode($kota, 'Kejaksan');
        $this->assertEquals('CRB-KJS-02', $code2);
    }

    public function test_annual_target_distribution_mode_rata_and_pmb_equal_annual_total()
    {
        $annualTarget = 100;
        
        $modeRata = Target::distributeAnnualTarget($annualTarget, 'rata');
        $this->assertCount(12, $modeRata);
        $this->assertEquals(100, array_sum($modeRata));

        $modePmb = Target::distributeAnnualTarget($annualTarget, 'pmb');
        $this->assertCount(5, $modePmb);
        $this->assertEquals(100, array_sum($modePmb));
    }

    public function test_cancel_status_is_tracked_without_deleting_history()
    {
        $sales = User::factory()->create(['role' => 'Sales', 'status' => 'Aktif']);
        $prospek = Prospek::create([
            'name' => 'Calon Mahasiswa Beasiswa',
            'type' => 'Siswa',
            'sales_id' => $sales->id,
            'status' => 'BERKAS',
            'whatsapp' => '081234567890',
        ]);

        $prospek->update(['status' => 'CANCEL']);
        
        $this->assertDatabaseHas('prospeks', [
            'id' => $prospek->id,
            'status' => 'CANCEL',
        ]);

        $this->assertEquals(1, Prospek::where('status', 'CANCEL')->count());
    }

    public function test_data_prodi_supports_ukt_and_ukt_reguler()
    {
        $prodi = Prodi::create([
            'kode' => 'TI-S1',
            'nama' => 'Teknik Informatika S1',
            'jenjang' => 'S1',
            'fakultas' => 'Teknologi Informasi',
            'kuota' => 100,
            'spp' => '4500000',
            'ukt' => '4500000',
            'ukt_reguler' => '5500000',
            'status' => 'Aktif',
        ]);

        $this->assertEquals('4500000', $prodi->ukt);
        $this->assertEquals('5500000', $prodi->ukt_reguler);
    }

    public function test_spv_can_create_sales_with_cic_email_and_multi_wilayah()
    {
        $kota = Wilayah::create(['kode' => '32.74', 'nama' => 'Kota Cirebon', 'level' => 'Kota/Kabupaten', 'status' => 'Aktif']);
        $kec1 = Wilayah::create(['parent_id' => $kota->id, 'kode' => 'CRB-KSB-01', 'nama' => 'Kesambi', 'level' => 'Kecamatan', 'status' => 'Aktif']);
        $kec2 = Wilayah::create(['parent_id' => $kota->id, 'kode' => 'CRB-KJS-02', 'nama' => 'Kejaksan', 'level' => 'Kecamatan', 'status' => 'Aktif']);

        $spv = User::factory()->create([
            'role' => 'SPV',
            'status' => 'Aktif',
            'wilayah_id' => $kota->id,
        ]);

        $response = $this->actingAs($spv)->post(route('spv.tim.sales.store'), [
            'name' => 'Budi Sales Baru',
            'email' => 'budi.sales@cic.ac.id',
            'phone' => '081234567890',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'area_ids' => [$kec1->id, $kec2->id],
        ]);

        $response->assertRedirect();

        $newSales = User::where('email', 'budi.sales@cic.ac.id')->first();
        $this->assertNotNull($newSales);
        $this->assertEquals('Sales', $newSales->role);
        $this->assertEquals('Sales', $newSales->jabatan);
        $this->assertEquals($spv->id, $newSales->supervisor_id);
        $this->assertMatchesRegularExpression('/^\d{4}[a-zA-Z]\d{3}$/', $newSales->kode);

        // Active multi-wilayah check
        $activeAreas = DB::table('user_wilayah')->where('user_id', $newSales->id)->where('is_active', true)->pluck('wilayah_id')->toArray();
        $this->assertContains($kec1->id, $activeAreas);
        $this->assertContains($kec2->id, $activeAreas);
    }

    public function test_sales_email_must_use_cic_ac_id_domain()
    {
        $kota = Wilayah::create(['kode' => '32.74', 'nama' => 'Kota Cirebon', 'level' => 'Kota/Kabupaten', 'status' => 'Aktif']);
        $spv = User::factory()->create(['role' => 'SPV', 'status' => 'Aktif', 'wilayah_id' => $kota->id]);

        $response = $this->actingAs($spv)->post(route('spv.tim.sales.store'), [
            'name' => 'Invalid Email Sales',
            'email' => 'budi.sales@gmail.com',
            'phone' => '081234567890',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertDatabaseMissing('users', ['name' => 'Invalid Email Sales']);
    }

    public function test_user_code_sequence_resets_per_calendar_year()
    {
        // 2026 Code Sales
        $code2026 = User::generateUserCode('Sales', '2609');
        $this->assertEquals('2609S001', $code2026);
        User::factory()->create(['kode' => $code2026, 'role' => 'Sales']);

        // 2026 Code CS
        $code2026_2 = User::generateUserCode('CS', '2610');
        $this->assertEquals('2610C001', $code2026_2);
        User::factory()->create(['kode' => $code2026_2, 'role' => 'CS']);

        // 2027 Code (Year reset)
        $code2027 = User::generateUserCode('Sales', '2701');
        $this->assertEquals('2701S001', $code2027);
    }

    public function test_spv_cannot_assign_area_outside_scope_id_tampering()
    {
        $kota1 = Wilayah::create(['kode' => '32.74', 'nama' => 'Kota Cirebon', 'level' => 'Kota/Kabupaten', 'status' => 'Aktif']);
        $kota2 = Wilayah::create(['kode' => '32.75', 'nama' => 'Kota Kuningan', 'level' => 'Kota/Kabupaten', 'status' => 'Aktif']);
        $kecOutside = Wilayah::create(['parent_id' => $kota2->id, 'kode' => 'KNG-KNG-01', 'nama' => 'Kuningan Timur', 'level' => 'Kecamatan', 'status' => 'Aktif']);

        $spv = User::factory()->create(['role' => 'SPV', 'status' => 'Aktif', 'wilayah_id' => $kota1->id]);

        $response = $this->actingAs($spv)->post(route('spv.tim.sales.store'), [
            'name' => 'Tamper Sales',
            'email' => 'tamper.sales@cic.ac.id',
            'phone' => '081234567890',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'area_ids' => [$kecOutside->id],
        ]);

        $response->assertStatus(403);
    }

    public function test_area_cannot_have_two_active_sales_simultaneously()
    {
        $kota = Wilayah::create(['kode' => '32.74', 'nama' => 'Kota Cirebon', 'level' => 'Kota/Kabupaten', 'status' => 'Aktif']);
        $kec = Wilayah::create(['parent_id' => $kota->id, 'kode' => 'CRB-KSB-01', 'nama' => 'Kesambi', 'level' => 'Kecamatan', 'status' => 'Aktif']);

        $spv = User::factory()->create(['role' => 'SPV', 'status' => 'Aktif', 'wilayah_id' => $kota->id]);

        $existingSales = User::factory()->create(['role' => 'Sales', 'status' => 'Aktif', 'supervisor_id' => $spv->id]);
        DB::table('user_wilayah')->insert([
            'user_id' => $existingSales->id,
            'wilayah_id' => $kec->id,
            'role' => 'Sales',
            'is_active' => true,
        ]);

        $response = $this->actingAs($spv)->post(route('spv.tim.sales.store'), [
            'name' => 'Sales Kedua',
            'email' => 'sales2@cic.ac.id',
            'phone' => '081234567899',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'area_ids' => [$kec->id],
        ]);

        $response->assertSessionHas('error');
    }

    public function test_kelola_target_spv_dropdown_is_dynamically_scoped_to_selected_wilayah()
    {
        $kotaCirebon = Wilayah::create(['kode' => '32.74', 'nama' => 'Kota Cirebon', 'level' => 'Kota/Kabupaten', 'status' => 'Aktif']);
        $kotaKuningan = Wilayah::create(['kode' => '32.75', 'nama' => 'Kabupaten Kuningan', 'level' => 'Kota/Kabupaten', 'status' => 'Aktif']);

        $spvBudi = User::factory()->create(['name' => 'SPV Budi', 'role' => 'SPV', 'status' => 'Aktif', 'wilayah_id' => $kotaCirebon->id]);
        $spvSiti = User::factory()->create(['name' => 'SPV Siti', 'role' => 'SPV', 'status' => 'Aktif', 'wilayah_id' => $kotaKuningan->id]);

        $admin = User::factory()->create(['role' => 'Admin', 'status' => 'Aktif']);

        $response = $this->actingAs($admin)->get(route('admin.target.index'));
        $response->assertOk();

        $wilayahTree = $response->viewData('wilayahTree');
        $this->assertNotEmpty($wilayahTree);

        $cirebonItem = collect($wilayahTree)->firstWhere('id', $kotaCirebon->id);
        $kuninganItem = collect($wilayahTree)->firstWhere('id', $kotaKuningan->id);

        $this->assertNotNull($cirebonItem);
        $this->assertNotNull($kuninganItem);

        $cirebonSpvIds = collect($cirebonItem['spvs'])->pluck('id')->toArray();
        $kuninganSpvIds = collect($kuninganItem['spvs'])->pluck('id')->toArray();

        $this->assertContains($spvBudi->id, $cirebonSpvIds);
        $this->assertNotContains($spvSiti->id, $cirebonSpvIds);

        $this->assertContains($spvSiti->id, $kuninganSpvIds);
        $this->assertNotContains($spvBudi->id, $kuninganSpvIds);
    }

    public function test_hm_kelola_target_only_sees_wilayahs_and_spvs_within_hm_scope()
    {
        $kotaCirebon = Wilayah::create(['kode' => '32.74', 'nama' => 'Kota Cirebon', 'level' => 'Kota/Kabupaten', 'status' => 'Aktif']);
        $kotaKuningan = Wilayah::create(['kode' => '32.75', 'nama' => 'Kabupaten Kuningan', 'level' => 'Kota/Kabupaten', 'status' => 'Aktif']);

        $hmAndi = User::factory()->create(['name' => 'HM Andi', 'role' => 'HM', 'status' => 'Aktif', 'wilayah_id' => $kotaCirebon->id]);
        $spvBudi = User::factory()->create(['name' => 'SPV Budi', 'role' => 'SPV', 'status' => 'Aktif', 'wilayah_id' => $kotaCirebon->id]);
        $spvSiti = User::factory()->create(['name' => 'SPV Siti', 'role' => 'SPV', 'status' => 'Aktif', 'wilayah_id' => $kotaKuningan->id]);

        $response = $this->actingAs($hmAndi)->get(route('admin.target.index'));
        $response->assertOk();

        $wilayahTree = $response->viewData('wilayahTree');
        $kotaIdsInTree = collect($wilayahTree)->pluck('id')->toArray();

        $this->assertContains($kotaCirebon->id, $kotaIdsInTree);
        $this->assertNotContains($kotaKuningan->id, $kotaIdsInTree);
    }

    public function test_backend_rejects_target_creation_for_wilayah_outside_hm_scope()
    {
        $kotaCirebon = Wilayah::create(['kode' => '32.74', 'nama' => 'Kota Cirebon', 'level' => 'Kota/Kabupaten', 'status' => 'Aktif']);
        $kotaKuningan = Wilayah::create(['kode' => '32.75', 'nama' => 'Kabupaten Kuningan', 'level' => 'Kota/Kabupaten', 'status' => 'Aktif']);

        $hmAndi = User::factory()->create(['name' => 'HM Andi', 'role' => 'HM', 'status' => 'Aktif', 'wilayah_id' => $kotaCirebon->id]);
        $spvSiti = User::factory()->create(['name' => 'SPV Siti', 'role' => 'SPV', 'status' => 'Aktif', 'wilayah_id' => $kotaKuningan->id]);

        $response = $this->actingAs($hmAndi)->post(route('admin.target.store'), [
            'wilayah_id'      => $kotaKuningan->id,
            'spv_id'          => $spvSiti->id,
            'sales_id'        => $spvSiti->id,
            'tipe_periode'    => 'Bulanan',
            'tanggal_mulai'   => '2026-10-01',
            'tanggal_selesai' => '2026-10-31',
            'target_lunas'    => 50,
            'target_kontak'   => 20,
            'target_followup' => 15,
            'target_kunjungan'=> 5,
            'status'          => 'Aktif',
        ]);

        $response->assertStatus(403);
    }

    public function test_backend_rejects_mismatched_spv_and_wilayah_target_creation()
    {
        $kotaCirebon = Wilayah::create(['kode' => '32.74', 'nama' => 'Kota Cirebon', 'level' => 'Kota/Kabupaten', 'status' => 'Aktif']);
        $kotaKuningan = Wilayah::create(['kode' => '32.75', 'nama' => 'Kabupaten Kuningan', 'level' => 'Kota/Kabupaten', 'status' => 'Aktif']);

        $hmAndi = User::factory()->create(['name' => 'HM Andi', 'role' => 'HM', 'status' => 'Aktif', 'wilayah_id' => $kotaCirebon->id]);
        $spvSiti = User::factory()->create(['name' => 'SPV Siti', 'role' => 'SPV', 'status' => 'Aktif', 'wilayah_id' => $kotaKuningan->id]);

        // Attempting to submit Kota Cirebon with SPV Siti from Kuningan
        $response = $this->actingAs($hmAndi)->post(route('admin.target.store'), [
            'wilayah_id'      => $kotaCirebon->id,
            'spv_id'          => $spvSiti->id,
            'sales_id'        => $spvSiti->id,
            'tipe_periode'    => 'Bulanan',
            'tanggal_mulai'   => '2026-10-01',
            'tanggal_selesai' => '2026-10-31',
            'target_lunas'    => 50,
            'target_kontak'   => 20,
            'target_followup' => 15,
            'target_kunjungan'=> 5,
            'status'          => 'Aktif',
        ]);

        $response->assertStatus(403);
    }

    public function test_spv_assigned_in_wilayah_saya_appears_in_kelola_target()
    {
        $kota = Wilayah::create(['kode' => '32.74', 'nama' => 'Kota Cirebon', 'level' => 'Kota/Kabupaten', 'status' => 'Aktif']);
        $hm = User::factory()->create(['name' => 'HM Andi', 'role' => 'HM', 'status' => 'Aktif', 'wilayah_id' => $kota->id]);
        $spvBudi = User::factory()->create(['name' => 'SPV Budi', 'role' => 'SPV', 'status' => 'Aktif']);

        // HM assigns Budi to Kota Cirebon in Wilayah Saya
        $this->actingAs($hm)->post(route('hm.wilayah.assignSpv', $kota->id), [
            'spv_id' => $spvBudi->id,
        ])->assertRedirect();

        // Check Kelola Target: Budi appears under Kota Cirebon
        $response = $this->actingAs($hm)->get(route('admin.target.index'));
        $response->assertOk();

        $wilayahTree = $response->viewData('wilayahTree');
        $cirebonItem = collect($wilayahTree)->firstWhere('id', $kota->id);
        $spvIds = collect($cirebonItem['spvs'])->pluck('id')->toArray();

        $this->assertContains($spvBudi->id, $spvIds);
    }

    public function test_newly_assigned_spv_in_wilayah_saya_automatically_appears_in_kelola_target()
    {
        $kota = Wilayah::create(['kode' => '32.74', 'nama' => 'Kota Cirebon', 'level' => 'Kota/Kabupaten', 'status' => 'Aktif']);
        $hm = User::factory()->create(['name' => 'HM Andi', 'role' => 'HM', 'status' => 'Aktif', 'wilayah_id' => $kota->id]);
        $spvBudi = User::factory()->create(['name' => 'SPV Budi', 'role' => 'SPV', 'status' => 'Aktif', 'wilayah_id' => $kota->id]);

        // HM adds second SPV Siti in Wilayah Saya
        $spvSiti = User::factory()->create(['name' => 'SPV Siti', 'role' => 'SPV', 'status' => 'Aktif']);
        $this->actingAs($hm)->post(route('hm.wilayah.assignSpv', $kota->id), [
            'spv_id' => $spvSiti->id,
        ])->assertRedirect();

        // Kelola Target automatically displays both Budi & Siti
        $response = $this->actingAs($hm)->get(route('admin.target.index'));
        $wilayahTree = $response->viewData('wilayahTree');
        $cirebonItem = collect($wilayahTree)->firstWhere('id', $kota->id);
        $spvIds = collect($cirebonItem['spvs'])->pluck('id')->toArray();

        $this->assertContains($spvBudi->id, $spvIds);
        $this->assertContains($spvSiti->id, $spvIds);
    }

    public function test_deactivated_spv_assignment_does_not_appear_for_new_targets()
    {
        $kota = Wilayah::create(['kode' => '32.74', 'nama' => 'Kota Cirebon', 'level' => 'Kota/Kabupaten', 'status' => 'Aktif']);
        $hm = User::factory()->create(['name' => 'HM Andi', 'role' => 'HM', 'status' => 'Aktif', 'wilayah_id' => $kota->id]);
        $spvBudi = User::factory()->create(['name' => 'SPV Budi', 'role' => 'SPV', 'status' => 'Aktif', 'wilayah_id' => $kota->id]);
        $spvSiti = User::factory()->create(['name' => 'SPV Siti', 'role' => 'SPV', 'status' => 'Aktif', 'wilayah_id' => $kota->id]);

        // HM deactivates Siti's assignment in Wilayah Saya
        $this->actingAs($hm)->post(route('hm.wilayah.unassignSpv', ['wilayah' => $kota->id, 'spv' => $spvSiti->id]))->assertRedirect();

        // Siti is no longer available in Kelola Target for new targets
        $response = $this->actingAs($hm)->get(route('admin.target.index'));
        $wilayahTree = $response->viewData('wilayahTree');
        $cirebonItem = collect($wilayahTree)->firstWhere('id', $kota->id);
        $spvIds = collect($cirebonItem['spvs'])->pluck('id')->toArray();

        $this->assertContains($spvBudi->id, $spvIds);
        $this->assertNotContains($spvSiti->id, $spvIds);
    }

    public function test_deactivated_spv_target_history_is_preserved()
    {
        $kota = Wilayah::create(['kode' => '32.74', 'nama' => 'Kota Cirebon', 'level' => 'Kota/Kabupaten', 'status' => 'Aktif']);
        $hm = User::factory()->create(['name' => 'HM Andi', 'role' => 'HM', 'status' => 'Aktif', 'wilayah_id' => $kota->id]);
        $spvSiti = User::factory()->create(['name' => 'SPV Siti', 'role' => 'SPV', 'status' => 'Aktif', 'wilayah_id' => $kota->id]);

        // Create historical target for Siti
        $targetSiti = Target::create([
            'wilayah_id'      => $kota->id,
            'spv_id'          => $spvSiti->id,
            'sales_id'        => $spvSiti->id,
            'target_type'     => 'Wilayah',
            'allocated_by'    => $hm->id,
            'tipe_periode'    => 'Bulanan',
            'tanggal_mulai'   => '2026-01-01',
            'tanggal_selesai' => '2026-01-31',
            'target_lunas'    => 50,
            'target_kontak'   => 30,
            'target_followup' => 20,
            'target_kunjungan'=> 5,
            'status'          => 'Selesai',
        ]);

        // HM deactivates Siti
        $this->actingAs($hm)->post(route('hm.wilayah.unassignSpv', ['wilayah' => $kota->id, 'spv' => $spvSiti->id]));

        // Siti's historical target remains in database
        $this->assertDatabaseHas('targets', [
            'id'       => $targetSiti->id,
            'spv_id'   => $spvSiti->id,
            'sales_id' => $spvSiti->id,
        ]);
    }

    public function test_spv_of_another_wilayah_does_not_appear_for_selected_wilayah()
    {
        $kotaCirebon = Wilayah::create(['kode' => '32.74', 'nama' => 'Kota Cirebon', 'level' => 'Kota/Kabupaten', 'status' => 'Aktif']);
        $kotaKuningan = Wilayah::create(['kode' => '32.75', 'nama' => 'Kabupaten Kuningan', 'level' => 'Kota/Kabupaten', 'status' => 'Aktif']);

        $hm = User::factory()->create(['name' => 'HM Andi', 'role' => 'HM', 'status' => 'Aktif', 'wilayah_id' => $kotaCirebon->id]);
        DB::table('user_wilayah')->insert([
            ['user_id' => $hm->id, 'wilayah_id' => $kotaCirebon->id, 'role' => 'HM', 'is_active' => true],
            ['user_id' => $hm->id, 'wilayah_id' => $kotaKuningan->id, 'role' => 'HM', 'is_active' => true],
        ]);

        $spvBudi = User::factory()->create(['name' => 'SPV Budi', 'role' => 'SPV', 'status' => 'Aktif', 'wilayah_id' => $kotaCirebon->id]);
        $spvRina = User::factory()->create(['name' => 'SPV Rina', 'role' => 'SPV', 'status' => 'Aktif', 'wilayah_id' => $kotaKuningan->id]);

        $response = $this->actingAs($hm)->get(route('admin.target.index'));
        $wilayahTree = $response->viewData('wilayahTree');

        $cirebonItem = collect($wilayahTree)->firstWhere('id', $kotaCirebon->id);
        $kuninganItem = collect($wilayahTree)->firstWhere('id', $kotaKuningan->id);

        $cirebonSpvIds = collect($cirebonItem['spvs'])->pluck('id')->toArray();
        $kuninganSpvIds = collect($kuninganItem['spvs'])->pluck('id')->toArray();

        $this->assertContains($spvBudi->id, $cirebonSpvIds);
        $this->assertNotContains($spvRina->id, $cirebonSpvIds);

        $this->assertContains($spvRina->id, $kuninganSpvIds);
        $this->assertNotContains($spvBudi->id, $kuninganSpvIds);
    }

    public function test_backend_tampering_of_unassigned_spv_id_is_rejected()
    {
        $kotaCirebon = Wilayah::create(['kode' => '32.74', 'nama' => 'Kota Cirebon', 'level' => 'Kota/Kabupaten', 'status' => 'Aktif']);
        $kotaKuningan = Wilayah::create(['kode' => '32.75', 'nama' => 'Kabupaten Kuningan', 'level' => 'Kota/Kabupaten', 'status' => 'Aktif']);

        $hm = User::factory()->create(['name' => 'HM Andi', 'role' => 'HM', 'status' => 'Aktif', 'wilayah_id' => $kotaCirebon->id]);
        $spvRina = User::factory()->create(['name' => 'SPV Rina', 'role' => 'SPV', 'status' => 'Aktif', 'wilayah_id' => $kotaKuningan->id]);

        // Attempt to create target for Cirebon using SPV Rina assigned to Kuningan
        $response = $this->actingAs($hm)->post(route('admin.target.store'), [
            'wilayah_id'      => $kotaCirebon->id,
            'spv_id'          => $spvRina->id,
            'sales_id'        => $spvRina->id,
            'tipe_periode'    => 'Bulanan',
            'tanggal_mulai'   => '2026-10-01',
            'tanggal_selesai' => '2026-10-31',
            'target_lunas'    => 50,
            'target_kontak'   => 20,
            'target_followup' => 15,
            'target_kunjungan'=> 5,
            'status'          => 'Aktif',
        ]);

        $response->assertStatus(403);
    }
}


