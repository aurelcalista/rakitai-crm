<?php

namespace Tests\Feature;

use App\Models\Prodi;
use App\Models\Prospek;
use App\Models\Sekolah;
use App\Models\TahunAkademik;
use App\Models\User;
use App\Models\Wilayah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ProspectImportTest extends TestCase
{
    use RefreshDatabase;

    protected User $csUser;
    protected User $salesUser;

    protected function setUp(): void
    {
        parent::setUp();

        $wilayah = Wilayah::create(['nama' => 'Kota Cirebon', 'kode' => 'CRB', 'status' => 'Aktif']);
        TahunAkademik::create(['nama' => '2026/2027', 'is_active' => true]);

        Prodi::create([
            'kode' => 'S2-MNJ',
            'nama' => 'S2 Manajemen (Tanpa Tesis)',
            'jenjang' => 'S2',
            'fakultas' => 'Pascasarjana',
            'kuota' => 50,
            'status' => 'Aktif',
        ]);

        Prodi::create([
            'kode' => 'DKV',
            'nama' => 'DKV',
            'jenjang' => 'S1',
            'fakultas' => 'Fakultas Teknologi Informasi',
            'kuota' => 100,
            'status' => 'Aktif',
        ]);

        $this->csUser = User::factory()->create([
            'name'       => 'Staff CS',
            'role'       => 'CS',
            'wilayah_id' => $wilayah->id,
            'status'     => 'Aktif',
        ]);

        $this->salesUser = User::factory()->create([
            'name'       => 'Sales Lapangan',
            'role'       => 'Sales',
            'wilayah_id' => $wilayah->id,
            'status'     => 'Aktif',
        ]);
    }

    public function test_non_cs_cannot_access_import_routes()
    {
        $file = UploadedFile::fake()->createWithContent('leads.csv', "Nama Leads,Phone\nBudi,081234567890");

        $response = $this->actingAs($this->salesUser)->postJson(route('cs.prospek.import.preview'), [
            'file' => $file,
        ]);

        $response->assertStatus(403);
    }

    public function test_cs_can_upload_and_preview_csv_with_auto_mapping_and_notes_preservation()
    {
        // Real sample from Google Sheets
        $csvContent = "Nama Leads,Status Diri,Minat Studi,Sumber Leads,Prospek,Phone,Notes,Email Tambahan,Pekerjaan\n" .
                      "Asep Hidayat,,S2 Magister Manajemen,Alumni,,0895324355090,,asep@gmail.com,Staff BUMN\n" .
                      "Riyanthie Amalia Putri,,DKV,SMKN 1 KEDAWUNG,,083838927288,Catatan khusus,riyan@gmail.com,Pelajar\n" .
                      "Invalid Person,,,,,,,\n" . // Invalid row: no phone and no name
                      "Hanan salmen,,S2 Magister Manajemen,Alumni,,081214800953,,hanan@gmail.com,Wiraswasta\n";

        $file = UploadedFile::fake()->createWithContent('leads_sample.csv', $csvContent);

        $response = $this->actingAs($this->csUser)->postJson(route('cs.prospek.import.preview'), [
            'file' => $file,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_rows', 4)
            ->assertJsonPath('data.valid_count', 3)
            ->assertJsonPath('data.invalid_count', 1);

        $responseData = $response->json('data');
        $this->assertNotEmpty($responseData['token']);
        $this->assertCount(9, $responseData['headers']);

        // Check header auto-mapping
        $mappingHeaders = collect($responseData['headers'])->keyBy('header');
        $this->assertEquals('name', $mappingHeaders['Nama Leads']['target_field']);
        $this->assertEquals('prodi_name', $mappingHeaders['Minat Studi']['target_field']);
        $this->assertEquals('whatsapp', $mappingHeaders['Phone']['target_field']);
        $this->assertEquals('extra_to_notes', $mappingHeaders['Email Tambahan']['target_field']);
        $this->assertEquals('extra_to_notes', $mappingHeaders['Pekerjaan']['target_field']);
    }

    public function test_cs_can_execute_mass_import_and_prospects_are_stored_correctly()
    {
        $csvContent = "Nama Leads,Status Diri,Minat Studi,Sumber Leads,Prospek,Phone,Notes,Pekerjaan\n" .
                      "Asep Hidayat,,S2 Magister Manajemen,Alumni,,0895324355090,,Manager PT XYZ\n" .
                      "Riyanthie Amalia Putri,,DKV,SMKN 1 KEDAWUNG,,083838927288,,Pelajar SMK\n";

        $file = UploadedFile::fake()->createWithContent('import_data.csv', $csvContent);

        $previewRes = $this->actingAs($this->csUser)->postJson(route('cs.prospek.import.preview'), [
            'file' => $file,
        ]);
        $previewRes->assertOk();
        $token = $previewRes->json('data.token');

        // Execute import
        $processRes = $this->actingAs($this->csUser)->postJson(route('cs.prospek.import.process'), [
            'token'          => $token,
            'default_source' => 'Google Sheets Import',
            'default_status' => 'BARU',
            'on_duplicate'   => 'skip',
        ]);

        $processRes->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.imported_count', 2)
            ->assertJsonPath('data.skipped_count', 0);

        // Verify database records
        $asep = Prospek::where('whatsapp', '+62895324355090')->first();
        $this->assertNotNull($asep);
        $this->assertEquals('Asep Hidayat', $asep->name);
        $this->assertEquals($this->csUser->id, $asep->cs_id);
        $this->assertEquals($this->csUser->id, $asep->owner_id);
        $this->assertEquals('Sudah Di-takeover', $asep->status_takeover);
        $this->assertEquals('BARU', $asep->status);
        $this->assertStringContainsString('Manager PT XYZ', $asep->notes);
        $this->assertNotNull($asep->prodi_id);

        $riyan = Prospek::where('whatsapp', '+6283838927288')->first();
        $this->assertNotNull($riyan);
        $this->assertEquals('Riyanthie Amalia Putri', $riyan->name);
        $this->assertEquals('Pelajar SMK', explode('• Pekerjaan: ', $riyan->notes)[1] ?? '');

        // Verify timeline created
        $this->assertTrue($asep->timelines()->where('title', 'Import Massal Spreadsheet')->exists());
    }

    public function test_duplicate_prevention_on_import()
    {
        // Create an existing prospect with this phone
        Prospek::create([
            'name'         => 'Existing Person',
            'type'         => 'Individu',
            'whatsapp'     => '+62895324355090',
            'status'       => 'FORMULIR',
            'stage_number' => 5,
            'cs_id'        => $this->csUser->id,
            'owner_id'     => $this->csUser->id,
        ]);

        $csvContent = "Nama Leads,Phone\n" .
                      "Asep Hidayat Duplikat,0895324355090\n" .
                      "New Person,081234567899\n";

        $file = UploadedFile::fake()->createWithContent('dupl_test.csv', $csvContent);

        $previewRes = $this->actingAs($this->csUser)->postJson(route('cs.prospek.import.preview'), [
            'file' => $file,
        ]);
        $previewRes->assertOk();
        $this->assertEquals(1, $previewRes->json('data.duplicate_count'));
        $this->assertEquals(1, $previewRes->json('data.valid_count'));

        $token = $previewRes->json('data.token');

        // Process with skip
        $processRes = $this->actingAs($this->csUser)->postJson(route('cs.prospek.import.process'), [
            'token'        => $token,
            'on_duplicate' => 'skip',
        ]);

        $processRes->assertOk()
            ->assertJsonPath('data.imported_count', 1)
            ->assertJsonPath('data.skipped_count', 1);

        // Ensure original record not overwritten
        $existing = Prospek::where('whatsapp', '+62895324355090')->first();
        $this->assertEquals('Existing Person', $existing->name);
        $this->assertEquals('FORMULIR', $existing->status);
    }

    public function test_cs_can_download_import_template()
    {
        $response = $this->actingAs($this->csUser)->get(route('cs.prospek.import.template'));
        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_import_with_various_google_forms_and_sheet_formats()
    {
        // 1. Format: Mini Bootcamp / Sekolah
        $csvBootcamp = "Timestamp,Nama,Asal Sekolah,Nomor WhatsApp Pribadi,blast wa,Nomor WhatsApp Aktif Orang Tua,Mini Boothcamp Pilihanmu?,Apa yang Kamu Harapkan Setelah Belajar di Mini Bothcamp ini?,Dari mana Kamu Tahu Mini Bothcamp?,Apakah kamu Berminat Melanjutkan ke Perkuliahan?\n" .
            "2025-11-11 10:15:27,Asyraf Fadhlurrohman,SMAN 6 CIREBON,089529967394,https://wa.me/62089529967394,081224480671,\"(BISNIS DIGITAL) Startup Starter Pack: Belajar Jadi Pengusaha Digital!\",mendapatkan ilmu mengenai teknologi,Sosialisasi UCIC di Sekolah,Minat\n" .
            "2025-11-11 10:16:38,Rafa Ghiyas Rahman,SMA NEGERI 6 CIREBON,082317697203,https://wa.me/62082317697203,082128556111,\"(RAKIT AI) Membuat Robot dengan Fitur AI dan IoT\",Pengalaman baru,expo,Minat\n";

        $file1 = UploadedFile::fake()->createWithContent('bootcamp.csv', $csvBootcamp);
        $res1 = $this->actingAs($this->csUser)->postJson(route('cs.prospek.import.preview'), ['file' => $file1]);
        $res1->assertOk();
        $this->assertEquals(2, $res1->json('data.valid_count'));

        $proc1 = $this->actingAs($this->csUser)->postJson(route('cs.prospek.import.process'), [
            'token' => $res1->json('data.token'),
        ]);
        $proc1->assertOk()->assertJsonPath('data.imported_count', 2);

        $asyraf = Prospek::where('whatsapp', '+6289529967394')->first();
        $this->assertNotNull($asyraf);
        $this->assertEquals('Asyraf Fadhlurrohman', $asyraf->name);
        $this->assertEquals('+6281224480671', $asyraf->wa_ortu);
        $this->assertEquals('Sekolah', $asyraf->type);
        $this->assertStringContainsString('SMAN 6 CIREBON', $asyraf->sekolah?->nama ?? '');
        $this->assertStringContainsString('mendapatkan ilmu mengenai teknologi', $asyraf->notes);

        // 2. Format: Alumni S2 Tracer / Survey
        $csvAlumni = "Timestamp,Email Address,Nama Lengkap,No WA,WA.ME,Alumni angkatan berapa nih?,Dulu kuliah jurusan apa kak?,Saat ini berkerja dimana dan posisinya sebagai apa?,Apakah berminat studi lanjut di UCIC?,Column 1\n" .
            "2026-08-30 15:41:43,m.rachmat.maulana@gmail.com,Mohammad Rachmat Maulana,081210270953,https://wa.me/62081210270953,2005,Manajemen Informatika,PT Lima Delapan Karya Bersama - Direktur Utama,Gass Lanjut S2,Catatan alumni penting\n";

        $file2 = UploadedFile::fake()->createWithContent('alumni.csv', $csvAlumni);
        $res2 = $this->actingAs($this->csUser)->postJson(route('cs.prospek.import.preview'), ['file' => $file2]);
        $res2->assertOk();

        $proc2 = $this->actingAs($this->csUser)->postJson(route('cs.prospek.import.process'), [
            'token' => $res2->json('data.token'),
        ]);
        $proc2->assertOk()->assertJsonPath('data.imported_count', 1);

        $rachmat = Prospek::where('whatsapp', '+6281210270953')->first();
        $this->assertNotNull($rachmat);
        $this->assertEquals('Mohammad Rachmat Maulana', $rachmat->name);
        $this->assertStringContainsString('PT Lima Delapan Karya Bersama - Direktur Utama', $rachmat->notes);
        $this->assertStringContainsString('Catatan alumni penting', $rachmat->notes);
    }

    public function test_import_button_and_modal_visible_only_to_cs_and_admin()
    {
        // 1. As CS: Import button and modal should be visible
        $csRes = $this->actingAs($this->csUser)->get(route('prospek.index'));
        $csRes->assertOk();
        $csRes->assertSee('Import Spreadsheet');
        $csRes->assertSee('modalImportProspek');

        // 2. As Sales: Import button should NOT be visible
        $salesRes = $this->actingAs($this->salesUser)->get(route('prospek.index'));
        $salesRes->assertOk();
        $salesRes->assertDontSee('Import Spreadsheet');
    }

    public function test_import_with_real_xlsx_file()
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['Nama Leads', 'Phone', 'Minat Studi', 'Nama Sekolah', 'Alamat Email', 'Notes'],
            ['John Doe', '081234567891', 'S2 Magister Manajemen', 'Universitas CIC', 'john@gmail.com', 'Catatan test xlsx'],
            ['Jane Doe', '089876543210', 'DKV', 'SMKN 1 KEDAWUNG', 'jane@gmail.com', 'Catatan test xlsx 2'],
        ]);

        $tempPath = tempnam(sys_get_temp_dir(), 'xlsx_test_') . '.xlsx';
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($tempPath);

        $uploadedFile = new UploadedFile($tempPath, 'leads_real.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $res = $this->actingAs($this->csUser)->postJson(route('cs.prospek.import.preview'), [
            'file' => $uploadedFile,
        ]);

        $res->assertOk()
            ->assertJsonPath('data.total_rows', 2)
            ->assertJsonPath('data.valid_count', 2);

        $proc = $this->actingAs($this->csUser)->postJson(route('cs.prospek.import.process'), [
            'token' => $res->json('data.token'),
        ]);

        $proc->assertOk()->assertJsonPath('data.imported_count', 2);

        $john = Prospek::where('whatsapp', '+6281234567891')->first();
        $this->assertNotNull($john);
        $this->assertEquals('John Doe', $john->name);
        $this->assertStringContainsString('john@gmail.com', $john->notes);

        if (file_exists($tempPath)) {
            @unlink($tempPath);
        }
    }

    public function test_import_all_option_allows_importing_all_duplicates_and_name_only_rows()
    {
        // Pre-create an existing prospect with same phone
        Prospek::create([
            'name'         => 'Existing Student',
            'type'         => 'Individu',
            'whatsapp'     => '+6281234567890',
            'status'       => 'BARU',
            'stage_number' => 1,
            'cs_id'        => $this->csUser->id,
            'owner_id'     => $this->csUser->id,
        ]);

        $csv = "Nama,Phone,Asal Sekolah\n" .
            "Siswa Satu,081234567890,SMKN 1 KEDAWUNG\n" . // Duplicate in DB
            "Siswa Satu Lagi,081234567890,SMKN 1 KEDAWUNG\n" . // Duplicate in file
            "Siswa Tanpa WA,ada,SMK WAHIDIN\n" . // Incomplete: no phone, but valid name
            "Siswa Baru,089999999999,SMAN 1 CIREBON\n"; // Valid new

        $file = UploadedFile::fake()->createWithContent('leads_mixed.csv', $csv);

        $res = $this->actingAs($this->csUser)->postJson(route('cs.prospek.import.preview'), [
            'file' => $file,
        ]);
        $res->assertOk()
            ->assertJsonPath('data.total_rows', 4)
            ->assertJsonPath('data.valid_count', 1)
            ->assertJsonPath('data.duplicate_count', 2)
            ->assertJsonPath('data.invalid_count', 1)
            ->assertJsonPath('data.name_only_count', 1);

        $token = $res->json('data.token');

        // Test with on_duplicate = 'import_all' and import_incomplete = 'import_valid_name'
        $proc = $this->actingAs($this->csUser)->postJson(route('cs.prospek.import.process'), [
            'token'             => $token,
            'on_duplicate'      => 'import_all',
            'import_incomplete' => 'import_valid_name',
        ]);

        // ALL 4 rows should be imported!
        $proc->assertOk()
            ->assertJsonPath('data.imported_count', 4)
            ->assertJsonPath('data.skipped_count', 0);

        $this->assertEquals(3, Prospek::where('whatsapp', '+6281234567890')->count());
        $this->assertNotNull(Prospek::where('name', 'Siswa Tanpa WA')->whereNull('whatsapp')->first());
    }

    public function test_imported_prospects_are_linked_to_schools_and_accessible_in_imported_scope()
    {
        $csvContent = implode("\n", [
            'Nama Lengkap,No WhatsApp,Asal Sekolah,Pilihan Prodi',
            'Alif Kurniawan,081299887766,SMAN 1 CIREBON,Informatika',
            'Bella Safitri,081299887767,SMK NEGERI 1 KEDAWUNG,DKV',
        ]);

        $file = UploadedFile::fake()->createWithContent('leads_schools.csv', $csvContent);

        $res = $this->actingAs($this->csUser)->postJson(route('cs.prospek.import.preview'), [
            'file' => $file,
        ]);
        $res->assertOk();
        $token = $res->json('data.token');

        $proc = $this->actingAs($this->csUser)->postJson(route('cs.prospek.import.process'), [
            'token' => $token,
        ]);
        $proc->assertOk()->assertJsonPath('data.imported_count', 2);

        // Verify school relations in DB
        $p1 = Prospek::where('whatsapp', '+6281299887766')->first();
        $this->assertNotNull($p1);
        $this->assertNotNull($p1->sekolah_id);
        $this->assertStringContainsString('SMAN 1 CIREBON', $p1->sekolah->nama);

        $p2 = Prospek::where('whatsapp', '+6281299887767')->first();
        $this->assertNotNull($p2);
        $this->assertNotNull($p2->sekolah_id);
        $this->assertStringContainsString('KEDAWUNG', $p2->sekolah->nama);

        // Access prospek index with scope=imported
        $viewRes = $this->actingAs($this->csUser)->get(route('prospek.index', ['scope' => 'imported']));
        $viewRes->assertOk();
        $viewRes->assertSee('Data Diimpor');
        $viewRes->assertSee('Alif Kurniawan');
        $viewRes->assertSee('Bella Safitri');
    }
}
