<?php

namespace Tests\Feature;

use App\Models\Prodi;
use App\Models\Prospek;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CamabaKatalogTest extends TestCase
{
    use RefreshDatabase;

    protected User $camaba;
    protected Prospek $prospek;

    protected function setUp(): void
    {
        parent::setUp();

        // Siapkan akun CAMABA
        $this->camaba = User::create([
            'name'     => 'Ananda Pratama',
            'email'    => 'ananda.pratama@gmail.com',
            'role'     => 'Camaba',
            'status'   => 'Aktif',
            'password' => Hash::make('password123'),
        ]);

        $this->prospek = Prospek::create([
            'user_id'         => $this->camaba->id,
            'name'            => 'Ananda Pratama',
            'email'           => 'ananda.pratama@gmail.com',
            'whatsapp'        => '081234567899',
            'no_registrasi'   => 'PMB-2026-0002',
            'status'          => 'BARU',
            'stage_number'    => 1,
            'status_formulir' => 'Belum Isi',
            'status_berkas'   => 'Belum Upload',
            'status_ujian'    => 'Belum Mengerjakan',
        ]);

        // Buat sample prodi jika belum ada
        if (Prodi::count() === 0) {
            Prodi::create([
                'kode'     => 'TI',
                'nama'     => 'Teknik Informatika',
                'jenjang'  => 'S1',
                'fakultas' => 'Fakultas Teknologi Informasi',
                'kuota'    => 180,
                'status'   => 'Aktif',
            ]);

            Prodi::create([
                'kode'     => 'SI',
                'nama'     => 'Sistem Informasi',
                'jenjang'  => 'S1',
                'fakultas' => 'Fakultas Teknologi Informasi',
                'kuota'    => 85,
                'status'   => 'Aktif',
            ]);

            Prodi::create([
                'kode'     => 'MNJ',
                'nama'     => 'Manajemen',
                'jenjang'  => 'S1',
                'fakultas' => 'Fakultas Ekonomi dan Bisnis',
                'kuota'    => 210,
                'status'   => 'Aktif',
            ]);

            Prodi::create([
                'kode'     => 'MB-D3',
                'nama'     => 'Manajemen Bisnis',
                'jenjang'  => 'D3',
                'fakultas' => 'Fakultas Ekonomi dan Bisnis',
                'kuota'    => 25,
                'status'   => 'Aktif',
            ]);

            Prodi::create([
                'kode'     => 'S2-MNJ',
                'nama'     => 'S2 Manajemen',
                'jenjang'  => 'S2',
                'fakultas' => 'Pascasarjana',
                'kuota'    => 50,
                'status'   => 'Aktif',
            ]);
        }
    }

    /**
     * Test 1: Tamu (Guest) yang belum login tidak dapat mengakses halaman Katalog.
     */
    public function test_guest_cannot_access_katalog_page_and_is_redirected_to_login(): void
    {
        $response = $this->get(route('pmb.akun.katalog'));
        $response->assertRedirect('/login');
    }

    /**
     * Test 2: CAMABA yang terautentikasi dapat membuka halaman Katalog Program Studi.
     */
    public function test_authenticated_camaba_can_access_katalog_page(): void
    {
        $response = $this->actingAs($this->camaba)->get(route('pmb.akun.katalog'));

        $response->assertOk();
        $response->assertSee('Katalog Program Studi');
        $response->assertSee('Universitas Catur Insan Cendekia (UCIC)');
        $response->assertSee('Katalog UCIC');
        $response->assertSee('Fakultas Teknologi Informasi');
        $response->assertSee('Teknik Informatika');
        $response->assertSee('Sistem Informasi');
        $response->assertSee('Manajemen');
        $response->assertSee('S1');
        $response->assertSee('D3');
        $response->assertSee('S2');
    }

    /**
     * Test 3: Pengguna dengan role selain CAMABA tidak mendapat akses ke halaman Katalog PMB.
     */
    public function test_non_camaba_user_cannot_access_katalog_page(): void
    {
        $salesUser = User::create([
            'name'     => 'Staff Sales',
            'email'    => 'sales@ucic.ac.id',
            'role'     => 'Sales',
            'status'   => 'Aktif',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->actingAs($salesUser)->get(route('pmb.akun.katalog'));

        // RoleMiddleware menolak pengguna selain CAMABA (bukan 200 OK)
        $this->assertNotEquals(200, $response->getStatusCode());
    }

    /**
     * Test 4: Halaman Katalog memuat elemen pencarian dan kontrol filter fakultas & jenjang.
     */
    public function test_katalog_page_contains_search_and_filter_elements(): void
    {
        $response = $this->actingAs($this->camaba)->get(route('pmb.akun.katalog'));

        $response->assertOk();
        $response->assertSee('Cari dan Filter Program Studi');
        $response->assertSee('Pilih Fakultas:');
        $response->assertSee('Jenjang Pendidikan:');
        $response->assertSee('Semua Fakultas');
        $response->assertSee('Semua Jenjang');
        $response->assertSee('Fakultas Teknologi Informasi');
        $response->assertSee('Fakultas Ekonomi dan Bisnis');
    }

    /**
     * Test 5: Navbar menampilkan menu Katalog yang aktif ketika berada di halaman Katalog.
     */
    public function test_navbar_displays_active_katalog_menu(): void
    {
        $response = $this->actingAs($this->camaba)->get(route('pmb.akun.katalog'));

        $response->assertOk();
        $response->assertSee('Katalog');
        // Memastikan routeIs('pmb.akun.katalog*') diterapkan pada layout
        $response->assertSee(route('pmb.akun.katalog'));
    }

    /**
     * Test 6: Halaman Katalog menyediakan navigasi kembali ke Dashboard CAMABA.
     */
    public function test_katalog_provides_navigation_back_to_dashboard(): void
    {
        $response = $this->actingAs($this->camaba)->get(route('pmb.akun.katalog'));

        $response->assertOk();
        $response->assertSee(route('pmb.akun.dashboard'));
        $response->assertSee('Dashboard');
    }

    /**
     * Test 7: Dashboard CAMABA memuat tautan menuju halaman Katalog Program Studi.
     */
    public function test_dashboard_contains_navigation_to_katalog_page(): void
    {
        $response = $this->actingAs($this->camaba)->get(route('pmb.akun.dashboard'));

        $response->assertOk();
        $response->assertSee(route('pmb.akun.katalog'));
        $response->assertSee('Katalog Program Studi');
    }

    /**
     * Test 8: Alias route /katalog mengarahkan pengguna CAMABA ke pmb.akun.katalog.
     */
    public function test_katalog_alias_route_redirects_camaba_to_katalog_page(): void
    {
        $response = $this->actingAs($this->camaba)->get('/katalog');
        $response->assertRedirect(route('pmb.akun.katalog'));
    }

    /**
     * Test 9: Tamu (Guest) yang mengakses /katalog tidak diizinkan dan diarahkan ke login.
     */
    public function test_guest_cannot_access_katalog_alias_route(): void
    {
        $response = $this->get('/katalog');
        $response->assertRedirect('/login');
    }
}

