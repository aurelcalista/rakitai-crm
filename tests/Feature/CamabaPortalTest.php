<?php

namespace Tests\Feature;

use App\Models\Prodi;
use App\Models\Prospek;
use App\Models\Sekolah;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CamabaPortalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create standard test prodi
        Prodi::create([
            'kode' => 'TI',
            'nama' => 'Teknik Informatika',
            'jenjang' => 'S1',
            'fakultas' => 'Fakultas Teknologi Informasi',
            'status' => 'Aktif',
        ]);

        Prodi::create([
            'kode' => 'SI',
            'nama' => 'Sistem Informasi',
            'jenjang' => 'S1',
            'fakultas' => 'Fakultas Teknologi Informasi',
            'status' => 'Aktif',
        ]);
    }

    /**
     * Test 1: Halaman utama publik portal PMB dapat diakses.
     */
    public function test_public_portal_landing_can_be_accessed(): void
    {
        $response = $this->get(route('pmb.portal'));

        $response->assertStatus(200);
        $response->assertSee('Universitas CIC');
        $response->assertSee('Teknik Informatika');
        $response->assertSee('Daftar Akun CAMABA');
    }

    /**
     * Test 2: Halaman registrasi CAMABA dapat diakses oleh guest.
     */
    public function test_camaba_registration_page_can_be_accessed(): void
    {
        $response = $this->get(route('pmb.register'));

        $response->assertStatus(200);
        $response->assertSee('Daftar Akun Baru');
        $response->assertSee('Asal Sekolah');
    }

    /**
     * Test 3: Registrasi berhasil membuat akun User dan record Prospek PMB secara konsisten.
     */
    public function test_camaba_registration_successfully_creates_user_and_prospek(): void
    {
        $payload = [
            'name'                  => 'Budi Santoso',
            'email'                 => 'budi.santoso@gmail.com',
            'whatsapp'              => '081234567890',
            'asal_sekolah'          => 'SMAN 1 Cirebon',
            'password'              => 'P@ssw0rd123',
            'password_confirmation' => 'P@ssw0rd123',
            'minat_kuliah'          => 'ya',
            'minat_miniclass'       => 'ya',
        ];

        $response = $this->post(route('pmb.register.post'), $payload);

        $response->assertRedirect(route('pmb.akun.dashboard'));

        // Assert user exists with Camaba role
        $this->assertDatabaseHas('users', [
            'email' => 'budi.santoso@gmail.com',
            'role'  => 'Camaba',
            'status'=> 'Aktif',
        ]);

        $user = User::where('email', 'budi.santoso@gmail.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue(Hash::check('P@ssw0rd123', $user->password));

        // Assert Prospek exists and is linked to user_id
        $this->assertDatabaseHas('prospeks', [
            'user_id'         => $user->id,
            'email'           => 'budi.santoso@gmail.com',
            'status'          => 'BARU',
            'status_formulir' => 'Belum Isi',
            'status_berkas'   => 'Belum Upload',
            'status_ujian'    => 'Belum Mengerjakan',
            'minat_kuliah'    => 'ya',
            'minat_miniclass' => 'ya',
        ]);

        $this->assertAuthenticatedAs($user);
    }

    /**
     * Test 4: Registrasi gagal jika email sudah terdaftar.
     */
    public function test_registration_prevents_duplicate_email(): void
    {
        User::create([
            'name'     => 'Existing User',
            'email'    => 'existing@gmail.com',
            'role'     => 'Camaba',
            'status'   => 'Aktif',
            'password' => Hash::make('password123'),
        ]);

        $payload = [
            'name'                  => 'New Person',
            'email'                 => 'existing@gmail.com',
            'whatsapp'              => '081234567891',
            'asal_sekolah'          => 'SMAN 2 Cirebon',
            'password'              => 'P@ssw0rd123',
            'password_confirmation' => 'P@ssw0rd123',
        ];

        $response = $this->from(route('pmb.register'))->post(route('pmb.register.post'), $payload);

        $response->assertRedirect(route('pmb.register'));
        $response->assertSessionHasErrors(['email']);
    }

    /**
     * Test 5: Login CAMABA berhasil dengan kredensial valid dan mengarah ke dashboard CAMABA.
     */
    public function test_camaba_can_login_with_valid_credentials(): void
    {
        $user = User::create([
            'name'     => 'Siti Aisyah',
            'email'    => 'siti@gmail.com',
            'role'     => 'Camaba',
            'status'   => 'Aktif',
            'password' => Hash::make('secret1234'),
        ]);

        $response = $this->post(route('pmb.login.post'), [
            'email'    => 'siti@gmail.com',
            'password' => 'secret1234',
        ]);

        $response->assertRedirect(route('pmb.akun.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Test 6: Login CAMABA gagal dengan password salah.
     */
    public function test_camaba_cannot_login_with_invalid_password(): void
    {
        User::create([
            'name'     => 'Siti Aisyah',
            'email'    => 'siti@gmail.com',
            'role'     => 'Camaba',
            'status'   => 'Aktif',
            'password' => Hash::make('secret1234'),
        ]);

        $response = $this->from(route('pmb.login'))->post(route('pmb.login.post'), [
            'email'    => 'siti@gmail.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertRedirect(route('pmb.login'));
        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    /**
     * Test 7: Dashboard akun CAMABA terlindungi oleh middleware auth.
     */
    public function test_dashboard_is_protected_against_unauthenticated_access(): void
    {
        $response = $this->get(route('pmb.akun.dashboard'));

        $response->assertRedirect(route('login'));
    }

    /**
     * Test 8: CAMABA tidak dapat mengakses area admin CRM (RoleMiddleware 403 / redirect).
     */
    public function test_camaba_cannot_access_crm_admin_areas(): void
    {
        $camaba = User::create([
            'name'     => 'Camaba User',
            'email'    => 'camaba@example.com',
            'role'     => 'Camaba',
            'status'   => 'Aktif',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->actingAs($camaba)->get(route('dashboard.admin'));

        // RoleMiddleware redirects camaba to pmb.akun.dashboard with an error message
        $response->assertRedirect(route('pmb.akun.dashboard'));
    }

    /**
     * Test 9: CAMABA dapat melihat dan memperbarui field profil yang diizinkan.
     */
    public function test_camaba_can_update_permitted_profile_fields(): void
    {
        $camaba = User::create([
            'name'     => 'Ahmad Dahlan',
            'email'    => 'ahmad@example.com',
            'role'     => 'Camaba',
            'status'   => 'Aktif',
            'password' => Hash::make('password123'),
        ]);

        $prospek = Prospek::create([
            'user_id'         => $camaba->id,
            'name'            => 'Ahmad Dahlan',
            'email'           => 'ahmad@example.com',
            'whatsapp'        => '081234567890',
            'status'          => 'BARU',
            'stage_number'    => 1,
            'status_formulir' => 'Belum Isi',
            'status_berkas'   => 'Belum Upload',
            'status_ujian'    => 'Belum Mengerjakan',
        ]);

        $prodi = Prodi::where('kode', 'TI')->first();

        $updateData = [
            'name'            => 'Ahmad Dahlan Updated',
            'whatsapp'        => '089876543210',
            'asal_sekolah'    => 'SMKN 1 Kedawung',
            'prodi_id'        => $prodi->id,
            'minat_kuliah'    => 'ya',
            'minat_miniclass' => 'tidak',
            // Attempt to maliciously inject internal CRM status
            'status'          => 'Closing',
            'stage_number'    => 5,
            'nilai_ujian'     => 100,
        ];

        $response = $this->actingAs($camaba)->put(route('pmb.akun.profil.update'), $updateData);

        $response->assertRedirect(route('pmb.akun.profil'));

        $prospek->refresh();
        $this->assertEquals('Ahmad Dahlan Updated', $prospek->name);
        $this->assertEquals($prodi->id, $prospek->prodi_id);
        $this->assertEquals('+6289876543210', $prospek->whatsapp);

        // Security check: sensitive internal status must NOT be changed
        $this->assertEquals('BARU', $prospek->status);
        $this->assertEquals(1, $prospek->stage_number);
        $this->assertNull($prospek->nilai_ujian);
    }

    /**
     * Test 10: Halaman pembayaran CAMABA otomatis menyiapkan Transaksi Midtrans pendaftaran Rp 300.000.
     */
    public function test_camaba_payment_page_initializes_midtrans_registration_transaction(): void
    {
        $camaba = User::create([
            'name'     => 'Rian Firmansyah',
            'email'    => 'rian@example.com',
            'role'     => 'Camaba',
            'status'   => 'Aktif',
            'password' => Hash::make('password123'),
        ]);

        $prospek = Prospek::create([
            'user_id'         => $camaba->id,
            'name'            => 'Rian Firmansyah',
            'email'           => 'rian@example.com',
            'whatsapp'        => '081234567890',
            'status'          => 'BARU',
            'stage_number'    => 1,
        ]);

        $response = $this->actingAs($camaba)->get(route('pmb.akun.bayar'));

        $response->assertStatus(200);
        $response->assertSee('Pembayaran Pendaftaran');
        $response->assertSee('300.000');

        $this->assertDatabaseHas('transaksis', [
            'prospek_id' => $prospek->id,
            'nominal'    => 300000,
            'kategori'   => Transaksi::KATEGORI_DP_DAFTAR,
        ]);
    }
}
