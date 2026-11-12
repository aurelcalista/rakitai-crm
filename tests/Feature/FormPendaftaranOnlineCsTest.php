<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\Prodi;
use App\Models\Prospek;
use App\Models\ProspekTimeline;
use App\Models\Sekolah;
use App\Models\User;
use App\Models\Wilayah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FormPendaftaranOnlineCsTest extends TestCase
{
    use RefreshDatabase;

    protected User $csUser;
    protected User $salesUser;
    protected Wilayah $wilayah;
    protected Sekolah $sekolah;
    protected Prodi $prodi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->wilayah = Wilayah::create([
            'kode' => 'W-CRB',
            'nama' => 'Kota Cirebon',
            'tipe' => 'Kota',
        ]);

        $this->csUser = User::create([
            'name'       => 'CS Yuda Thomas',
            'email'      => 'cs.yuda@cic.ac.id',
            'role'       => 'CS',
            'status'     => 'Aktif',
            'password'   => Hash::make('password123'),
            'wilayah_id' => $this->wilayah->id,
        ]);

        $this->salesUser = User::create([
            'name'       => 'Sales Lapangan',
            'email'      => 'sales.lapangan@cic.ac.id',
            'role'       => 'Sales',
            'status'     => 'Aktif',
            'password'   => Hash::make('password123'),
            'wilayah_id' => $this->wilayah->id,
        ]);

        $this->sekolah = Sekolah::create([
            'kode'       => 'SCH-001',
            'nama'       => 'SMAN 1 Cirebon',
            'wilayah_id' => $this->wilayah->id,
            'sales_id'   => $this->salesUser->id,
            'status'     => 'Aktif',
        ]);

        $this->prodi = Prodi::create([
            'kode'     => 'TI',
            'nama'     => 'Teknik Informatika',
            'jenjang'  => 'S1',
            'fakultas' => 'Teknologi Informasi',
            'status'   => 'Aktif',
        ]);
    }

    /**
     * Test 1: Pendaftaran akun CAMABA baru otomatis di-assign ke user dengan role CS,
     * dan tidak diambil alih oleh Sales sekolah.
     */
    public function test_camaba_registration_is_automatically_assigned_to_role_cs(): void
    {
        $payload = [
            'name'                  => 'Siswa Baru PMB',
            'email'                 => 'siswa.baru@gmail.com',
            'whatsapp'              => '+6281234567899',
            'asal_sekolah'          => 'SMAN 1 Cirebon',
            'password'              => 'P@ssw0rd123',
            'password_confirmation' => 'P@ssw0rd123',
            'minat_kuliah'          => 'ya',
            'minat_miniclass'       => 'ya',
        ];

        $response = $this->post(route('pmb.register.post'), $payload);

        $response->assertRedirect(route('pmb.login'));

        $prospek = Prospek::where('email', 'siswa.baru@gmail.com')->first();
        $this->assertNotNull($prospek);

        // Input masuk ke role CS (cs_id = csUser)
        $this->assertEquals($this->csUser->id, $prospek->cs_id);
        $this->assertEquals('CS — CS Yuda Thomas', $prospek->activeHandlerLabel());

        // Field sales sekolah TIDAK otomatis mengklaim prospek online PMB
        $this->assertNull($prospek->sales_id);
    }

    /**
     * Test 2: Pengisian Form Pendaftaran Online UCIC (updateProfile) inputannya
     * masuk ke role CS, mencatat timeline, dan mengirim notifikasi aktivitas CRM ke CS.
     */
    public function test_submitting_form_pendaftaran_online_ucic_routes_to_role_cs(): void
    {
        $camaba = User::create([
            'name'     => 'Calon Maba Online',
            'email'    => 'calon.maba@gmail.com',
            'phone'    => '+6281234567888',
            'role'     => 'Camaba',
            'status'   => 'Aktif',
            'password' => Hash::make('password123'),
        ]);

        $prospek = Prospek::create([
            'user_id'          => $camaba->id,
            'name'             => 'Calon Maba Online',
            'email'            => 'calon.maba@gmail.com',
            'whatsapp'         => '+6281234567888',
            'category'         => 'PMB Online',
            'source'           => 'Website PMB',
            'status'           => 'BARU',
            'status_formulir'  => 'Belum Isi',
            'status_berkas'    => 'Belum Upload',
            'status_ujian'     => 'Belum Mengerjakan',
            'stage_number'     => 1,
            'sekolah_id'       => $this->sekolah->id,
            'wilayah_id'       => $this->wilayah->id,
        ]);

        $formData = [
            'name'                  => 'Calon Maba Online Terupdate',
            'whatsapp'              => '+6281234567888',
            'asal_sekolah'          => 'SMAN 1 Cirebon',
            'nama_sekolah'          => 'SMAN 1 Cirebon',
            'prodi_id'              => $this->prodi->id,
            'tipe_kelas'            => 'Reguler',
            'informasi_pendaftaran' => 'Instagram / Media Sosial',
            'tempat_lahir'          => 'Cirebon',
            'tanggal_lahir'         => '2008-05-15',
            'nik'                   => '3274011505080001',
            'jenis_kelamin'         => 'Laki-Laki',
            'agama'                 => 'Islam',
            'alamat'                => 'Jl. Kesambi No. 202',
            'nama_ibu_kandung'      => 'Ibu Kandung Maba',
        ];

        // Camaba mengisi dan mengirim Form Pendaftaran Online UCIC
        $response = $this->actingAs($camaba)->post(route('pmb.akun.form-pendaftaran.update'), $formData);

        $response->assertRedirect(route('pmb.akun.bayar'));

        $prospek->refresh();

        // 1. Status formulir menjadi 'Sudah Isi'
        $this->assertEquals('Sudah Isi', $prospek->status_formulir);

        // 2. Data masuk ke role CS
        $this->assertEquals($this->csUser->id, $prospek->cs_id);
        $this->assertNotNull($prospek->handover_at);
        $this->assertEquals('CS — CS Yuda Thomas', $prospek->activeHandlerLabel());

        // 3. Form data tersimpan
        $this->assertIsArray($prospek->form_data);
        $this->assertEquals('3274011505080001', $prospek->form_data['nik']);
        $this->assertEquals('Ibu Kandung Maba', $prospek->form_data['nama_ibu_kandung']);

        // 4. Riwayat timeline dibuat
        $this->assertDatabaseHas('prospek_timelines', [
            'prospek_id' => $prospek->id,
            'user_id'    => $this->csUser->id,
            'title'      => 'Form Pendaftaran Online Disubmit',
        ]);

        // 5. Notifikasi tersampaikan ke user CS
        $notification = \Illuminate\Support\Facades\DB::table('notifications')
            ->where('notifiable_id', $this->csUser->id)
            ->where('notifiable_type', User::class)
            ->where('data', 'like', '%form_pendaftaran_submitted%')
            ->first();
        $this->assertNotNull($notification);
        $this->assertStringContainsString('Form Pendaftaran Online Baru', $notification->data);
    }

    /**
     * Test 3: User CS dapat melihat pendaftar Form Pendaftaran Online di Dashboard CS dan PMB Pendaftar.
     */
    public function test_cs_can_view_pendaftar_in_cs_dashboard_and_pmb_pages(): void
    {
        $prospek = Prospek::create([
            'name'             => 'Pendaftar Form Online',
            'email'            => 'pendaftar.form@gmail.com',
            'whatsapp'         => '+6281234567777',
            'category'         => 'PMB Online',
            'source'           => 'Website PMB',
            'status'           => 'BARU',
            'status_formulir'  => 'Sudah Isi',
            'stage_number'     => 1,
            'cs_id'            => $this->csUser->id,
            'handover_at'      => now(),
            'prodi_id'         => $this->prodi->id,
        ]);

        // Akses dashboard CS
        $responseCs = $this->actingAs($this->csUser)->get(route('dashboard.cs'));
        $responseCs->assertOk();
        $responseCs->assertSee('Pendaftar Form Online');

        // Akses PMB Data Pendaftar
        $responsePmb = $this->actingAs($this->csUser)->get(route('pmb.pendaftar.index'));
        $responsePmb->assertOk();
        $responsePmb->assertSee('Pendaftar Form Online');
        $responsePmb->assertSee('CS Yuda Thomas');

        // Akses PMB Detail Pendaftar
        $responseDetail = $this->actingAs($this->csUser)->get(route('pmb.pendaftar.show', $prospek->id));
        $responseDetail->assertOk();
        $responseDetail->assertSee('Pendaftar Form Online');
        $responseDetail->assertSee('CS Yuda Thomas');
    }
}
