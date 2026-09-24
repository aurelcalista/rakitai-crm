<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wilayah;
use App\Models\Target;
use App\Models\TahunAkademik;
use App\Services\TargetAchievementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class HmSalesAndGlobalTerritoryTest extends TestCase
{
    use RefreshDatabase;

    protected User $hmUser;
    protected User $spvUser;
    protected Wilayah $kotaCirebon;
    protected Wilayah $kecKesambi;
    protected Wilayah $kecKejaksan;
    protected Wilayah $kecHarjamukti;
    protected Wilayah $kotaKuningan;
    protected Wilayah $kecCilimus;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed Wilayah Master Data
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

        $this->kecHarjamukti = Wilayah::create([
            'kode' => 'CRB-HJM-01',
            'nama' => 'Harjamukti',
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

        // HM & SPV Users
        $this->hmUser = User::create([
            'name' => 'HM Boss',
            'email' => 'hm.boss@cic.ac.id',
            'phone' => '08111111111',
            'password' => Hash::make('password'),
            'role' => 'HM',
            'status' => 'Aktif',
            'wilayah_id' => $this->kotaCirebon->id,
        ]);

        $this->spvUser = User::create([
            'name' => 'SPV Lead',
            'email' => 'spv.lead@cic.ac.id',
            'phone' => '08222222222',
            'password' => Hash::make('password'),
            'role' => 'SPV',
            'status' => 'Aktif',
            'wilayah_id' => $this->kotaCirebon->id,
        ]);

        // Ensure active TA exists
        TahunAkademik::create([
            'nama' => '2027/2028',
            'semester' => 'Ganjil',
            'is_aktif' => true,
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    // 1-10. Sales Creation, Phone Removal & Password Defaults
    // ──────────────────────────────────────────────────────────────

    public function test_01_03_07_08_sales_gets_automatic_code_format_yymmsxxx()
    {
        $code = User::generateUserCode('Sales');
        $ym = date('ym');
        $this->assertMatchesRegularExpression("/^{$ym}S\d{3}$/", $code);
    }

    public function test_02_04_cs_gets_automatic_code_format_yymmcxxx()
    {
        $code = User::generateUserCode('CS');
        $ym = date('ym');
        $this->assertMatchesRegularExpression("/^{$ym}C\d{3}$/", $code);
    }

    public function test_05_sequence_increments_correctly()
    {
        $code1 = User::generateUserCode('Sales');
        User::create([
            'name' => 'Sales One',
            'email' => 's1@cic.ac.id',
            'password' => Hash::make('secret'),
            'role' => 'Sales',
            'kode' => $code1,
        ]);

        $code2 = User::generateUserCode('Sales');
        $seq1 = (int) substr($code1, -3);
        $seq2 = (int) substr($code2, -3);

        $this->assertEquals($seq1 + 1, $seq2);
    }

    public function test_06_10_sequence_resets_on_new_year()
    {
        $code2026 = User::generateUserCode('Sales', '2609');
        $this->assertStringStartsWith('2609S', $code2026);

        $code2027 = User::generateUserCode('Sales', '2701');
        $this->assertStringStartsWith('2701S', $code2027);
        $this->assertStringEndsWith('001', $code2027);
    }

    public function test_spv_can_create_sales_without_phone_and_password_defaults_to_123_hashed()
    {
        $response = $this->actingAs($this->spvUser)->post(route('spv.tim.sales.store'), [
            'name' => 'Sales Default Pass',
            'email_username' => 'sales.defpass',
            'area_id' => $this->kecKesambi->id,
            // phone & password omitted!
        ]);

        $response->assertRedirect(route('spv.tim.index'));
        $created = User::where('email', 'sales.defpass@cic.ac.id')->first();
        $this->assertNotNull($created);
        $this->assertTrue(Hash::check('123', $created->password));
        $this->assertNotEquals('123', $created->password); // Hashed, not plaintext!
        $this->assertEquals('Sales', $created->jabatan);
    }

    public function test_sales_can_login_with_default_password_and_update_profile_and_change_password()
    {
        $sales = User::create([
            'name' => 'Sales User',
            'email' => 'sales.user@cic.ac.id',
            'password' => Hash::make('123'),
            'role' => 'Sales',
            'jabatan' => 'Sales',
        ]);

        // 1. Sales updates profile phone number
        $this->actingAs($sales)->post(route('profil.update'), [
            'name' => 'Sales User Full',
            'phone' => '081299998888',
        ]);

        $this->assertEquals('081299998888', $sales->fresh()->phone);

        // 2. Sales changes password from 123 to myNewPass123
        $response = $this->actingAs($sales)->post(route('profil.password.update'), [
            'current_password' => '123',
            'password' => 'myNewPass123',
            'password_confirmation' => 'myNewPass123',
        ]);

        $response->assertSessionHas('success');
        $this->assertTrue(Hash::check('myNewPass123', $sales->fresh()->password));
    }

    // ──────────────────────────────────────────────────────────────
    // 11-16. Territory & Radio Selection Tests
    // ──────────────────────────────────────────────────────────────

    public function test_11_12_14_spv_page_renders_radio_selection_for_wilayah()
    {
        // Assign Kesambi to Sales A
        $salesA = User::create([
            'name' => 'Sales Occupant',
            'email' => 'sales.occ@cic.ac.id',
            'password' => Hash::make('123'),
            'role' => 'Sales',
        ]);
        DB::table('user_wilayah')->insert([
            'user_id' => $salesA->id,
            'wilayah_id' => $this->kecKesambi->id,
            'role' => 'Sales',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->spvUser)->get(route('spv.tim.index'));

        $response->assertStatus(200);
        $response->assertSee('name="area_id"', false);
        $response->assertSee('Sudah ada Sales (Sales Occupant)');
    }

    public function test_13_16_backend_rejects_occupied_wilayah_assignment()
    {
        $salesA = User::create([
            'name' => 'Sales A',
            'email' => 'salesa@cic.ac.id',
            'password' => Hash::make('123'),
            'role' => 'Sales',
        ]);
        DB::table('user_wilayah')->insert([
            'user_id' => $salesA->id,
            'wilayah_id' => $this->kecKesambi->id,
            'role' => 'Sales',
            'is_active' => true,
        ]);

        // SPV attempts to create another Sales for Kesambi -> REJECTED
        $response = $this->actingAs($this->spvUser)->post(route('spv.tim.sales.store'), [
            'name' => 'Sales Conflict',
            'email_username' => 'sales.conf',
            'area_id' => $this->kecKesambi->id,
        ]);

        $response->assertSessionHas('error');
    }

    public function test_15_one_sales_can_have_multiple_active_areas()
    {
        $sales = User::create([
            'name' => 'Sales Multi',
            'email' => 'sales.multi@cic.ac.id',
            'password' => Hash::make('123'),
            'role' => 'Sales',
        ]);

        DB::table('user_wilayah')->insert([
            ['user_id' => $sales->id, 'wilayah_id' => $this->kecKesambi->id, 'role' => 'Sales', 'is_active' => true],
            ['user_id' => $sales->id, 'wilayah_id' => $this->kecKejaksan->id, 'role' => 'Sales', 'is_active' => true],
        ]);

        $activeAreas = $sales->activeWilayahes->pluck('id')->toArray();
        $this->assertContains($this->kecKesambi->id, $activeAreas);
        $this->assertContains($this->kecKejaksan->id, $activeAreas);
    }

    // ──────────────────────────────────────────────────────────────
    // 17-26. Target Flow Tests (HM -> SPV -> Sales -> Daily)
    // ──────────────────────────────────────────────────────────────

    public function test_17_18_19_hm_can_set_spv_target_bound_to_academic_year()
    {
        $response = $this->actingAs($this->hmUser)->post(route('admin.target.store'), [
            'wilayah_id' => $this->kotaCirebon->id,
            'sales_id' => $this->spvUser->id,
            'tipe_periode' => 'Bulanan',
            'tanggal_mulai' => '2026-09-01',
            'tanggal_selesai' => '2026-09-30',
            'target_lunas' => 40,
            'target_kontak' => 100,
            'target_followup' => 80,
            'target_kunjungan' => 15,
            'status' => 'Aktif',
            'tahun_akademik' => '2027/2028',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('targets', [
            'spv_id' => $this->spvUser->id,
            'target_lunas' => 40,
            'tahun_akademik' => '2027/2028',
        ]);
    }

    public function test_20_spv_target_allocation_to_sales_cannot_exceed_spv_monthly_target()
    {
        // SPV has monthly target of 40 Lunas from HM
        Target::create([
            'sales_id' => $this->spvUser->id,
            'spv_id' => $this->spvUser->id,
            'wilayah_id' => $this->kotaCirebon->id,
            'target_type' => 'Wilayah',
            'tipe_periode' => 'Bulanan',
            'tanggal_mulai' => '2026-09-01',
            'tanggal_selesai' => '2026-09-30',
            'target_lunas' => 40,
            'target_kontak' => 100,
            'target_followup' => 80,
            'target_kunjungan' => 15,
            'status' => 'Aktif',
            'tahun_akademik' => '2027/2028',
            'allocated_by' => $this->hmUser->id,
        ]);

        $sales1 = User::create([
            'name' => 'Sales Member 1',
            'email' => 'sm1@cic.ac.id',
            'password' => Hash::make('123'),
            'role' => 'Sales',
            'supervisor_id' => $this->spvUser->id,
        ]);

        // SPV attempts to allocate 50 Lunas (> 40 limit) -> REJECTED
        $response = $this->actingAs($this->spvUser)->post(route('spv.performa.alokasi'), [
            'sales_id' => $sales1->id,
            'tipe_periode' => 'Bulanan',
            'tanggal_mulai' => '2026-09-01',
            'tanggal_selesai' => '2026-09-30',
            'target_lunas' => 50,
            'target_formulir' => 20,
            'target_kontak' => 80,
        ]);

        $response->assertSessionHasErrors('target_lunas');
    }

    public function test_22_23_spv_can_allocate_valid_target_to_sales()
    {
        Target::create([
            'sales_id' => $this->spvUser->id,
            'spv_id' => $this->spvUser->id,
            'wilayah_id' => $this->kotaCirebon->id,
            'target_type' => 'Wilayah',
            'tipe_periode' => 'Bulanan',
            'tanggal_mulai' => '2026-09-01',
            'tanggal_selesai' => '2026-09-30',
            'target_lunas' => 40,
            'target_kontak' => 100,
            'target_followup' => 80,
            'target_kunjungan' => 15,
            'status' => 'Aktif',
            'tahun_akademik' => '2027/2028',
            'allocated_by' => $this->hmUser->id,
        ]);

        $sales1 = User::create([
            'name' => 'Sales Member 1',
            'email' => 'sm1@cic.ac.id',
            'password' => Hash::make('123'),
            'role' => 'Sales',
            'supervisor_id' => $this->spvUser->id,
        ]);

        $response = $this->actingAs($this->spvUser)->post(route('spv.performa.alokasi'), [
            'sales_id' => $sales1->id,
            'tipe_periode' => 'Bulanan',
            'tanggal_mulai' => '2026-09-01',
            'tanggal_selesai' => '2026-09-30',
            'target_lunas' => 15,
            'target_kontak' => 30,
            'target_formulir' => 10,
        ]);

        $response->assertRedirect(route('spv.performa.index'));
        $this->assertDatabaseHas('targets', [
            'sales_id' => $sales1->id,
            'target_lunas' => 15,
            'allocated_by' => $this->spvUser->id,
        ]);
    }

    public function test_24_25_26_sales_monthly_target_automatically_generates_daily_target_in_service()
    {
        $sales1 = User::create([
            'name' => 'Sales Daily Test',
            'email' => 'sdaily@cic.ac.id',
            'password' => Hash::make('123'),
            'role' => 'Sales',
            'supervisor_id' => $this->spvUser->id,
        ]);

        Target::create([
            'sales_id' => $sales1->id,
            'spv_id' => $this->spvUser->id,
            'wilayah_id' => $this->kecKesambi->id,
            'target_type' => 'Individual',
            'tipe_periode' => 'Bulanan',
            'tanggal_mulai' => '2026-09-01',
            'tanggal_selesai' => '2026-09-30',
            'target_lunas' => 25,
            'target_kontak' => 50,
            'target_followup' => 40,
            'target_kunjungan' => 10,
            'status' => 'Aktif',
            'tahun_akademik' => '2027/2028',
            'allocated_by' => $this->spvUser->id,
        ]);

        $service = app(TargetAchievementService::class);
        $dailyData = service_get_dashboard($service, $sales1, 'harian');

        $this->assertNotEmpty($dailyData['kpi_rows']);
        $closingRow = collect($dailyData['kpi_rows'])->firstWhere('label', 'Maba Lunas (Closing)');
        $this->assertNotNull($closingRow);
        // Monthly 25 prorates to 1 daily target
        $this->assertEquals(1, $closingRow['target']);
    }
}

function service_get_dashboard($service, $user, $periode) {
    return $service->getDashboardTargetData($user, $periode);
}
