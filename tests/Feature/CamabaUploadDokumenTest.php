<?php

namespace Tests\Feature;

use App\Models\Prospek;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CamabaUploadDokumenTest extends TestCase
{
    use RefreshDatabase;

    protected User $camaba;
    protected Prospek $prospek;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->camaba = User::create([
            'name'     => 'Budi Pratama',
            'email'    => 'budi.pratama@gmail.com',
            'role'     => 'Camaba',
            'status'   => 'Aktif',
            'password' => Hash::make('password123'),
        ]);

        $this->prospek = Prospek::create([
            'user_id'         => $this->camaba->id,
            'name'            => 'Budi Pratama',
            'email'           => 'budi.pratama@gmail.com',
            'whatsapp'        => '081234567890',
            'no_registrasi'   => 'PMB-2026-0001',
            'status'          => 'BARU',
            'stage_number'    => 1,
            'status_formulir' => 'Belum Isi',
            'status_berkas'   => 'Belum Upload',
            'status_ujian'    => 'Belum Mengerjakan',
        ]);
    }

    /**
     * Test 1: Tamu (Guest) tidak dapat mengakses halaman upload dokumen dan diarahkan ke login.
     */
    public function test_guest_cannot_access_upload_dokumen_page(): void
    {
        $response = $this->get(route('pmb.akun.upload-dokumen'));
        $response->assertRedirect('/login');
    }

    /**
     * Test 2: CAMABA terautentikasi dapat mengakses halaman Upload Dokumen Persyaratan.
     */
    public function test_camaba_can_access_upload_dokumen_page(): void
    {
        $response = $this->actingAs($this->camaba)->get(route('pmb.akun.upload-dokumen'));

        $response->assertOk();
        $response->assertSee('Upload Dokumen Persyaratan');
        $response->assertSee('Progress Upload Dokumen');
        $response->assertSee('0 dari 7 dokumen telah berhasil diupload');
        $response->assertSee('Informasi Penting');

        // Pastikan ketujuh kartu dokumen tampil
        $response->assertSee('Pas Foto Formal');
        $response->assertSee('Kartu Tanda Penduduk (KTP)');
        $response->assertSee('Kartu Keluarga (KK)');
        $response->assertSee('Akta Kelahiran');
        $response->assertSee('Ijazah / Surat Keterangan Lulus (SKL)');
        $response->assertSee('Transkrip Nilai / Rapor');
        $response->assertSee('Surat Keterangan Bebas Narkoba / Sehat');
    }

    /**
     * Test 3: CAMABA dapat mengunggah dokumen pas foto yang valid dan tersimpan di database & storage.
     */
    public function test_camaba_can_upload_valid_pas_foto(): void
    {
        $file = UploadedFile::fake()->image('foto_formal.jpg', 600, 800);

        $response = $this->actingAs($this->camaba)->post(route('pmb.akun.upload-dokumen.store'), [
            'dokumen_type' => 'pas_foto',
            'dokumen_file' => $file,
        ]);

        $response->assertRedirect(route('pmb.akun.upload-dokumen'));
        $response->assertSessionHas('success');

        $this->prospek->refresh();
        $this->assertEquals('Pending', $this->prospek->status_berkas);

        $berkasData = $this->prospek->berkas_data;
        $this->assertIsArray($berkasData);
        $this->assertArrayHasKey('pas_foto', $berkasData);
        $this->assertEquals('Menunggu Verifikasi', $berkasData['pas_foto']['status']);
        $this->assertEquals('foto_formal.jpg', $berkasData['pas_foto']['original_name']);

        // Pastikan file tersimpan pada storage private 'local'
        Storage::disk('local')->assertExists($berkasData['pas_foto']['file_path']);
    }

    /**
     * Test 4: Sistem menolak ekstensi file yang tidak diperbolehkan (misal file .exe atau .php).
     */
    public function test_upload_rejects_invalid_file_extension(): void
    {
        $file = UploadedFile::fake()->create('dokumen_jahat.exe', 500);

        $response = $this->actingAs($this->camaba)->post(route('pmb.akun.upload-dokumen.store'), [
            'dokumen_type' => 'ktp',
            'dokumen_file' => $file,
        ]);

        $response->assertSessionHasErrors('dokumen_file');

        $this->prospek->refresh();
        $this->assertEquals('Belum Upload', $this->prospek->status_berkas);
    }

    /**
     * Test 5: Sistem menolak file yang ukurannya melebihi batas maksimal 2MB (2048 KB).
     */
    public function test_upload_rejects_oversized_file(): void
    {
        $oversizedFile = UploadedFile::fake()->create('ktp_terlalu_besar.pdf', 3000); // 3MB

        $response = $this->actingAs($this->camaba)->post(route('pmb.akun.upload-dokumen.store'), [
            'dokumen_type' => 'ktp',
            'dokumen_file' => $oversizedFile,
        ]);

        $response->assertSessionHasErrors('dokumen_file');

        $this->prospek->refresh();
        $this->assertEquals('Belum Upload', $this->prospek->status_berkas);
    }

    /**
     * Test 6: CAMABA dapat melihat file dokumen yang sudah berhasil diunggah.
     */
    public function test_camaba_can_view_uploaded_document(): void
    {
        $file = UploadedFile::fake()->create('ktp_budi.pdf', 800, 'application/pdf');

        $this->actingAs($this->camaba)->post(route('pmb.akun.upload-dokumen.store'), [
            'dokumen_type' => 'ktp',
            'dokumen_file' => $file,
        ]);

        $response = $this->actingAs($this->camaba)->get(route('pmb.akun.dokumen.view', 'ktp'));
        $response->assertOk();
    }

    /**
     * Test 7: CAMABA tidak dapat melihat atau mengunduh dokumen milik CAMABA lain (Isolasi data & Keamanan).
     */
    public function test_camaba_cannot_view_other_camaba_document(): void
    {
        // 1. Upload dokumen oleh CAMABA 1
        $file = UploadedFile::fake()->create('ktp_budi.pdf', 500, 'application/pdf');
        $this->actingAs($this->camaba)->post(route('pmb.akun.upload-dokumen.store'), [
            'dokumen_type' => 'ktp',
            'dokumen_file' => $file,
        ]);

        // 2. Buat CAMABA 2 yang belum upload dokumen
        $otherCamaba = User::create([
            'name'     => 'Siti Aisyah',
            'email'    => 'siti.aisyah@gmail.com',
            'role'     => 'Camaba',
            'status'   => 'Aktif',
            'password' => Hash::make('password123'),
        ]);

        Prospek::create([
            'user_id'         => $otherCamaba->id,
            'name'            => 'Siti Aisyah',
            'email'           => 'siti.aisyah@gmail.com',
            'whatsapp'        => '089988776655',
            'status'          => 'BARU',
            'status_berkas'   => 'Belum Upload',
        ]);

        // 3. CAMABA 2 mencoba mengakses view dokumen ktp
        // Sistem hanya mencari dokumen milik akun CAMABA 2 yang sedang login, sehingga mengembalikan 404
        $response = $this->actingAs($otherCamaba)->get(route('pmb.akun.dokumen.view', 'ktp'));
        $response->assertStatus(404);
    }

    /**
     * Test 8: Ketika semua 7 dokumen berhasil diunggah, status_berkas otomatis menjadi 'Sudah Upload'
     * dan progress bar mencapai 100%.
     */
    public function test_completing_all_seven_documents_updates_status_berkas_to_sudah_upload(): void
    {
        $types = ['pas_foto', 'ktp', 'kk', 'akta', 'ijazah', 'transkrip', 'surat_keterangan'];

        foreach ($types as $type) {
            $ext = $type === 'pas_foto' ? 'jpg' : 'pdf';
            $file = UploadedFile::fake()->create("{$type}.{$ext}", 300);

            $this->actingAs($this->camaba)->post(route('pmb.akun.upload-dokumen.store'), [
                'dokumen_type' => $type,
                'dokumen_file' => $file,
            ]);
        }

        $this->prospek->refresh();
        $this->assertEquals('Sudah Upload', $this->prospek->status_berkas);

        $viewResponse = $this->actingAs($this->camaba)->get(route('pmb.akun.upload-dokumen'));
        $viewResponse->assertOk();
        $viewResponse->assertSee('7 dari 7 dokumen telah berhasil diupload (100%)');
    }

    /**
     * Test 9: CAMABA dapat menghapus dokumen yang belum disetujui untuk diunggah ulang.
     */
    public function test_camaba_can_delete_unverified_document(): void
    {
        $file = UploadedFile::fake()->image('pas_foto.jpg');
        $this->actingAs($this->camaba)->post(route('pmb.akun.upload-dokumen.store'), [
            'dokumen_type' => 'pas_foto',
            'dokumen_file' => $file,
        ]);

        $this->prospek->refresh();
        $this->assertArrayHasKey('pas_foto', $this->prospek->berkas_data);

        $deleteResponse = $this->actingAs($this->camaba)->delete(route('pmb.akun.dokumen.delete', 'pas_foto'));
        $deleteResponse->assertRedirect(route('pmb.akun.upload-dokumen'));
        $deleteResponse->assertSessionHas('success');

        $this->prospek->refresh();
        $this->assertArrayNotHasKey('pas_foto', $this->prospek->berkas_data ?? []);
        $this->assertEquals('Belum Upload', $this->prospek->status_berkas);
    }

    /**
     * Test 10: Tombol Upload Dokumen di Dashboard mengarah ke route upload-dokumen yang benar.
     */
    public function test_dashboard_links_correctly_to_upload_dokumen_page(): void
    {
        $response = $this->actingAs($this->camaba)->get(route('pmb.akun.dashboard'));
        $response->assertOk();
        $response->assertSee(route('pmb.akun.upload-dokumen'));
    }

    /**
     * Test 11: Route alias /user/upload-dokumen me-redirect ke route pmb.akun.upload-dokumen.
     */
    public function test_user_upload_dokumen_alias_redirects(): void
    {
        $response = $this->actingAs($this->camaba)->get('/user/upload-dokumen');
        $response->assertRedirect(route('pmb.akun.upload-dokumen'));
    }
}
