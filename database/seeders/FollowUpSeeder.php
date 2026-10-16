<?php

namespace Database\Seeders;

use App\Models\FollowUp;
use App\Models\Prospek;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class FollowUpSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Menyisakan 1 data follow up sampel.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        FollowUp::truncate();
        Schema::enableForeignKeyConstraints();

        $prospek = Prospek::first();
        if (!$prospek) return;

        $user = $prospek->sales ?? User::where('role', 'Sales')->first();
        if (!$user) return;

        FollowUp::create([
            'prospek_id'     => $prospek->id,
            'user_id'        => $user->id,
            'metode'         => 'WhatsApp',
            'tanggal'        => now()->subDays(2),
            'catatan'        => 'Follow-up via WhatsApp membahas rincian program beasiswa & kurikulum AI UCIC.',
            'hasil'          => 'Prospek sangat antusias dan telah menyelesaikan proses pendaftaran.',
            'next_follow_up' => now()->addDays(5),
        ]);
    }
}
