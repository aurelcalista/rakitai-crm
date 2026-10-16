<?php

namespace Database\Seeders\Admin;

use Illuminate\Database\Seeder;
use App\Models\Perusahaan;
use Illuminate\Support\Facades\Schema;

class PerusahaanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Menyisakan 1 data perusahaan sampel untuk pengujian sistem.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        Perusahaan::truncate();
        Schema::enableForeignKeyConstraints();

        Perusahaan::create([
            'kode'      => 'PRU-001',
            'nama'      => 'PT Cirebon Electric Power',
            'alamat'    => 'Jl. Raya Kanci, Cirebon',
            'telepon'   => '0231-321001',
            'status'    => 'Aktif',
            'kecamatan' => 'Astanajapura',
        ]);
    }
}
