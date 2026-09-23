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
            // Status Prospek (Pipeline 8 Status - PRD P0 8.3)
            ['type' => 'status_prospek', 'kode' => 'SP-01-BARU', 'nama' => 'BARU', 'deskripsi' => 'Kontak baru, belum dihubungi'],
            ['type' => 'status_prospek', 'kode' => 'SP-02-KONTAK', 'nama' => 'KONTAK', 'deskripsi' => 'Sudah dihubungi, belum respons'],
            ['type' => 'status_prospek', 'kode' => 'SP-03-HANGAT', 'nama' => 'HANGAT', 'deskripsi' => 'Merespons, menanyakan biaya/jadwal'],
            ['type' => 'status_prospek', 'kode' => 'SP-04-PANAS', 'nama' => 'PANAS', 'deskripsi' => 'Menyatakan berminat mendaftar'],
            ['type' => 'status_prospek', 'kode' => 'SP-05-FORMULIR', 'nama' => 'FORMULIR', 'deskripsi' => 'Sudah bayar biaya pendaftaran'],
            ['type' => 'status_prospek', 'kode' => 'SP-06-BERKAS', 'nama' => 'BERKAS', 'deskripsi' => 'Formulir dibayar, berkas belum lengkap'],
            ['type' => 'status_prospek', 'kode' => 'SP-07-LUNAS', 'nama' => 'LUNAS', 'deskripsi' => 'Termin-1 lunas, resmi mahasiswa'],
            ['type' => 'status_prospek', 'kode' => 'SP-08-DINGIN', 'nama' => 'DINGIN', 'deskripsi' => '14 hari tanpa respons setelah 5 sentuhan'],

            // Status Follow Up
            ['type' => 'status_followup', 'kode' => 'FU-DIJAWAB', 'nama' => 'Dijawab', 'deskripsi' => 'Telepon/Pesan dijawab'],
            ['type' => 'status_followup', 'kode' => 'FU-TIDAK-DIJAWAB', 'nama' => 'Tidak Dijawab', 'deskripsi' => 'Tidak ada respon'],
            ['type' => 'status_followup', 'kode' => 'FU-DITOLAK', 'nama' => 'Ditolak', 'deskripsi' => 'Prospek menolak dihubungi'],
            ['type' => 'status_followup', 'kode' => 'FU-TERTARIK', 'nama' => 'Tertarik & Minta Brosur', 'deskripsi' => 'Prospek minta brosur'],
            ['type' => 'status_followup', 'kode' => 'FU-JADWAL-KUNJUNGAN', 'nama' => 'Jadwalkan Kunjungan', 'deskripsi' => 'Kunjungan ke sekolah'],
            ['type' => 'status_followup', 'kode' => 'FU-BELUM-RESPON', 'nama' => 'Belum Respon', 'deskripsi' => 'Belum ada balasan'],
            ['type' => 'status_followup', 'kode' => 'FU-BELI-FORMULIR', 'nama' => 'FORMULIR', 'deskripsi' => 'Sudah membeli form'],

            // Jenis Kunjungan
            ['type' => 'jenis_kunjungan', 'kode' => 'JK-PRESENTASI', 'nama' => 'Presentasi', 'deskripsi' => 'Presentasi ke siswa/guru'],
            ['type' => 'jenis_kunjungan', 'kode' => 'JK-SEBAR-BROSUR', 'nama' => 'Sebar Brosur', 'deskripsi' => 'Menyebarkan brosur'],
            ['type' => 'jenis_kunjungan', 'kode' => 'JK-MOU', 'nama' => 'MoU', 'deskripsi' => 'Kerjasama/MoU'],

            // Kategori Prospek
            ['type' => 'kategori_prospek', 'kode' => 'KP-SANGAT-BERPELUANG', 'nama' => 'Sangat Berpeluang', 'deskripsi' => 'Prospek sangat tertarik (Hot)'],
            ['type' => 'kategori_prospek', 'kode' => 'KP-MASIH-RAGU', 'nama' => 'Masih Ragu', 'deskripsi' => 'Prospek masih pikir-pikir (Warm)'],
            ['type' => 'kategori_prospek', 'kode' => 'KP-BELUM-TERTARIK', 'nama' => 'Belum Tertarik', 'deskripsi' => 'Prospek belum tertarik (Cold)'],

            // Sumber Prospek (10 Dropdown + Lainnya - PRD P0 8.1.1)
            ['type' => 'sumber_prospek', 'kode' => 'SRC-01', 'nama' => 'Teman/Keluarga/Saudara', 'deskripsi' => 'Rujukan dari teman, keluarga, atau saudara'],
            ['type' => 'sumber_prospek', 'kode' => 'SRC-02', 'nama' => 'Sekolah', 'deskripsi' => 'Dari pihak sekolah/guru BK'],
            ['type' => 'sumber_prospek', 'kode' => 'SRC-03', 'nama' => 'Sosial Media (Facebook, Instagram, X)', 'deskripsi' => 'Dari konten/ads sosial media'],
            ['type' => 'sumber_prospek', 'kode' => 'SRC-04', 'nama' => 'Website CIC', 'deskripsi' => 'Mengisi form di website resmi CIC'],
            ['type' => 'sumber_prospek', 'kode' => 'SRC-05', 'nama' => 'Brosur/Poster', 'deskripsi' => 'Dari penyebaran brosur atau cetak'],
            ['type' => 'sumber_prospek', 'kode' => 'SRC-06', 'nama' => 'Sekretariat Kampus (Walk-in)', 'deskripsi' => 'Datang langsung ke sekretariat PMB'],
            ['type' => 'sumber_prospek', 'kode' => 'SRC-07', 'nama' => 'Pameran/Expo/University Day', 'deskripsi' => 'Hasil partisipasi event pameran'],
            ['type' => 'sumber_prospek', 'kode' => 'SRC-08', 'nama' => 'Acara Kampus', 'deskripsi' => 'Dari acara/seminar kampus'],
            ['type' => 'sumber_prospek', 'kode' => 'SRC-09', 'nama' => 'MGBK/Miniclass', 'deskripsi' => 'Hasil miniclass atau MGBK'],
            ['type' => 'sumber_prospek', 'kode' => 'SRC-10', 'nama' => 'Spanduk/Baliho', 'deskripsi' => 'Media baliho atau spanduk luar ruangan'],
            ['type' => 'sumber_prospek', 'kode' => 'SRC-11', 'nama' => 'Lainnya', 'deskripsi' => 'Sumber informasi lainnya'],

            // Kategori Sekolah
            ['type' => 'kategori_sekolah', 'kode' => 'KAT-SMA', 'nama' => 'SMA', 'deskripsi' => 'Sekolah Menengah Atas'],
            ['type' => 'kategori_sekolah', 'kode' => 'KAT-SMK', 'nama' => 'SMK', 'deskripsi' => 'Sekolah Menengah Kejuruan'],
            ['type' => 'kategori_sekolah', 'kode' => 'KAT-MA', 'nama' => 'MA', 'deskripsi' => 'Madrasah Aliyah'],

            // Kategori Perusahaan
            ['type' => 'kategori_perusahaan', 'kode' => 'KAT-IT', 'nama' => 'IT / Software House', 'deskripsi' => 'Perusahaan bidang teknologi'],
            ['type' => 'kategori_perusahaan', 'kode' => 'KAT-MANUFAKTUR', 'nama' => 'Manufaktur', 'deskripsi' => 'Pabrik dan industri'],
            
            // Program Studi (PRD Bab 7.2)
            ['type' => 'program_studi', 'kode' => 'PRD-MNJ', 'nama' => 'Manajemen', 'deskripsi' => 'Program Studi S1 Manajemen'],
            ['type' => 'program_studi', 'kode' => 'PRD-TI', 'nama' => 'Teknik Informatika', 'deskripsi' => 'Program Studi S1 Teknik Informatika'],
            ['type' => 'program_studi', 'kode' => 'PRD-DKV', 'nama' => 'DKV', 'deskripsi' => 'Program Studi S1 Desain Komunikasi Visual'],
            ['type' => 'program_studi', 'kode' => 'PRD-BD', 'nama' => 'Bisnis Digital (Baru)', 'deskripsi' => 'Program Studi S1 Bisnis Digital'],
            ['type' => 'program_studi', 'kode' => 'PRD-AKT', 'nama' => 'Akuntansi', 'deskripsi' => 'Program Studi S1 Akuntansi'],
            ['type' => 'program_studi', 'kode' => 'PRD-SI', 'nama' => 'Sistem Informasi', 'deskripsi' => 'Program Studi S1 Sistem Informasi'],
            ['type' => 'program_studi', 'kode' => 'PRD-PKOR', 'nama' => 'PKOR (Baru)', 'deskripsi' => 'Program Studi S1 Pendidikan Kepelatihan Olahraga'],
            ['type' => 'program_studi', 'kode' => 'PRD-PMAT', 'nama' => 'Pendidikan Matematika (Baru)', 'deskripsi' => 'Program Studi S1 Pendidikan Matematika'],
            ['type' => 'program_studi', 'kode' => 'PRD-MB-D3', 'nama' => 'Manajemen Bisnis (D3)', 'deskripsi' => 'Program Studi D3 Manajemen Bisnis'],
            ['type' => 'program_studi', 'kode' => 'PRD-MI-D3', 'nama' => 'Manajemen Informatika (D3)', 'deskripsi' => 'Program Studi D3 Manajemen Informatika'],
            ['type' => 'program_studi', 'kode' => 'PRD-S2-MNJ', 'nama' => 'S2 Manajemen (Tanpa Tesis)', 'deskripsi' => 'Program Magister S2 Manajemen'],

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
