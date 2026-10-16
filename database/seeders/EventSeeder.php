<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\MasterData;
use App\Models\Prodi;
use App\Models\Sekolah;
use App\Models\User;
use App\Models\TahunAkademik;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class EventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Menyisakan 1 data event sampel.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        Event::truncate();
        Schema::enableForeignKeyConstraints();

        $eo = User::where('role', 'EO')->first() ?? User::where('role', 'Admin')->first();
        $prodi = Prodi::first();
        $sekolah = Sekolah::first();
        $type = MasterData::where('type', 'jenis_event')->first();
        $ta = TahunAkademik::getAktif();

        if (!$eo) return;

        // 1. Edufair Kampus UCIC 2026
        Event::create([
            'nama'             => 'Edufair Kampus UCIC 2026',
            'name'             => 'Edufair Kampus UCIC 2026',
            'type_id'          => $type?->id,
            'tanggal'          => now()->addDays(5)->toDateString(),
            'tanggal_mulai'    => now()->addDays(5)->toDateString(),
            'tanggal_selesai'  => now()->addDays(5)->toDateString(),
            'waktu_mulai'      => '08:00',
            'waktu_selesai'    => '14:00',
            'lokasi'           => 'Hall Utama Kampus UCIC Cirebon',
            'deskripsi'        => 'Pameran pendidikan dan sosialisasi program studi penerimaan mahasiswa baru UCIC.',
            'eo_id'            => $eo->id,
            'status'           => 'Terjadwal',
            'prodi_id'         => $prodi?->id,
            'sekolah_id'       => $sekolah?->id,
            'academic_year_id' => $ta?->id,
            'jenis_institusi'  => 'Sekolah',
            'nama_institusi'   => $sekolah?->nama ?? 'SMA Negeri 1 Cirebon',
            'pic_name'         => 'Bpk. Ahmad Suhendar',
            'pic_whatsapp'     => '081234567890',
        ]);
    }
}
