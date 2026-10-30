<?php

namespace Tests\Feature;

use App\Models\Prospek;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CamabaBiayaDokumenPdfTest extends TestCase
{
    use RefreshDatabase;

    protected User $camaba;
    protected Prospek $prospek;

    protected function setUp(): void
    {
        parent::setUp();

        $this->camaba = User::create([
            'name'     => 'Dimas Maulana',
            'email'    => 'dimas.maulana@gmail.com',
            'role'     => 'Camaba',
            'status'   => 'Aktif',
            'password' => Hash::make('password123'),
        ]);

        $this->prospek = Prospek::create([
            'user_id'         => $this->camaba->id,
            'name'            => 'Dimas Maulana',
            'email'           => 'dimas.maulana@gmail.com',
            'whatsapp'        => '081234567811',
            'no_registrasi'   => 'PMB-2026-0003',
            'status'          => 'BARU',
            'stage_number'    => 1,
            'status_formulir' => 'Belum Isi',
            'status_berkas'   => 'Belum Upload',
            'status_ujian'    => 'Belum Mengerjakan',
        ]);
    }

    /**
     * Test 1: File fisik PDF dan Excel Rincian Biaya PMB UCIC 2027 tersimpan dengan benar di direktori public.
     */
    public function test_physical_pdf_and_excel_files_exist_in_project(): void
    {
        $pdfPath = public_path('documents/rincian-biaya-pmb-ucic-2027.pdf');
        $excelPath = public_path('documents/Rincian Biaya PMB UCIC 2027.xlsx');

        $this->assertFileExists($pdfPath, 'File PDF rincian biaya harus ada di public/documents/');
        $this->assertGreaterThan(50000, filesize($pdfPath), 'Ukuran PDF harus valid dan memuat konten resmi.');

        $this->assertFileExists($excelPath, 'File Excel rincian biaya harus ada di public/documents/');
        $this->assertGreaterThan(1000, filesize($excelPath), 'Ukuran Excel harus valid.');
    }

    /**
     * Test 2: CAMABA dapat mengakses halaman Biaya dan melihat PDF Document Viewer resmi.
     */
    public function test_camaba_can_access_biaya_page_with_pdf_viewer(): void
    {
        $response = $this->actingAs($this->camaba)->get(route('pmb.akun.status'));

        $response->assertOk();
        $response->assertSee('Detail Biaya Kuliah');
        $response->assertSee('Dokumen Biaya Kuliah');
        $response->assertSee('Informasi lengkap biaya kuliah seluruh program studi UCIC');

        // Pastikan dokumen PDF dimuat
        $response->assertSee('documents/rincian-biaya-pmb-ucic-2027.pdf');

        // Pastikan tombol aksi PDF tersedia
        $response->assertSee('Unduh Dokumen PDF');
        $response->assertSee('Buka Dokumen di Tab Baru');
        $response->assertSee('Perbesar / Layar Penuh');

        // Pastikan kontrol viewer halaman & zoom tersedia
        $response->assertSee('Hal Sebelumnya');
        $response->assertSee('Hal Berikutnya');
        $response->assertSee('Reset Zoom');

        // Pastikan TIDAK ADA lagi embed Google Sheets atau docs.google.com
        $response->assertDontSee('docs.google.com/spreadsheets');
        $response->assertDontSee('sheets.google.com');
    }

    /**
     * Test 3: Seluruh nominal biaya kuliah asli tetap dipertahankan pada halaman.
     */
    public function test_original_tuition_fees_are_retained(): void
    {
        $response = $this->actingAs($this->camaba)->get(route('pmb.akun.status'));

        $response->assertOk();
        $response->assertSee('S1 Teknik Informatika');
        $response->assertSee('Rp 3.500.000');
        $response->assertSee('S1 Sistem Informasi');
        $response->assertSee('Rp 3.200.000');
        $response->assertSee('S1 Desain Komunikasi Visual (DKV)');
        $response->assertSee('Rp 3.400.000');
        $response->assertSee('S1 Bisnis Digital');
        $response->assertSee('Rp 3.250.000');
        $response->assertSee('S1 Manajemen Bisnis');
        $response->assertSee('Rp 3.000.000');
        $response->assertSee('S1 Akuntansi');
        $response->assertSee('Rp 3.000.000');
        $response->assertSee('D3 Manajemen Bisnis');
        $response->assertSee('Rp 2.500.000');
        $response->assertSee('S2 Magister Manajemen (Tanpa Tesis)');
        $response->assertSee('Rp 6.000.000');
        $response->assertSee('Rp 300.000'); // Biaya formulir
    }

    /**
     * Test 4: Tamu tidak terautentikasi dicegah mengakses halaman Biaya dan dialihkan ke login.
     */
    public function test_guest_is_redirected_to_login_when_accessing_biaya_page(): void
    {
        $response = $this->get(route('pmb.akun.status'));
        $response->assertRedirect('/login');
    }
}

