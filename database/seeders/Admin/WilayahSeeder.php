<?php

namespace Database\Seeders\Admin;

use Illuminate\Database\Seeder;
use App\Models\Wilayah;

class WilayahSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $wilayahs = [
            ['kode' => 'W-CRB', 'nama' => 'Kota Cirebon', 'kecamatans' => ['Kejaksan', 'Kesambi', 'Pekalipan', 'Lemahwungkuk', 'Harjamukti']],
            ['kode' => 'W-KAB-CRB', 'nama' => 'Kabupaten Cirebon', 'kecamatans' => ['Kedawung', 'Weru', 'Sumber', 'Plumbon']],
            ['kode' => 'W-IND', 'nama' => 'Indramayu', 'kecamatans' => ['Indramayu', 'Karangampel', 'Jatibarang']],
            ['kode' => 'W-MJL', 'nama' => 'Majalengka', 'kecamatans' => ['Majalengka', 'Kadipaten', 'Jatiwangi']],
            ['kode' => 'W-KNG', 'nama' => 'Kuningan', 'kecamatans' => ['Kuningan', 'Cilimus', 'Luragung']],
            ['kode' => 'W-LAIN', 'nama' => 'Di Kota Lainnya', 'kecamatans' => ['Lainnya']],
        ];

        foreach ($wilayahs as $w) {
            $parent = Wilayah::updateOrCreate(
                ['kode' => $w['kode']],
                ['nama' => $w['nama'], 'level' => 'Kota/Kabupaten', 'parent_id' => null]
            );

            foreach ($w['kecamatans'] as $index => $kec) {
                // Generate a unique code for the kecamatan
                $kodeKecamatan = $w['kode'] . '-' . ($index + 1);
                Wilayah::updateOrCreate(
                    ['kode' => $kodeKecamatan],
                    ['nama' => $kec, 'level' => 'Kecamatan', 'parent_id' => $parent->id]
                );
            }
        }
    }
}
