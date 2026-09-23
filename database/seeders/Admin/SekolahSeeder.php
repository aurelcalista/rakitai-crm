<?php

namespace Database\Seeders\Admin;

use Illuminate\Database\Seeder;
use App\Models\Sekolah;
use App\Models\MasterData;
use App\Models\Wilayah;

class SekolahSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $smaCat = MasterData::where('type', 'kategori_sekolah')->where('nama', 'SMA')->first();
        $smkCat = MasterData::where('type', 'kategori_sekolah')->where('nama', 'SMK')->first();
        $maCat  = MasterData::where('type', 'kategori_sekolah')->where('nama', 'MA')->first();

        $wilayahCrb = Wilayah::where('nama', 'Kota Cirebon')->first() ?? Wilayah::first();

        $sekolahs = [
            [
                'kode' => 'SKL-001',
                'nama' => 'SMA Negeri 1 Cirebon',
                'tier' => 'A',
                'kategori_id' => $smaCat?->id ?? 23,
                'wilayah_id' => $wilayahCrb?->id ?? 1,
                'kecamatan' => 'Kejaksan',
                'alamat' => 'Jl. Wahidin No. 81, Sukapura',
                'telepon' => '0231-203541',
                'email' => 'info@sman1cirebon.sch.id',
                'pic_name' => 'Drs. H. Mulyono',
                'pic_jabatan' => 'Kepala Sekolah',
                'pic_phone' => '08123456001',
                'status' => 'Aktif',
            ],
            [
                'kode' => 'SKL-002',
                'nama' => 'SMA Negeri 2 Cirebon',
                'tier' => 'A',
                'kategori_id' => $smaCat?->id ?? 23,
                'wilayah_id' => $wilayahCrb?->id ?? 1,
                'kecamatan' => 'Kesambi',
                'alamat' => 'Jl. Dr. Cipto Mangunkusumo No. 1',
                'telepon' => '0231-204120',
                'email' => 'info@sman2cirebon.sch.id',
                'pic_name' => 'Hj. Nurlaila, M.Pd',
                'pic_jabatan' => 'Guru BK',
                'pic_phone' => '08123456002',
                'status' => 'Aktif',
            ],
            [
                'kode' => 'SKL-003',
                'nama' => 'SMA Negeri 3 Cirebon',
                'tier' => 'B',
                'kategori_id' => $smaCat?->id ?? 23,
                'wilayah_id' => $wilayahCrb?->id ?? 1,
                'kecamatan' => 'Kesambi',
                'alamat' => 'Jl. Nyi Ageng Serang No. 33',
                'telepon' => '0231-207889',
                'email' => 'info@sman3cirebon.sch.id',
                'pic_name' => 'Bambang Irawan, S.Pd',
                'pic_jabatan' => 'Kesiswaan',
                'pic_phone' => '08123456003',
                'status' => 'Aktif',
            ],
            [
                'kode' => 'SKL-004',
                'nama' => 'SMA Santa Maria Cirebon',
                'tier' => 'A',
                'kategori_id' => $smaCat?->id ?? 23,
                'wilayah_id' => $wilayahCrb?->id ?? 1,
                'kecamatan' => 'Pekalipan',
                'alamat' => 'Jl. Sisingamangaraja No. 22',
                'telepon' => '0231-202311',
                'email' => 'info@santamaria-crb.sch.id',
                'pic_name' => 'Theresia Endang, S.Pd',
                'pic_jabatan' => 'Koordinator BK',
                'pic_phone' => '08123456004',
                'status' => 'Aktif',
            ],
            [
                'kode' => 'SKL-005',
                'nama' => 'SMK Negeri 1 Cirebon',
                'tier' => 'A',
                'kategori_id' => $smkCat?->id ?? 24,
                'wilayah_id' => $wilayahCrb?->id ?? 1,
                'kecamatan' => 'Kesambi',
                'alamat' => 'Jl. Perjuangan No. 10',
                'telepon' => '0231-200987',
                'email' => 'smkn1crb@sch.id',
                'pic_name' => 'Dr. H. Ahmad Santoso',
                'pic_jabatan' => 'Waka Hubinmas',
                'pic_phone' => '08123456005',
                'status' => 'Aktif',
            ],
            [
                'kode' => 'SKL-006',
                'nama' => 'SMK Negeri 2 Cirebon',
                'tier' => 'B',
                'kategori_id' => $smkCat?->id ?? 24,
                'wilayah_id' => $wilayahCrb?->id ?? 1,
                'kecamatan' => 'Kesambi',
                'alamat' => 'Jl. Dr. Cipto Mangunkusumo No. 20',
                'telepon' => '0231-206543',
                'email' => 'smkn2cirebon@sch.id',
                'pic_name' => 'Indra Gunawan, S.T',
                'pic_jabatan' => 'BKK / Hubinmas',
                'pic_phone' => '08123456006',
                'status' => 'Aktif',
            ],
            [
                'kode' => 'SKL-007',
                'nama' => 'SMK Informatika Al-Irsyad',
                'tier' => 'A',
                'kategori_id' => $smkCat?->id ?? 24,
                'wilayah_id' => $wilayahCrb?->id ?? 1,
                'kecamatan' => 'Kejaksan',
                'alamat' => 'Jl. Panjunan No. 54',
                'telepon' => '0231-208765',
                'email' => 'smk@alirsyad-crb.sch.id',
                'pic_name' => 'Faisal Basri, M.Kom',
                'pic_jabatan' => 'Kaprog RPL',
                'pic_phone' => '08123456007',
                'status' => 'Aktif',
            ],
            [
                'kode' => 'SKL-008',
                'nama' => 'MAN 1 Kota Cirebon',
                'tier' => 'B',
                'kategori_id' => $maCat?->id ?? 25,
                'wilayah_id' => $wilayahCrb?->id ?? 1,
                'kecamatan' => 'Harjamukti',
                'alamat' => 'Jl. Pilang Raya No. 4',
                'telepon' => '0231-201234',
                'email' => 'man1cirebon@kemenag.go.id',
                'pic_name' => 'Dra. Hj. Siti Rohmah',
                'pic_jabatan' => 'Guru BK',
                'pic_phone' => '08123456008',
                'status' => 'Aktif',
            ],
            [
                'kode' => 'SKL-009',
                'nama' => 'SMA Negeri 1 Sumber',
                'tier' => 'A',
                'kategori_id' => $smaCat?->id ?? 23,
                'wilayah_id' => $wilayahCrb?->id ?? 1,
                'kecamatan' => 'Sumber',
                'alamat' => 'Jl. Raden Dewi Sartika No. 102',
                'telepon' => '0231-321123',
                'email' => 'info@sman1sumber.sch.id',
                'pic_name' => 'Drs. H. Suharjo',
                'pic_jabatan' => 'Kepala Sekolah',
                'pic_phone' => '08123456009',
                'status' => 'Aktif',
            ],
            [
                'kode' => 'SKL-010',
                'nama' => 'SMK Negeri 1 Kedawung',
                'tier' => 'A',
                'kategori_id' => $smkCat?->id ?? 24,
                'wilayah_id' => $wilayahCrb?->id ?? 1,
                'kecamatan' => 'Kedawung',
                'alamat' => 'Jl. Tuparev No. 12',
                'telepon' => '0231-209988',
                'email' => 'smkn1kedawung@sch.id',
                'pic_name' => 'Eko Prasetyo, S.Pd',
                'pic_jabatan' => 'Hubinmas',
                'pic_phone' => '08123456010',
                'status' => 'Aktif',
            ],
        ];

        foreach ($sekolahs as $item) {
            Sekolah::updateOrCreate(
                ['kode' => $item['kode']],
                $item
            );
        }
    }
}
