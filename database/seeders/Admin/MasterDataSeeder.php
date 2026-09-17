<?php

namespace Database\Seeders\Admin;

use Illuminate\Database\Seeder;
use App\Models\MasterData;

class MasterDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            // Status Prospek
            ['type' => 'status_prospek', 'kode' => 'SP-BARU', 'nama' => 'Baru', 'deskripsi' => 'Prospek baru masuk, belum dihubungi'],
            ['type' => 'status_prospek', 'kode' => 'SP-FU1', 'nama' => 'Follow Up 1', 'deskripsi' => 'Sudah dihubungi pertama kali'],
            ['type' => 'status_prospek', 'kode' => 'SP-NEGO', 'nama' => 'Negosiasi', 'deskripsi' => 'Tahap negosiasi/ketertarikan tinggi'],
            ['type' => 'status_prospek', 'kode' => 'SP-DAFTAR', 'nama' => 'Mendaftar', 'deskripsi' => 'Sudah mendaftar / Closing'],
            ['type' => 'status_prospek', 'kode' => 'SP-TOLAK', 'nama' => 'Ditolak/Batal', 'deskripsi' => 'Tidak tertarik'],

            // Status Follow Up
            ['type' => 'status_followup', 'kode' => 'FU-DIJAWAB', 'nama' => 'Dijawab', 'deskripsi' => 'Telepon/Pesan dijawab'],
            ['type' => 'status_followup', 'kode' => 'FU-TIDAK-DIJAWAB', 'nama' => 'Tidak Dijawab', 'deskripsi' => 'Tidak ada respon'],
            ['type' => 'status_followup', 'kode' => 'FU-DITOLAK', 'nama' => 'Ditolak', 'deskripsi' => 'Prospek menolak dihubungi'],
            ['type' => 'status_followup', 'kode' => 'FU-TERTARIK', 'nama' => 'Tertarik & Minta Brosur', 'deskripsi' => 'Prospek minta brosur'],
            ['type' => 'status_followup', 'kode' => 'FU-JADWAL-KUNJUNGAN', 'nama' => 'Jadwalkan Kunjungan', 'deskripsi' => 'Kunjungan ke sekolah'],
            ['type' => 'status_followup', 'kode' => 'FU-BELUM-RESPON', 'nama' => 'Belum Respon', 'deskripsi' => 'Belum ada balasan'],
            ['type' => 'status_followup', 'kode' => 'FU-BELI-FORMULIR', 'nama' => 'Beli Formulir', 'deskripsi' => 'Sudah membeli form'],

            // Jenis Kunjungan
            ['type' => 'jenis_kunjungan', 'kode' => 'JK-PRESENTASI', 'nama' => 'Presentasi', 'deskripsi' => 'Presentasi ke siswa/guru'],
            ['type' => 'jenis_kunjungan', 'kode' => 'JK-SEBAR-BROSUR', 'nama' => 'Sebar Brosur', 'deskripsi' => 'Menyebarkan brosur'],
            ['type' => 'jenis_kunjungan', 'kode' => 'JK-MOU', 'nama' => 'MoU', 'deskripsi' => 'Kerjasama/MoU'],

            // Kategori Prospek
            ['type' => 'kategori_prospek', 'kode' => 'KP-SANGAT-BERPELUANG', 'nama' => 'Sangat Berpeluang', 'deskripsi' => 'Prospek sangat tertarik (Hot)'],
            ['type' => 'kategori_prospek', 'kode' => 'KP-MASIH-RAGU', 'nama' => 'Masih Ragu', 'deskripsi' => 'Prospek masih pikir-pikir (Warm)'],
            ['type' => 'kategori_prospek', 'kode' => 'KP-BELUM-TERTARIK', 'nama' => 'Belum Tertarik', 'deskripsi' => 'Prospek belum tertarik (Cold)'],

            // Sumber Prospek
            ['type' => 'sumber_prospek', 'kode' => 'SRC-BROSUR', 'nama' => 'Brosur', 'deskripsi' => 'Dari penyebaran brosur'],
            ['type' => 'sumber_prospek', 'kode' => 'SRC-SEKOLAH', 'nama' => 'Kunjungan Sekolah', 'deskripsi' => 'Hasil kunjungan langsung ke sekolah'],
            ['type' => 'sumber_prospek', 'kode' => 'SRC-IG', 'nama' => 'Instagram', 'deskripsi' => 'Dari DM atau Iklan IG'],
            ['type' => 'sumber_prospek', 'kode' => 'SRC-WEB', 'nama' => 'Website CIC', 'deskripsi' => 'Mengisi form di website'],

            // Kategori Sekolah
            ['type' => 'kategori_sekolah', 'kode' => 'KAT-SMA', 'nama' => 'SMA', 'deskripsi' => 'Sekolah Menengah Atas'],
            ['type' => 'kategori_sekolah', 'kode' => 'KAT-SMK', 'nama' => 'SMK', 'deskripsi' => 'Sekolah Menengah Kejuruan'],
            ['type' => 'kategori_sekolah', 'kode' => 'KAT-MA', 'nama' => 'MA', 'deskripsi' => 'Madrasah Aliyah'],

            // Kategori Perusahaan
            ['type' => 'kategori_perusahaan', 'kode' => 'KAT-IT', 'nama' => 'IT / Software House', 'deskripsi' => 'Perusahaan bidang teknologi'],
            ['type' => 'kategori_perusahaan', 'kode' => 'KAT-MANUFAKTUR', 'nama' => 'Manufaktur', 'deskripsi' => 'Pabrik dan industri'],
            
            // Fakultas
            ['type' => 'fakultas', 'kode' => 'FAK-FTI', 'nama' => 'Fakultas Teknologi Informasi', 'deskripsi' => 'FTI'],
            ['type' => 'fakultas', 'kode' => 'FAK-FEB', 'nama' => 'Fakultas Ekonomi dan Bisnis', 'deskripsi' => 'FEB'],

            // Jenjang
            ['type' => 'jenjang', 'kode' => 'JENJANG-D3', 'nama' => 'D3', 'deskripsi' => 'Diploma 3'],
            ['type' => 'jenjang', 'kode' => 'JENJANG-S1', 'nama' => 'S1', 'deskripsi' => 'Strata 1'],

            // Tahun Akademik / Gelombang
            ['type' => 'gelombang', 'kode' => 'GLB-1-24', 'nama' => 'Gelombang 1 2024/2025', 'deskripsi' => 'Pendaftaran Gel 1'],
            ['type' => 'gelombang', 'kode' => 'GLB-2-24', 'nama' => 'Gelombang 2 2024/2025', 'deskripsi' => 'Pendaftaran Gel 2'],
        ];

        foreach ($data as $item) {
            MasterData::updateOrCreate(
                ['kode' => $item['kode']], // kondisi pencarian (harus unik)
                $item                      // data yang diupdate/dibuat
            );
        }
    }
}
