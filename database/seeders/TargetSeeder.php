<?php

namespace Database\Seeders;

use App\Models\Target;
use App\Models\User;
use App\Models\Wilayah;
use App\Models\TahunAkademik;
use Illuminate\Database\Seeder;

class TargetSeeder extends Seeder
{
    public function run(): void
    {
        $activeTa = TahunAkademik::getAktif() ?? TahunAkademik::first();
        $taId = $activeTa?->id;
        $taName = $activeTa?->nama ?? '2027/2028';

        $hm = User::where('role', 'HM')->first();
        $spv = User::where('role', 'SPV')->first();
        $salesList = User::where('role', 'Sales')->get();
        $wilayah = Wilayah::whereNull('parent_id')->first();

        // 1. Target Wilayah (Set by HM for SPV)
        if ($hm && $spv && $wilayah) {
            Target::updateOrCreate(
                [
                    'target_type'      => 'Wilayah',
                    'wilayah_id'       => $wilayah->id,
                    'academic_year_id' => $taId,
                ],
                [
                    'spv_id'           => $spv->id,
                    'sales_id'         => $spv->id,
                    'allocated_by'     => $hm->id,
                    'tipe_periode'     => 'Bulanan',
                    'tahun_akademik'   => $taName,
                    'tanggal_mulai'    => now()->startOfMonth(),
                    'tanggal_selesai'  => now()->endOfMonth(),
                    'target_kontak'    => 100,
                    'target_menghubungi'=> 80,
                    'target_followup'  => 60,
                    'target_kunjungan' => 20,
                    'target_formulir'  => 30,
                    'target_pemberkasan'=> 20,
                    'target_lunas'     => 15,
                    'status'           => 'Aktif',
                    'is_locked'        => true,
                    'locked_at'        => now(),
                    'locked_by'        => $hm->id,
                ]
            );
        }

        // 2. Target Individual for Sales (Set by SPV)
        if ($spv && $salesList->count() > 0) {
            foreach ($salesList as $sales) {
                Target::updateOrCreate(
                    [
                        'target_type'      => 'Individual',
                        'sales_id'         => $sales->id,
                        'academic_year_id' => $taId,
                    ],
                    [
                        'spv_id'           => $spv->id,
                        'allocated_by'     => $spv->id,
                        'wilayah_id'       => $sales->wilayah_id ?? $wilayah?->id,
                        'tipe_periode'     => 'Bulanan',
                        'tahun_akademik'   => $taName,
                        'tanggal_mulai'    => now()->startOfMonth(),
                        'tanggal_selesai'  => now()->endOfMonth(),
                        'target_kontak'    => 25,
                        'target_menghubungi'=> 20,
                        'target_followup'  => 15,
                        'target_kunjungan' => 5,
                        'target_formulir'  => 8,
                        'target_pemberkasan'=> 5,
                        'target_lunas'     => 4,
                        'status'           => 'Aktif',
                        'is_locked'        => false,
                    ]
                );
            }
        }
    }
}
