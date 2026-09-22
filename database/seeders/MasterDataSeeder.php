<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            // Status Follow Up
            ['type' => 'status_followup', 'nama' => 'Tertarik & Minta Brosur', 'kode' => 'SF01', 'deskripsi' => 'Prospek minta brosur', 'status' => 'Aktif'],
            ['type' => 'status_followup', 'nama' => 'Jadwalkan Kunjungan', 'kode' => 'SF02', 'deskripsi' => 'Kunjungan ke sekolah', 'status' => 'Aktif'],
            ['type' => 'status_followup', 'nama' => 'Belum Respon', 'kode' => 'SF03', 'deskripsi' => 'Belum ada balasan', 'status' => 'Aktif'],
            ['type' => 'status_followup', 'nama' => 'Kurang Berminat', 'kode' => 'SF04', 'deskripsi' => 'Tidak tertarik', 'status' => 'Aktif'],
            ['type' => 'status_followup', 'nama' => 'FORMULIR', 'kode' => 'SF05', 'deskripsi' => 'Sudah membeli form', 'status' => 'Aktif'],
            
            // Jenjang
            ['type' => 'jenjang', 'nama' => 'S1', 'kode' => 'J01', 'deskripsi' => 'Sarjana', 'status' => 'Aktif'],
            ['type' => 'jenjang', 'nama' => 'D3', 'kode' => 'J02', 'deskripsi' => 'Diploma 3', 'status' => 'Aktif'],
            
            // Fakultas
            ['type' => 'fakultas', 'nama' => 'Fakultas Teknologi Informasi', 'kode' => 'F01', 'deskripsi' => 'FTI', 'status' => 'Aktif'],
            ['type' => 'fakultas', 'nama' => 'Fakultas Ekonomi dan Bisnis', 'kode' => 'F02', 'deskripsi' => 'FEB', 'status' => 'Aktif'],
        ];

        foreach ($data as $item) {
            \App\Models\MasterData::updateOrCreate(
                ['type' => $item['type'], 'nama' => $item['nama']],
                $item
            );
        }
    }
}
