<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\Prospek;
use App\Models\ProspekTimeline;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CrmController extends Controller
{
    /**
     * Get current logged in user details based on active session role.
     */
    public static function getCurrentUser(Request $request): array
    {
        if (auth()->check()) {
            $user = auth()->user();
            $roleKey = strtolower($user->role ?? 'sales');

            $roleLabels = [
                'admin' => 'Super Administrator',
                'hm'    => 'Head Marketing',
                'spv'   => 'Supervisor Marketing',
                'cs'    => 'Customer Service',
                'sales' => 'Sales Inbound',
                'eo'    => 'Event Organizer',
            ];

            $roleNames = [
                'admin' => 'Admin',
                'hm'    => 'Head Marketing',
                'spv'   => 'Supervisor',
                'cs'    => 'CS',
                'sales' => 'Sales',
                'eo'    => 'EO',
            ];

            $name = $user->name;
            $words = array_values(array_filter(explode(' ', trim($name))));
            $avatar = strtoupper(substr($words[0] ?? 'A', 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));

            $wilayahNama = null;
            if ($user->wilayah) {
                $wilayahNama = $user->wilayah->nama . ($user->wilayah->parent ? ' (' . $user->wilayah->parent->nama . ')' : '');
            } else {
                if ($roleKey === 'admin') {
                    $wilayahNama = 'Semua Wilayah (Pusat Kampus UCIC)';
                } elseif ($roleKey === 'hm') {
                    $wilayahNama = 'Seluruh Wilayah Operasional (Regional & Nasional)';
                } elseif ($roleKey === 'cs') {
                    $wilayahNama = 'Kampus Utama UCIC (Inbound CS)';
                } elseif ($roleKey === 'eo') {
                    $wilayahNama = 'Wilayah Promosi & Event Kampus';
                } elseif ($roleKey === 'spv') {
                    $wilayahNama = 'Wilayah Cirebon & Sekitarnya (Supervisi)';
                } else {
                    $wilayahNama = 'Wilayah Cirebon & Sekitarnya';
                }
            }

            return [
                'id'              => $user->id,
                'name'            => $name,
                'role'            => $roleNames[$roleKey] ?? $user->role,
                'role_label'      => $roleLabels[$roleKey] ?? $user->role,
                'email'           => $user->email,
                'phone'           => $user->phone ?? '081234567890',
                'nik'             => '202408' . str_pad($user->id ?? 1, 3, '0', STR_PAD_LEFT),
                'avatar'          => $avatar ?: 'AD',
                'avatar_url'      => $user->avatar_url,
                'wilayah'         => $wilayahNama,
                'division'        => $roleKey === 'admin' 
                    ? 'Divisi Administrator & Pengelolaan Sistem — Universitas Catur Insan Cendekia' 
                    : 'Divisi Marketing & Admisi Mahasiswa Baru — Universitas Catur Insan Cendekia',
                'dashboard_route' => 'dashboard.' . ($roleKey === 'spv' ? 'spv' : ($roleKey === 'hm' ? 'hm' : ($roleKey === 'eo' ? 'eo' : $roleKey))),
                'user'            => $user,
            ];
        }

        $role = strtolower($request->session()->get('user_role', 'sales'));
        $customProfile = $request->session()->get('custom_profile_' . $role);

        $usersByRole = [
            'sales' => [
                'id'              => 1,
                'name'            => 'Aurel Calista',
                'role'            => 'Sales',
                'role_label'      => 'Sales Inbound',
                'email'           => 'aurel.calista@cic.ac.id',
                'phone'           => '081298765432',
                'nik'             => '202408119',
                'wilayah'         => 'Wilayah Cirebon & Sekitarnya',
                'avatar'          => 'AC',
                'division'        => 'Divisi Marketing & Admisi Mahasiswa Baru — Universitas Catur Insan Cendekia',
                'dashboard_route' => 'dashboard.sales',
            ],
            'cs' => [
                'id'              => 4,
                'name'            => 'Dina Marlina',
                'role'            => 'CS',
                'role_label'      => 'Customer Service',
                'email'           => 'dina.cs@cic.ac.id',
                'phone'           => '082199887766',
                'nik'             => '202408104',
                'wilayah'         => 'Kampus Utama UCIC (Inbound CS)',
                'avatar'          => 'DM',
                'division'        => 'Divisi Marketing & Admisi Mahasiswa Baru — Universitas Catur Insan Cendekia',
                'dashboard_route' => 'dashboard.cs',
            ],
            'spv' => [
                'id'              => 6,
                'name'            => 'Hendra Setiawan, S.Kom',
                'role'            => 'Supervisor',
                'role_label'      => 'Supervisor Marketing',
                'email'           => 'hendra.spv@cic.ac.id',
                'phone'           => '085277889911',
                'nik'             => '202408106',
                'wilayah'         => 'Indramayu & Cirebon (Supervisi)',
                'avatar'          => 'HS',
                'division'        => 'Divisi Marketing & Admisi Mahasiswa Baru — Universitas Catur Insan Cendekia',
                'dashboard_route' => 'dashboard.spv',
            ],
            'hm' => [
                'id'              => 7,
                'name'            => 'Dr. Rahmat Hidayat, M.M',
                'role'            => 'Head Marketing',
                'role_label'      => 'Head Marketing',
                'email'           => 'head.marketing@cic.ac.id',
                'phone'           => '081122334455',
                'nik'             => '202408107',
                'wilayah'         => 'Seluruh Wilayah Operasional (Regional & Nasional)',
                'avatar'          => 'RH',
                'division'        => 'Divisi Marketing & Admisi Mahasiswa Baru — Universitas Catur Insan Cendekia',
                'dashboard_route' => 'dashboard.hm',
            ],
            'eo' => [
                'id'              => 9,
                'name'            => 'Fajar Sidiq',
                'role'            => 'EO',
                'role_label'      => 'Event Organizer',
                'email'           => 'eo@cic.ac.id',
                'phone'           => '085344556677',
                'nik'             => '202408109',
                'wilayah'         => 'Wilayah Promosi & Event Kampus',
                'avatar'          => 'FS',
                'division'        => 'Divisi Marketing & Admisi Mahasiswa Baru — Universitas Catur Insan Cendekia',
                'dashboard_route' => 'dashboard.eo',
            ],
            'admin' => [
                'id'              => 8,
                'name'            => 'Administrator UCIC',
                'role'            => 'Admin',
                'role_label'      => 'Super Administrator',
                'email'           => 'admin@cic.ac.id',
                'phone'           => '081234567890',
                'nik'             => '202408001',
                'wilayah'         => 'Semua Wilayah (Pusat Kampus UCIC)',
                'avatar'          => 'AD',
                'division'        => 'Divisi Administrator & Pengelolaan Sistem — Universitas Catur Insan Cendekia',
                'dashboard_route' => 'dashboard.admin',
            ],
        ];

        $base = $usersByRole[$role] ?? $usersByRole['sales'];
        if ($customProfile) {
            $base = array_merge($base, $customProfile);
            $words = array_values(array_filter(explode(' ', trim($base['name']))));
            $base['avatar'] = strtoupper(substr($words[0] ?? 'A', 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
        }

        return $base;
    }

    /**
     * Get mock prospect dataset.
     */
    private function getProspects(): array
    {
        return [
            [
                'id' => 1,
                'name' => 'SMK Negeri 1 Cirebon',
                'type' => 'Sekolah',
                'category' => 'SMK',
                'pic' => 'Drs. H. Bambang Sutrisno, M.Pd (Kepala Sekolah)',
                'pic_phone' => '08122334455',
                'whatsapp' => '08122334455',
                'status' => 'KONTAK',
                'stage_number' => 2,
                'takeover_sales' => 'Aurel Calista',
                'takeover_cs' => 'Dina Marlina',
                'active_takeover' => 'Sales — Aurel Calista',
                'takeover_time' => '10 Sep 2026, 09:30',
                'owner' => 'Aurel Calista',
                'last_activity' => '2 jam lalu',
                'last_contact' => '10 Sep 2026, 14:00',
                'next_follow_up' => '12 Sep 2026, 10:00',
                'created_at' => '01 Sep 2026',
                'potential' => '120 Siswa Kelas XII (Jurusan TKJ & RPL) tertarik beasiswa & program D3/S1 AI & Software',
                'ai_training' => 'Bersedia Training AI/Robotics Oktober 2026',
                'notes' => 'Pihak sekolah mengundang UCIC untuk presentasi di aula saat workshop teknologi.',
                'timeline' => [
                    ['user' => 'Aurel Calista', 'role' => 'Sales', 'title' => 'Follow Up via WhatsApp', 'time' => '11 Sep 2026, 09:30', 'notes' => 'Konfirmasi jadwal audiensi dengan guru BK & Kepala Sekolah untuk tanggal 16 Sep.'],
                    ['user' => 'Dina Marlina', 'role' => 'CS', 'title' => 'Takeover CS ke Sales', 'time' => '10 Sep 2026, 14:15', 'notes' => 'Prospek meminta proposal resmi beasiswa sekolah, dilimpahkan ke tim Sales lapangan.'],
                    ['user' => 'System', 'role' => 'System', 'title' => 'Status Berubah Menjadi Interested', 'time' => '05 Sep 2026, 11:20', 'notes' => 'Respon positif terhadap program UCIC AI Campus.'],
                    ['user' => 'Aurel Calista', 'role' => 'Sales', 'title' => 'Prospek Dibuat', 'time' => '01 Sep 2026, 08:30', 'notes' => 'Inbound kontak melalui kampanye EduFair Cirebon.'],
                ],
            ],
            [
                'id' => 2,
                'name' => 'PT Surya Digital Nusantara',
                'type' => 'Corporate',
                'category' => 'Corporate',
                'pic' => 'Maya Kartika, S.Psi (HRD Manager)',
                'pic_phone' => '08139988776',
                'whatsapp' => '08139988776',
                'status' => 'HANGAT',
                'stage_number' => 3,
                'takeover_sales' => 'Rizky Pratama',
                'takeover_cs' => 'Rini Anggraini',
                'active_takeover' => 'Sales — Rizky Pratama',
                'takeover_time' => '09 Sep 2026, 11:00',
                'owner' => 'Rizky Pratama',
                'last_activity' => '4 jam lalu',
                'last_contact' => '10 Sep 2026, 16:30',
                'next_follow_up' => '11 Sep 2026, 13:30',
                'created_at' => '28 Aug 2026',
                'potential' => 'Program Kelas Karyawan S1 Informatika & S2 Manajemen Bisnis untuk 25 staf IT',
                'ai_training' => 'Tertarik Corporate In-house AI Upskilling',
                'notes' => 'HRD meminta penawaran skema cicilan khusus corporate UCIC.',
                'timeline' => [
                    ['user' => 'Rizky Pratama', 'role' => 'Sales', 'title' => 'Follow Up Telepon', 'time' => '10 Sep 2026, 16:30', 'notes' => 'Mengirimkan simulasi skema kelas karyawan.'],
                    ['user' => 'Rizky Pratama', 'role' => 'Sales', 'title' => 'Kunjungan Corporate', 'time' => '08 Sep 2026, 14:00', 'notes' => 'Meeting dengan Direktur HRD di Kawasan Industri Cirebon.'],
                ],
            ],
            [
                'id' => 3,
                'name' => 'SMA Negeri 2 Majalengka',
                'type' => 'Sekolah',
                'category' => 'SMA',
                'pic' => 'Ibu Nenden Kurniawati (Koordinator BK)',
                'pic_phone' => '08176543210',
                'whatsapp' => '08176543210',
                'status' => 'Beli Formulir',
                'stage_number' => 4,
                'takeover_sales' => 'Aurel Calista',
                'takeover_cs' => 'Dina Marlina',
                'active_takeover' => 'CS — Dina Marlina',
                'takeover_time' => '11 Sep 2026, 08:00',
                'owner' => 'Aurel Calista',
                'last_activity' => '30 menit lalu',
                'last_contact' => '11 Sep 2026, 09:15',
                'next_follow_up' => '13 Sep 2026, 09:00',
                'created_at' => '15 Aug 2026',
                'potential' => '35 Siswa mendaftar jalur PMDK Formulir UCIC 2026/2027',
                'ai_training' => 'Workshop AI for Education',
                'notes' => 'Guru BK mengkoordinasikan pembelian formulir kolektif tahap 1.',
                'timeline' => [
                    ['user' => 'Dina Marlina', 'role' => 'CS', 'title' => 'Verifikasi Pembelian Formulir', 'time' => '11 Sep 2026, 09:15', 'notes' => 'Formulir online terbit untuk 35 siswa gelombang 1.'],
                ],
            ],
            [
                'id' => 4,
                'name' => 'Faris Akbar (Mandiri S1 Bisnis)',
                'type' => 'Individu',
                'category' => 'Mahasiswa Baru',
                'pic' => 'Faris Akbar',
                'pic_phone' => '08218877665',
                'whatsapp' => '08218877665',
                'status' => 'Pembayaran Termin 1',
                'stage_number' => 5,
                'takeover_sales' => 'Budi Santoso',
                'takeover_cs' => 'Dina Marlina',
                'active_takeover' => 'CS — Dina Marlina',
                'takeover_time' => '10 Sep 2026, 15:30',
                'owner' => 'Budi Santoso',
                'last_activity' => '1 hari lalu',
                'last_contact' => '10 Sep 2026, 15:30',
                'next_follow_up' => '15 Sep 2026, 10:00',
                'created_at' => '10 Aug 2026',
                'potential' => 'Prodi Bisnis Digital S1, Pembayaran Termin 1 Lunas',
                'ai_training' => '-',
                'notes' => 'Menunggu verifikasi KRS dan orientasi mahasiswa baru.',
                'timeline' => [
                    ['user' => 'Dina Marlina', 'role' => 'CS', 'title' => 'Pembayaran Termin 1 Diterima', 'time' => '10 Sep 2026, 15:30', 'notes' => 'Bukti transfer termin 1 tervalidasi di sistem keuangan.'],
                ],
            ],
            [
                'id' => 5,
                'name' => 'SMK Bina Informatika Indramayu',
                'type' => 'Sekolah',
                'category' => 'SMK',
                'pic' => 'Pak Hendra Gunawan, S.Kom',
                'pic_phone' => '08191234567',
                'whatsapp' => '08191234567',
                'status' => 'LUNAS',
                'stage_number' => 6,
                'takeover_sales' => 'Aurel Calista',
                'takeover_cs' => 'Rini Anggraini',
                'active_takeover' => 'Sales — Aurel Calista',
                'takeover_time' => '08 Sep 2026, 10:00',
                'owner' => 'Aurel Calista',
                'last_activity' => '2 hari lalu',
                'last_contact' => '09 Sep 2026, 11:00',
                'next_follow_up' => '20 Sep 2026, 09:00',
                'created_at' => '01 Aug 2026',
                'potential' => 'MoU Kemitraan Kampus + 48 Siswa Closing Registrasi Ulang Gelombang 1',
                'ai_training' => 'Program Kolaborasi Lab AI UCIC',
                'notes' => 'MoU ditandatangani Rektor UCIC dan Kepala Sekolah SMK Bina Informatika.',
                'timeline' => [
                    ['user' => 'Aurel Calista', 'role' => 'Sales', 'title' => 'Penandatanganan MoU & Closing', 'time' => '08 Sep 2026, 10:00', 'notes' => '48 mahasiswa baru resmi terdaftar dengan beasiswa prestasi.'],
                ],
            ],
            [
                'id' => 6,
                'name' => 'SMA IT Al-Hikmah Kuningan',
                'type' => 'Sekolah',
                'category' => 'SMA',
                'pic' => 'Ustadz Ahmad Fauzi',
                'pic_phone' => '08521122334',
                'whatsapp' => '08521122334',
                'status' => 'BARU',
                'stage_number' => 1,
                'takeover_sales' => 'Rizky Pratama',
                'takeover_cs' => 'Dina Marlina',
                'active_takeover' => 'Sales — Rizky Pratama',
                'takeover_time' => '07 Sep 2026, 08:30',
                'owner' => 'Rizky Pratama',
                'last_activity' => '3 hari lalu',
                'last_contact' => '07 Sep 2026, 08:30',
                'next_follow_up' => '14 Sep 2026, 09:30',
                'created_at' => '05 Sep 2026',
                'potential' => 'Database 80 Siswa Kelas XII IPA/IPS',
                'ai_training' => 'Belum dijadwalkan',
                'notes' => 'Materi pengenalan kampus UCIC telah dikirim via email sekolah.',
                'timeline' => [
                    ['user' => 'Rizky Pratama', 'role' => 'Sales', 'title' => 'Input Cold Lead Baru', 'time' => '05 Sep 2026, 09:00', 'notes' => 'Data diperoleh dari roadshow kuningan.'],
                ],
            ],
            [
                'id' => 7,
                'name' => 'CV Mandiri Logistik Prima',
                'type' => 'Corporate',
                'category' => 'Corporate',
                'pic' => 'Bpk. Hendro Sasongko',
                'pic_phone' => '08129911223',
                'whatsapp' => '08129911223',
                'status' => 'DINGIN',
                'stage_number' => 0,
                'takeover_sales' => 'Budi Santoso',
                'takeover_cs' => 'Rini Anggraini',
                'active_takeover' => 'Sales — Budi Santoso',
                'takeover_time' => '02 Sep 2026, 14:00',
                'owner' => 'Budi Santoso',
                'last_activity' => '5 hari lalu',
                'last_contact' => '06 Sep 2026, 10:00',
                'next_follow_up' => '-',
                'created_at' => '12 Aug 2026',
                'potential' => 'Program Magang & Rekrutmen',
                'ai_training' => '-',
                'notes' => 'Perusahaan sedang moratorium budget pelatihan untuk semester ini. Tetap dicatat sebagai arsip prospek.',
                'timeline' => [
                    ['user' => 'Budi Santoso', 'role' => 'Sales', 'title' => 'Status Berubah Menjadi Lost', 'time' => '06 Sep 2026, 10:00', 'notes' => 'Alasan: Alokasi anggaran internal perusahaan dialihkan ke divisi lain.'],
                ],
            ],
        ];
    }

    /**
     * Get mock team performance data.
     */
    private function getTeamPerformance(): array
    {
        return [
            [
                'rank' => 1,
                'name' => 'Aurel Calista',
                'role' => 'Senior Sales Executive',
                'avatar' => 'AC',
                'target' => 50,
                'prospects' => 42,
                'follow_up' => 58,
                'LUNAS' => 36,
                'DINGIN' => 3,
                'achievement' => 72,
                'trend' => '+14% vs bulan lalu',
                'status' => 'On Track',
            ],
            [
                'rank' => 2,
                'name' => 'Rizky Pratama',
                'role' => 'Sales Representative',
                'avatar' => 'RP',
                'target' => 45,
                'prospects' => 38,
                'follow_up' => 49,
                'LUNAS' => 28,
                'DINGIN' => 4,
                'achievement' => 62,
                'trend' => '+8% vs bulan lalu',
                'status' => 'Good',
            ],
            [
                'rank' => 3,
                'name' => 'Budi Santoso',
                'role' => 'Sales Representative',
                'avatar' => 'BS',
                'target' => 45,
                'prospects' => 31,
                'follow_up' => 39,
                'LUNAS' => 22,
                'DINGIN' => 5,
                'achievement' => 48,
                'trend' => '+2% vs bulan lalu',
                'status' => 'Needs Attention',
            ],
            [
                'rank' => 4,
                'name' => 'Siti Nurhaliza',
                'role' => 'Junior Sales',
                'avatar' => 'SN',
                'target' => 40,
                'prospects' => 25,
                'follow_up' => 32,
                'LUNAS' => 18,
                'DINGIN' => 2,
                'achievement' => 45,
                'trend' => '+10% vs bulan lalu',
                'status' => 'Developing',
            ],
        ];
    }

    /**
     * Get mock visits data (Sekolah & Corporate).
     */
    /**
     * Format a Kunjungan model to the array expected by kunjungan/index.blade.php.
     * Blade expects: id, name, type, pic, sales, date, time, address, potential, photo, notes
     */
    private function formatKunjunganForBlade(\App\Models\Kunjungan $k): array
    {
        // Determine potential text based on visit type
        $potential = '-';
        if ($k->jenis === 'Sekolah') {
            $potential = $k->potensi_beasiswa ?? $k->hasil ?? '-';
        } else {
            $parts = array_filter([
                $k->potensi_s1 ? 'S1: ' . $k->potensi_s1 : null,
                $k->potensi_s2 ? 'S2: ' . $k->potensi_s2 : null,
                $k->potensi_csr ? 'CSR: ' . $k->potensi_csr : null,
            ]);
            $potential = count($parts) ? implode(' | ', $parts) : ($k->hasil ?? '-');
        }

        // Photo URL: use storage if available, else null (blade handles missing gracefully)
        $photoUrl = $k->foto_path
            ? \Illuminate\Support\Facades\Storage::url($k->foto_path)
            : null;

        return [
            'id'        => $k->id,
            'name'      => $k->nama_institusi ?? $k->tujuan_kunjungan ?? '-',
            'type'      => $k->jenis ?? '-',
            'pic'       => $k->pic_name ?? '-',
            'whatsapp'  => $k->pic_whatsapp ?? '-',
            'sales'     => $k->sales ? $k->sales->name : '-',
            'date'      => $k->tanggal ? $k->tanggal->format('d M Y') : '-',
            'time'      => $k->waktu ?? '-',
            'address'   => $k->alamat ?? '-',
            'potential' => $potential,
            'photo'     => $photoUrl,
            'notes'     => $k->catatan ?? $k->hasil ?? '-',
            // Type-specific details
            'potensi_beasiswa'      => $k->potensi_beasiswa ?? '-',
            'detail_beasiswa'       => $k->detail_beasiswa ?? '-',
            'kesediaan_training_ai' => $k->kesediaan_training_ai,
            'bidang_usaha'          => $k->bidang_usaha ?? '-',
            'potensi_s1'            => $k->potensi_s1 ?? '-',
            'potensi_s2'            => $k->potensi_s2 ?? '-',
            'potensi_csr'           => $k->potensi_csr ?? '-',
        ];
    }

    /**
     * Get mock system users dataset (for Admin).
     */
    private function getSystemUsers(): array
    {
        return [
            ['id' => 1, 'name' => 'Aurel Calista', 'email' => 'aurel.calista@cic.ac.id', 'role' => 'Sales', 'phone' => '081298765432', 'status' => 'Aktif', 'target' => 50, 'last_login' => '10 menit lalu', 'avatar' => 'AC'],
            ['id' => 2, 'name' => 'Rizky Pratama', 'email' => 'rizky.pratama@cic.ac.id', 'role' => 'Sales', 'phone' => '081377889900', 'status' => 'Aktif', 'target' => 45, 'last_login' => '1 jam lalu', 'avatar' => 'RP'],
            ['id' => 3, 'name' => 'Budi Santoso', 'email' => 'budi.santoso@cic.ac.id', 'role' => 'Sales', 'phone' => '081544332211', 'status' => 'Aktif', 'target' => 45, 'last_login' => '3 jam lalu', 'avatar' => 'BS'],
            ['id' => 4, 'name' => 'Dina Marlina', 'email' => 'dina.cs@cic.ac.id', 'role' => 'CS', 'phone' => '082199887766', 'status' => 'Aktif', 'target' => 0, 'last_login' => '5 menit lalu', 'avatar' => 'DM'],
            ['id' => 5, 'name' => 'Rini Anggraini', 'email' => 'rini.cs@cic.ac.id', 'role' => 'CS', 'phone' => '081911223344', 'status' => 'Aktif', 'target' => 0, 'last_login' => 'Kemarin', 'avatar' => 'RA'],
            ['id' => 6, 'name' => 'Hendra Setiawan, S.Kom', 'email' => 'hendra.spv@cic.ac.id', 'role' => 'Supervisor', 'phone' => '085277889911', 'status' => 'Aktif', 'target' => 180, 'last_login' => '20 menit lalu', 'avatar' => 'HS'],
            ['id' => 7, 'name' => 'Dr. Rahmat Hidayat, M.M', 'email' => 'head.marketing@cic.ac.id', 'role' => 'Head Marketing', 'phone' => '081122334455', 'status' => 'Aktif', 'target' => 500, 'last_login' => '2 jam lalu', 'avatar' => 'RH'],
            ['id' => 8, 'name' => 'Administrator UCIC', 'email' => 'admin@cic.ac.id', 'role' => 'Admin', 'phone' => '081234567890', 'status' => 'Aktif', 'target' => 0, 'last_login' => 'Sekarang', 'avatar' => 'AD'],
        ];
    }

    /**
     * Get mock master data (Study Programs & Lead Categories).
     */
    private function getMasterData(): array
    {
        return [
            'prodi' => [
                ['id' => 1, 'faculty' => 'Fakultas Teknologi Informasi (FTI)', 'code' => 'TI-S1', 'name' => 'S1 Teknik Informatika (Konsentrasi AI & Software)', 'quota' => 150, 'registered' => 92, 'tuition' => 'Rp 4.500.000 / semester'],
                ['id' => 2, 'faculty' => 'Fakultas Teknologi Informasi (FTI)', 'code' => 'SI-S1', 'name' => 'S1 Sistem Informasi (Enterprise ERP & Big Data)', 'quota' => 100, 'registered' => 64, 'tuition' => 'Rp 4.250.000 / semester'],
                ['id' => 3, 'faculty' => 'Fakultas Teknologi Informasi (FTI)', 'code' => 'DKV-S1', 'name' => 'S1 Desain Komunikasi Visual (UI/UX & Creative Tech)', 'quota' => 80, 'registered' => 52, 'tuition' => 'Rp 4.500.000 / semester'],
                ['id' => 4, 'faculty' => 'Fakultas Ekonomi & Bisnis (FEB)', 'code' => 'BD-S1', 'name' => 'S1 Bisnis Digital & E-Commerce', 'quota' => 120, 'registered' => 78, 'tuition' => 'Rp 4.000.000 / semester'],
                ['id' => 5, 'faculty' => 'Fakultas Ekonomi & Bisnis (FEB)', 'code' => 'MN-S1', 'name' => 'S1 Manajemen Bisnis (Reguler & Karyawan)', 'quota' => 100, 'registered' => 68, 'tuition' => 'Rp 3.850.000 / semester'],
                ['id' => 6, 'faculty' => 'Pascasarjana', 'code' => 'MM-S2', 'name' => 'S2 Magister Manajemen (Corporate Class)', 'quota' => 50, 'registered' => 28, 'tuition' => 'Rp 7.500.000 / semester'],
            ],
            'categories' => [
                ['id' => 1, 'name' => 'Sekolah (SMA/SMK/MA)', 'description' => 'Kerjasama audiensi, seminar edukasi, dan roadshow sekolah', 'leads_count' => 86],
                ['id' => 2, 'name' => 'Corporate / Perusahaan', 'description' => 'Kemitraan industri, kelas karyawan, dan program CSR', 'leads_count' => 34],
                ['id' => 3, 'name' => 'Individu / Siswa Langsung', 'description' => 'Pendaftaran mandiri inbound via iklan digital & website', 'leads_count' => 122],
                ['id' => 4, 'name' => 'Jalur Beasiswa Khusus', 'description' => 'Beasiswa Prestasi, Beasiswa Tahfidz, dan Beasiswa Mitra AI', 'leads_count' => 48],
            ],
            'batches' => [
                ['id' => 1, 'name' => 'Gelombang 1 (Early Bird / PMDK)', 'period' => '01 Jun 2026 - 31 Agu 2026', 'discount' => 'Potongan 50% DPP + Free Formulir', 'status' => 'Selesai'],
                ['id' => 2, 'name' => 'Gelombang 2 (Reguler & Beasiswa AI)', 'period' => '01 Sep 2026 - 31 Okt 2026', 'discount' => 'Potongan 25% DPP', 'status' => 'Aktif'],
                ['id' => 3, 'name' => 'Gelombang 3 (Last Call / Penutupan)', 'period' => '01 Nov 2026 - 15 Des 2026', 'discount' => 'Reguler Standard', 'status' => 'Akan Datang'],
            ],
        ];
    }

    /**
     * Get dynamic audit logs dataset from database with fallback.
     */
    private function getAuditLogs(): array
    {
        $dbTimelines = \App\Models\ProspekTimeline::with(['user', 'prospek'])
            ->latest('time')
            ->limit(100)
            ->get()
            ->map(function ($t) {
                return [
                    'id'     => $t->id,
                    'user'   => $t->user?->name ?? 'Sistem',
                    'role'   => $t->user?->role ?? 'System',
                    'action' => $t->title ?? 'Aktivitas Prospek',
                    'target' => $t->prospek?->name ?? ('Prospek #' . $t->prospek_id),
                    'detail' => $t->notes ?? ($t->status_before ? "Status: {$t->status_before} → {$t->status_after}" : 'Pencatatan aktivitas'),
                    'ip'     => '127.0.0.1',
                    'time'   => \Carbon\Carbon::parse($t->time ?? $t->created_at)->translatedFormat('d M Y, H:i:s'),
                ];
            })
            ->toArray();

        if (!empty($dbTimelines)) {
            return $dbTimelines;
        }

        return [
            ['id' => 101, 'user' => 'Aurel Calista', 'role' => 'Sales', 'action' => 'Update Status Prospek', 'target' => 'SMK Negeri 1 Cirebon', 'detail' => 'Status diubah dari Cold Lead menjadi Interested', 'ip' => '180.252.164.12', 'time' => '11 Sep 2026, 09:30:14'],
            ['id' => 102, 'user' => 'Dina Marlina', 'role' => 'CS', 'action' => 'Takeover Penugasan', 'target' => 'SMA Negeri 2 Majalengka', 'detail' => 'Pelimpahan prospek dari Sales ke CS untuk verifikasi formulir', 'ip' => '180.252.164.88', 'time' => '11 Sep 2026, 08:45:22'],
            ['id' => 103, 'user' => 'Administrator UCIC', 'role' => 'Admin', 'action' => 'Update WhatsApp Gateway', 'target' => 'Sistem Integrasi', 'detail' => 'Perpanjangan sesi koneksi WhatsApp Gateway Node-01', 'ip' => '10.10.1.5', 'time' => '11 Sep 2026, 08:00:00'],
            ['id' => 104, 'user' => 'Rizky Pratama', 'role' => 'Sales', 'action' => 'Input Laporan Kunjungan', 'target' => 'PT Surya Digital Nusantara', 'detail' => 'Upload 1 foto dokumentasi meeting corporate', 'ip' => '114.122.34.19', 'time' => '10 Sep 2026, 16:30:05'],
            ['id' => 105, 'user' => 'Hendra Setiawan', 'role' => 'Supervisor', 'action' => 'Export Laporan Rekap', 'target' => 'Laporan Periode Agustus', 'detail' => 'Download file CSV rekapitulasi data 312 prospek', 'ip' => '180.252.164.10', 'time' => '10 Sep 2026, 15:10:40'],
            ['id' => 106, 'user' => 'Dr. Rahmat Hidayat', 'role' => 'Head Marketing', 'action' => 'Konfigurasi Kuota', 'target' => 'Gelombang 2 PMDK', 'detail' => 'Penambahan kuota beasiswa AI sebanyak 50 kursi', 'ip' => '180.252.164.02', 'time' => '09 Sep 2026, 11:20:18'],
        ];
    }

    /**
     * Login page view.
     */
    public function login(): View
    {
        return view('auth.login');
    }

    /**
     * Dashboard Sales.
     */
    public function dashboardSales(Request $request): View
    {
        $request->session()->put('user_role', 'sales');
        $userId = auth()->id();
        
        // Use real DB queries for prospect data
        $prospectsData = \App\Models\Prospek::where('sales_id', $userId)->get();
        $totalProspek = $prospectsData->count();
        $activeProspek = $prospectsData->whereNotIn('status', ['LUNAS', 'DINGIN'])->count();
        $followUp = $prospectsData->where('status', 'HANGAT')->count();
        $closing = $prospectsData->where('status', 'LUNAS')->count();
        $lost = $prospectsData->where('status', 'DINGIN')->count();

        // Target Bulan Ini dalam Tahun Akademik Aktif.
        // Both conditions required: active TA scope AND current calendar month.
        $activeAyId = \App\Services\AkademikService::getAktifId();
        $targetMonth = \App\Models\Target::where('sales_id', $userId)
            ->whereMonth('tanggal_mulai', now()->month)
            ->whereYear('tanggal_mulai', now()->year)
            ->when($activeAyId, fn($q) => $q->where('academic_year_id', $activeAyId))
            ->first();
        $targetBulanIni = $targetMonth ? $targetMonth->target_kontak : 50;
        $percentage = $targetBulanIni > 0 ? round(($closing / $targetBulanIni) * 100) : 0;
        $sisaTarget = max(0, $targetBulanIni - $closing);

        $stats = [
            'total_prospek' => $totalProspek,
            'active_prospek' => $activeProspek,
            'follow_up' => $followUp,
            'LUNAS' => $closing,
            'DINGIN' => $lost,
            'target_bulan_ini' => $targetBulanIni,
            'realisasi_closing' => $closing,
            'percentage' => min(100, $percentage),
            'sisa_target' => $sisaTarget,
            'bulan_label' => now()->locale('id')->isoFormat('MMMM Y'),
        ];

        // Daily Target — use SalesTargetService (real snowball calculation, no hardcode)
        $targetService = app(\App\Services\SalesTargetService::class);
        $authUser = auth()->user();
        $dailyTarget = $authUser ? $targetService->calculateDailyTarget($authUser) : [
            'target_hari_ini_kontak'      => 0,
            'target_hari_ini_followup'    => 0,
            'sisa_akumulasi_kontak'       => 0,
            'sisa_akumulasi_followup'     => 0,
            'pencapaian_hari_ini_kontak'  => 0,
            'pencapaian_hari_ini_followup'=> 0,
        ];

        // Pipeline Stages
        $pipelineStages = collect(\App\Models\Prospek::ACTIVE_STAGES)->map(function($key) use ($prospectsData) {
            $count = $prospectsData->where('status', $key)->count();
            return [
                'name' => $key,
                'count' => $count,
                'color' => 'badge-' . strtolower(str_replace([' ', '/', '(', ')'], '-', $key))
            ];
        })->values()->toArray();

        // Recent prospects
        $recentProspects = $prospectsData->sortByDesc('created_at')->take(5)->map(function($p) {
            return [
                'id' => $p->id,
                'name' => $p->name,
                'type' => $p->type,
                'category' => $p->category ?? '-',
                'pic' => $p->pic ?? '-',
                'pic_phone' => $p->whatsapp ?? '-',
                'whatsapp' => $p->whatsapp ?? '-',
                'status' => $p->status,
                'active_takeover' => $p->activeHandlerLabel(),
                'last_activity' => $p->updated_at->diffForHumans(),
                'potential' => $p->notes ?? '-',
            ];
        })->values()->toArray();

        // Recent live activity
        $recentActivity = \App\Models\ProspekTimeline::where(function ($query) use ($userId) {
            $query->where('user_id', $userId)
                ->orWhereHas('prospek', function ($q) use ($userId) {
                    $q->where('sales_id', $userId);
                });
        })
        ->with('prospek')
        ->orderBy('time', 'desc')
        ->take(6)
        ->get()
        ->map(function ($t) {
            return [
                'time' => $t->time ? $t->time->format('d M, H:i') : ($t->created_at ? $t->created_at->format('d M, H:i') : '-'),
                'title' => $t->title ?? 'Aktivitas Prospek',
                'prospek_name' => $t->prospek ? $t->prospek->name : '-',
                'notes' => $t->notes ?? '-',
            ];
        })->toArray();
        return view('sales.dashboard', compact('stats', 'pipelineStages', 'recentProspects', 'dailyTarget', 'recentActivity'));
    }

    /**
     * Dashboard CS.
     */
    public function dashboardCs(Request $request): View
    {
        if ($request->hasSession()) {
            $request->session()->put('user_role', 'cs');
        }
        
        $prospects = $this->getDbProspects(request());
        
        $today = now()->toDateString();
        $followUpTodayCount = 0;
        $followUpPendingCount = 0;
        $followUpsToday = [];

        foreach ($prospects as $p) {
            if ($p['next_follow_up_date'] === $today) {
                $followUpTodayCount++;
                if (count($followUpsToday) < 4) {
                    $followUpsToday[] = $p;
                }
            } else if ($p['next_follow_up_date'] && $p['next_follow_up_date'] < $today && !in_array($p['status'], ['LUNAS', 'DINGIN'])) {
                $followUpPendingCount++;
            }
        }

        $lunasCount = count(array_filter($prospects, fn($p) => $p['status'] === 'LUNAS'));
        $dinginCount = count(array_filter($prospects, fn($p) => $p['status'] === 'DINGIN'));

        $stats = [
            'total_prospek' => count($prospects),
            'takeover_cs' => count(array_filter($prospects, fn($p) => str_contains($p['active_takeover'] ?? '', 'CS'))),
            'follow_up_today' => $followUpTodayCount,
            'follow_up_pending' => $followUpPendingCount,
            'closing' => $lunasCount,
            'lost' => $dinginCount,
            'LUNAS' => $lunasCount,
            'DINGIN' => $dinginCount,
        ];

        $targetAchievementService = app(\App\Services\TargetAchievementService::class);
        $period = request('periode', 'bulanan');
        $wilayahId = request('wilayah_id');
        $targetAchievementData = $targetAchievementService->getDashboardTargetData(auth()->user(), $period, $wilayahId);

        return view('cs.dashboard', compact('stats', 'followUpsToday', 'targetAchievementData'));
    }

    /**
     * Dashboard SPV — Dynamic from real Sales team data in Database.
     */
    public function dashboardSpv(Request $request): View
    {
        if ($request->hasSession()) {
            $request->session()->put('user_role', 'spv');
        }
        $user = auth()->user();

        // Retrieve Sales team members under this SPV
        $salesList = $user ? $user->salesSubordinates()->get() : collect();
        $salesIds = $salesList->pluck('id');

        // Aggregated Dynamic Stats from DB for this SPV's team
        $totalProspek = Prospek::whereIn('sales_id', $salesIds)->count();
        $totalClosing = Prospek::whereIn('sales_id', $salesIds)->where('status', 'LUNAS')->count();
        $totalLost = Prospek::whereIn('sales_id', $salesIds)->where('status', 'DINGIN')->count();
        $activeProspek = Prospek::whereIn('sales_id', $salesIds)->whereNotIn('status', ['LUNAS', 'DINGIN'])->count();

        $totalFollowUp = \App\Models\FollowUp::where(function ($q) use ($salesIds) {
            $q->whereIn('user_id', $salesIds)
              ->orWhereHas('prospek', fn ($p) => $p->whereIn('sales_id', $salesIds));
        })->count();

        $totalKunjungan = \App\Models\Kunjungan::whereIn('sales_id', $salesIds)->count();

        $stats = [
            'total_sales'      => $salesList->count(),
            'total_prospek'    => $totalProspek,
            'active_prospek'   => $activeProspek,
            'total_follow_up'  => $totalFollowUp,
            'total_closing'    => $totalClosing,
            'total_lost'       => $totalLost,
            'total_kunjungan'  => $totalKunjungan,
            'conversion_rate'  => $totalProspek > 0 ? round(($totalClosing / $totalProspek) * 100, 1) : 0,
        ];

        // Team Performance Per Personil
        $targetService = app(\App\Services\SalesTargetService::class);
        $team = $salesList->map(function ($s) use ($targetService) {
            $closing = Prospek::where('sales_id', $s->id)->where('status', 'LUNAS')->count();
            $prospectsCount = Prospek::where('sales_id', $s->id)->count();
            $followUpCount = \App\Models\FollowUp::where('user_id', $s->id)->count();
            $lostCount = Prospek::where('sales_id', $s->id)->where('status', 'DINGIN')->count();

            $activeTarget = $targetService->getActiveTarget($s);
            // Fix: use target_lunas (target_closing does not exist in DB)
            $targetAmount = $activeTarget ? (int)$activeTarget->target_lunas : 0;
            if ($targetAmount <= 0 && $activeTarget) {
                $targetAmount = (int)$activeTarget->target_kontak;
            }

            $achievement = $targetAmount > 0 ? round(($closing / $targetAmount) * 100) : 0;
            // Note: this dashboard method is dead code (no route). Kept for code correctness only.
            $statusLabel = $achievement >= 100 ? 'Tercapai' : ($achievement >= \App\Services\TargetMetricsService::YELLOW_THRESHOLD ? 'Mendekati Target' : 'Perlu Perhatian');


            return [
                'id'          => $s->id,
                'name'        => $s->name,
                'role'        => $s->role,
                'avatar'      => strtoupper(substr($s->name, 0, 2)),
                'target'      => $targetAmount,
                'prospects'   => $prospectsCount,
                'follow_up'   => $followUpCount,
                'closing'     => $closing,
                'lost'        => $lostCount,
                'LUNAS'       => $closing,
                'DINGIN'      => $lostCount,
                'achievement' => $achievement,
                'status'      => $statusLabel,
            ];
        })->sortByDesc('achievement')->values()->map(function ($member, $index) {
            $member['rank'] = $index + 1;
            return $member;
        })->toArray();

        return view('spv.dashboard', compact('stats', 'team', 'salesList'));
    }

    /**
     * Dashboard Head Marketing (HM).
     * Scoped to active Tahun Akademik. Team target uses actual target_lunas from DB.
     */
    public function dashboardHm(Request $request): View
    {
        if ($request->hasSession()) {
            $request->session()->put('user_role', 'hm');
        }

        $user = auth()->user();
        $spvId = $request->get('spv_id');

        // Active Tahun Akademik
        $activeAyId = \App\Services\AkademikService::getAktifId();
        $targetService = app(\App\Services\TargetAchievementService::class);

        // Subquery or ids for Sales filtering
        $salesIdsScope = null;
        if ($spvId) {
            $salesIdsScope = \App\Models\User::where('supervisor_id', $spvId)
                ->where('role', 'Sales')
                ->pluck('id')->toArray();
        }

        // Global stats (all prospects or filtered by SPV's Sales)
        $totalProspek  = Prospek::when($salesIdsScope !== null, fn($q) => $q->whereIn('sales_id', $salesIdsScope))->count();
        $closing       = Prospek::whereIn('status', ['CLOSING', 'LUNAS'])->when($salesIdsScope !== null, fn($q) => $q->whereIn('sales_id', $salesIdsScope))->count();
        $activeProspek = Prospek::whereNotIn('status', ['LUNAS', 'DINGIN', 'CANCEL'])->when($salesIdsScope !== null, fn($q) => $q->whereIn('sales_id', $salesIdsScope))->count();
        $lost          = Prospek::where('status', 'DINGIN')->when($salesIdsScope !== null, fn($q) => $q->whereIn('sales_id', $salesIdsScope))->count();
        $cancelCount   = Prospek::where('status', 'CANCEL')->when($salesIdsScope !== null, fn($q) => $q->whereIn('sales_id', $salesIdsScope))->count();

        // Global target rollup (sum of all active Sales targets)
        $totalTargetLunas = \App\Models\Target::where('status', 'Aktif')
            ->when($activeAyId, fn($q) => $q->where('academic_year_id', $activeAyId))
            ->when($salesIdsScope !== null, fn($q) => $q->whereIn('sales_id', $salesIdsScope))
            ->sum('target_lunas');
        $sisaTarget = max(0, $totalTargetLunas - $closing);
        $pctLunas   = $totalTargetLunas > 0 ? round(($closing / $totalTargetLunas) * 100, 1) : 0;
        $colorStatus = $pctLunas >= 100 ? 'green' : ($pctLunas >= 70 ? 'yellow' : 'red');

        $stats = [
            'total_prospek'   => $totalProspek,
            'active_prospek'  => $activeProspek,
            'LUNAS'           => $closing, // Keep keys for compatibility
            'closing'         => $closing,
            'DINGIN'          => $lost,
            'lost'            => $lost,
            'cancel'          => $cancelCount,
            'conversion_rate' => $totalProspek > 0 ? round(($closing / $totalProspek) * 100, 1) : 0,
            'total_sales'     => \App\Models\User::where('role', 'Sales')->when($spvId, fn($q) => $q->where('supervisor_id', $spvId))->count(),
            'total_cs'        => \App\Models\User::where('role', 'CS')->count(), // Usually global
            'target_global'   => $totalTargetLunas,
            'sisa_target'     => $sisaTarget,
            'pct_lunas'       => $pctLunas,
            'color_status'    => $colorStatus,
        ];

        $salesUsers = \App\Models\User::where('role', 'Sales')->when($spvId, fn($q) => $q->where('supervisor_id', $spvId))->get();
        $team = $salesUsers->map(function ($s) use ($activeAyId) {
            $closing        = Prospek::where('sales_id', $s->id)->whereIn('status', ['CLOSING', 'LUNAS'])
                ->when($activeAyId, fn($q) => $q->where('academic_year_id', $activeAyId))->count();
            $prospectsCount = Prospek::where('sales_id', $s->id)
                ->when($activeAyId, fn($q) => $q->where('academic_year_id', $activeAyId))->count();
            $activeTarget   = \App\Models\Target::where('sales_id', $s->id)
                ->where('status', 'Aktif')
                ->when($activeAyId, fn($q) => $q->where('academic_year_id', $activeAyId))
                ->latest()->first();
            $targetNum      = $activeTarget ? (int)$activeTarget->target_lunas : 0;
            $achievement    = $targetNum > 0 ? round(($closing / $targetNum) * 100) : 0;
            return [
                'name'        => $s->name,
                'role'        => $s->role,
                'avatar'      => strtoupper(substr($s->name, 0, 2)),
                'target'      => $targetNum,
                'prospects'   => $prospectsCount,
                'LUNAS'       => $closing,
                'closing'     => $closing,
                'achievement' => $achievement,
            ];
        })->sortByDesc('achievement')->values()->map(function ($member, $index) {
            $member['rank'] = $index + 1;
            return $member;
        })->toArray();

        $stagesCount = array_fill_keys(\App\Models\Prospek::ACTIVE_STAGES, 0);
        $prospectsData = Prospek::select('status')
            ->when($activeAyId, fn($q) => $q->where('academic_year_id', $activeAyId))
            ->when($salesIdsScope !== null, fn($q) => $q->whereIn('sales_id', $salesIdsScope))
            ->get();
        foreach ($prospectsData as $p) {
            if (isset($stagesCount[$p->status])) {
                $stagesCount[$p->status]++;
            }
        }

        $pipelineStages = collect($stagesCount)->map(function ($count, $name) use ($totalProspek) {
            $percentage = $totalProspek > 0 ? round(($count / $totalProspek) * 100) : 0;
            return [
                'name'  => $name,
                'count' => $count,
                'pct'   => $percentage,
                'color' => 'badge-' . strtolower(str_replace([' ', '/', '(', ')'], '-', $name))
            ];
        })->values()->toArray();

        // Data Dashboard Target & Pencapaian Berjenjang (PRD Bab 6.2)
        $targetAchievementData = app(\App\Services\TargetAchievementService::class)->getDashboardTargetData(
            auth()->user(),
            $request->get('periode', 'bulanan'),
            $request->get('wilayah_id') ? (int)$request->get('wilayah_id') : null,
            $request->get('ta')
        );

        // Data Trend Bulanan (Dinamis)
        $currentYear = date('Y');
        $monthlyData = Prospek::select(
                DB::raw('MONTH(updated_at) as month'),
                DB::raw('COUNT(*) as count')
            )
            ->whereIn('status', ['CLOSING', 'LUNAS'])
            ->when($activeAyId, fn($q) => $q->where('academic_year_id', $activeAyId))
            ->when($user->wilayah_id, fn($q) => $q->where('wilayah_id', $user->wilayah_id))
            ->when($salesIdsScope !== null, fn($q) => $q->whereIn('sales_id', $salesIdsScope))
            ->whereYear('updated_at', $currentYear)
            ->groupBy('month')
            ->pluck('count', 'month')
            ->toArray();

        $maxMonthly = !empty($monthlyData) ? max($monthlyData) : 1;
        $maxMonthly = $maxMonthly > 0 ? $maxMonthly : 1;
        
        $bulanList = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        
        $monthlyTrend = [];
        $currentMonth = date('n');
        
        foreach ($bulanList as $num => $name) {
            $count = $monthlyData[$num] ?? 0;
            $monthlyTrend[] = [
                'month_name' => $name,
                'is_current' => $num == $currentMonth,
                'count' => $count,
                'percentage' => round(($count / $maxMonthly) * 100)
            ];
        }

        $spvs = \App\Models\User::where('role', 'SPV')->where('status', 'Aktif')->get();

        return view('hm.dashboard', compact('stats', 'team', 'pipelineStages', 'targetAchievementData', 'monthlyTrend', 'currentYear', 'spvs'));
    }

    /**
     * Dashboard Admin (Overview System, Gateway, Users, Audit).
     */
    public function dashboardAdmin(Request $request): View
    {
        $request->session()->put('user_role', 'admin');
        $systemStats = [
            'total_users' => 8,
            'active_sessions' => 6,
            'total_prospects' => 485,
            'wa_gateway_status' => 'Terhubung (Online)',
            'storage_used' => '1.2 GB / 20 GB',
            'api_uptime' => '99.98%',
        ];

        $users = $this->getSystemUsers();
        $auditLogs = array_slice($this->getAuditLogs(), 0, 5);
        $masterData = $this->getMasterData();

        return view('admin.dashboard', compact('systemStats', 'users', 'auditLogs', 'masterData'));
    }

    /**
     * Admin: User & Team Management.
     */
    public function adminUsers(): View
    {
        $users = $this->getSystemUsers();

        return view('admin.users.index', compact('users'));
    }

    /**
     * Admin: Master Data (Prodi, Kategori, Gelombang).
     */
    public function adminMasterData(): View
    {
        $masterData = $this->getMasterData();

        return view('admin.master-data.index', compact('masterData'));
    }

    /**
     * Admin: Audit Logs.
     */
    public function adminAuditLogs(): View
    {
        $logs = $this->getAuditLogs();

        return view('admin.audit-logs.index', compact('logs'));
    }

    /**
     * Admin: System Settings & Integrations.
     */
    public function adminSettings(): View
    {
        return view('admin.settings.index');
    }

    /**
     * Daftar Prospek (Scoped to authenticated user role / team hierarchy).
     */
    private function getDbProspects(?Request $request = null)
    {
        $user = auth()->user();
        $query = Prospek::with(['sales', 'cs', 'owner', 'sekolah', 'perusahaan', 'followUps' => function($q) {
            $q->orderBy('tanggal', 'desc');
        }, 'timelines' => function ($q) {
            $q->orderBy('time', 'desc')->with('user');
        }]);

        if ($user) {
            $role = strtolower($user->role);
            if ($role === 'sales') {
                $query->where('sales_id', $user->id);
            } elseif ($role === 'spv') {
                $salesIds = $user->teamMemberIds();
                $query->where(function ($q) use ($salesIds, $user) {
                    $q->whereIn('sales_id', $salesIds)
                      ->orWhereIn('owner_id', $salesIds);
                    if ($user->wilayah_id) {
                        $q->orWhere('wilayah_id', $user->wilayah_id);
                    }
                });
            } elseif ($role === 'cs') {
                $query->where(function ($q) use ($user) {
                    $q->where('cs_id', $user->id)
                      ->orWhereNotNull('sales_id');
                });
            } elseif (in_array($role, ['hm', 'eo'])) {
                if ($role === 'hm') {
                    $hmMemberIds = $user->hmMemberIds();
                    
                    if ($request && $request->sales_id) {
                        $query->where('sales_id', $request->sales_id);
                    } elseif ($request && $request->spv_id) {
                        $spvSalesIds = \App\Models\User::where('supervisor_id', $request->spv_id)
                                          ->where('role', 'Sales')
                                          ->pluck('id')->toArray();
                        $query->whereIn('sales_id', $spvSalesIds);
                    } else {
                        // See all prospects whose sales are under SPVs in HM's scope
                        $spvsInScope = \App\Models\User::whereIn('id', $hmMemberIds)->where('role', 'SPV')->pluck('id')->toArray();
                        $salesInScope = \App\Models\User::whereIn('supervisor_id', $spvsInScope)->where('role', 'Sales')->pluck('id')->toArray();
                        // Also include Sales who might have explicitly matched HM's wilayah_id
                        $directSalesInScope = \App\Models\User::whereIn('id', $hmMemberIds)->where('role', 'Sales')->pluck('id')->toArray();
                        
                        $allSalesInScope = array_unique(array_merge($salesInScope, $directSalesInScope));
                        
                        $query->where(function($q) use ($allSalesInScope, $user) {
                            $q->whereIn('sales_id', $allSalesInScope);
                            if ($user->wilayah_id) {
                                // Also include any prospects explicitly assigned to HM's wilayah
                                $q->orWhere('wilayah_id', $user->wilayah_id);
                            }
                        });
                    }
                } else {
                    if ($user->wilayah_id) {
                        $query->where('wilayah_id', $user->wilayah_id);
                    }
                }
            }
        }

        $prospectsRaw = $query->orderBy('updated_at', 'desc')->get();

        return $prospectsRaw->map(function ($p) {
            return [
                'id' => $p->id,
                'name' => $p->name,
                'type' => $p->type,
                'sekolah_name' => $p->type === 'Sekolah' ? ($p->sekolah ? $p->sekolah->nama : '-') : ($p->type === 'Corporate' ? ($p->perusahaan ? $p->perusahaan->nama : '-') : '-'),
                'sales_name' => $p->sales ? $p->sales->name : '-',
                'category' => $p->category ?? '-',
                'pic' => $p->pic ?? '-',
                'pic_phone' => $p->pic_phone ?? '-',
                'whatsapp' => $p->whatsapp ?? '-',
                'status' => $p->status,
                'stage_number' => $p->stage_number,
                'takeover_sales' => $p->sales ? $p->sales->name : null,
                'takeover_cs' => $p->cs ? $p->cs->name : null,
                'active_takeover' => $p->activeHandlerLabel(),
                'owner' => $p->owner ? $p->owner->name : 'Sistem',
                'last_activity' => $p->updated_at->diffForHumans(),
                'potential' => $p->potential ?? '-',
                'source' => $p->source ?? '-',
                'ai_training' => $p->ai_training ?? '-',
                'notes' => $p->notes ?? '',
                'lost_reason' => $p->lost_reason ?? null,
                'lost_note' => $p->lost_note ?? null,
                'created_at' => $p->created_at ? $p->created_at->format('d M Y') : '-',
                'takeover_time' => $p->updated_at ? $p->updated_at->format('d M Y, H:i') : '-',
                'last_contact' => $p->followUps->first() ? \Carbon\Carbon::parse($p->followUps->first()->tanggal)->format('d M Y, H:i') : '-',
                'next_follow_up' => $p->followUps->first() && $p->followUps->first()->next_follow_up ? \Carbon\Carbon::parse($p->followUps->first()->next_follow_up)->format('d M Y, H:i') : '-',
                'next_follow_up_date' => $p->followUps->first() && $p->followUps->first()->next_follow_up ? \Carbon\Carbon::parse($p->followUps->first()->next_follow_up)->toDateString() : null,
                'timeline' => $p->timelines->map(function ($t) {
                    return [
                        'time' => $t->time ? $t->time->format('d M, H:i') : '-',
                        'title' => $t->title,
                        'notes' => $t->notes,
                        'status' => $t->status_after,
                        'user' => $t->user ? $t->user->name : 'Sistem',
                        'role' => $t->user ? $t->user->role : 'Admin',
                    ];
                })->toArray(),
            ];
        })->toArray();
    }

    public function prospekIndex(Request $request): View
    {
        $prospects = $this->getDbProspects($request);
        $sekolahs = \App\Models\Sekolah::where('status', 'Aktif')->get();
        $perusahaans = \App\Models\Perusahaan::where('status', 'Aktif')->get();
        $statuses = \App\Models\Prospek::ACTIVE_STAGES;

        $user = auth()->user();
        $isHm = $user && strtolower($user->role) === 'hm';
        $spvs = [];
        $salesList = [];

        if ($isHm) {
            $hmMemberIds = $user->hmMemberIds();
            $spvs = \App\Models\User::where('role', 'SPV')->whereIn('id', $hmMemberIds)->get();
            
            if ($request->spv_id) {
                $salesList = \App\Models\User::where('role', 'Sales')
                    ->where('supervisor_id', $request->spv_id)
                    ->get();
            } else {
                $spvIds = $spvs->pluck('id')->toArray();
                $salesList = \App\Models\User::where('role', 'Sales')
                    ->where(function($q) use ($spvIds, $hmMemberIds) {
                        $q->whereIn('supervisor_id', $spvIds)
                          ->orWhereIn('id', $hmMemberIds);
                    })->get();
            }
        }

        return view('prospek.index', compact('prospects', 'sekolahs', 'perusahaans', 'statuses', 'isHm', 'spvs', 'salesList'));
    }

    public function prospekStore(Request $request)
    {
        $request->validate([
            'name'          => 'nullable|string|max:255',
            'type'          => 'required|string',
            'status'        => 'required|string',
            'pic'           => 'required|string',
            'whatsapp'      => 'required|string',
            'prodi_id'      => 'nullable|exists:prodis,id',
            'sekolah_id'    => 'nullable|exists:sekolahs,id',
            'perusahaan_id' => 'nullable|exists:perusahaans,id',
            'sales_id'      => 'nullable|exists:users,id',
        ]);

        DB::transaction(function () use ($request) {
            $ownerId = auth()->id() ?? 1;
            $user = \App\Models\User::find($ownerId);
            
            $salesId = $request->sales_id;
            if (!$salesId && $user && $user->role === 'Sales') {
                $salesId = $user->id;
            }

            $csId = $request->cs_id ?? ($user && $user->role === 'CS' ? $user->id : null);
            $wilayahId = $user?->wilayah_id;

            if ($salesId) {
                $sales = \App\Models\User::find($salesId);
                if ($sales && $sales->wilayah_id) {
                    $wilayahId = $sales->wilayah_id;
                    $csId = null;
                }
            }

            // Resolve name
            $name = trim($request->name ?: '');
            if ($request->type === 'Sekolah' && !empty($request->sekolah_id)) {
                $sek = \App\Models\Sekolah::find($request->sekolah_id);
                if ($sek) $name = $sek->nama;
            } elseif ($request->type === 'Corporate' && !empty($request->perusahaan_id)) {
                $per = \App\Models\Perusahaan::find($request->perusahaan_id);
                if ($per) $name = $per->nama;
            }
            if (empty($name)) {
                $name = $request->pic;
            }

            $prodi = $request->prodi_id ? \App\Models\Prodi::find($request->prodi_id) : null;
            $stageNumber = \App\Models\Prospek::STAGES[$request->status] ?? 1;

            $prospek = Prospek::create([
                'name'               => $name,
                'type'               => $request->type,
                'status'             => $request->status,
                'stage_number'       => $stageNumber,
                'pic'                => $request->pic,
                'pic_phone'          => $request->pic_phone ?? null,
                'whatsapp'           => $request->whatsapp,
                'sales_id'           => $salesId,
                'cs_id'              => $csId,
                'wilayah_id'         => $wilayahId,
                'prodi_id'           => $prodi?->id,
                'category'           => $request->category ?? ($prodi?->nama ?? '-'),
                'source'             => $request->source ?? 'Inbound Direct',
                'kelas'              => $request->kelas ?? 'Reguler',
                'notes'              => $request->notes,
                'owner_id'           => $ownerId,
                'sekolah_id'         => $request->type === 'Sekolah' ? $request->sekolah_id : null,
                'perusahaan_id'      => $request->type === 'Corporate' ? $request->perusahaan_id : null,
            ]);

            $assignLabel = $salesId ? (\App\Models\User::find($salesId)?->name . ' (Sales)') : 'Mandiri/Sistem';

            ProspekTimeline::create([
                'prospek_id'   => $prospek->id,
                'user_id'      => $ownerId,
                'title'        => 'Prospek Dibuat',
                'notes'        => "Prospek baru dibuat oleh {$user?->name} dan ditugaskan ke {$assignLabel}",
                'status_after' => $prospek->status,
                'time'         => now(),
            ]);
        });

        $userRole = auth()->user()?->role;
        if ($userRole === 'SPV') {
            return redirect()->route('spv.prospek.index')->with('success', 'Prospek baru berhasil ditambahkan dan ditugaskan ke tim Sales!');
        } elseif ($userRole === 'Sales') {
            return redirect()->route('sales.prospek.index')->with('success', 'Prospek baru berhasil ditambahkan!');
        }

        return redirect()->back()->with('success', 'Prospek baru berhasil ditambahkan!');
    }

    /**
     * Detail Prospek.
     */
    public function prospekShow(int $id): View
    {
        $prospectRaw = Prospek::with(['sales', 'cs', 'owner', 'followUps' => function($q) {
            $q->orderBy('tanggal', 'desc');
        }, 'timelines' => function($q) {
            $q->orderBy('time', 'desc')->with('user');
        }])->findOrFail($id);

        \Illuminate\Support\Facades\Gate::authorize('view', $prospectRaw);

        $activeTakeover = [];
        if ($prospectRaw->sales) $activeTakeover[] = 'Sales';
        if ($prospectRaw->cs) $activeTakeover[] = 'CS';

        $prospect = [
            'id' => $prospectRaw->id,
            'name' => $prospectRaw->name,
            'type' => $prospectRaw->type,
            'category' => $prospectRaw->category ?? '-',
            'pic' => $prospectRaw->pic ?? '-',
            'pic_phone' => $prospectRaw->pic_phone ?? '-',
            'whatsapp' => $prospectRaw->whatsapp ?? '-',
            'status' => $prospectRaw->status,
            'stage_number' => $prospectRaw->stage_number,
            'takeover_sales' => $prospectRaw->sales ? $prospectRaw->sales->name : null,
            'takeover_cs' => $prospectRaw->cs ? $prospectRaw->cs->name : null,
            'active_takeover' => $prospectRaw->activeHandlerLabel(),
            'owner' => $prospectRaw->owner ? $prospectRaw->owner->name : 'Sistem',
            'last_activity' => $prospectRaw->updated_at->diffForHumans(),
            'source' => $prospectRaw->source ?? '-',
            'potential' => $prospectRaw->potential ?? '-',
            'ai_training' => $prospectRaw->ai_training ?? '-',
            'notes' => $prospectRaw->notes ?? '',
            'lost_reason' => $prospectRaw->lost_reason ?? null,
            'lost_note' => $prospectRaw->lost_note ?? null,
            'created_at' => $prospectRaw->created_at ? $prospectRaw->created_at->format('d M Y') : '-',
            'takeover_time' => $prospectRaw->updated_at ? $prospectRaw->updated_at->format('d M Y, H:i') : '-',
            'last_contact' => $prospectRaw->followUps->first() ? \Carbon\Carbon::parse($prospectRaw->followUps->first()->tanggal)->format('d M Y, H:i') : '-',
            'next_follow_up' => $prospectRaw->followUps->first() && $prospectRaw->followUps->first()->next_follow_up ? \Carbon\Carbon::parse($prospectRaw->followUps->first()->next_follow_up)->format('d M Y, H:i') : '-',
            'next_follow_up_date' => $prospectRaw->followUps->first() && $prospectRaw->followUps->first()->next_follow_up ? \Carbon\Carbon::parse($prospectRaw->followUps->first()->next_follow_up)->toDateString() : null,
            'timeline' => $prospectRaw->timelines->map(function ($t) {
                return [
                    'time' => $t->time ? $t->time->format('d M, H:i') : '-',
                    'title' => $t->title,
                    'notes' => $t->notes,
                    'status' => $t->status_after,
                    'user' => $t->user ? $t->user->name : 'Sistem',
                    'role' => $t->user ? $t->user->role : 'Admin',
                ];
            })->toArray(),
        ];

        $allStages = array_map(function ($stageName) {
            return ['name' => $stageName, 'number' => \App\Models\Prospek::STAGES[$stageName]];
        }, \App\Models\Prospek::ACTIVE_STAGES);

        // Retrieve available Sales team for re-allocation
        $user = auth()->user();
        if ($user && strtolower($user->role) === 'spv') {
            $salesTeam = $user->teamSales()->get();
        } else {
            $salesTeam = \App\Models\User::where('role', 'Sales')->where('status', 'aktif')->get();
        }

        return view('prospek.show', compact('prospect', 'allStages', 'salesTeam', 'prospectRaw'));
    }

    public function prospekUpdate(Request $request, int $id)
    {
        $prospek = Prospek::findOrFail($id);
        \Illuminate\Support\Facades\Gate::authorize('update', $prospek);
        
        $request->validate([
            'status' => 'required|string',
        ]);

        $user = auth()->user();
        $oldStatus = $prospek->status;
        $newStatus = strtoupper($request->status);

        // Status LUNAS cannot be changed to anything else
        if ($oldStatus === 'LUNAS' && $newStatus !== 'LUNAS') {
            return redirect()->back()->withErrors(['status' => 'Prospek yang sudah Lunas tidak dapat diubah statusnya.']);
        }

        if ($user && strtolower($user->role) === 'sales' && $newStatus === 'LUNAS') {
            return redirect()->back()->withErrors(['status' => 'Sales tidak dapat mengubah status menjadi Lunas.']);
        }

        if ($user && strtolower($user->role) === 'cs' && $newStatus !== 'LUNAS' && $oldStatus !== $newStatus) {
            return redirect()->back()->withErrors(['status' => 'CS hanya dapat mengubah status menjadi Lunas.']);
        }

        $prospek->status = $newStatus;

        if (isset(\App\Models\Prospek::STAGES[$newStatus])) {
            $prospek->stage_number = \App\Models\Prospek::STAGES[$newStatus];
        }

        $prospek->save();

        if ($oldStatus !== $prospek->status) {
            ProspekTimeline::create([
                'prospek_id' => $prospek->id,
                'user_id' => auth()->id() ?? 1,
                'title' => 'Status Diperbarui',
                'notes' => 'Status prospek diubah dari ' . $oldStatus . ' menjadi ' . $prospek->status,
                'status_before' => $oldStatus,
                'status_after' => $prospek->status,
                'time' => now(),
            ]);
        }

        return redirect()->back()->with('success', 'Status prospek berhasil diperbarui!');
    }

    public function prospekDestroy(int $id)
    {
        $prospek = Prospek::findOrFail($id);
        \Illuminate\Support\Facades\Gate::authorize('delete', $prospek);
        $prospek->delete();
        
        return redirect()->route('prospek.index')->with('success', 'Prospek berhasil dihapus!');
    }

    public function prospekTakeover(int $id)
    {
        $prospek = Prospek::findOrFail($id);
        \Illuminate\Support\Facades\Gate::authorize('takeover', $prospek);

        $cs = null;
        if (strtolower(auth()->user()->role) === 'cs') {
            $cs = auth()->user();
        } else {
            // Pick any active CS
            $cs = \App\Models\User::where('role', 'CS')->first();
        }

        if (!$cs) {
            return redirect()->back()->withErrors(['cs_id' => 'Tidak ada user CS yang tersedia untuk menerima prospek.']);
        }

        $prospek->update([
            'cs_id' => $cs->id,
        ]);

        \App\Models\ProspekTimeline::create([
            'prospek_id'   => $prospek->id,
            'user_id'      => auth()->id(),
            'title'        => 'Prospek Diserahkan ke CS',
            'notes'        => 'Prospek diserahkan ke CS: ' . $cs->name,
            'status_after'  => $prospek->status,
            'time'          => now(),
        ]);

        return redirect()->back()->with('success', 'Prospek berhasil diserahkan ke CS ' . $cs->name);
    }

    /**
     * Re-alokasi Prospek dari satu Sales ke Sales lain dalam tim SPV.
     */
    public function prospekRealokasi(Request $request, int $id)
    {
        $prospek = Prospek::findOrFail($id);
        \Illuminate\Support\Facades\Gate::authorize('reallocate', $prospek);

        $request->validate([
            'sales_id' => 'required|exists:users,id',
            'alasan'   => 'nullable|string|max:500',
        ]);

        $user = auth()->user();
        $newSales = \App\Models\User::findOrFail($request->sales_id);

        // SPV can only re-allocate to Sales within their own team
        if (strtolower($user->role) === 'spv' && !$user->isSupervisorOf($newSales->id)) {
            return redirect()->back()->withErrors(['sales_id' => 'Sales yang dipilih bukan anggota tim Anda.']);
        }

        $oldSales = $prospek->sales;
        $oldSalesName = $oldSales ? $oldSales->name : 'Belum Ditugaskan';

        DB::transaction(function () use ($prospek, $newSales, $oldSalesName, $user, $request) {
            $prospek->update([
                'sales_id' => $newSales->id,
                'active_follow_up_count' => 0,
            ]);

            \App\Models\ProspekTimeline::create([
                'prospek_id'    => $prospek->id,
                'user_id'       => $user->id,
                'title'         => 'Re-alokasi Prospek: ' . $oldSalesName . ' → ' . $newSales->name,
                'notes'         => 'Re-alokasi oleh ' . $user->name . ($request->alasan ? '. Alasan: ' . $request->alasan : ''),
                'status_before' => $prospek->status,
                'status_after'  => $prospek->status,
                'time'          => now(),
            ]);
        });

        return redirect()->back()->with('success', 'Prospek berhasil dialokasikan ke ' . $newSales->name);
    }

    public function pipelineUpdateStatus(Request $request)
    {
        $prospek = Prospek::findOrFail($request->prospek_id);
        \Illuminate\Support\Facades\Gate::authorize('updateStatus', $prospek);
        
        $user = auth()->user();
        $oldStatus = $prospek->status;
        $newStatus = strtoupper($request->status);

        // Status LUNAS cannot be changed to anything else
        if ($oldStatus === 'LUNAS' && $newStatus !== 'LUNAS') {
            return response()->json(['success' => false, 'message' => 'Prospek yang sudah Lunas tidak dapat diubah statusnya.'], 403);
        }

        if ($user && strtolower($user->role) === 'sales' && $newStatus === 'LUNAS') {
            return response()->json(['success' => false, 'message' => 'Sales tidak dapat mengubah status menjadi Lunas.'], 403);
        }

        if ($user && strtolower($user->role) === 'cs' && $newStatus !== 'LUNAS' && $oldStatus !== $newStatus) {
            return response()->json(['success' => false, 'message' => 'CS hanya dapat mengubah status menjadi Lunas.'], 403);
        }

        $prospek->status = $newStatus;
        
        if (isset(\App\Models\Prospek::STAGES[$newStatus])) {
            $prospek->stage_number = \App\Models\Prospek::STAGES[$newStatus];
        }
        
        $prospek->save();

        if ($oldStatus !== $prospek->status) {
            ProspekTimeline::create([
                'prospek_id' => $prospek->id,
                'user_id' => auth()->id() ?? 1,
                'title' => 'Status Diperbarui',
                'notes' => 'Status prospek diubah dari ' . $oldStatus . ' menjadi ' . $prospek->status,
                'status_before' => $oldStatus,
                'status_after' => $prospek->status,
                'time' => now(),
            ]);
        }
        
        return response()->json(['success' => true]);
    }


    /**
     * Halaman Kunjungan.
     */
    public function kunjunganIndex(): View
    {
        $user = auth()->user();
        $query = \App\Models\Kunjungan::with('sales')
            ->orderBy('tanggal', 'desc');

        if ($user) {
            $role = strtolower($user->role);
            if ($role === 'sales') {
                $query->where('sales_id', $user->id);
            } elseif ($role === 'spv') {
                $salesIds = $user->teamMemberIds();
                $query->whereIn('sales_id', $salesIds);
            }
        }

        $visits = $query->get()->map(fn ($k) => $this->formatKunjunganForBlade($k))->toArray();

        return view('kunjungan.index', compact('visits'));
    }

    public function kunjunganStore(Request $request)
    {
        $user = auth()->user();

        // Resolve nama_institusi from prospek or sekolah if not directly filled
        if (!$request->filled('nama_institusi')) {
            if ($request->filled('prospek_id')) {
                $prospek = \App\Models\Prospek::find($request->prospek_id);
                if ($prospek) {
                    $request->merge(['nama_institusi' => $prospek->name]);
                }
            } elseif ($request->filled('sekolah_id')) {
                $sek = \App\Models\Sekolah::find($request->sekolah_id);
                if ($sek) {
                    $request->merge(['nama_institusi' => $sek->nama]);
                }
            } elseif ($request->filled('perusahaan_id')) {
                $per = \App\Models\Perusahaan::find($request->perusahaan_id);
                if ($per) {
                    $request->merge(['nama_institusi' => $per->nama]);
                }
            }
        }

        $request->validate([
            'nama_institusi' => 'required|string|max:255',
            'jenis'          => 'required|in:Sekolah,Perusahaan',
            'tanggal'        => 'required|date',
            'pic_name'       => 'required|string|max:255',
            'pic_whatsapp'   => 'required|string|max:20',
            'catatan'        => 'nullable|string|max:2000',
            'foto'           => 'nullable|image|mimes:jpeg,jpg,png,webp|max:5120',
        ]);

        $fotoPath = null;
        if ($request->hasFile('foto') && $request->file('foto')->isValid()) {
            $fotoPath = $request->file('foto')->store('kunjungan', 'public');
        }

        $tujuanId = 0;
        if ($request->jenis === 'Sekolah' && $request->filled('sekolah_id')) {
            $tujuanId = $request->sekolah_id;
        } elseif ($request->jenis === 'Perusahaan' && $request->filled('perusahaan_id')) {
            $tujuanId = $request->perusahaan_id;
        }

        $salesId = $request->input('sales_id');
        if (empty($salesId) || $user->role === 'Sales') {
            $salesId = $user->id;
        }

        $activeTa = \App\Models\TahunAkademik::getAktif();

        $kunjungan = \App\Models\Kunjungan::create([
            'nomor'                 => 'KNJ-' . ($salesId ?? 1) . '-' . now()->format('YmdHis'),
            'tanggal'               => $request->tanggal,
            'waktu'                 => $request->input('waktu', now()->format('H:i:s')),
            'sales_id'              => $salesId,
            'prodi_id'              => $request->input('prodi_id') ?? \App\Models\Prodi::first()?->id ?? 1,
            'jenis'                 => $request->jenis,
            'tujuan_id'             => $tujuanId,
            'tujuan_kunjungan'      => $request->nama_institusi,
            'hasil'                 => 'Kunjungan ' . $request->jenis . ' — ' . $request->nama_institusi,
            'catatan'               => $request->catatan,
            'status'                => 'Selesai',
            'status_verifikasi'     => 'Valid',
            'is_verified'           => true,
            'academic_year_id'      => $activeTa?->id,
            'nama_institusi'        => $request->nama_institusi,
            'alamat'                => $request->alamat,
            'lokasi_penugasan'      => $request->lokasi_penugasan,
            'pic_name'              => $request->pic_name,
            'pic_whatsapp'          => $request->pic_whatsapp,
            'foto_path'             => $fotoPath,
            'lat'                   => $request->input('lat'),
            'lng'                   => $request->input('lng'),
            // School-specific
            'potensi_mahasiswa'       => $request->input('potensi_mahasiswa') ?? $request->input('potensi_beasiswa'),
            'detail_potensi_mahasiswa'=> $request->input('detail_potensi_mahasiswa') ?? $request->input('detail_beasiswa'),
            'kesediaan_training_ai'   => $request->boolean('kesediaan_training_ai'),
            // Corporate-specific
            'bidang_usaha'          => $request->bidang_usaha,
            'potensi_s1'            => $request->potensi_s1,
            'potensi_s2'            => $request->potensi_s2,
            'potensi_csr'           => $request->potensi_csr,
        ]);

        if ($request->filled('prospek_id')) {
            $prospek = \App\Models\Prospek::find($request->prospek_id);
            if ($prospek) {
                if ($prospek->status === 'BARU') {
                    $prospek->status = 'KONTAK';
                    $prospek->stage_number = 2;
                    $prospek->save();
                }
                \App\Models\ProspekTimeline::create([
                    'prospek_id'   => $prospek->id,
                    'user_id'      => $salesId,
                    'title'        => 'Laporan Kunjungan Selesai',
                    'notes'        => 'Kunjungan langsung telah dilaporkan (' . $kunjungan->nomor . ')',
                    'status_after' => $prospek->status,
                    'time'         => now(),
                ]);
            }
        }

        return redirect()->back()->with('success', 'Laporan kunjungan berhasil disimpan!');
    }

    /**
     * Halaman Follow Up.
     */
    public function followUpIndex(): View
    {
        $allProspects = collect($this->getDbProspects(request()));
        $today = now()->toDateString();
        
        $prospects = [
            'today' => [],
            'upcoming' => [],
            'overdue' => [],
            'done' => [],
        ];

        foreach ($allProspects as $p) {
            if (in_array($p['status'], ['LUNAS', 'DINGIN'])) {
                $prospects['done'][] = $p;
            } else if ($p['next_follow_up_date']) {
                if ($p['next_follow_up_date'] === $today) {
                    $prospects['today'][] = $p;
                } else if ($p['next_follow_up_date'] > $today) {
                    $prospects['upcoming'][] = $p;
                } else {
                    $prospects['overdue'][] = $p;
                }
            } else {
                $prospects['today'][] = $p;
            }
        }

        return view('follow-up.index', compact('prospects'));
    }

    public function followUpStore(Request $request)
    {
        $request->validate([
            'prospek_id' => 'required|exists:prospeks,id',
            'metode' => 'nullable|string',
            'hasil' => 'required|string',
            'catatan' => 'required|string',
            'status' => 'required|string',
            'next_follow_up' => 'nullable|date',
        ]);

        $prospek = Prospek::findOrFail($request->prospek_id);
        \Illuminate\Support\Facades\Gate::authorize('followUp', $prospek);
        
        // Save follow up log
        $catatan = '[Status: ' . $request->hasil . '] ' . $request->catatan;
        \App\Models\FollowUp::create([
            'prospek_id' => $prospek->id,
            'user_id' => auth()->id() ?? 1,
            'metode' => $request->metode ?? 'WhatsApp',
            'hasil' => $request->hasil,
            'tanggal' => now(),
            'catatan' => $catatan,
            'next_follow_up' => $request->next_follow_up,
        ]);

        // Update status if changed
        $oldStatus = $prospek->status;
        if ($oldStatus !== $request->status) {
            $prospek->status = $request->status;
            
            if (isset(\App\Models\Prospek::STAGES[$request->status])) {
                $prospek->stage_number = \App\Models\Prospek::STAGES[$request->status];
            }
            
            $prospek->save();

            ProspekTimeline::create([
                'prospek_id' => $prospek->id,
                'user_id' => auth()->id() ?? 1,
                'title' => 'Follow Up & Update Status',
                'notes' => 'Status diubah dari ' . $oldStatus . ' menjadi ' . $prospek->status,
                'status_before' => $oldStatus,
                'status_after' => $prospek->status,
                'time' => now(),
            ]);
        }

        return redirect()->back()->with('success', 'Follow-up berhasil disimpan!');
    }

    public function transaksiStore(Request $request, int $id)
    {
        $prospek = Prospek::findOrFail($id);
        $this->authorize('transaction', $prospek);

        $request->validate([
            'jenis' => 'required|in:Beli Formulir,Pembayaran Termin 1',
            'nominal' => 'required|numeric|min:0',
            'tanggal' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        \App\Models\Transaksi::create([
            'prospek_id' => $prospek->id,
            'user_id' => auth()->id(),
            'jenis' => $request->jenis,
            'nominal' => $request->nominal,
            'tanggal' => $request->tanggal,
            'notes' => $request->notes,
        ]);

        $oldStatus = $prospek->status;
        
        if (\App\Services\ProspekService::isClosingValid($prospek)) {
            $prospek->status = 'LUNAS';
            $prospek->stage_number = 7;
        } else if ($request->jenis === 'Pembayaran Termin 1') {
            $prospek->status = 'BERKAS';
            $prospek->stage_number = 6;
        } else if ($request->jenis === 'Beli Formulir' && $prospek->stage_number < 5) {
            $prospek->status = 'FORMULIR';
            $prospek->stage_number = 5;
        }
        
        $prospek->save();

        ProspekTimeline::create([
            'prospek_id' => $prospek->id,
            'user_id' => auth()->id(),
            'title' => 'Input Transaksi Manual: ' . $request->jenis,
            'notes' => 'Nominal: Rp ' . number_format($request->nominal, 0, ',', '.') . ($request->notes ? ' | Catatan: ' . $request->notes : ''),
            'status_before' => $oldStatus,
            'status_after' => $prospek->status,
            'time' => now(),
        ]);

        return redirect()->back()->with('success', 'Transaksi ' . $request->jenis . ' berhasil disimpan!');
    }

    /**
     * Halaman Pipeline Board.
     */
    public function pipelineIndex(): View
    {
        $prospects = $this->getDbProspects();
        $pipelineStages = \App\Models\Prospek::ACTIVE_STAGES;

        return view('pipeline.index', compact('prospects', 'pipelineStages'));
    }

    /**
     * Halaman Target & Performa (Data Akumulasi).
     */
    public function performaIndex(): View
    {
        $user = auth()->user();
        $targetService = app(\App\Services\SalesTargetService::class);

        if ($user && strtolower($user->role) === 'cs') {
            $salesUsers = \App\Models\User::where('id', $user->id)->get();
        } elseif ($user && strtolower($user->role) === 'spv') {
            $salesUsers = $user->teamSales()->get();
        } elseif ($user && strtolower($user->role) === 'sales') {
            $salesUsers = collect([$user]);
        } else {
            $salesUsers = \App\Models\User::where('role', 'Sales')->get();
        }

        $team = $salesUsers->map(function ($s) use ($targetService) {
            $stats = $targetService->getStats($s);
            $activeTarget = $targetService->getActiveTarget($s);

            // Use target_lunas (target_closing does not exist in DB)
            $targetAmount = $activeTarget ? (int)$activeTarget->target_lunas : 0;
            if ($targetAmount <= 0 && $activeTarget) {
                $targetAmount = (int)$activeTarget->target_kontak;
            }

            $achievement = $targetAmount > 0 ? min(100, round(($stats['realisasi_closing'] / $targetAmount) * 100)) : 0;

            return [
                'id'          => $s->id,
                'name'        => $s->name,
                'role'        => $s->role,
                'target'      => $targetAmount,
                'prospects'   => $stats['total_prospek'],
                'follow_up'   => $stats['follow_up'],
                'closing'     => $stats['realisasi_closing'],
                'lost'        => $stats['realisasi_closing'] - $stats['realisasi_closing'], // placeholder
                'LUNAS'       => $stats['realisasi_closing'],
                'DINGIN'      => $stats['DINGIN'] ?? 0,
                'achievement' => $achievement,
                'avatar'      => strtoupper(substr($s->name, 0, 2)),
                'status'      => $stats['realisasi_closing'] >= $targetAmount ? 'Target Achieved' : ($achievement >= 40 ? 'On Track' : 'Progres Berjalan')
            ];
        })->toArray();

        $totalTarget = count($team) > 0 ? array_sum(array_column($team, 'target')) : 0;
        $totalRealisasi = array_sum(array_column($team, 'closing'));

        $breakdown = [
            'Sekolah' => \App\Models\Prospek::where('type', 'Sekolah')->where('status', 'LUNAS')->count(),
            'Corporate' => \App\Models\Prospek::where('type', 'Corporate')->where('status', 'LUNAS')->count(),
            'Individu' => \App\Models\Prospek::where('type', 'Individu')->where('status', 'LUNAS')->count(),
        ];

        $summary = [
            'target'      => $totalTarget,
            'realisasi'   => $totalRealisasi,
            'achievement' => $totalTarget > 0 ? round(($totalRealisasi / $totalTarget) * 100) : 0,
            'sisa_target' => max(0, $totalTarget - $totalRealisasi),
            'breakdown' => $breakdown,
            'periode_label' => \Carbon\Carbon::now()->locale('id')->isoFormat('MMMM YYYY'),
        ];

        $targetAchievementService = app(\App\Services\TargetAchievementService::class);
        $period = request('periode', 'bulanan');
        $wilayahId = request('wilayah_id');
        $targetAchievementData = $targetAchievementService->getDashboardTargetData($user, $period, $wilayahId);

        return view('performa.index', compact('team', 'summary', 'targetAchievementData'));
    }

    /**
     * Halaman Laporan.
     */
    public function laporanIndex(): View
    {
        $user = auth()->user();
        $prospects = $this->getDbProspects();

        if ($user && strtolower($user->role) === 'spv') {
            $salesIds = $user->teamMemberIds();
        } elseif ($user && strtolower($user->role) === 'sales') {
            $salesIds = collect([$user->id]);
        } else {
            $salesIds = \App\Models\User::where('role', 'Sales')->pluck('id');
        }

        $totalProspek = Prospek::whereIn('sales_id', $salesIds)->count();
        $closing = Prospek::whereIn('sales_id', $salesIds)->where('status', 'LUNAS')->count();
        $active = Prospek::whereIn('sales_id', $salesIds)->whereNotIn('status', ['LUNAS', 'DINGIN'])->count();
        $lost = Prospek::whereIn('sales_id', $salesIds)->where('status', 'DINGIN')->count();

        $summary = [
            'total_prospek'   => $totalProspek,
            'active'          => $active,
            'closing'         => $closing,

            'lost'            => $lost,
            'LUNAS'           => $closing,
            'DINGIN'          => $lost,
            'conversion_rate' => $totalProspek > 0 ? round(($closing / $totalProspek) * 100, 1) : 0,
        ];

        return view('laporan.index', compact('prospects', 'summary'));
    }

    /**
     * Halaman Profil & Akun.
     */
    public function profilIndex(Request $request): View
    {
        $currentUser = self::getCurrentUser($request);
        return view('profil.index', compact('currentUser'));
    }

    /**
     * Update Logged in / Active User Profile.
     */
    public function profilUpdate(Request $request)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'phone'         => 'nullable|string|max:20',
            'avatar'        => 'nullable|image|mimes:jpeg,png,jpg,webp,gif|max:3072',
            'remove_avatar' => 'nullable',
        ]);

        if (auth()->check()) {
            $user = auth()->user();
            $dataToUpdate = [
                'name'  => $validated['name'],
                'phone' => $validated['phone'] ?? $user->phone,
            ];

            if ($request->filled('remove_avatar') && $request->remove_avatar == '1') {
                if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                    Storage::disk('public')->delete($user->avatar);
                }
                $dataToUpdate['avatar'] = null;
            } elseif ($request->hasFile('avatar')) {
                if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                    Storage::disk('public')->delete($user->avatar);
                }
                $path = $request->file('avatar')->store('avatars', 'public');
                $dataToUpdate['avatar'] = $path;
            }

            $user->update($dataToUpdate);
        } else {
            $role = strtolower($request->session()->get('user_role', 'sales'));
            $request->session()->put('custom_profile_' . $role, [
                'name'  => $validated['name'],
                'phone' => $validated['phone'],
            ]);
        }

        return back()->with('success', 'Profil dan foto berhasil diperbarui!');
    }

    /**
     * Update User Password.
     */
    public function profilPasswordUpdate(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password'         => 'required|confirmed',
        ]);

        if (auth()->check()) {
            $user = auth()->user();
            if (!\Illuminate\Support\Facades\Hash::check($request->current_password, $user->password)) {
                return back()->withErrors(['current_password' => 'Password saat ini tidak sesuai.']);
            }
            $user->update([
                'password' => \Illuminate\Support\Facades\Hash::make($request->password),
            ]);
        }

        return back()->with('success', 'Password berhasil diperbarui!');
    }

    /**
     * Halaman Pengaturan.
     */
    public function pengaturanIndex(): View
    {
        return view('profil.pengaturan');
    }
}
