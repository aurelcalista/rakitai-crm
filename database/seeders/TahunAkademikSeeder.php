<?php

namespace Database\Seeders;

use App\Models\TahunAkademik;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TahunAkademikSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        TahunAkademik::updateOrCreate(['nama' => '2025/2026'], ['status' => 'Non-Aktif']);
        TahunAkademik::updateOrCreate(['nama' => '2026/2027'], ['status' => 'Non-Aktif']);
        TahunAkademik::updateOrCreate(['nama' => '2027/2028'], ['status' => 'Aktif']);
    }
}
