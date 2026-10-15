<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\MasterData;
use App\Models\Perusahaan;
use App\Models\Prodi;
use App\Models\Prospek;
use App\Models\Sekolah;
use App\Models\TahunAkademik;
use App\Models\Target;
use App\Models\User;
use App\Models\WeeklyHoliday;
use App\Models\Wilayah;
use App\Models\WorkCalendar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCrudComprehensiveTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Wilayah $wilayah;
    protected MasterData $kategoriSekolah;
    protected MasterData $kategoriPerusahaan;

    protected function setUp(): void
    {
        parent::setUp();

        TahunAkademik::create([
            'nama' => '2027/2028',
            'status' => 'Aktif',
            'is_aktif' => true,
        ]);

        $this->wilayah = Wilayah::create([
            'nama' => 'Kota Cirebon',
            'kode' => 'CRB-01',
            'level' => 'Kota/Kabupaten',
            'status' => 'Aktif',
        ]);

        $this->admin = User::factory()->create([
            'name' => 'Admin Test',
            'email' => 'admin.test@cic.ac.id',
            'role' => 'Admin',
            'status' => 'Aktif',
        ]);

        $this->kategoriSekolah = MasterData::create([
            'type' => 'kategori_sekolah',
            'nama' => 'SMA Negeri',
            'kode' => 'SMAN',
            'status' => 'Aktif',
        ]);

        $this->kategoriPerusahaan = MasterData::create([
            'type' => 'kategori_perusahaan',
            'nama' => 'BUMN',
            'kode' => 'BUMN',
            'status' => 'Aktif',
        ]);
    }

    /** 1. User CRUD */
    public function test_admin_can_create_update_toggle_and_delete_user()
    {
        // CREATE
        $response = $this->actingAs($this->admin)->post(route('admin.users.store'), [
            'name' => 'Sales Baru Test',
            'email' => 'sales.baru@cic.ac.id',
            'phone' => '081234567890',
            'role' => 'Sales',
            'status' => 'Aktif',
            'wilayah_id' => $this->wilayah->id,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['email' => 'sales.baru@cic.ac.id']);

        $user = User::where('email', 'sales.baru@cic.ac.id')->first();

        // UPDATE
        $updateResponse = $this->actingAs($this->admin)->put(route('admin.users.update', $user->id), [
            'name' => 'Sales Diperbarui',
            'email' => 'sales.baru@cic.ac.id',
            'phone' => '081299998888',
            'role' => 'Sales',
            'status' => 'Aktif',
            'wilayah_id' => $this->wilayah->id,
        ]);
        $updateResponse->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Sales Diperbarui', 'phone' => '081299998888']);

        // TOGGLE STATUS
        $toggleResponse = $this->actingAs($this->admin)->post(route('admin.users.toggle-status', $user->id));
        $toggleResponse->assertSessionHasNoErrors();
        $this->assertEquals('Nonaktif', $user->fresh()->status);

        // RESET PASSWORD
        $resetResponse = $this->actingAs($this->admin)->post(route('admin.users.reset-password', $user->id));
        $resetResponse->assertSessionHasNoErrors();

        // DELETE
        $deleteResponse = $this->actingAs($this->admin)->delete(route('admin.users.destroy', $user->id));
        $deleteResponse->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    /** 1b. Data Analyst User CRUD by Admin */
    public function test_admin_can_create_and_update_data_analyst_user()
    {
        // CREATE Data Analyst
        $response = $this->actingAs($this->admin)->post(route('admin.users.store'), [
            'name' => 'Data Analyst User Test',
            'email' => 'data.analyst.crud@cic.ac.id',
            'phone' => '081234567899',
            'role' => 'Data Analyst',
            'status' => 'Aktif',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['email' => 'data.analyst.crud@cic.ac.id', 'role' => 'Data Analyst']);

        $analyst = User::where('email', 'data.analyst.crud@cic.ac.id')->first();
        $this->assertNotNull($analyst->kode);
        $this->assertStringContainsString('D', $analyst->kode);

        // UPDATE Data Analyst
        $updateResponse = $this->actingAs($this->admin)->put(route('admin.users.update', $analyst->id), [
            'name' => 'Data Analyst Diperbarui',
            'email' => 'data.analyst.crud@cic.ac.id',
            'phone' => '081299990000',
            'role' => 'Data Analyst',
            'status' => 'Aktif',
        ]);
        $updateResponse->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', [
            'id' => $analyst->id,
            'name' => 'Data Analyst Diperbarui',
            'role' => 'Data Analyst',
        ]);
    }

    /** 2. Sekolah CRUD */
    public function test_admin_can_create_update_toggle_and_delete_sekolah()
    {
        // CREATE
        $response = $this->actingAs($this->admin)->post(route('admin.sekolah.store'), [
            'nama' => 'SMAN 1 Test City',
            'kategori_id' => $this->kategoriSekolah->id,
            'wilayah_id' => $this->wilayah->id,
            'kecamatan' => 'Kesambi',
            'status' => 'Aktif',
            'telepon' => '0231123456',
        ]);
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('sekolahs', ['nama' => 'SMAN 1 Test City']);

        $sekolah = Sekolah::where('nama', 'SMAN 1 Test City')->first();

        // UPDATE
        $updateResponse = $this->actingAs($this->admin)->put(route('admin.sekolah.update', $sekolah->id), [
            'nama' => 'SMAN 1 Test City Diperbarui',
            'kategori_id' => $this->kategoriSekolah->id,
            'wilayah_id' => $this->wilayah->id,
            'kecamatan' => 'Kejaksan',
            'status' => 'Aktif',
            'telepon' => '0231999999',
        ]);
        $updateResponse->assertSessionHasNoErrors();
        $this->assertDatabaseHas('sekolahs', ['id' => $sekolah->id, 'nama' => 'SMAN 1 Test City Diperbarui']);

        // TOGGLE STATUS
        $this->actingAs($this->admin)->post(route('admin.sekolah.toggle-status', $sekolah->id));
        $this->assertEquals('Nonaktif', $sekolah->fresh()->status);

        // DELETE
        $deleteResponse = $this->actingAs($this->admin)->delete(route('admin.sekolah.destroy', $sekolah->id));
        $deleteResponse->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('sekolahs', ['id' => $sekolah->id]);
    }

    /** 3. Wilayah CRUD */
    public function test_admin_can_create_update_toggle_and_delete_wilayah()
    {
        // CREATE
        $response = $this->actingAs($this->admin)->post(route('admin.wilayah.store'), [
            'kode' => 'KNG-01',
            'nama' => 'Kabupaten Kuningan',
            'kecamatans' => 'Cilimus, Jalaksana, Kramatmulya',
            'status' => 'Aktif',
        ]);
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('wilayahs', ['kode' => 'KNG-01', 'nama' => 'Kabupaten Kuningan']);

        $wilayah = Wilayah::where('kode', 'KNG-01')->first();
        $this->assertEquals(3, $wilayah->children()->count());

        // UPDATE
        $updateResponse = $this->actingAs($this->admin)->put(route('admin.wilayah.update', $wilayah->id), [
            'kode' => 'KNG-01',
            'nama' => 'Kabupaten Kuningan Raya',
            'kecamatans' => 'Cilimus, Jalaksana, Cigugur',
            'status' => 'Aktif',
        ]);
        $updateResponse->assertSessionHasNoErrors();
        $this->assertDatabaseHas('wilayahs', ['id' => $wilayah->id, 'nama' => 'Kabupaten Kuningan Raya']);

        // TOGGLE STATUS
        $this->actingAs($this->admin)->post(route('admin.wilayah.toggle-status', $wilayah->id));
        $this->assertEquals('Nonaktif', $wilayah->fresh()->status);

        // DELETE
        $deleteResponse = $this->actingAs($this->admin)->delete(route('admin.wilayah.destroy', $wilayah->id));
        $deleteResponse->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('wilayahs', ['id' => $wilayah->id]);
    }

    /** 4. Prodi CRUD */
    public function test_admin_can_create_update_toggle_and_delete_prodi()
    {
        // CREATE
        $response = $this->actingAs($this->admin)->post(route('admin.prodi.store'), [
            'kode' => 'TI-S1',
            'nama' => 'Teknik Informatika',
            'fakultas' => 'Teknologi Informasi',
            'jenjang' => 'S1',
            'kuota' => 150,
            'ukt' => '5000000',
            'status' => 'Aktif',
        ]);
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('prodis', ['kode' => 'TI-S1', 'nama' => 'Teknik Informatika']);

        $prodi = Prodi::where('kode', 'TI-S1')->first();

        // UPDATE
        $updateResponse = $this->actingAs($this->admin)->put(route('admin.prodi.update', $prodi->id), [
            'kode' => 'TI-S1',
            'nama' => 'Teknik Informatika & AI',
            'fakultas' => 'Teknologi Informasi',
            'jenjang' => 'S1',
            'kuota' => 200,
            'ukt' => '5500000',
            'status' => 'Aktif',
        ]);
        $updateResponse->assertSessionHasNoErrors();
        $this->assertDatabaseHas('prodis', ['id' => $prodi->id, 'nama' => 'Teknik Informatika & AI', 'kuota' => 200]);

        // TOGGLE STATUS
        $this->actingAs($this->admin)->post(route('admin.prodi.toggle-status', $prodi->id));
        $this->assertEquals('Nonaktif', $prodi->fresh()->status);

        // DELETE
        $deleteResponse = $this->actingAs($this->admin)->delete(route('admin.prodi.destroy', $prodi->id));
        $deleteResponse->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('prodis', ['id' => $prodi->id]);
    }

    /** 5. Perusahaan CRUD */
    public function test_admin_can_create_update_toggle_and_delete_perusahaan()
    {
        // CREATE
        $response = $this->actingAs($this->admin)->post(route('admin.perusahaan.store'), [
            'nama' => 'PT Telekomunikasi Test',
            'kategori_id' => $this->kategoriPerusahaan->id,
            'wilayah_id' => $this->wilayah->id,
            'status' => 'Aktif',
            'telepon' => '021987654',
        ]);
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('perusahaans', ['nama' => 'PT Telekomunikasi Test']);

        $perusahaan = Perusahaan::where('nama', 'PT Telekomunikasi Test')->first();

        // UPDATE
        $updateResponse = $this->actingAs($this->admin)->put(route('admin.perusahaan.update', $perusahaan->id), [
            'nama' => 'PT Telekomunikasi Test Global',
            'kategori_id' => $this->kategoriPerusahaan->id,
            'wilayah_id' => $this->wilayah->id,
            'status' => 'Aktif',
            'telepon' => '021987655',
        ]);
        $updateResponse->assertSessionHasNoErrors();
        $this->assertDatabaseHas('perusahaans', ['id' => $perusahaan->id, 'nama' => 'PT Telekomunikasi Test Global']);

        // TOGGLE STATUS
        $this->actingAs($this->admin)->post(route('admin.perusahaan.toggle-status', $perusahaan->id));
        $this->assertEquals('Nonaktif', $perusahaan->fresh()->status);

        // DELETE
        $deleteResponse = $this->actingAs($this->admin)->delete(route('admin.perusahaan.destroy', $perusahaan->id));
        $deleteResponse->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('perusahaans', ['id' => $perusahaan->id]);
    }

    /** 6. Master Data CRUD */
    public function test_admin_can_create_update_toggle_and_delete_master_data()
    {
        // CREATE
        $response = $this->actingAs($this->admin)->post(route('admin.master-data.store'), [
            'type' => 'sumber_prospek',
            'kode' => 'SRC-TEST',
            'nama' => 'Instagram Ads Test',
            'deskripsi' => 'Kampanye iklan medsos',
            'status' => 'Aktif',
        ]);
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('master_data', ['kode' => 'SRC-TEST', 'nama' => 'Instagram Ads Test']);

        $md = MasterData::where('kode', 'SRC-TEST')->first();

        // UPDATE
        $updateResponse = $this->actingAs($this->admin)->put(route('admin.master-data.update', $md->id), [
            'type' => 'sumber_prospek',
            'kode' => 'SRC-TEST',
            'nama' => 'TikTok & Instagram Ads',
            'deskripsi' => 'Kampanye digital terintegrasi',
            'status' => 'Aktif',
        ]);
        $updateResponse->assertSessionHasNoErrors();
        $this->assertDatabaseHas('master_data', ['id' => $md->id, 'nama' => 'TikTok & Instagram Ads']);

        // TOGGLE STATUS
        $this->actingAs($this->admin)->post(route('admin.master-data.toggle-status', $md->id));
        $this->assertEquals('Nonaktif', $md->fresh()->status);

        // DELETE
        $deleteResponse = $this->actingAs($this->admin)->delete(route('admin.master-data.destroy', $md->id));
        $deleteResponse->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('master_data', ['id' => $md->id]);
    }

    /** 7. Tahun Akademik CRUD */
    public function test_admin_can_create_activate_and_delete_tahun_akademik()
    {
        // CREATE
        $response = $this->actingAs($this->admin)->post(route('admin.tahun-akademik.store'), [
            'nama' => '2028/2029',
            'status' => 'Non-Aktif',
        ]);
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('tahun_akademiks', ['nama' => '2028/2029']);

        $ta = TahunAkademik::where('nama', '2028/2029')->first();

        // ACTIVATE
        $actResponse = $this->actingAs($this->admin)->post(route('admin.tahun-akademik.activate', $ta->id));
        $actResponse->assertSessionHasNoErrors();
        $this->assertEquals('Aktif', $ta->fresh()->status);

        // Re-activate original so we can delete the new one
        $orig = TahunAkademik::where('nama', '2027/2028')->first();
        \App\Services\AkademikService::activate($orig);

        // DELETE
        $delResponse = $this->actingAs($this->admin)->delete(route('admin.tahun-akademik.destroy', $ta->id));
        $delResponse->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('tahun_akademiks', ['id' => $ta->id]);
    }

    /** 8. Bank Account CRUD */
    public function test_admin_can_create_update_toggle_and_delete_bank_account()
    {
        // CREATE
        $response = $this->actingAs($this->admin)->post(route('admin.bank-accounts.store'), [
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_name' => 'Universitas CIC',
            'notes' => 'Rekening Penerimaan Registrasi',
            'is_active' => '1',
        ]);
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('bank_accounts', ['bank_name' => 'BCA', 'account_number' => '1234567890']);

        $bank = BankAccount::where('account_number', '1234567890')->first();

        // UPDATE
        $updateResponse = $this->actingAs($this->admin)->put(route('admin.bank-accounts.update', $bank->id), [
            'bank_name' => 'Bank Central Asia',
            'account_number' => '1234567890',
            'account_name' => 'Yayasan Universitas CIC',
            'notes' => 'Rekening Resmi PMB',
            'is_active' => '1',
        ]);
        $updateResponse->assertSessionHasNoErrors();
        $this->assertDatabaseHas('bank_accounts', ['id' => $bank->id, 'bank_name' => 'Bank Central Asia']);

        // TOGGLE STATUS
        $this->actingAs($this->admin)->post(route('admin.bank-accounts.toggle-status', $bank->id));
        $this->assertFalse((bool)$bank->fresh()->is_active);

        // DELETE
        $deleteResponse = $this->actingAs($this->admin)->delete(route('admin.bank-accounts.destroy', $bank->id));
        $deleteResponse->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('bank_accounts', ['id' => $bank->id]);
    }

    /** 9. Event Type CRUD */
    public function test_admin_can_create_update_and_delete_event_type()
    {
        // CREATE
        $response = $this->actingAs($this->admin)->post(route('admin.event-types.store'), [
            'nama' => 'Workshop AI & Data',
        ]);
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('master_data', ['nama' => 'Workshop AI & Data', 'type' => 'jenis_event']);

        $eventType = MasterData::where('nama', 'Workshop AI & Data')->where('type', 'jenis_event')->first();

        // UPDATE
        $updateResponse = $this->actingAs($this->admin)->put(route('admin.event-types.update', $eventType->id), [
            'nama' => 'Workshop AI & Data Science',
            'status' => 'Aktif',
        ]);
        $updateResponse->assertSessionHasNoErrors();
        $this->assertDatabaseHas('master_data', ['id' => $eventType->id, 'nama' => 'Workshop AI & Data Science']);

        // DELETE
        $deleteResponse = $this->actingAs($this->admin)->delete(route('admin.event-types.destroy', $eventType->id));
        $deleteResponse->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('master_data', ['id' => $eventType->id]);
    }

    /** 10. Work Calendar CRUD */
    public function test_admin_can_create_update_and_delete_work_calendar()
    {
        // CREATE
        $response = $this->actingAs($this->admin)->post(route('admin.work-calendar.store'), [
            'date' => '2028-12-25',
            'description' => 'Hari Libur Natal',
            'status' => 'holiday',
        ]);
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('work_calendars', ['date' => '2028-12-25']);

        $cal = WorkCalendar::where('date', '2028-12-25')->first();

        // UPDATE
        $updateResponse = $this->actingAs($this->admin)->put(route('admin.work-calendar.update', $cal->id), [
            'date' => '2028-12-25',
            'description' => 'Hari Raya Natal Bersama',
            'status' => 'holiday',
        ]);
        $updateResponse->assertSessionHasNoErrors();
        $this->assertDatabaseHas('work_calendars', ['id' => $cal->id, 'description' => 'Hari Raya Natal Bersama']);

        // DELETE
        $deleteResponse = $this->actingAs($this->admin)->delete(route('admin.work-calendar.destroy', $cal->id));
        $deleteResponse->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('work_calendars', ['id' => $cal->id]);
    }

    /** 11. Target CRUD */
    public function test_admin_can_create_update_and_delete_target()
    {
        $sales = User::factory()->create([
            'name' => 'Sales Target Test',
            'role' => 'Sales',
            'status' => 'Aktif',
            'wilayah_id' => $this->wilayah->id,
        ]);

        // CREATE
        $response = $this->actingAs($this->admin)->post(route('admin.target.store'), [
            'sales_id' => $sales->id,
            'wilayah_id' => $this->wilayah->id,
            'tipe_periode' => 'Bulanan',
            'tanggal_mulai' => now()->startOfMonth()->toDateString(),
            'tanggal_selesai' => now()->endOfMonth()->toDateString(),
            'target_lunas' => 10,
            'target_formulir' => 20,
            'target_kontak' => 50,
            'target_followup' => 40,
            'target_kunjungan' => 15,
            'status' => 'Aktif',
            'tahun_akademik' => '2027/2028',
        ]);
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('targets', ['sales_id' => $sales->id, 'target_lunas' => 10]);

        $target = Target::where('sales_id', $sales->id)->first();

        // UPDATE
        $updateResponse = $this->actingAs($this->admin)->put(route('admin.target.update', $target->id), [
            'sales_id' => $sales->id,
            'wilayah_id' => $this->wilayah->id,
            'tipe_periode' => 'Bulanan',
            'tanggal_mulai' => now()->startOfMonth()->toDateString(),
            'tanggal_selesai' => now()->endOfMonth()->toDateString(),
            'target_lunas' => 12,
            'target_formulir' => 25,
            'target_kontak' => 60,
            'target_followup' => 45,
            'target_kunjungan' => 18,
            'status' => 'Aktif',
            'tahun_akademik' => '2027/2028',
        ]);
        $updateResponse->assertSessionHasNoErrors();
        $this->assertDatabaseHas('targets', ['id' => $target->id, 'target_lunas' => 12]);

        // DELETE
        $deleteResponse = $this->actingAs($this->admin)->delete(route('admin.target.destroy', $target->id));
        $deleteResponse->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('targets', ['id' => $target->id]);
    }

    /** 12. Prospek CRUD by Admin */
    public function test_admin_can_create_update_and_delete_prospek()
    {
        // CREATE
        $response = $this->actingAs($this->admin)->post(route('prospek.store'), [
            'name' => 'Prospek Admin Test',
            'pic' => 'Prospek Admin Test',
            'whatsapp' => '081234560000',
            'type' => 'Individu',
            'status' => 'BARU',
            'wilayah_id' => $this->wilayah->id,
        ]);
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('prospeks', ['name' => 'Prospek Admin Test']);

        $prospek = Prospek::where('name', 'Prospek Admin Test')->first();

        // UPDATE
        $updateResponse = $this->actingAs($this->admin)->put(route('prospek.update', $prospek->id), [
            'name' => 'Prospek Admin Test Diperbarui',
            'pic' => 'Prospek Admin Test Diperbarui',
            'whatsapp' => '081234560001',
            'type' => 'Individu',
            'status' => 'DATA DIHUBUNGI',
        ]);
        $updateResponse->assertSessionHasNoErrors();
        $this->assertDatabaseHas('prospeks', ['id' => $prospek->id, 'name' => 'Prospek Admin Test Diperbarui']);

        // DELETE
        $deleteResponse = $this->actingAs($this->admin)->delete(route('prospek.destroy', $prospek->id));
        $deleteResponse->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('prospeks', ['id' => $prospek->id]);
    }
}
