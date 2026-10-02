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
            ['kode' => 'MNJ', 'nama' => 'Manajemen', 'jenjang' => 'S1', 'fakultas' => 'Fakultas Ekonomi dan Bisnis', 'kuota' => 210, 'status' => 'Aktif'],
            ['kode' => 'TI', 'nama' => 'Teknik Informatika', 'jenjang' => 'S1', 'fakultas' => 'Fakultas Teknologi Informasi', 'kuota' => 180, 'status' => 'Aktif'],
            ['kode' => 'DKV', 'nama' => 'DKV', 'jenjang' => 'S1', 'fakultas' => 'Fakultas Teknologi Informasi', 'kuota' => 130, 'status' => 'Aktif'],
            ['kode' => 'BD', 'nama' => 'Bisnis Digital (Baru)', 'jenjang' => 'S1', 'fakultas' => 'Fakultas Ekonomi dan Bisnis', 'kuota' => 160, 'status' => 'Aktif'],
            ['kode' => 'AKT', 'nama' => 'Akuntansi', 'jenjang' => 'S1', 'fakultas' => 'Fakultas Ekonomi dan Bisnis', 'kuota' => 105, 'status' => 'Aktif'],
            ['kode' => 'SI', 'nama' => 'Sistem Informasi', 'jenjang' => 'S1', 'fakultas' => 'Fakultas Teknologi Informasi', 'kuota' => 85, 'status' => 'Aktif'],
            ['kode' => 'PKOR', 'nama' => 'PKOR (Baru)', 'jenjang' => 'S1', 'fakultas' => 'Fakultas Ilmu Kesehatan & Olahraga', 'kuota' => 50, 'status' => 'Aktif'],
            ['kode' => 'PMAT', 'nama' => 'Pendidikan Matematika (Baru)', 'jenjang' => 'S1', 'fakultas' => 'Fakultas Keguruan & Ilmu Pendidikan', 'kuota' => 50, 'status' => 'Aktif'],
            ['kode' => 'MB-D3', 'nama' => 'Manajemen Bisnis (D3)', 'jenjang' => 'D3', 'fakultas' => 'Fakultas Ekonomi dan Bisnis', 'kuota' => 25, 'status' => 'Aktif'],
            ['kode' => 'S2-MNJ', 'nama' => 'S2 Manajemen (Tanpa Tesis)', 'jenjang' => 'S2', 'fakultas' => 'Pascasarjana', 'kuota' => 50, 'status' => 'Aktif'],
        ];

        foreach ($prodis as $p) {
            Prodi::updateOrCreate(
                ['kode' => $p['kode']],
                $p
            );
        }
    }
}
