<?php

namespace Database\Seeders;

use App\Models\Kunjungan;
use App\Models\Sekolah;
use App\Models\Prodi;
use App\Models\User;
use App\Models\TahunAkademik;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class KunjunganSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Menyisakan 1 data kunjungan sampel.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        Kunjungan::truncate();
        Schema::enableForeignKeyConstraints();

        $sales = User::where('role', 'Sales')->first();
        $sekolah = Sekolah::first();
        $prodi = Prodi::first();
        $ta = TahunAkademik::getAktif();

        if (!$sales || !$sekolah) return;

        // 1 Kunjungan Sekolah Sampel (SMAN 1 Cirebon)
        Kunjungan::create([
            'nomor'                    => 'KNJ-' . date('Ymd') . '-001',
            'tanggal'                  => now()->subDays(2)->toDateString(),
            'waktu'                    => '09:30:00',
            'sales_id'                 => $sales->id,
            'prodi_id'                 => $prodi?->id,
            'jenis'                    => 'Sekolah',
            'tujuan_id'                => $sekolah->id,
            'tujuan_kunjungan'         => 'Presentasi Sosialisasi PMB & Demo AI',
            'hasil'                    => 'Presentasi berjalan lancar di hadapan 120 siswa kelas XII.',
            'catatan'                  => 'Pihak sekolah mengizinkan pendaftaran via jalur khusus beasiswa.',
            'status'                   => 'Selesai',
            'status_verifikasi'        => 'Valid',
            'status_lokasi'            => 'Dalam Radius',
            'kehadiran'                => 'Hadir',
            'nama_institusi'           => $sekolah->nama,
            'tier'                     => 'Tier 1',
            'alamat'                   => $sekolah->alamat ?? 'Jl. Wahidin Sudirohusodo No. 81, Cirebon',
            'lokasi_penugasan'         => 'Kota Cirebon',
            'pic_name'                 => 'Bpk. Drs. H. Mulyadi',
            'pic_whatsapp'             => '081234567890',
            'dosen_pemateri'           => 'Dr. Ir. H. Ahmad, M.T.',
            'potensi_mahasiswa'        => 120,
            'detail_potensi_mahasiswa' => 'Siswa kelas XII sangat berminat pada prodi Informatika & Bisnis Digital.',
            'kesediaan_training_ai'    => true,
            'is_verified'              => true,
            'is_outside_radius'        => false,
            'lat'                      => -6.713500,
            'lng'                      => 108.558000,
            'jarak_meter'              => 35.0,
            'academic_year_id'         => $ta?->id,
        ]);
    }
}
