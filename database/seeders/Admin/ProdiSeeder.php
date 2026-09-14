<?php

namespace Database\Seeders\Admin;

use Illuminate\Database\Seeder;
use App\Models\Prodi;

class ProdiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $prodis = [
            ['kode' => 'TI', 'nama' => 'Teknik Informatika', 'jenjang' => 'S1', 'fakultas' => 'Fakultas Teknologi Informasi', 'kuota' => 100],
            ['kode' => 'SI', 'nama' => 'Sistem Informasi', 'jenjang' => 'S1', 'fakultas' => 'Fakultas Teknologi Informasi', 'kuota' => 100],
            ['kode' => 'DKV', 'nama' => 'Desain Komunikasi Visual', 'jenjang' => 'S1', 'fakultas' => 'Fakultas Teknologi Informasi', 'kuota' => 50],
            ['kode' => 'MNJ', 'nama' => 'Manajemen', 'jenjang' => 'S1', 'fakultas' => 'Fakultas Ekonomi dan Bisnis', 'kuota' => 150],
            ['kode' => 'AKT', 'nama' => 'Akuntansi', 'jenjang' => 'S1', 'fakultas' => 'Fakultas Ekonomi dan Bisnis', 'kuota' => 100],
        ];

        foreach ($prodis as $p) {
            Prodi::updateOrCreate(
                ['kode' => $p['kode']],
                $p
            );
        }
    }
}
