<?php

namespace Database\Seeders;

use App\Models\Target;
use App\Models\User;
use App\Models\Wilayah;
use App\Models\TahunAkademik;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class TargetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Menyisakan 1 data target sampel untuk pengujian sistem.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        Target::truncate();
        Schema::enableForeignKeyConstraints();

        $activeTa = TahunAkademik::getAktif() ?? TahunAkademik::first();
        $taId = $activeTa?->id;
        $taName = $activeTa?->nama ?? '2026/2027';

        $hm = User::where('role', 'HM')->first();
        $spv = User::where('role', 'SPV')->first();
        $wilayah = Wilayah::whereNull('parent_id')->first();

        // 1 Target Wilayah (HM untuk SPV)
        if ($hm && $spv && $wilayah) {
            Target::create([
                'target_type'        => 'Wilayah',
                'wilayah_id'         => $wilayah->id,
                'academic_year_id'   => $taId,
                'spv_id'             => $spv->id,
                'sales_id'           => $spv->id,
                'allocated_by'       => $hm->id,
                'tipe_periode'       => 'Bulanan',
                'tahun_akademik'     => $taName,
                'tanggal_mulai'      => now()->startOfMonth(),
                'tanggal_selesai'    => now()->endOfMonth(),
                'target_kontak'      => 100,
                'target_menghubungi' => 80,
                'target_followup'    => 60,
                'target_kunjungan'   => 20,
                'target_formulir'    => 30,
                'target_pemberkasan' => 20,
                'target_lunas'       => 15,
                'status'             => 'Aktif',
                'is_locked'          => true,
                'locked_at'          => now(),
                'locked_by'          => $hm->id,
            ]);
        }
    }
}
