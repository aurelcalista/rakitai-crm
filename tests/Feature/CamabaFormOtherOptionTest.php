<?php

namespace Tests\Feature;

use App\Models\Prodi;
use App\Models\Prospek;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CamabaFormOtherOptionTest extends TestCase
{
    use RefreshDatabase;

    protected function createFreshCamaba(): array
    {
        $camaba = User::create([
            'name'     => 'Camaba Lainnya',
            'email'    => 'lainnya.' . uniqid() . '@example.com',
            'phone'    => '0812' . rand(10000000, 99999999),
            'password' => Hash::make('secret123'),
            'role'     => 'camaba',
        ]);

        $prospek = Prospek::create([
            'user_id'         => $camaba->id,
            'name'            => $camaba->name,
            'email'           => $camaba->email,
            'whatsapp'        => $camaba->phone,
            'type'            => 'Individu',
            'source'          => 'Website',
            'status'          => 'BARU',
            'status_formulir' => 'Belum',
        ]);

        return [$camaba, $prospek];
    }

    public function test_fresh_camaba_form_does_not_treat_default_website_source_as_lainnya_and_renders_text_jurusan(): void
    {
        [$camaba] = $this->createFreshCamaba();

        $res = $this->actingAs($camaba)->get(route('pmb.akun.profil'));
        $res->assertOk();
        $res->assertSee('<input type="text"', false);
        $res->assertSee('name="jurusan_sekolah"', false);
        $res->assertSee('informasiPendaftaran: ""');
    }

    public function test_custom_lainnya_dropdown_values_and_jurusan_sekolah_are_saved_and_reloaded(): void
    {
        $prodi = Prodi::create([
            'kode'               => 'TI-S1',
            'nama'               => 'Teknik Informatika',
            'jenjang'            => 'S1',
            'fakultas'           => 'FTI',
            'kuota'              => 100,
            'status'             => 'Aktif',
            'biaya_pendaftaran'  => 250000,
            'biaya_perlengkapan' => 600000,
            'biaya_dpp'          => 500000,
            'biaya_uang_gedung'  => 0,
        ]);

        [$camaba, $prospek] = $this->createFreshCamaba();

        $res = $this->actingAs($camaba)->post(route('pmb.akun.form-pendaftaran.update'), [
            'name'                          => 'Camaba Custom',
            'whatsapp'                      => '+6281234567890',
            'prodi_id'                      => $prodi->id,
            'informasi_pendaftaran'         => 'Lainnya',
            'informasi_pendaftaran_lainnya' => 'TikTok Live Kampus',
            'status_tempat_tinggal'         => 'Lainnya',
            'status_tempat_tinggal_lainnya' => 'Tinggal Bersama Wali',
            'jenis_sekolah'                 => 'Lainnya',
            'jenis_sekolah_lainnya'         => 'Homeschooling Resmi',
            'jurusan_sekolah'               => 'Rekayasa Perangkat Lunak',
            'pekerjaan_orang_tua'           => 'Lainnya',
            'pekerjaan_orang_tua_lainnya'   => 'Dosen Perguruan Tinggi',
        ]);

        $res->assertRedirect(route('pmb.akun.bayar'));

        $prospek->refresh();
        $this->assertEquals('TikTok Live Kampus', $prospek->source);
        $this->assertEquals('TikTok Live Kampus', $prospek->form_data['informasi_pendaftaran']);
        $this->assertEquals('Tinggal Bersama Wali', $prospek->form_data['status_tempat_tinggal']);
        $this->assertEquals('Homeschooling Resmi', $prospek->form_data['jenis_sekolah']);
        $this->assertEquals('Rekayasa Perangkat Lunak', $prospek->form_data['jurusan_sekolah']);
        $this->assertEquals('Dosen Perguruan Tinggi', $prospek->form_data['pekerjaan_orang_tua']);

        // Buka ulang halaman profil: dropdown harus preselect "Lainnya" dan input custom terisi kembali
        $profilRes = $this->actingAs($camaba)->get(route('pmb.akun.profil'));
        $profilRes->assertOk();
        $profilRes->assertSee('informasiPendaftaran: "Lainnya"');
        $profilRes->assertSee('statusTempatTinggal: "Lainnya"');
        $profilRes->assertSee('jenisSekolah: "Lainnya"');
        $profilRes->assertSee('pekerjaanOrangTua: "Lainnya"');
        $profilRes->assertSee('value="TikTok Live Kampus"', false);
        $profilRes->assertSee('value="Tinggal Bersama Wali"', false);
        $profilRes->assertSee('value="Homeschooling Resmi"', false);
        $profilRes->assertSee('value="Rekayasa Perangkat Lunak"', false);
        $profilRes->assertSee('value="Dosen Perguruan Tinggi"', false);
    }

    public function test_form_pendaftaran_submits_to_form_pendaftaran_put_and_redirects_to_bayar(): void
    {
        $prodi = Prodi::create([
            'kode'               => 'SI-S1',
            'nama'               => 'Sistem Informasi',
            'jenjang'            => 'S1',
            'fakultas'           => 'FTI',
            'kuota'              => 80,
            'status'             => 'Aktif',
            'biaya_pendaftaran'  => 250000,
            'biaya_perlengkapan' => 600000,
            'biaya_dpp'          => 500000,
            'biaya_uang_gedung'  => 0,
        ]);

        [$camaba, $prospek] = $this->createFreshCamaba();

        // Buka via /pmb/akun/profil -> form action harus menuju pmb.akun.form-pendaftaran.put
        $formPage = $this->actingAs($camaba)->get(route('pmb.akun.profil'));
        $formPage->assertOk();
        $formPage->assertSee('action="' . route('pmb.akun.form-pendaftaran.put') . '"', false);

        // Submit PUT ke form action yang tertera -> diarahkan ke halaman bayar
        $submitRes = $this->actingAs($camaba)->put(route('pmb.akun.form-pendaftaran.put'), [
            'name'     => 'Camaba Redirect Bayar',
            'whatsapp' => '+6281234567899',
            'prodi_id' => $prodi->id,
        ]);

        $submitRes->assertRedirect(route('pmb.akun.bayar'));
        $prospek->refresh();
        $this->assertEquals('Sudah Isi', $prospek->status_formulir);
    }
}
