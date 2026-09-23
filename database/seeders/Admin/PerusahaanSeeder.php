<?php

namespace Database\Seeders\Admin;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PerusahaanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $perusahaans = [
            [
                'kode'        => 'PRU-001',
                'nama'        => 'PT Cirebon Electric Power',
                'alamat'      => 'Jl. Raya Kanci, Cirebon',
                'telepon'     => '0231-321001',
                'status'      => 'Aktif',
                'kecamatan'   => 'Astanajapura',
            ],
            [
                'kode'        => 'PRU-002',
                'nama'        => 'PT Bank Mandiri Cabang Cirebon',
                'alamat'      => 'Jl. Siliwangi No. 123, Cirebon',
                'telepon'     => '0231-234567',
                'status'      => 'Aktif',
                'kecamatan'   => 'Kejaksan',
            ],
            [
                'kode'        => 'PRU-003',
                'nama'        => 'PT Indocement Tunggal Prakarsa',
                'alamat'      => 'Jl. Cirebon–Bandung Km 14, Palimanan',
                'telepon'     => '0231-341000',
                'status'      => 'Aktif',
                'kecamatan'   => 'Palimanan',
            ],
            [
                'kode'        => 'PRU-004',
                'nama'        => 'PT BRI Cabang Cirebon',
                'alamat'      => 'Jl. Pekiringan No. 6, Cirebon',
                'telepon'     => '0231-202222',
                'status'      => 'Aktif',
                'kecamatan'   => 'Kesambi',
            ],
            [
                'kode'        => 'PRU-005',
                'nama'        => 'PT Telkom Indonesia Regional Cirebon',
                'alamat'      => 'Jl. Terusan Pemuda No. 1, Cirebon',
                'telepon'     => '0231-231100',
                'status'      => 'Aktif',
                'kecamatan'   => 'Kejaksan',
            ],
            [
                'kode'        => 'PRU-006',
                'nama'        => 'PT Krakatau Steel Cirebon',
                'alamat'      => 'Kawasan Industri Cirebon',
                'telepon'     => '0231-880001',
                'status'      => 'Aktif',
                'kecamatan'   => 'Astanajapura',
            ],
            [
                'kode'        => 'PRU-007',
                'nama'        => 'Rumah Sakit Mitra Plumbon',
                'alamat'      => 'Jl. Raya Plumbon No. 20, Cirebon',
                'telepon'     => '0231-321500',
                'status'      => 'Aktif',
                'kecamatan'   => 'Plumbon',
            ],
            [
                'kode'        => 'PRU-008',
                'nama'        => 'PT Tiga Pilar Sejahtera Cirebon',
                'alamat'      => 'Jl. Brigjen Darsono No. 12, Cirebon',
                'telepon'     => '0231-488001',
                'status'      => 'Aktif',
                'kecamatan'   => 'Harjamukti',
            ],
            [
                'kode'        => 'PRU-009',
                'nama'        => 'PT Sumber Alfaria Trijaya (Alfamart) Cirebon',
                'alamat'      => 'Jl. Tuparev No. 12, Cirebon',
                'telepon'     => '0231-484000',
                'status'      => 'Aktif',
                'kecamatan'   => 'Kedawung',
            ],
            [
                'kode'        => 'PRU-010',
                'nama'        => 'Pemerintah Kota Cirebon - Dinas Pendidikan',
                'alamat'      => 'Jl. Brigjend Darsono No. 2, Cirebon',
                'telepon'     => '0231-235501',
                'status'      => 'Aktif',
                'kecamatan'   => 'Harjamukti',
            ],
        ];

        foreach ($perusahaans as $data) {
            // Only insert if doesn't exist yet
            DB::table('perusahaans')->updateOrInsert(
                ['kode' => $data['kode']],
                array_merge($data, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
