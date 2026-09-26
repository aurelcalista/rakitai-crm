<?php

namespace Database\Seeders;

use App\Models\FollowUp;
use App\Models\Prospek;
use App\Models\User;
use Illuminate\Database\Seeder;

class FollowUpSeeder extends Seeder
{
    public function run(): void
    {
        $prospeks = Prospek::all();
        if ($prospeks->isEmpty()) return;

        $methods = FollowUp::METODE_OPTIONS;

        foreach ($prospeks as $prospek) {
            $user = $prospek->sales ?? User::where('role', 'Sales')->first();
            if (!$user) continue;

            // Create 1-3 follow up logs per prospect
            $count = rand(1, 3);
            for ($i = 0; $i < $count; $i++) {
                $method = $methods[array_rand($methods)];
                $date = now()->subDays(rand(1, 14));

                FollowUp::create([
                    'prospek_id'     => $prospek->id,
                    'user_id'        => $user->id,
                    'metode'         => $method,
                    'tanggal'        => $date,
                    'catatan'        => "Follow-up via {$method} membahas rincian program studi & kurikulum AI UCIC.",
                    'hasil'          => "Prospek merespon positif dan berminat mengikuti sesi presentasi.",
                    'next_follow_up' => $date->copy()->addDays(rand(3, 7)),
                ]);
            }
        }
    }
}
