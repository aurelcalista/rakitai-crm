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
            [
                'kode' => 'W-CRB',
                'nama' => 'Kota Cirebon',
                'level' => 'Kota/Kabupaten',
                'kecamatans' => ['Kejaksan', 'Kesambi', 'Pekalipan', 'Lemahwungkuk', 'Harjamukti']
            ],
            [
                'kode' => 'W-KAB-CRB',
                'nama' => 'Kabupaten Cirebon',
                'level' => 'Kota/Kabupaten',
                'kecamatans' => ['Kedawung', 'Weru', 'Sumber', 'Plumbon', 'Astanajapura', 'Arjawinangun', 'Ciwaringin', 'Babakan']
            ],
            [
                'kode' => 'W-IND',
                'nama' => 'Kabupaten Indramayu',
                'level' => 'Kota/Kabupaten',
                'kecamatans' => ['Indramayu', 'Karangampel', 'Jatibarang', 'Haurgeulis']
            ],
            [
                'kode' => 'W-MJL',
                'nama' => 'Kabupaten Majalengka',
                'level' => 'Kota/Kabupaten',
                'kecamatans' => ['Majalengka', 'Kadipaten', 'Jatiwangi', 'Rajagaluh']
            ],
            [
                'kode' => 'W-KNG',
                'nama' => 'Kabupaten Kuningan',
                'level' => 'Kota/Kabupaten',
                'kecamatans' => ['Kuningan', 'Cilimus', 'Luragung', 'Jalaksana']
            ],
        ];

        foreach ($wilayahs as $w) {
            $parent = Wilayah::updateOrCreate(
                ['kode' => $w['kode']],
                [
                    'nama' => $w['nama'],
                    'level' => 'Kota/Kabupaten',
                    'parent_id' => null,
                    'status' => 'Aktif',
                ]
            );

            foreach ($w['kecamatans'] as $index => $kec) {
                $kodeKecamatan = Wilayah::generateKecamatanKode($parent, $kec);

                Wilayah::updateOrCreate(
                    [
                        'nama' => $kec,
                        'parent_id' => $parent->id,
                    ],
                    [
                        'kode' => $kodeKecamatan,
                        'level' => 'Kecamatan',
                        'status' => 'Aktif',
                    ]
                );
            }
        }
    }
}
