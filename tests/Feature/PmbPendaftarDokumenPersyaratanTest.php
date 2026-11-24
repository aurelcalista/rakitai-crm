<?php

namespace Tests\Feature;

use App\Models\Prospek;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PmbPendaftarDokumenPersyaratanTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $csUser;
    protected User $camabaUser;
    protected Prospek $prospekSudah;
    protected Prospek $prospekBelum;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->adminUser = User::create([
            'name'     => 'Admin PMB',
            'email'    => 'admin.pmb@cic.ac.id',
            'role'     => 'Admin',
            'status'   => 'Aktif',
            'password' => Hash::make('password123'),
        ]);

        $this->csUser = User::create([
            'name'     => 'CS Staff',
            'email'    => 'cs.pmb@cic.ac.id',
            'role'     => 'CS',
            'status'   => 'Aktif',
            'password' => Hash::make('password123'),
        ]);

        $this->camabaUser = User::create([
            'name'     => 'Camaba Test',
            'email'    => 'camaba@gmail.com',
            'role'     => 'Camaba',
            'status'   => 'Aktif',
            'password' => Hash::make('password123'),
        ]);

        // Simulasikan file KTP di storage
        $ktpPath = 'dokumen_pmb/1/KTP_Camaba_1_123456.pdf';
        Storage::disk('local')->put($ktpPath, 'konten file ktp dummy pdf');

        $this->prospekSudah = Prospek::create([
            'id'              => 1,
            'user_id'         => $this->camabaUser->id,
            'name'            => 'Budi Sudah Upload',
            'email'           => 'budi.upload@gmail.com',
            'whatsapp'        => '081234567891',
            'no_registrasi'   => 'PMB-2026-0001',
            'status'          => 'BERKAS',
            'status_formulir' => 'Sudah Isi',
            'status_berkas'   => 'Sudah Upload',
            'status_ujian'    => 'Belum Mengerjakan',
            'berkas_data'     => [
                'ktp' => [
                    'file_path'     => $ktpPath,
                    'file_name'     => 'KTP_Camaba_1_123456.pdf',
                    'original_name' => 'scan_ktp_asli.pdf',
                    'file_size'     => 102400,
                    'mime_type'     => 'application/pdf',
                    'uploaded_at'   => '2026-11-21 10:00:00',
                    'status'        => 'Menunggu Verifikasi',
                    'catatan'       => null,
                ],
            ],
        ]);

        $this->prospekBelum = Prospek::create([
            'id'              => 2,
            'name'            => 'Siti Belum Upload',
            'email'           => 'siti.belum@gmail.com',
            'whatsapp'        => '081234567892',
            'no_registrasi'   => 'PMB-2026-0002',
            'status'          => 'BARU',
            'status_formulir' => 'Belum Isi',
            'status_berkas'   => 'Belum Upload',
            'status_ujian'    => 'Belum Mengerjakan',
        ]);
    }

    /**
     * Test 1: Admin dan CS dapat memfilter pendaftar berdasarkan status berkas di menu Data Pendaftar.
     */
    public function test_admin_and_cs_can_filter_by_status_berkas(): void
    {
        // Akses oleh CS
        $response = $this->actingAs($this->csUser)->get(route('pmb.pendaftar.index', ['status_berkas' => 'Sudah Upload']));
        $response->assertOk();
        $response->assertSee('Budi Sudah Upload');
        $response->assertDontSee('Siti Belum Upload');

        // Filter belum upload
        $responseBelum = $this->actingAs($this->adminUser)->get(route('pmb.pendaftar.index', ['status_berkas' => 'Belum Upload']));
        $responseBelum->assertOk();
        $responseBelum->assertSee('Siti Belum Upload');
        $responseBelum->assertDontSee('Budi Sudah Upload');
    }

    /**
     * Test 2: Halaman detail pendaftar menampilkan bagian dokumen persyaratan secara lengkap.
     */
    public function test_detail_pendaftar_shows_uploaded_documents_section(): void
    {
        $response = $this->actingAs($this->csUser)->get(route('pmb.pendaftar.show', $this->prospekSudah->id));

        $response->assertOk();
        $response->assertSee('Dokumen Persyaratan Calon Mahasiswa');
        $response->assertSee('scan_ktp_asli.pdf');
        $response->assertSee('Kartu Tanda Penduduk (KTP)');
        $response->assertSee('Lihat File');
        $response->assertSee('Verifikasi');
    }

    /**
     * Test 3: Admin dan CS dapat melihat dan mengunduh berkas pendaftar.
     */
    public function test_admin_and_cs_can_view_and_download_document(): void
    {
        // View via browser
        $viewRes = $this->actingAs($this->csUser)->get(route('pmb.pendaftar.dokumen.view', [
            'id'   => $this->prospekSudah->id,
            'type' => 'ktp',
        ]));
        $viewRes->assertOk();

        // Download file
        $downloadRes = $this->actingAs($this->adminUser)->get(route('pmb.pendaftar.dokumen.view', [
            'id'       => $this->prospekSudah->id,
            'type'     => 'ktp',
            'download' => 1,
        ]));
        $downloadRes->assertOk();
        $downloadRes->assertHeader('content-disposition');
    }

    /**
     * Test 4: Admin dan CS dapat memverifikasi (menyetujui atau menolak) dokumen pendaftar.
     */
    public function test_admin_and_cs_can_verify_document(): void
    {
        $response = $this->actingAs($this->csUser)->post(route('pmb.pendaftar.dokumen.verify', [
            'id'   => $this->prospekSudah->id,
            'type' => 'ktp',
        ]), [
            'status'  => 'Disetujui',
            'catatan' => 'KTP jelas dan valid',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->prospekSudah->refresh();
        $this->assertEquals('Disetujui', $this->prospekSudah->berkas_data['ktp']['status']);
        $this->assertEquals('KTP jelas dan valid', $this->prospekSudah->berkas_data['ktp']['catatan']);
        $this->assertEquals('CS Staff', $this->prospekSudah->berkas_data['ktp']['verified_by']);
    }

    /**
     * Test 5: Role non-staff ditolak saat mengakses endpoint dokumen pendaftar PMB.
     */
    public function test_non_staff_cannot_access_pmb_pendaftar_dokumen(): void
    {
        $guestRes = $this->get(route('pmb.pendaftar.dokumen.view', [
            'id'   => $this->prospekSudah->id,
            'type' => 'ktp',
        ]));
        $guestRes->assertRedirect('/login');

        $camabaRes = $this->actingAs($this->camabaUser)->get(route('pmb.pendaftar.dokumen.view', [
            'id'   => $this->prospekSudah->id,
            'type' => 'ktp',
        ]));
        // Camaba diredirect ke dashboard akun camaba oleh RoleMiddleware
        $camabaRes->assertRedirect(route('pmb.akun.dashboard'));
    }
}
