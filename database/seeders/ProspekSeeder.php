<?php

namespace Database\Seeders;

use App\Models\Prospek;
use App\Models\User;
use App\Models\Wilayah;
use App\Models\Sekolah;
use App\Models\Perusahaan;
use App\Models\Prodi;
use App\Models\TahunAkademik;
use Illuminate\Database\Seeder;

class ProspekSeeder extends Seeder
{
    public function run(): void
    {
        $salesList = User::where('role', 'Sales')->get();
        $cs = User::where('role', 'CS')->first();
        $owner = User::where('role', 'Admin')->first();
        $wilayahs = Wilayah::all();
        $sekolahs = Sekolah::all();
        $perusahaans = Perusahaan::all();
        $prodis = Prodi::all();
        $ta = TahunAkademik::getAktif() ?? TahunAkademik::first();

        if ($salesList->isEmpty()) return;

        $prospectSamples = [
            [
                'name' => 'Budi Hendrawan',
                'type' => 'Individu',
                'category' => 'B2C',
                'pic' => 'Budi Hendrawan',
                'pic_phone' => '081234567801',
                'whatsapp' => '081234567801',
                'status' => 'LUNAS',
                'source' => 'Kunjungan Sekolah',
                'notes' => 'Telah membayar lunas biaya registrasi dan UKT semester 1 Informatika.',
                'kelas' => 'Reguler',
            ],
            [
                'name' => 'Siti Nurhaliza',
                'type' => 'Individu',
                'category' => 'B2C',
                'pic' => 'Siti Nurhaliza',
                'pic_phone' => '081234567802',
                'whatsapp' => '081234567802',
                'status' => 'BERKAS',
                'source' => 'Website PMB',
                'notes' => 'Sudah beli formulir & melengkapi ijazah/SKL.',
                'kelas' => 'Reguler',
            ],
            [
                'name' => 'Andi Wijaya',
                'type' => 'Individu',
                'category' => 'B2C',
                'pic' => 'Andi Wijaya',
                'pic_phone' => '081234567803',
                'whatsapp' => '081234567803',
                'status' => 'FORMULIR',
                'source' => 'Edufair Kampus',
                'notes' => 'Membeli voucher formulir PMB program Sistem Informasi.',
                'kelas' => 'Reguler',
            ],
            [
                'name' => 'Dewi Lestari',
                'type' => 'Individu',
                'category' => 'B2C',
                'pic' => 'Dewi Lestari',
                'pic_phone' => '081234567804',
                'whatsapp' => '081234567804',
                'status' => 'PRESENTATION',
                'source' => 'Sosial Media / IG',
                'notes' => 'Tertarik program Beasiswa AI & Cyber Security.',
                'kelas' => 'Reguler',
            ],
            [
                'name' => 'Rian Hidayat',
                'type' => 'Sekolah',
                'category' => 'B2C',
                'pic' => 'Rian Hidayat',
                'pic_phone' => '081234567805',
                'whatsapp' => '081234567805',
                'status' => 'PROSPECT',
                'source' => 'Kunjungan Sekolah',
                'notes' => 'Prospek hangat dari presentasi kelas XII IPA SMAN 1 Cirebon.',
                'kelas' => 'Reguler',
            ],
            [
                'name' => 'PT Cirebon Power Development',
                'type' => 'Corporate',
                'category' => 'B2B',
                'pic' => 'Bpk. Ir. Rahmat Hidayat (HRD Manager)',
                'pic_phone' => '081234567806',
                'whatsapp' => '081234567806',
                'status' => 'PROSPECT',
                'source' => 'Kerjasama Corporate',
                'notes' => 'Prospek program kelas karyawan & upskilling AI karyawan PT Cirebon Power.',
                'kelas' => 'Karyawan',
            ],
            [
                'name' => 'Fajar Nugraha',
                'type' => 'Individu',
                'category' => 'B2C',
                'pic' => 'Fajar Nugraha',
                'pic_phone' => '081234567807',
                'whatsapp' => '081234567807',
                'status' => 'DISTRIBUTED',
                'source' => 'WhatsApp Inbound',
                'notes' => 'Telah didistribusikan ke sales area Cirebon Kota.',
                'kelas' => 'Reguler',
            ],
            [
                'name' => 'Aulia Putri',
                'type' => 'Individu',
                'category' => 'B2C',
                'pic' => 'Aulia Putri',
                'pic_phone' => '081234567808',
                'whatsapp' => '081234567808',
                'status' => 'DINGIN',
                'source' => 'Kunjungan Sekolah',
                'lost_reason' => 'Memilih kampus lain',
                'lost_note' => 'Diterima di PTN Jalur SNBP.',
                'kelas' => 'Reguler',
            ],
            [
                'name' => 'Eko Prasetyo',
                'type' => 'Individu',
                'category' => 'B2C',
                'pic' => 'Eko Prasetyo',
                'pic_phone' => '081234567809',
                'whatsapp' => '081234567809',
                'status' => 'LUNAS',
                'source' => 'Rekomendasi Alumni',
                'notes' => 'Mendaftar Prodi Desain Komunikasi Visual (DKV).',
                'kelas' => 'Reguler',
            ],
            [
                'name' => 'Rina Kartika',
                'type' => 'Individu',
                'category' => 'B2C',
                'pic' => 'Rina Kartika',
                'pic_phone' => '081234567810',
                'whatsapp' => '081234567810',
                'status' => 'FORMULIR',
                'source' => 'Website PMB',
                'notes' => 'Membeli formulir jalur reguler prodi Manajemen.',
                'kelas' => 'Reguler',
            ],
        ];

        foreach ($prospectSamples as $idx => $sample) {
            $sales = $salesList[$idx % $salesList->count()];
            $wilayah = $wilayahs->isNotEmpty() ? $wilayahs[$idx % $wilayahs->count()] : null;
            $sekolah = $sekolahs->isNotEmpty() ? $sekolahs[$idx % $sekolahs->count()] : null;
            $perusahaan = $perusahaans->isNotEmpty() ? $perusahaans[$idx % $perusahaans->count()] : null;
            $prodi = $prodis->isNotEmpty() ? $prodis[$idx % $prodis->count()] : null;

            Prospek::updateOrCreate(
                ['name' => $sample['name']],
                [
                    'type'                 => $sample['type'],
                    'category'             => $sample['category'],
                    'pic'                  => $sample['pic'],
                    'pic_phone'            => $sample['pic_phone'],
                    'whatsapp'             => $sample['whatsapp'],
                    'status'               => $sample['status'],
                    'source'               => $sample['source'],
                    'notes'                => $sample['notes'] ?? null,
                    'kelas'                => $sample['kelas'] ?? 'Reguler',
                    'lost_reason'          => $sample['lost_reason'] ?? null,
                    'lost_note'            => $sample['lost_note'] ?? null,
                    'wilayah_id'           => $wilayah?->id,
                    'sales_id'             => $sales->id,
                    'cs_id'                => $cs?->id,
                    'owner_id'             => $owner?->id ?? $sales->id,
                    'sekolah_id'           => $sample['category'] === 'B2C' ? $sekolah?->id : null,
                    'perusahaan_id'        => $sample['category'] === 'B2B' ? $perusahaan?->id : null,
                    'prodi_id'             => $prodi?->id,
                    'academic_year_id'     => $ta?->id,
                    'tahun_akademik'       => $ta?->nama ?? '2027/2028',
                    'follow_up_count'      => rand(1, 5),
                    'active_follow_up_count'=> rand(1, 3),
                ]
            );
        }
    }
}
