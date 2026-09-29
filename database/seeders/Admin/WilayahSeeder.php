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
                'kecamatans' => [
                    'Harjamukti',
                    'Kejaksan',
                    'Kesambi',
                    'Lemahwungkuk',
                    'Pekalipan',
                ]
            ],
            [
                'kode' => 'W-KAB-CRB',
                'nama' => 'Kabupaten Cirebon',
                'level' => 'Kota/Kabupaten',
                'kecamatans' => [
                    'Arjawinangun',
                    'Astanajapura',
                    'Babakan',
                    'Beber',
                    'Ciledug',
                    'Ciwaringin',
                    'Depok',
                    'Dukupuntang',
                    'Gebang',
                    'Gegesik',
                    'Gempol',
                    'Greged',
                    'Gunungjati',
                    'Jamblang',
                    'Kaliwedi',
                    'Kapetakan',
                    'Karangsembung',
                    'Karangwareng',
                    'Kedawung',
                    'Klangenan',
                    'Lemahabang',
                    'Losari',
                    'Mundu',
                    'Pabedilan',
                    'Pabuaran',
                    'Palimanan',
                    'Pangenan',
                    'Panguragan',
                    'Pasaleman',
                    'Plered',
                    'Plumbon',
                    'Sedong',
                    'Sumber',
                    'Suranenggala',
                    'Susukan',
                    'Susukanlebak',
                    'Talun',
                    'Tengahtani',
                    'Waled',
                    'Weru',
                ]
            ],
            [
                'kode' => 'W-IND',
                'nama' => 'Kabupaten Indramayu',
                'level' => 'Kota/Kabupaten',
                'kecamatans' => [
                    'Anjatan',
                    'Arahan',
                    'Balongan',
                    'Bangodua',
                    'Bongas',
                    'Cantigi',
                    'Cikedung',
                    'Gabuswetan',
                    'Gantar',
                    'Haurgeulis',
                    'Indramayu',
                    'Jatibarang',
                    'Juntinyuat',
                    'Kandanghaur',
                    'Karangampel',
                    'Kedokan Bunder',
                    'Kertasemaya',
                    'Krangkeng',
                    'Kroya',
                    'Lelea',
                    'Lohbener',
                    'Losarang',
                    'Pasekan',
                    'Patrol',
                    'Sindang',
                    'Sliyeg',
                    'Sukagumiwang',
                    'Sukra',
                    'Terisi',
                    'Tukdana',
                    'Widasari',
                ]
            ],
            [
                'kode' => 'W-MJL',
                'nama' => 'Kabupaten Majalengka',
                'level' => 'Kota/Kabupaten',
                'kecamatans' => [
                    'Argapura',
                    'Banjaran',
                    'Bantarujeg',
                    'Cigasong',
                    'Cikijing',
                    'Cingambul',
                    'Dawuan',
                    'Jatitujuh',
                    'Jatiwangi',
                    'Kadipaten',
                    'Kasokandel',
                    'Kertajati',
                    'Lemahsugih',
                    'Leuwimunding',
                    'Ligung',
                    'Maja',
                    'Majalengka',
                    'Malausma',
                    'Panyingkiran',
                    'Palasah',
                    'Rajagaluh',
                    'Sindang',
                    'Sindangwangi',
                    'Sukahaji',
                    'Sumberjaya',
                    'Talaga',
                ]
            ],
            [
                'kode' => 'W-KNG',
                'nama' => 'Kabupaten Kuningan',
                'level' => 'Kota/Kabupaten',
                'kecamatans' => [
                    'Ciawigebang',
                    'Cibeureum',
                    'Cibingbin',
                    'Cidahu',
                    'Cigandamekar',
                    'Cigugur',
                    'Cilebak',
                    'Cilimus',
                    'Cimahi',
                    'Ciniru',
                    'Cipicung',
                    'Ciwaru',
                    'Darma',
                    'Garawangi',
                    'Hantara',
                    'Jalaksana',
                    'Japara',
                    'Kadugede',
                    'Kalimanggis',
                    'Karangkancana',
                    'Kramatmulya',
                    'Kuningan',
                    'Lebakwangi',
                    'Luragung',
                    'Maleber',
                    'Mandirancan',
                    'Nusaherang',
                    'Pancalang',
                    'Pasawahan',
                    'Selajambe',
                    'Sindangagung',
                    'Subang',
                ]
            ],
            [
                'kode' => 'W-BBS',
                'nama' => 'Kabupaten Brebes',
                'level' => 'Kota/Kabupaten',
                'kecamatans' => [
                    'Banjarharjo',
                    'Bantarkawung',
                    'Brebes',
                    'Bulakamba',
                    'Bumiayu',
                    'Jatibarang',
                    'Ketanggungan',
                    'Kersana',
                    'Larangan',
                    'Losari',
                    'Paguyangan',
                    'Salem',
                    'Sirampog',
                    'Songgom',
                    'Tanjung',
                    'Tonjong',
                    'Wanasari',
                ]
            ]
        ];

        foreach ($wilayahs as $w) {
            $parent = Wilayah::where('kode', $w['kode'])
                ->orWhere(function($q) use ($w) {
                    $q->whereNull('parent_id')->where('nama', $w['nama']);
                })->first();

            if ($parent) {
                $parent->update([
                    'kode' => $w['kode'],
                    'nama' => $w['nama'],
                    'level' => 'Kota/Kabupaten',
                    'parent_id' => null,
                    'status' => 'Aktif',
                ]);
            } else {
                $parent = Wilayah::create([
                    'kode' => $w['kode'],
                    'nama' => $w['nama'],
                    'level' => 'Kota/Kabupaten',
                    'parent_id' => null,
                    'status' => 'Aktif',
                ]);
            }

            foreach ($w['kecamatans'] as $index => $kec) {
                $existing = Wilayah::where('nama', $kec)->where('parent_id', $parent->id)->first();
                $kodeKecamatan = $existing ? $existing->kode : Wilayah::generateKecamatanKode($parent, $kec);

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
