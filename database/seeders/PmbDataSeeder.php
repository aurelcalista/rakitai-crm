<?php

namespace Database\Seeders;

use App\Models\Prodi;
use App\Models\Prospek;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PmbDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed PMB Admins
        $admins = [
            ['name' => 'Administrator UCIC', 'email' => 'admin@ucic.ac.id', 'role' => 'Admin', 'created_at' => '2025-08-12 09:00:00'],
            ['name' => 'Admin CS PMB', 'email' => 'pmb@cic.ac.id', 'role' => 'Admin', 'created_at' => '2025-08-12 09:00:00'],
            ['name' => 'meifie nuur aafiyah', 'email' => 'meifie.nuuraafiyah@cic.ac.id', 'role' => 'Admin', 'created_at' => '2025-08-16 10:15:00'],
            ['name' => 'BAAK UCIC', 'email' => 'official.baak@cic.ac.id', 'role' => 'Admin', 'created_at' => '2025-08-16 11:30:00'],
            ['name' => 'Lorenz Adam', 'email' => 'lorenzrenz@gmail.com', 'role' => 'Admin', 'created_at' => '2025-08-19 14:20:00'],
            ['name' => 'Kefas', 'email' => 'kefas.zefanya@cic.ac.id', 'role' => 'Admin', 'created_at' => '2025-10-02 08:45:00'],
            ['name' => 'yuda', 'email' => 'yuda.thomas@cic.ac.id', 'role' => 'Admin', 'created_at' => '2026-05-19 13:10:00'],
            ['name' => 'Biro Kemahasiswaan', 'email' => 'birokemahasiswaan.UCIC@gmail.com', 'role' => 'Admin', 'created_at' => '2026-09-09 10:00:00'],
            ['name' => 'Developper', 'email' => 'develop@gmail.com', 'role' => 'Admin', 'created_at' => '2026-10-05 16:30:00'],
            ['name' => 'Afif Abdul Muiz', 'email' => 'afif.abdul@cic.ac.id', 'role' => 'Admin', 'created_at' => '2026-10-06 09:20:00'],
        ];

        foreach ($admins as $adm) {
            $existing = User::where('email', $adm['email'])->first();
            if ($existing) {
                $existing->update([
                    'name' => $adm['name'],
                    'role' => $adm['role'],
                    'status' => 'Aktif',
                ]);
            } else {
                User::create([
                    'name' => $adm['name'],
                    'email' => $adm['email'],
                    'password' => Hash::make('password123'),
                    'role' => $adm['role'],
                    'status' => 'Aktif',
                    'created_at' => $adm['created_at'],
                ]);
            }
        }

        // 2. Fetch Prodis for mapping
        $prodiTi = Prodi::where('nama', 'like', '%Teknik Informatika%')->first();
        $prodiBd = Prodi::where('nama', 'like', '%Bisnis Digital%')->first();
        $prodiMan = Prodi::where('nama', 'like', '%Manajemen%')->first();
        $prodiSi = Prodi::where('nama', 'like', '%Sistem Informasi%')->first();

        // 3. Seed PMB Applicants (Prospeks)
        $applicants = [
            [
                'id' => 986,
                'name' => 'Ibnati Salsabila Ahmad',
                'email' => 'salsa30ibnati@gmail.com',
                'whatsapp' => '082111739914',
                'no_registrasi' => 'PMB-2026-0986',
                'gelombang' => '- Data Lama',
                'prodi_id' => null,
                'prodi_pilihan_2_id' => null,
                'status_formulir' => 'Belum Isi',
                'status_berkas' => 'Belum Upload',
                'status_ujian' => 'Belum Mengerjakan',
                'minat_kuliah' => 'ya',
                'minat_miniclass' => 'tidak',
                'status' => 'PROSPEK',
                'created_at' => '2026-10-21 09:15:00',
            ],
            [
                'id' => 985,
                'name' => 'Baladagehu',
                'email' => 'baladagehu992@gmail.com',
                'whatsapp' => '08989811657',
                'no_registrasi' => 'PMB-2026-0985',
                'gelombang' => '- Data Lama',
                'prodi_id' => null,
                'prodi_pilihan_2_id' => null,
                'status_formulir' => 'Belum Isi',
                'status_berkas' => 'Pending',
                'status_ujian' => 'Belum Mengerjakan',
                'minat_kuliah' => 'ya',
                'minat_miniclass' => 'ya',
                'status' => 'PROSPEK',
                'created_at' => '2026-10-20 14:22:00',
            ],
            [
                'id' => 984,
                'name' => 'Susana',
                'email' => 'susanaria3004@gmail.com',
                'whatsapp' => '081818241359',
                'no_registrasi' => 'PMB-2026-0984',
                'gelombang' => '- Data Lama',
                'prodi_id' => $prodiMan?->id,
                'prodi_pilihan_2_id' => $prodiBd?->id,
                'status_formulir' => 'Sudah Isi',
                'status_berkas' => 'Belum Upload',
                'status_ujian' => 'Belum Mengerjakan',
                'minat_kuliah' => 'ya',
                'minat_miniclass' => 'tidak',
                'status' => 'FORMULIR',
                'created_at' => '2026-10-20 11:05:00',
            ],
            [
                'id' => 983,
                'name' => 'Syariful Anwar',
                'email' => 'ipulsmart1122@gmail.com',
                'whatsapp' => '083148319010',
                'no_registrasi' => 'PMB-2026-0983',
                'gelombang' => '- Data Lama',
                'prodi_id' => $prodiTi?->id,
                'prodi_pilihan_2_id' => $prodiMan?->id,
                'status_formulir' => 'Sudah Isi',
                'status_berkas' => 'Pending',
                'status_ujian' => 'Sudah Mengerjakan',
                'nilai_ujian' => 84.50,
                'tanggal_ujian' => '2026-10-19 14:00:00',
                'minat_kuliah' => 'ya',
                'minat_miniclass' => 'tidak',
                'status' => 'BERKAS',
                'created_at' => '2026-10-19 16:45:00',
            ],
            [
                'id' => 982,
                'name' => 'Ashimatul Halimah',
                'email' => 'ashimatulhalimah@gmail.com',
                'whatsapp' => '0895429141663',
                'no_registrasi' => 'PMB-2026-0982',
                'gelombang' => '- Data Lama',
                'prodi_id' => null,
                'prodi_pilihan_2_id' => null,
                'status_formulir' => 'Belum Isi',
                'status_berkas' => 'Belum Upload',
                'status_ujian' => 'Belum Mengerjakan',
                'minat_kuliah' => 'ya',
                'minat_miniclass' => 'tidak',
                'status' => 'PROSPEK',
                'created_at' => '2026-10-19 10:12:00',
            ],
            [
                'id' => 981,
                'name' => 'Fiorenza Rivera Sinaga',
                'email' => 'fiorenzarivera@gmail.com',
                'whatsapp' => '082119840404',
                'no_registrasi' => 'PMB-2026-0981',
                'gelombang' => 'Gelombang 1',
                'prodi_id' => $prodiBd?->id,
                'prodi_pilihan_2_id' => $prodiSi?->id,
                'status_formulir' => 'Sudah Isi',
                'status_berkas' => 'Sudah Upload',
                'status_ujian' => 'Sudah Mengerjakan',
                'nilai_ujian' => 91.00,
                'tanggal_ujian' => '2026-10-18 11:30:00',
                'minat_kuliah' => 'ya',
                'minat_miniclass' => 'tidak',
                'status' => 'LUNAS',
                'created_at' => '2026-10-18 09:30:00',
            ],
            [
                'id' => 980,
                'name' => 'Rafi Priyamitra Nuraga',
                'email' => 'rafi.priyamitra.nuraga@gmail.com',
                'whatsapp' => '081223344551',
                'no_registrasi' => 'PMB-2026-0980',
                'gelombang' => 'Gelombang 1',
                'prodi_id' => $prodiTi?->id,
                'prodi_pilihan_2_id' => null,
                'status_formulir' => 'Sudah Isi',
                'status_berkas' => 'Pending',
                'status_ujian' => 'Sudah Mengerjakan',
                'nilai_ujian' => 78.00,
                'tanggal_ujian' => '2026-10-21 10:00:00',
                'minat_kuliah' => 'ya',
                'minat_miniclass' => 'ya',
                'status' => 'FORMULIR',
                'created_at' => '2026-10-21 08:00:00',
            ],
            [
                'id' => 979,
                'name' => 'Iqrot Jabar',
                'email' => 'iqrotjabar21@gmail.com',
                'whatsapp' => '081223344552',
                'no_registrasi' => 'PMB-2026-0979',
                'gelombang' => 'Gelombang 1',
                'prodi_id' => $prodiSi?->id,
                'prodi_pilihan_2_id' => $prodiTi?->id,
                'status_formulir' => 'Sudah Isi',
                'status_berkas' => 'Sudah Upload',
                'status_ujian' => 'Sudah Mengerjakan',
                'nilai_ujian' => 88.50,
                'tanggal_ujian' => '2026-10-13 09:00:00',
                'minat_kuliah' => 'ya',
                'minat_miniclass' => 'tidak',
                'status' => 'LUNAS',
                'created_at' => '2026-10-13 08:30:00',
            ],
            [
                'id' => 978,
                'name' => 'Cinta Cahyati',
                'email' => 'cyntacahyu@gmail.com',
                'whatsapp' => '081223344553',
                'no_registrasi' => 'PMB-2026-0978',
                'gelombang' => 'Gelombang 1',
                'prodi_id' => $prodiMan?->id,
                'prodi_pilihan_2_id' => null,
                'status_formulir' => 'Sudah Isi',
                'status_berkas' => 'Sudah Upload',
                'status_ujian' => 'Sudah Mengerjakan',
                'nilai_ujian' => 82.00,
                'tanggal_ujian' => '2026-10-12 08:30:00',
                'minat_kuliah' => 'ya',
                'minat_miniclass' => 'ya',
                'status' => 'LUNAS',
                'created_at' => '2026-10-12 08:15:00',
            ],
            [
                'id' => 977,
                'name' => 'Nur Azizah',
                'email' => 'nurazizah4360356@gmail.com',
                'whatsapp' => '081223344554',
                'no_registrasi' => 'PMB-2026-0977',
                'gelombang' => 'Gelombang 1',
                'prodi_id' => $prodiBd?->id,
                'prodi_pilihan_2_id' => null,
                'status_formulir' => 'Sudah Isi',
                'status_berkas' => 'Sudah Upload',
                'status_ujian' => 'Sudah Mengerjakan',
                'nilai_ujian' => 95.00,
                'tanggal_ujian' => '2026-10-09 08:15:00',
                'minat_kuliah' => 'ya',
                'minat_miniclass' => 'tidak',
                'status' => 'LUNAS',
                'created_at' => '2026-10-09 08:00:00',
            ],
            [
                'id' => 976,
                'name' => 'Mahdavikia Darmawan',
                'email' => 'darmawanmahdavikia@gmail.com',
                'whatsapp' => '081223344555',
                'no_registrasi' => 'PMB-2026-0976',
                'gelombang' => 'Gelombang 1',
                'prodi_id' => $prodiTi?->id,
                'prodi_pilihan_2_id' => null,
                'status_formulir' => 'Sudah Isi',
                'status_berkas' => 'Sudah Upload',
                'status_ujian' => 'Sudah Mengerjakan',
                'nilai_ujian' => 79.50,
                'tanggal_ujian' => '2026-10-09 08:10:00',
                'minat_kuliah' => 'ya',
                'minat_miniclass' => 'tidak',
                'status' => 'LUNAS',
                'created_at' => '2026-10-09 08:05:00',
            ],
            [
                'id' => 975,
                'name' => 'Muhamad Habibi',
                'email' => 'muhhabibi872@gmail.com',
                'whatsapp' => '081223344556',
                'no_registrasi' => 'PMB-2026-0975',
                'gelombang' => 'Gelombang 1',
                'prodi_id' => $prodiSi?->id,
                'prodi_pilihan_2_id' => null,
                'status_formulir' => 'Sudah Isi',
                'status_berkas' => 'Sudah Upload',
                'status_ujian' => 'Sudah Mengerjakan',
                'nilai_ujian' => 86.00,
                'tanggal_ujian' => '2026-10-09 08:00:00',
                'minat_kuliah' => 'ya',
                'minat_miniclass' => 'tidak',
                'status' => 'LUNAS',
                'created_at' => '2026-10-09 07:55:00',
            ],
        ];

        foreach ($applicants as $appData) {
            $prospek = Prospek::where('email', $appData['email'])->orWhere('id', $appData['id'])->first();
            if ($prospek) {
                $prospek->update($appData);
            } else {
                Prospek::create($appData);
            }
        }

        // 4. Seed PMB Transactions (Matching Screenshot 2)
        $transactions = [
            [
                'order_id' => 'PMB-UCIC-273-1792567889',
                'prospek_email' => 'rafi.priyamitra.nuraga@gmail.com',
                'nominal' => 600000,
                'detail_pembayaran' => '• Perlengkapan',
                'metode_pembayaran' => 'MIDTRANS',
                'payment_status' => Transaksi::STATUS_PENDING,
                'tanggal' => '2026-10-21 14:31:00',
            ],
            [
                'order_id' => 'PMB-UCIC-978-1791863061',
                'prospek_email' => 'iqrotjabar21@gmail.com',
                'nominal' => 3575000,
                'detail_pembayaran' => '• Perlengkapan • Pendaftaran • Tahap 1 • DPP',
                'metode_pembayaran' => 'TRANSFER BRI',
                'payment_status' => Transaksi::STATUS_VERIFIED,
                'tanggal' => '2026-10-13 10:44:00',
            ],
            [
                'order_id' => 'PMB-UCIC-975-1791770515',
                'prospek_email' => 'cyntacahyu@gmail.com',
                'nominal' => 300000,
                'detail_pembayaran' => '• Pendaftaran',
                'metode_pembayaran' => 'TRANSFER BRI',
                'payment_status' => Transaksi::STATUS_VERIFIED,
                'tanggal' => '2026-10-12 09:01:00',
            ],
            [
                'order_id' => 'PMB-UCIC-974-1791510785',
                'prospek_email' => 'nurazizah4360356@gmail.com',
                'nominal' => 900000,
                'detail_pembayaran' => '• Perlengkapan • Pendaftaran',
                'metode_pembayaran' => 'OFFLINE',
                'payment_status' => Transaksi::STATUS_VERIFIED,
                'tanggal' => '2026-10-09 08:53:00',
            ],
            [
                'order_id' => 'PMB-UCIC-973-1791509970',
                'prospek_email' => 'darmawanmahdavikia@gmail.com',
                'nominal' => 900000,
                'detail_pembayaran' => '• Perlengkapan • Pendaftaran',
                'metode_pembayaran' => 'OFFLINE',
                'payment_status' => Transaksi::STATUS_VERIFIED,
                'tanggal' => '2026-10-09 08:39:00',
            ],
            [
                'order_id' => 'PMB-UCIC-885-1791509892',
                'prospek_email' => 'muhhabibi872@gmail.com',
                'nominal' => 900000,
                'detail_pembayaran' => '• Perlengkapan • Pendaftaran',
                'metode_pembayaran' => 'OFFLINE',
                'payment_status' => Transaksi::STATUS_VERIFIED,
                'tanggal' => '2026-10-09 08:38:00',
            ],
            [
                'order_id' => 'PMB-UCIC-542-1791200110',
                'prospek_email' => 'ashimatulhalimah@gmail.com',
                'nominal' => 300000,
                'detail_pembayaran' => '• Pendaftaran',
                'metode_pembayaran' => 'MIDTRANS',
                'payment_status' => Transaksi::STATUS_REJECTED,
                'tanggal' => '2026-10-08 11:20:00',
                'rejection_reason' => 'Waktu pembayaran kadaluarsa (Expired)',
            ],
        ];

        foreach ($transactions as $tData) {
            $prospek = Prospek::where('email', $tData['prospek_email'])->first();
            if ($prospek) {
                Transaksi::updateOrCreate(
                    ['order_id' => $tData['order_id']],
                    [
                        'prospek_id' => $prospek->id,
                        'user_id' => 1,
                        'jenis' => 'Beli Formulir',
                        'kategori' => Transaksi::KATEGORI_DP_DAFTAR,
                        'nominal' => $tData['nominal'],
                        'detail_pembayaran' => $tData['detail_pembayaran'],
                        'metode_pembayaran' => $tData['metode_pembayaran'],
                        'payment_status' => $tData['payment_status'],
                        'tanggal' => $tData['tanggal'],
                        'rejection_reason' => $tData['rejection_reason'] ?? null,
                    ]
                );
            }
        }
    }
}
