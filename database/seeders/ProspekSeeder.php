<?php

namespace Database\Seeders;

use App\Models\Prospek;
use App\Models\ProspekTimeline;
use App\Models\User;
use App\Models\Wilayah;
use App\Models\Sekolah;
use App\Models\Prodi;
use App\Models\TahunAkademik;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class ProspekSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Menyisakan 1 data prospek sampel.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        ProspekTimeline::truncate();
        Prospek::truncate();
        Schema::enableForeignKeyConstraints();

        $sales = User::where('role', 'Sales')->first();
        $cs = User::where('role', 'CS')->first();
        $owner = User::where('role', 'Admin')->first();
        $wilayah = Wilayah::first();
        $sekolah = Sekolah::first();
        $prodi = Prodi::first();
        $ta = TahunAkademik::getAktif() ?? TahunAkademik::first();

        if (!$sales) return;

        // 1 Prospek Sampel (B2C - Budi Hendrawan)
        $prospek = Prospek::create([
            'name'             => 'Budi Hendrawan',
            'type'             => 'Individu',
            'category'         => 'B2C',
            'pic'              => 'Budi Hendrawan',
            'pic_phone'        => '081234567801',
            'whatsapp'         => '081234567801',
            'status'           => 'LUNAS',
            'stage_number'     => 6,
            'source'           => 'Kunjungan Sekolah',
            'sales_id'         => $sales->id,
            'cs_id'            => $cs?->id,
            'owner_id'         => $owner?->id ?? $sales->id,
            'wilayah_id'       => $wilayah?->id,
            'sekolah_id'       => $sekolah?->id,
            'prodi_id'         => $prodi?->id,
            'academic_year_id' => $ta?->id,
            'tahun_akademik'   => $ta?->nama ?? '2026/2027',
            'kelas'            => 'Reguler',
            'notes'            => 'Telah membayar lunas biaya registrasi dan UKT semester 1 Informatika.',
        ]);

        ProspekTimeline::create([
            'prospek_id'   => $prospek->id,
            'user_id'      => $sales->id,
            'title'        => 'Prospek Dibuat',
            'notes'        => 'Data prospek masuk dari sosialisasi kunjungan sekolah.',
            'status_after' => 'LUNAS',
            'time'         => now()->subDays(5),
        ]);
    }
}
