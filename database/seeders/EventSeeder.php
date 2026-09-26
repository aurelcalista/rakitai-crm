<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\MasterData;
use App\Models\Prodi;
use App\Models\Sekolah;
use App\Models\Perusahaan;
use App\Models\User;
use App\Models\TahunAkademik;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        $eo = User::where('role', 'EO')->first() ?? User::where('role', 'Admin')->first();
        $spv = User::where('role', 'SPV')->first();
        $salesList = User::where('role', 'Sales')->get();
        $prodi = Prodi::first();
        $sekolah = Sekolah::first();
        $perusahaan = Perusahaan::first();
        $type = MasterData::where('type', 'jenis_event')->first();

        $ta = TahunAkademik::getAktif();

        if (!$eo) return;

        // 1. Edufair Kampus UCIC
        $event1 = Event::updateOrCreate(
            ['nama' => 'Edufair Kampus UCIC 2026'],
            [
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
            ]
        );

        // 2. Workshop Artificial Intelligence & Coding di Sekolah
        $event2 = Event::updateOrCreate(
            ['nama' => 'Workshop AI & Coding SMAN 1 Cirebon'],
            [
                'name'             => 'Workshop AI & Coding SMAN 1 Cirebon',
                'type_id'          => $type?->id,
                'tanggal'          => now()->subDays(2)->toDateString(),
                'tanggal_mulai'    => now()->subDays(2)->toDateString(),
                'tanggal_selesai'  => now()->subDays(2)->toDateString(),
                'waktu_mulai'      => '09:00',
                'waktu_selesai'    => '12:00',
                'lokasi'           => 'Lab Komputer SMAN 1 Cirebon',
                'deskripsi'        => 'Pelatihan pengenalan Artificial Intelligence dan pemrograman dasar untuk siswa kelas XII.',
                'eo_id'            => $eo->id,
                'status'           => 'Selesai',
                'prodi_id'         => $prodi?->id,
                'sekolah_id'       => $sekolah?->id,
                'academic_year_id' => $ta?->id,
                'jenis_institusi'  => 'Sekolah',
                'nama_institusi'   => $sekolah?->nama ?? 'SMAN 1 Cirebon',
                'pic_name'         => 'Ibu Ratna Dewi, M.Pd',
                'pic_whatsapp'     => '081987654321',
            ]
        );

        // Assign SPV & Sales to events
        if ($spv) {
            $event1->spvs()->syncWithoutDetaching([$spv->id]);
            $event2->spvs()->syncWithoutDetaching([$spv->id]);
        }

        if ($salesList->count() > 0) {
            $salesIds = $salesList->pluck('id')->toArray();
            $event1->sales()->syncWithoutDetaching($salesIds);
            $event2->sales()->syncWithoutDetaching($salesIds);
        }
    }
}
