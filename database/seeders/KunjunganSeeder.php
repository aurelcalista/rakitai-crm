<?php

namespace Database\Seeders;

use App\Models\Kunjungan;
use App\Models\Sekolah;
use App\Models\Perusahaan;
use App\Models\Prodi;
use App\Models\User;
use App\Models\TahunAkademik;
use Illuminate\Database\Seeder;

class KunjunganSeeder extends Seeder
{
    public function run(): void
    {
        $salesList = User::where('role', 'Sales')->get();
        $sekolahs = Sekolah::all();
        $perusahaans = Perusahaan::all();
        $prodi = Prodi::first();
        $ta = TahunAkademik::getAktif();

        if ($salesList->isEmpty()) return;

        // 1. Kunjungan Sekolah (SMAN 1 Cirebon)
        if ($sekolahs->isNotEmpty()) {
            $sekolah = $sekolahs->first();
            $sales = $salesList->first();

            Kunjungan::updateOrCreate(
                ['nomor' => 'KNJ-' . date('Ymd') . '-001'],
                [
                    'tanggal'                 => now()->subDays(3)->toDateString(),
                    'waktu'                   => '09:30:00',
                    'sales_id'                => $sales->id,
                    'prodi_id'                => $prodi?->id,
                    'jenis'                   => 'Sekolah',
                    'tujuan_id'               => $sekolah->id,
                    'tujuan_kunjungan'        => 'Presentasi Sosialisasi PMB & Demo AI',
                    'hasil'                   => 'Presentasi berjalan lancar di hadapan 120 siswa kelas XII.',
                    'catatan'                 => 'Pihak sekolah mengizinkan pendaftaran via jalur khusus beasiswa.',
                    'status'                  => 'Terverifikasi',
                    'status_verifikasi'       => 'Terverifikasi',
                    'status_lokasi'           => 'Dalam Radius',
                    'kehadiran'               => 'Hadir',
                    'nama_institusi'          => $sekolah->nama,
                    'tier'                    => 'Tier 1',
                    'alamat'                  => $sekolah->alamat ?? 'Jl. Wahidin Sudirohusodo No. 81, Cirebon',
                    'lokasi_penugasan'        => 'Kota Cirebon',
                    'pic_name'                => 'Bpk. Drs. H. Mulyadi',
                    'pic_whatsapp'            => '081234567890',
                    'potensi_mahasiswa'       => 150,
                    'detail_potensi_mahasiswa'=> 'Siswa kelas XII IPA & IPS sangat antusias terhadap prodi Informatika & DKV.',
                    'kesediaan_training_ai'   => true,
                    'is_verified'             => true,
                    'is_outside_radius'       => false,
                    'lat'                     => -6.713500,
                    'lng'                     => 108.558000,
                    'jarak_meter'             => 45.5,
                    'academic_year_id'        => $ta?->id,
                ]
            );
        }

        // 2. Kunjungan Corporate (PT Cirebon Power)
        if ($perusahaans->isNotEmpty()) {
            $perusahaan = $perusahaans->first();
            $sales = $salesList->count() > 1 ? $salesList[1] : $salesList->first();

            Kunjungan::updateOrCreate(
                ['nomor' => 'KNJ-' . date('Ymd') . '-002'],
                [
                    'tanggal'                 => now()->subDays(1)->toDateString(),
                    'waktu'                   => '14:00:00',
                    'sales_id'                => $sales->id,
                    'prodi_id'                => $prodi?->id,
                    'jenis'                   => 'Perusahaan',
                    'tujuan_id'               => $perusahaan->id,
                    'tujuan_kunjungan'        => 'Audensi Program Kelas Karyawan & Upskilling AI',
                    'hasil'                   => 'HRD PT Cirebon Power menyetujui MoU kerjasama perkuliahan karyawan.',
                    'catatan'                 => 'Rencana pendaftaran 25 karyawan untuk perkuliahan semester ganjil.',
                    'status'                  => 'Terverifikasi',
                    'status_verifikasi'       => 'Terverifikasi',
                    'status_lokasi'           => 'Dalam Radius',
                    'kehadiran'               => 'Hadir',
                    'nama_institusi'          => $perusahaan->nama,
                    'tier'                    => 'Tier 1',
                    'bidang_usaha'            => 'Pembangkit Listrik & Energi',
                    'potensi_s1'              => 20,
                    'potensi_s2'              => 5,
                    'potensi_csr'             => 50000000,
                    'alamat'                  => $perusahaan->alamat ?? 'Kawasan Industri Kanci, Cirebon',
                    'lokasi_penugasan'        => 'Kabupaten Cirebon',
                    'pic_name'                => 'Bpk. Ir. Rahmat Hidayat',
                    'pic_whatsapp'            => '081399887766',
                    'is_verified'             => true,
                    'is_outside_radius'       => false,
                    'lat'                     => -6.772100,
                    'lng'                     => 108.621000,
                    'jarak_meter'             => 60.0,
                    'academic_year_id'        => $ta?->id,
                ]
            );
        }
    }
}
