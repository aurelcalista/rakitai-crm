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
                'admin'        => 'Super Administrator',
                'hm'           => 'Head Marketing',
                'spv'          => 'Supervisor Marketing',
                'cs'           => 'Customer Service',
                'sales'        => 'Sales Inbound',
                'eo'           => 'Event Organizer',
                'data analyst' => 'Data Analyst PMB',
                'data-analyst' => 'Data Analyst PMB',
            ];

            $roleNames = [
                'admin'        => 'Admin',
                'hm'           => 'Head Marketing',
                'spv'          => 'Supervisor',
                'cs'           => 'CS',
                'sales'        => 'Sales',
                'eo'           => 'EO',
                'data analyst' => 'Data Analyst',
                'data-analyst' => 'Data Analyst',
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
                } elseif (in_array($roleKey, ['data analyst', 'data-analyst'])) {
                    $wilayahNama = 'Seluruh Wilayah CRM (Akses Analitik Lintas Wilayah)';
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
                'avatar'          => $avatar ?: 'DA',
                'avatar_url'      => $user->avatar_url,
                'wilayah'         => $wilayahNama,
                'division'        => in_array($roleKey, ['data analyst', 'data-analyst'])
                    ? 'Unit Analisis Data & Intelijen PMB — Universitas Catur Insan Cendekia'
                    : ($roleKey === 'admin' 
                        ? 'Divisi Administrator & Pengelolaan Sistem — Universitas Catur Insan Cendekia' 
                        : 'Divisi Marketing & Admisi Mahasiswa Baru — Universitas Catur Insan Cendekia'),
                'dashboard_route' => in_array($roleKey, ['data analyst', 'data-analyst'])
                    ? 'dashboard.data-analyst'
                    : ('dashboard.' . ($roleKey === 'spv' ? 'spv' : ($roleKey === 'hm' ? 'hm' : ($roleKey === 'eo' ? 'eo' : $roleKey)))),
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
            'raw_date'  => $k->tanggal ? \Carbon\Carbon::parse($k->tanggal)->format('Y-m-d') : '',
            'time'      => $k->waktu ?? '-',
            'raw_time'  => $k->waktu ? \Carbon\Carbon::parse($k->waktu)->format('H:i') : '',
            'address'   => $k->alamat ?? '-',
            'potential' => $potential,
            'photo'     => $photoUrl,
            'notes'     => $k->catatan ?? $k->hasil ?? '-',
            'status'    => $k->status ?? 'Selesai',
            // Type-specific details
            'potensi_mahasiswa'     => $k->potensi_mahasiswa ?? $k->potensi_beasiswa ?? '-',
            'detail_potensi_mahasiswa' => $k->detail_potensi_mahasiswa ?? $k->detail_beasiswa ?? '-',
            'potensi_beasiswa'      => $k->potensi_beasiswa ?? '-',
            'detail_beasiswa'       => $k->detail_beasiswa ?? '-',
            'kesediaan_training_ai' => (bool) $k->kesediaan_training_ai,
            'bidang_usaha'          => $k->bidang_usaha ?? '-',
            'potensi_s1'            => $k->potensi_s1 ?? '-',
            'potensi_s2'            => $k->potensi_s2 ?? '-',
            'potensi_csr'           => $k->potensi_csr ?? '-',
            'dosen'                 => $k->dosen ? $k->dosen->name : ($k->dosen_pemateri ?? '-'),
            'dosen_id'              => $k->dosen_id,
            'dosen_pemateri'        => $k->dosen_pemateri,
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
        $pipelineStages = collect(\App\Models\Prospek::getActiveStages())->map(function($key) use ($prospectsData) {
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

        $user = auth()->user();
        $isCs = $user && strtolower($user->role) === 'cs';

        // 1. Data Pembayaran Menunggu Verifikasi CS (seluruh jenis transaksi pending dapat diverifikasi oleh semua CS)
        $pendingPaymentsQuery = \App\Models\Transaksi::with(['prospek.cs', 'prospek', 'user', 'prospek.sales'])
            ->where('payment_status', \App\Models\Transaksi::STATUS_PENDING);

        $pendingPaymentsQuery->latest('tanggal');

        $pendingPaymentCount = (clone $pendingPaymentsQuery)->distinct('prospek_id')->count('prospek_id');
        $pendingPaymentSum   = (clone $pendingPaymentsQuery)->sum('nominal');
        $pendingPayments     = $pendingPaymentsQuery->limit(5)->get();

        // 2. Data Prospek Baru Serah Terima (Handover) & Pemantauan SLA 2 Jam
        $handoverProspects = \App\Models\Prospek::with(['sales', 'sekolah', 'perusahaan'])
            ->whereIn('status', ['FORMULIR', '05 FORMULIR'])
            ->where(function ($q) use ($user) {
                if ($user) {
                    $q->where('cs_id', $user->id)
                      ->orWhereNull('cs_id');
                }
            })
            ->whereNotNull('handover_at')
            ->latest('handover_at')
            ->limit(5)
            ->get()
            ->map(function ($p) {
                $hasFollowUp = \App\Models\FollowUp::where('prospek_id', $p->id)
                    ->where('created_at', '>=', $p->handover_at)
                    ->exists();
                $elapsedMinutes = $p->handover_at ? (int) $p->handover_at->diffInMinutes(now()) : 0;
                $isOverdueSla = !$hasFollowUp && $elapsedMinutes >= 120;
                $remainingMinutes = max(0, 120 - $elapsedMinutes);

                return [
                    'id'                => $p->id,
                    'name'              => $p->name,
                    'no_registrasi'     => $p->no_registrasi,
                    'gelombang'         => $p->gelombang,
                    'type'              => $p->type,
                    'whatsapp'          => $p->whatsapp,
                    'sales_name'        => $p->sales?->name ?? 'Sales Lapangan',
                    'handover_time'     => $p->handover_at?->diffForHumans() ?? '-',
                    'has_follow_up'     => $hasFollowUp,
                    'is_overdue'        => $isOverdueSla,
                    'remaining_minutes' => $remainingMinutes,
                    'elapsed_minutes'   => $elapsedMinutes,
                ];
            });

        $slaOverdueCount = $handoverProspects->where('is_overdue', true)->count();

        $stats = [
            'total_prospek'         => count($prospects),
            'takeover_cs'           => count(array_filter($prospects, fn($p) => str_contains($p['active_takeover'] ?? '', 'CS'))),
            'follow_up_today'       => $followUpTodayCount,
            'follow_up_pending'     => $followUpPendingCount,
            'closing'               => $lunasCount,
            'lost'                  => $dinginCount,
            'LUNAS'                 => $lunasCount,
            'DINGIN'                => $dinginCount,
            'pending_payment_count' => $pendingPaymentCount,
            'pending_payment_sum'   => $pendingPaymentSum,
            'handover_count'        => $handoverProspects->count(),
            'sla_overdue_count'     => $slaOverdueCount,
        ];

        $targetAchievementService = app(\App\Services\TargetAchievementService::class);
        $period = request('periode', 'bulanan');
        $wilayahId = request('wilayah_id');
        $targetAchievementData = $targetAchievementService->getDashboardTargetData(auth()->user(), $period, $wilayahId);

        return view('cs.dashboard', compact(
            'stats',
            'followUpsToday',
            'targetAchievementData',
            'pendingPayments',
            'pendingPaymentCount',
            'pendingPaymentSum',
            'handoverProspects',
            'slaOverdueCount'
        ));
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
        $salesId = $request->get('sales_id');
        $wilayahId = $request->get('wilayah_id');

        // Active Tahun Akademik
        $activeAyId = \App\Services\AkademikService::getAktifId();
        $targetService = app(\App\Services\TargetAchievementService::class);

        // Subquery or ids for Sales/Tim filtering
        $salesIdsScope = null;
        if ($salesId) {
            $salesIdsScope = [(int)$salesId];
        } elseif ($spvId) {
            $salesIdsScope = \App\Models\User::where('supervisor_id', $spvId)
                ->whereIn('role', ['Sales', 'CS'])
                ->pluck('id')->toArray();
        } elseif ($wilayahId) {
            $salesIdsScope = \App\Models\User::where('wilayah_id', $wilayahId)
                ->whereIn('role', ['Sales', 'CS'])
                ->pluck('id')->toArray();
        }

        // Global stats (all prospects or filtered by scope & wilayah)
        $prospekQuery = Prospek::query()
            ->when($salesIdsScope !== null, fn($q) => $q->whereIn('sales_id', $salesIdsScope))
            ->when($wilayahId, fn($q) => $q->where('wilayah_id', $wilayahId));

        $totalProspek  = (clone $prospekQuery)->count();
        $closing       = (clone $prospekQuery)->whereIn('status', ['CLOSING', 'LUNAS'])->count();
        $activeProspek = (clone $prospekQuery)->whereNotIn('status', ['LUNAS', 'DINGIN', 'CANCEL'])->count();
        $lost          = (clone $prospekQuery)->where('status', 'DINGIN')->count();
        $cancelCount   = (clone $prospekQuery)->where('status', 'CANCEL')->count();

        // Global target rollup (sum of all active Sales targets)
        $totalTargetLunas = \App\Models\Target::where('status', 'Aktif')
            ->when($activeAyId, fn($q) => $q->where('academic_year_id', $activeAyId))
            ->when($salesIdsScope !== null, fn($q) => $q->whereIn('sales_id', $salesIdsScope))
            ->when($wilayahId, fn($q) => $q->whereHas('user', fn($uq) => $uq->where('wilayah_id', $wilayahId)))
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
            'total_sales'     => \App\Models\User::where('role', 'Sales')->when($spvId, fn($q) => $q->where('supervisor_id', $spvId))->when($salesId, fn($q) => $q->where('id', $salesId))->count(),
            'total_cs'        => \App\Models\User::where('role', 'CS')->when($spvId, fn($q) => $q->where('supervisor_id', $spvId))->when($salesId, fn($q) => $q->where('id', $salesId))->count(),
            'target_global'   => $totalTargetLunas,
            'sisa_target'     => $sisaTarget,
            'pct_lunas'       => $pctLunas,
            'color_status'    => $colorStatus,
        ];

        $salesUsers = \App\Models\User::whereIn('role', ['Sales', 'CS'])
            ->when($spvId, fn($q) => $q->where('supervisor_id', $spvId))
            ->when($salesId, fn($q) => $q->where('id', $salesId))
            ->when($wilayahId, fn($q) => $q->where('wilayah_id', $wilayahId))
            ->get();

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

        $activeStagesList = \App\Models\Prospek::getActiveStages();
        $stagesCount = array_fill_keys($activeStagesList, 0);
        $prospectsData = Prospek::select('status')
            ->when($activeAyId, fn($q) => $q->where('academic_year_id', $activeAyId))
            ->when($salesIdsScope !== null, fn($q) => $q->whereIn('sales_id', $salesIdsScope))
            ->when($wilayahId, fn($q) => $q->where('wilayah_id', $wilayahId))
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
            ->when($wilayahId, fn($q) => $q->where('wilayah_id', $wilayahId))
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
        $salesList = \App\Models\User::whereIn('role', ['Sales', 'CS'])->where('status', 'Aktif')->when($spvId, fn($q) => $q->where('supervisor_id', $spvId))->get();
        $wilayahList = \App\Models\Wilayah::orderBy('nama')->get();

        return view('hm.dashboard', compact('stats', 'team', 'pipelineStages', 'targetAchievementData', 'monthlyTrend', 'currentYear', 'spvs', 'salesList', 'wilayahList'));
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

    public function updateTerimaKasihSettings(Request $request)
    {
        if (strtolower(auth()->user()->role) !== 'admin') {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        \App\Models\MasterData::updateOrCreate(
            ['type' => 'setting', 'kode' => 'TK_TITLE'],
            ['nama' => $request->title, 'status' => 'Aktif']
        );

        \App\Models\MasterData::updateOrCreate(
            ['type' => 'setting', 'kode' => 'TK_SUBTITLE'],
            ['nama' => 'Subtitle', 'deskripsi' => $request->subtitle ?? '', 'status' => 'Aktif']
        );

        if ($request->has('delete_image') && $request->delete_image == '1') {
            $existingImage = \App\Models\MasterData::where('type', 'setting')->where('kode', 'TK_IMAGE')->first();
            if ($existingImage && $existingImage->nama) {
                if (\Illuminate\Support\Facades\Storage::disk('public')->exists($existingImage->nama)) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($existingImage->nama);
                }
                $existingImage->delete();
            }
        } elseif ($request->hasFile('image')) {
            $path = $request->file('image')->store('settings', 'public');
            \App\Models\MasterData::updateOrCreate(
                ['type' => 'setting', 'kode' => 'TK_IMAGE'],
                ['nama' => $path, 'status' => 'Aktif']
            );
        }

        return redirect()->back()->with('success', 'Pengaturan Halaman Terima Kasih berhasil diperbarui!');
    }

    public function updateTerimaKasihCorporateSettings(Request $request)
    {
        if (strtolower(auth()->user()->role) !== 'admin') {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        \App\Models\MasterData::updateOrCreate(
            ['type' => 'setting', 'kode' => 'TK_CORP_TITLE'],
            ['nama' => $request->title, 'status' => 'Aktif']
        );

        \App\Models\MasterData::updateOrCreate(
            ['type' => 'setting', 'kode' => 'TK_CORP_SUBTITLE'],
            ['nama' => 'Subtitle', 'deskripsi' => $request->subtitle ?? '', 'status' => 'Aktif']
        );

        if ($request->has('delete_image') && $request->delete_image == '1') {
            $existingImage = \App\Models\MasterData::where('type', 'setting')->where('kode', 'TK_CORP_IMAGE')->first();
            if ($existingImage && $existingImage->nama) {
                if (\Illuminate\Support\Facades\Storage::disk('public')->exists($existingImage->nama)) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($existingImage->nama);
                }
                $existingImage->delete();
            }
        } elseif ($request->hasFile('image')) {
            $path = $request->file('image')->store('settings', 'public');
            \App\Models\MasterData::updateOrCreate(
                ['type' => 'setting', 'kode' => 'TK_CORP_IMAGE'],
                ['nama' => $path, 'status' => 'Aktif']
            );
        }

        return redirect()->back()->with('success', 'Pengaturan Halaman Terima Kasih Corporate berhasil diperbarui!');
    }

    /**
     * Daftar Prospek (Scoped to authenticated user role / team hierarchy).
     */
    private function getDbProspects(?Request $request = null)
    {
        $user = auth()->user();
        $query = Prospek::with(['sales', 'cs', 'owner', 'takeoverBy', 'sekolah.wilayah', 'perusahaan', 'latestFollowUp.user', 'followUps', 'timelines' => function ($q) {
            $q->orderBy('time', 'desc')->with('user');
        }])->withCount('followUps');

        if ($user) {
            $role = strtolower($user->role);
            if ($role === 'sales') {
                $query->where(function ($q) use ($user) {
                    $q->where('sales_id', $user->id)
                      ->orWhere('owner_id', $user->id)
                      ->orWhere('takeover_by_id', $user->id)
                      ->orWhere(function ($sub) {
                          $sub->where(function ($s) {
                              $s->whereHas('sales', function ($sq) {
                                  $sq->whereIn('role', ['Sales', 'sales']);
                              })->orWhereHas('owner', function ($oq) {
                                  $oq->whereIn('role', ['Sales', 'sales']);
                              });
                          });
                      })
                      // Prospek milik CS (cs_id terisi) juga bisa dilihat semua role
                      ->orWhereNotNull('cs_id')
                      // Pengecualian akses lintas wilayah untuk Sales pada sumber Presensi Siswa
                      ->orWhere(function ($pq) {
                          $pq->where('source', Prospek::SOURCE_PRESENSI_SISWA)
                             ->orWhere('source', 'Presensi Siswa')
                             ->orWhere('source', 'like', '%presensi%');
                      });
                });
            } elseif ($role === 'spv') {
                $salesIds = $user->teamMemberIds();
                $query->where(function ($q) use ($salesIds, $user) {
                    $q->whereIn('sales_id', $salesIds)
                      ->orWhereIn('owner_id', $salesIds)
                      ->orWhere('takeover_by_id', $user->id)
                      ->orWhere(function ($pq) {
                          $pq->where('source', Prospek::SOURCE_PRESENSI_SISWA)
                             ->orWhere('source', 'Presensi Siswa')
                             ->orWhere('source', 'like', '%presensi%');
                      })
                      // Prospek milik CS juga tampil di SPV
                      ->orWhereNotNull('cs_id');
                    if ($user->wilayah_id) {
                        $q->orWhere('wilayah_id', $user->wilayah_id);
                    }
                });
            } elseif ($role === 'cs') {
                $query->where(function ($q) use ($user) {
                    $q->where(function ($mainQ) use ($user) {
                        $mainQ->where(function ($sq) use ($user) {
                            $sq->where('cs_id', $user->id)
                               ->orWhere('owner_id', $user->id)
                               ->orWhere('takeover_by_id', $user->id)
                               ->orWhereNotNull('cs_id')
                               ->orWhereHas('owner', function ($oq) {
                                   $oq->whereIn('role', ['CS', 'cs']);
                               })
                               ->orWhere(function ($st) {
                                   $st->where('stage_number', '>=', 5)
                                      ->orWhereIn('status', [
                                          'FORMULIR', 'BERKAS', 'CLOSING', 'LUNAS',
                                          '05 FORMULIR', '06 BERKAS', '07 CLOSING', '08 LUNAS'
                                      ]);
                               });
                        });
                        if ($user->wilayah_id) {
                            $mainQ->where(function ($w) use ($user) {
                                $w->where('wilayah_id', $user->wilayah_id)
                                  ->orWhereNull('wilayah_id');
                            });
                        }
                    })
                    // Pengecualian akses terbuka lintas wilayah untuk Presensi Siswa (dapat dilihat seluruh CS)
                    ->orWhere(function ($pq) {
                        $pq->where('source', Prospek::SOURCE_PRESENSI_SISWA)
                           ->orWhere('source', 'Presensi Siswa')
                           ->orWhere('source', 'like', '%presensi%');
                    });
                });
            } elseif (in_array($role, ['hm', 'eo'])) {
                if ($role === 'hm') {
                    $hmMemberIds = $user->hmMemberIds();
                    
                    if ($request && $request->sales_id && $request->scope !== 'presensi') {
                        $query->where('sales_id', $request->sales_id);
                    } elseif ($request && $request->spv_id && $request->scope !== 'presensi') {
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
                            $q->whereIn('sales_id', $allSalesInScope)
                              ->orWhere(function ($pq) {
                                  $pq->where('source', Prospek::SOURCE_PRESENSI_SISWA)
                                     ->orWhere('source', 'Presensi Siswa')
                                     ->orWhere('source', 'like', '%presensi%');
                              })
                              // Prospek milik CS juga tampil di HM
                              ->orWhereNotNull('cs_id');
                            if ($user->wilayah_id) {
                                // Also include any prospects explicitly assigned to HM's wilayah
                                $q->orWhere('wilayah_id', $user->wilayah_id);
                            }
                        });
                    }
                } else {
                    $query->where(function ($q) use ($user) {
                        $q->where(function ($pq) {
                            $pq->where('source', Prospek::SOURCE_PRESENSI_SISWA)
                               ->orWhere('source', 'Presensi Siswa')
                               ->orWhere('source', 'like', '%presensi%');
                        });
                        if ($user->wilayah_id) {
                            $q->orWhere('wilayah_id', $user->wilayah_id);
                        }
                    });
                }
            }
        }

        // Backend filter tab scope: all, own, others, presensi
        if ($request && $request->filled('scope')) {
            if ($request->scope === 'own') {
                $query->where(function ($q) use ($user) {
                    $q->where('sales_id', $user->id)
                      ->orWhere('owner_id', $user->id)
                      ->orWhere('takeover_by_id', $user->id)
                      ->orWhere('cs_id', $user->id);
                });
            } elseif ($request->scope === 'others') {
                $query->where(function ($q) use ($user) {
                    $q->where(function ($sub) use ($user) {
                        $sub->whereNull('sales_id')->orWhere('sales_id', '!=', $user->id);
                    })
                    ->where(function ($sub) use ($user) {
                        $sub->whereNull('owner_id')->orWhere('owner_id', '!=', $user->id);
                    })
                    ->where(function ($sub) use ($user) {
                        $sub->whereNull('takeover_by_id')->orWhere('takeover_by_id', '!=', $user->id);
                    })
                    ->where(function ($sub) use ($user) {
                        $sub->whereNull('cs_id')->orWhere('cs_id', '!=', $user->id);
                    })
                    ->where(function ($sub) {
                        $sub->whereNotNull('sales_id')
                           ->orWhereNotNull('takeover_by_id')
                           ->orWhereNotNull('cs_id')
                           ->orWhereHas('sales')
                           ->orWhereHas('owner');
                    });
                });
            } elseif ($request->scope === 'presensi') {
                $query->where(function ($pq) {
                    $pq->where('source', Prospek::SOURCE_PRESENSI_SISWA)
                       ->orWhere('source', 'Presensi Siswa')
                       ->orWhere('source', 'like', '%presensi%');
                });
            }
        }

        if ($request && $request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request && $request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $prospectsRaw = $query->orderBy('updated_at', 'desc')->get();

        return $prospectsRaw->map(function ($p) use ($user) {
            $latestFollowUp = $p->latestFollowUp ?? ($p->relationLoaded('followUps') ? $p->followUps->first() : null);
            $authId = $user ? $user->id : 0;
            $authRole = strtolower($user->role ?? '');
            $hasFollowUpHistory = ($p->follow_ups_count > 0) || ($latestFollowUp !== null);

            $today = now()->toDateString();
            $nextFollowUpDate = $latestFollowUp && $latestFollowUp->next_follow_up ? \Carbon\Carbon::parse($latestFollowUp->next_follow_up)->toDateString() : null;
            $nextFollowUpRaw  = $latestFollowUp && $latestFollowUp->next_follow_up ? \Carbon\Carbon::parse($latestFollowUp->next_follow_up)->toIso8601String() : null;
            $isDone = in_array(strtoupper((string)$p->status), ['LUNAS', 'DINGIN']);

            if ($isDone) {
                $timingCategory = 'done';
                $priorityLabel = strtoupper((string)$p->status);
                $priorityBadgeClass = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                $needFuPriority = null;
                $othersPriority = null;
            } elseif ($nextFollowUpDate !== null) {
                if ($nextFollowUpDate < $today) {
                    $timingCategory = 'overdue';
                    $priorityLabel = 'Terlambat';
                    $priorityBadgeClass = 'bg-rose-50 text-rose-700 border-rose-200';
                    $needFuPriority = 1;
                    $othersPriority = 1;
                } elseif ($nextFollowUpDate === $today) {
                    $timingCategory = 'today';
                    $priorityLabel = 'Jadwal Hari Ini';
                    $priorityBadgeClass = 'bg-amber-50 text-amber-700 border-amber-200';
                    $needFuPriority = 2;
                    $othersPriority = 2;
                } else {
                    $timingCategory = 'upcoming';
                    $priorityLabel = 'Mendatang';
                    $priorityBadgeClass = 'bg-blue-50 text-blue-700 border-blue-200';
                    $needFuPriority = null;
                    $othersPriority = 5;
                }
            } else {
                if (!$hasFollowUpHistory) {
                    $timingCategory = 'uncontacted';
                    $priorityLabel = 'Belum Dihubungi';
                    $priorityBadgeClass = 'bg-sky-50 text-sky-700 border-sky-200';
                    $needFuPriority = 3;
                    $othersPriority = 3;
                } else {
                    $timingCategory = 'unscheduled';
                    $priorityLabel = 'Belum Dijadwalkan';
                    $priorityBadgeClass = 'bg-purple-50 text-purple-700 border-purple-200';
                    $needFuPriority = 4;
                    $othersPriority = 4;
                }
            }

            $sekolahNama = $p->sekolah?->nama ?? ($p->type === 'Sekolah' && $p->name !== $p->pic ? $p->name : '-');
            $wilayahNama = $p->sekolah?->wilayah?->nama ?? $p->wilayah?->nama ?? 'Belum Diisi';

            return [
                'id' => $p->id,
                'name' => $p->name,
                'no_registrasi' => $p->no_registrasi ?? '',
                'gelombang' => $p->gelombang ?? '',
                'type' => $p->type,
                'kelas' => $p->kelas ?: 'Reguler',
                'sekolah_name' => $p->type === 'Sekolah' ? $sekolahNama : ($p->type === 'Corporate' ? ($p->perusahaan ? $p->perusahaan->nama : ($p->name !== $p->pic ? $p->name : '-')) : '-'),
                'wilayah_name' => $wilayahNama,
                'sales_id' => $p->sales_id,
                'owner_id' => $p->owner_id,
                'cs_id' => $p->cs_id,
                'takeover_by_id' => $p->takeover_by_id,
                'sales_name' => $p->sales ? $p->sales->name : '-',
                'cs_name' => $p->cs ? $p->cs->name : ($p->owner && strtolower($p->owner->role ?? '') === 'cs' ? $p->owner->name : '-'),
                'category' => !empty($p->category) ? $p->category : ($p->prodi?->nama ?: '-'),
                'pic' => $p->pic ?? '-',
                'pic_phone' => $p->pic_phone ?? '-',
                'whatsapp' => $p->whatsapp ?? '-',
                'wa_ortu' => $p->wa_ortu ?? '',
                'asal_kelas' => $p->asal_kelas ?? '',
                'prodi_id' => $p->prodi_id,
                'prodi_nama' => $p->prodi ? $p->prodi->nama : (!empty($p->category) ? $p->category : '-'),
                'prodi_lainnya' => $p->prodi_lainnya ?? '',
                'sekolah_id' => $p->sekolah_id,
                'status' => $p->status,
                'stage_number' => $p->stage_number,
                'takeover_sales' => $p->sales ? $p->sales->name : null,
                'takeover_cs' => $p->cs ? $p->cs->name : ($p->owner && strtolower($p->owner->role ?? '') === 'cs' ? $p->owner->name : null),
                'active_takeover' => $p->activeHandlerLabel(),
                'owner' => $p->owner_id ? ($p->owner?->name ?? 'User') : 'Sistem',
                'status_takeover' => $p->status_takeover ?: ($p->takeover_by_id ? 'Sudah Di-takeover' : 'Belum Di-takeover'),
                'takeover_by_name' => $p->takeoverBy?->name ?? null,
                'takeover_by_role' => $p->takeoverBy?->role ?? null,
                'takeover_at' => $p->takeover_at ? $p->takeover_at->format('d M Y, H:i') : null,
                'is_presensi' => $p->isPresensiSiswa(),
                'last_activity' => $p->updated_at->diffForHumans(),
                'potential' => $p->potential ?? '-',
                'source' => $p->source ?? '-',
                'ai_training' => $p->ai_training ?? '-',
                'notes' => $latestFollowUp && $latestFollowUp->catatan ? $latestFollowUp->catatan : ($p->notes ?? ''),
                'lost_reason' => $p->lost_reason ?? null,
                'lost_note' => $p->lost_note ?? null,
                'created_at' => $p->created_at ? $p->created_at->format('d M Y') : '-',
                'takeover_time' => $p->updated_at ? $p->updated_at->format('d M Y, H:i') : '-',
                'last_contact' => $latestFollowUp && $latestFollowUp->tanggal ? \Carbon\Carbon::parse($latestFollowUp->tanggal)->format('d M Y, H:i') : '-',
                'next_follow_up' => $latestFollowUp && $latestFollowUp->next_follow_up ? \Carbon\Carbon::parse($latestFollowUp->next_follow_up)->format('d M Y, H:i') : '-',
                'next_follow_up_date' => $nextFollowUpDate,
                'next_follow_up_raw'  => $nextFollowUpRaw,
                'updated_at_raw'      => $p->updated_at ? $p->updated_at->toIso8601String() : null,
                'created_at_raw'      => $p->created_at ? $p->created_at->toIso8601String() : null,
                'timing_category'     => $timingCategory,
                'priority_label'      => $priorityLabel,
                'priority_badge_class'=> $priorityBadgeClass,
                'need_fu_priority'    => $needFuPriority,
                'others_priority'     => $othersPriority,
                'metode_terakhir' => $latestFollowUp ? ($latestFollowUp->metode ?? '-') : '-',
                'hasil_terakhir' => $latestFollowUp ? ($latestFollowUp->hasil ?? '-') : '-',
                'latest_follow_up_user' => $latestFollowUp && $latestFollowUp->user ? $latestFollowUp->user->name : null,
                'latest_follow_up_user_id' => $latestFollowUp ? $latestFollowUp->user_id : null,
                'is_assisted_by_other' => (bool) ($latestFollowUp && $latestFollowUp->user_id && $latestFollowUp->user_id != $p->sales_id && $latestFollowUp->user_id != $p->owner_id && $latestFollowUp->user_id != $p->cs_id),
                'is_own' => (bool) ($authId && ($authRole === 'cs'
                    ? ($p->cs_id == $authId || $p->owner_id == $authId || $p->takeover_by_id == $authId || (is_null($p->cs_id) && in_array(strtoupper((string)$p->status), ['FORMULIR', 'BERKAS', 'CLOSING', 'LUNAS', '05 FORMULIR', '06 BERKAS', '07 CLOSING', '08 LUNAS'])))
                    : ($p->sales_id == $authId || $p->owner_id == $authId || $p->takeover_by_id == $authId))),
                'is_other' => (bool) ($authId && ($authRole === 'cs'
                    ? (!($p->cs_id == $authId || $p->owner_id == $authId || $p->takeover_by_id == $authId || (is_null($p->cs_id) && in_array(strtoupper((string)$p->status), ['FORMULIR', 'BERKAS', 'CLOSING', 'LUNAS', '05 FORMULIR', '06 BERKAS', '07 CLOSING', '08 LUNAS'])))
                       && ((!is_null($p->cs_id) && $p->cs_id != $authId) || (strtolower(optional($p->owner)->role ?? '') === 'cs' && $p->owner_id != $authId) || (!is_null($p->takeover_by_id) && $p->takeover_by_id != $authId)))
                    : (!($p->sales_id == $authId || $p->owner_id == $authId || $p->takeover_by_id == $authId) && (strtolower(optional($p->sales)->role ?? '') === 'sales' || strtolower(optional($p->owner)->role ?? '') === 'sales' || !is_null($p->sales_id) || !is_null($p->takeover_by_id))))),
                'follow_up_count' => $p->follow_ups_count ?? ($p->relationLoaded('followUps') ? $p->followUps->count() : 0),
                'has_follow_up_history' => $hasFollowUpHistory,
                'follow_ups_history' => $p->relationLoaded('followUps') ? $p->followUps->map(fn($f) => [
                    'tanggal' => $f->tanggal ? \Carbon\Carbon::parse($f->tanggal)->toDateString() : null,
                    'metode'  => $f->metode,
                    'user_id' => $f->user_id,
                ])->toArray() : [],
                'can_update_status' => (bool) (auth()->user() && auth()->user()->can('updateStatus', $p)),
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
        $statuses = \App\Models\Prospek::getActiveStages();
        $prodis = \App\Models\Prodi::where('status', 'Aktif')->orderBy('nama')->get();
        $gelombangList = \App\Models\MasterData::where('type', 'gelombang')->where('status', 'Aktif')->orderBy('id')->get();

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

        $csMetrics = null;
        $salesMetrics = null;
        if ($user && in_array(strtolower($user->role), ['cs', 'admin'])) {
            $startDateCarbon = $request->filled('start_date') ? \Carbon\Carbon::parse($request->start_date)->startOfDay() : null;
            $endDateCarbon   = $request->filled('end_date') ? \Carbon\Carbon::parse($request->end_date)->endOfDay() : null;

            $csBaseQuery = \App\Models\Prospek::query();
            if (strtolower($user->role) === 'cs' && $user->wilayah_id) {
                $csBaseQuery->where(function ($w) use ($user) {
                    $w->where('wilayah_id', $user->wilayah_id)
                      ->orWhereNull('wilayah_id')
                      ->orWhere('source', \App\Models\Prospek::SOURCE_PRESENSI_SISWA)
                      ->orWhere('source', 'Presensi Siswa')
                      ->orWhere('source', 'like', '%presensi%');
                });
            }

            // 1. Jumlah prospek yang diinput (berdasarkan waktu pembuatan)
            $inputQuery = (clone $csBaseQuery);
            if ($startDateCarbon) $inputQuery->where('created_at', '>=', $startDateCarbon);
            if ($endDateCarbon)   $inputQuery->where('created_at', '<=', $endDateCarbon);
            $totalDiinput = $inputQuery->count();

            // List ID prospek CS
            $csProspectIds = (clone $csBaseQuery)->pluck('id');

            // 2. Jumlah prospek yang dihubungi
            $contactQuery = \App\Models\FollowUp::whereIn('prospek_id', $csProspectIds);
            if ($startDateCarbon) $contactQuery->where('tanggal', '>=', $startDateCarbon);
            if ($endDateCarbon)   $contactQuery->where('tanggal', '<=', $endDateCarbon);
            $totalDihubungi = (clone $contactQuery)->where(function ($q) {
                $q->whereIn('metode', ['Telepon', 'WhatsApp', 'Panggilan', 'SMS', 'Chat'])
                  ->orWhere('hasil', 'like', '%Hubung%')
                  ->orWhere('hasil', 'like', '%Kontak%');
            })->distinct('prospek_id')->count('prospek_id');

            if ($totalDihubungi === 0) {
                $totalDihubungi = (clone $contactQuery)->distinct('prospek_id')->count('prospek_id');
            }

            // 3. Jumlah prospek yang di-follow-up
            $fuQuery = \App\Models\FollowUp::whereIn('prospek_id', $csProspectIds);
            if ($startDateCarbon) $fuQuery->where('tanggal', '>=', $startDateCarbon);
            if ($endDateCarbon)   $fuQuery->where('tanggal', '<=', $endDateCarbon);
            $totalFollowUp = $fuQuery->distinct('prospek_id')->count('prospek_id');

            // 4. Jumlah hot prospect
            $hotQuery = (clone $csBaseQuery)->where(function ($q) use ($startDateCarbon, $endDateCarbon) {
                $q->where(function ($sq) {
                    $sq->whereIn('status', ['HOT PROSPEK', 'PANAS'])
                       ->orWhere('category', 'like', '%Hot%');
                });
                if ($startDateCarbon || $endDateCarbon) {
                    $q->orWhereHas('timelines', function ($tq) use ($startDateCarbon, $endDateCarbon) {
                        $tq->where(function ($tsq) {
                            $tsq->whereIn('status_after', ['HOT PROSPEK', 'PANAS'])
                                ->orWhere('title', 'like', '%Hot%');
                        });
                        if ($startDateCarbon) $tq->where('time', '>=', $startDateCarbon);
                        if ($endDateCarbon)   $tq->where('time', '<=', $endDateCarbon);
                    });
                }
            });
            if ($startDateCarbon) $hotQuery->where('created_at', '>=', $startDateCarbon);
            if ($endDateCarbon)   $hotQuery->where('created_at', '<=', $endDateCarbon);
            $totalHot = $hotQuery->count();

            $csMetrics = [
                'diinput'      => $totalDiinput,
                'dihubungi'    => $totalDihubungi,
                'follow_up'    => $totalFollowUp,
                'hot_prospect' => $totalHot,
                'start_date'   => $request->start_date,
                'end_date'     => $request->end_date,
            ];
        } elseif ($user && strtolower($user->role) === 'sales') {
            $startDateCarbon = $request->filled('start_date') ? \Carbon\Carbon::parse($request->start_date)->startOfDay() : null;
            $endDateCarbon   = $request->filled('end_date') ? \Carbon\Carbon::parse($request->end_date)->endOfDay() : null;

            $salesBaseQuery = \App\Models\Prospek::where(function ($q) use ($user) {
                $q->where('sales_id', $user->id)
                  ->orWhere('owner_id', $user->id)
                  ->orWhere('takeover_by_id', $user->id)
                  ->orWhere('source', \App\Models\Prospek::SOURCE_PRESENSI_SISWA)
                  ->orWhere('source', 'Presensi Siswa')
                  ->orWhere('source', 'like', '%presensi%');

                if ($user->wilayah_id) {
                    $q->orWhere(function ($wq) use ($user) {
                        $wq->where('wilayah_id', $user->wilayah_id)
                           ->whereNull('sales_id');
                    });
                }
            });

            // 1. Prospek Diinput oleh Sales ini
            $inputQuery = (clone $salesBaseQuery);
            if ($startDateCarbon) $inputQuery->where('created_at', '>=', $startDateCarbon);
            if ($endDateCarbon)   $inputQuery->where('created_at', '<=', $endDateCarbon);
            $totalDiinput = $inputQuery->count();

            // List ID prospek milik Sales ini
            $salesProspectIds = (clone $salesBaseQuery)->pluck('id');

            // 2. Prospek Dihubungi
            $contactQuery = \App\Models\FollowUp::where(function ($q) use ($user, $salesProspectIds) {
                $q->where('user_id', $user->id)
                  ->orWhereIn('prospek_id', $salesProspectIds);
            });
            if ($startDateCarbon) $contactQuery->where('tanggal', '>=', $startDateCarbon);
            if ($endDateCarbon)   $contactQuery->where('tanggal', '<=', $endDateCarbon);
            $totalDihubungi = (clone $contactQuery)->where(function ($q) {
                $q->whereIn('metode', ['Telepon', 'WhatsApp', 'Panggilan', 'SMS', 'Chat'])
                  ->orWhere('hasil', 'like', '%Hubung%')
                  ->orWhere('hasil', 'like', '%Kontak%');
            })->distinct('prospek_id')->count('prospek_id');

            if ($totalDihubungi === 0) {
                $totalDihubungi = (clone $contactQuery)->distinct('prospek_id')->count('prospek_id');
            }

            // 3. Di-Follow-up
            $fuQuery = \App\Models\FollowUp::where(function ($q) use ($user, $salesProspectIds) {
                $q->where('user_id', $user->id)
                  ->orWhereIn('prospek_id', $salesProspectIds);
            });
            if ($startDateCarbon) $fuQuery->where('tanggal', '>=', $startDateCarbon);
            if ($endDateCarbon)   $fuQuery->where('tanggal', '<=', $endDateCarbon);
            $totalFollowUp = $fuQuery->distinct('prospek_id')->count('prospek_id');

            // 4. Hot Prospect
            $hotQuery = (clone $salesBaseQuery)->where(function ($q) use ($startDateCarbon, $endDateCarbon) {
                $q->where(function ($sq) {
                    $sq->whereIn('status', ['HOT PROSPEK', 'PANAS', 'HOT'])
                       ->orWhere('category', 'like', '%Hot%');
                });
                if ($startDateCarbon || $endDateCarbon) {
                    $q->orWhereHas('timelines', function ($tq) use ($startDateCarbon, $endDateCarbon) {
                        $tq->where(function ($tsq) {
                            $tsq->whereIn('status_after', ['HOT PROSPEK', 'PANAS', 'HOT'])
                                ->orWhere('title', 'like', '%Hot%');
                        });
                        if ($startDateCarbon) $tq->where('time', '>=', $startDateCarbon);
                        if ($endDateCarbon)   $tq->where('time', '<=', $endDateCarbon);
                    });
                }
            });
            if ($startDateCarbon) $hotQuery->where('created_at', '>=', $startDateCarbon);
            if ($endDateCarbon)   $hotQuery->where('created_at', '<=', $endDateCarbon);
            $totalHot = $hotQuery->count();

            $salesMetrics = [
                'diinput'      => $totalDiinput,
                'dihubungi'    => $totalDihubungi,
                'follow_up'    => $totalFollowUp,
                'hot_prospect' => $totalHot,
                'start_date'   => $request->start_date,
                'end_date'     => $request->end_date,
            ];
        }

        return view('prospek.index', compact('prospects', 'sekolahs', 'perusahaans', 'statuses', 'isHm', 'spvs', 'salesList', 'csMetrics', 'salesMetrics', 'prodis', 'gelombangList'));
    }

    public function prospekStore(Request $request)
    {
        if ($request->filled('whatsapp')) {
            $rawWa = preg_replace('/[^0-9]/', '', (string)$request->whatsapp);
            $cleanWa = preg_replace('/^(62|0)+/', '', $rawWa);
            if (!empty($cleanWa)) {
                $request->merge(['whatsapp' => '+62' . $cleanWa]);
            } else {
                $request->merge(['whatsapp' => \App\Support\WhatsAppHelper::prepareForValidation($request->whatsapp)]);
            }
        }

        if ($request->filled('wa_ortu') && trim((string)$request->wa_ortu) !== '') {
            $rawOrtu = preg_replace('/[^0-9]/', '', (string)$request->wa_ortu);
            $cleanOrtu = preg_replace('/^(62|0)+/', '', $rawOrtu);
            if (!empty($cleanOrtu)) {
                $request->merge(['wa_ortu' => '+62' . $cleanOrtu]);
            } else {
                $request->merge(['wa_ortu' => null]);
            }
        } else {
            $request->merge(['wa_ortu' => null]);
        }

        $request->validate([
            'name'          => 'nullable|string|max:255',
            'no_registrasi' => 'nullable|string|max:100',
            'gelombang'     => 'nullable|string|max:100',
            'type'          => 'required|string',
            'status'        => 'required|string',
            'pic'           => 'required|string',
            'whatsapp'      => ['required', 'string', 'max:25', new \App\Rules\IndonesianWhatsApp(allowEmpty: false)],
            'prodi_id'      => 'nullable|string',
            'prodi_lainnya' => 'nullable|string',
            'sekolah_id'    => 'nullable|exists:sekolahs,id',
            'perusahaan_id' => 'nullable|exists:perusahaans,id',
            'sales_id'      => 'nullable|exists:users,id',
            'wa_ortu'       => ['nullable', 'string', 'max:25', new \App\Rules\IndonesianWhatsApp(allowEmpty: true)],
            'asal_kelas'    => 'nullable|string',
        ]);

        DB::transaction(function () use ($request) {
            $ownerId = auth()->id() ?? 1;
            $user = \App\Models\User::find($ownerId);
            
            $salesId = $request->sales_id;
            if (!$salesId && $user && strtolower($user->role) === 'sales') {
                $salesId = $user->id;
            }

            $csId = $request->cs_id ?? ($user && strtolower($user->role) === 'cs' ? $user->id : null);
            $wilayahId = $user?->wilayah_id;

            if ($salesId) {
                $sales = \App\Models\User::find($salesId);
                if ($sales && $sales->wilayah_id) {
                    $wilayahId = $sales->wilayah_id;
                    if ($user && strtolower($user->role) !== 'cs') {
                        $csId = null;
                    }
                }
            }

            // Handle Manual Input (Sekolah / Perusahaan)
            if ($request->type === 'Sekolah' && empty($request->sekolah_id) && $request->filled('sekolah_manual')) {
                $manualName = trim($request->sekolah_manual);
                $sek = \App\Models\Sekolah::where('nama', $manualName)->first();
                if (!$sek) {
                    $kecVal = $request->input('sekolah_kecamatan');
                    $kecName = $kecVal;
                    if (is_numeric($kecVal)) {
                        $wKec = \App\Models\Wilayah::find($kecVal);
                        $kecName = $wKec?->nama;
                    }

                    $sek = \App\Models\Sekolah::create([
                        'kode'        => 'SCH-' . strtoupper(\Illuminate\Support\Str::random(6)),
                        'nama'        => $manualName,
                        'kategori_id' => $request->input('sekolah_kategori_id') ?: null,
                        'wilayah_id'  => $request->input('sekolah_wilayah_id') ?: null,
                        'kecamatan'   => $kecName ?: '-',
                        'telepon'     => $request->input('sekolah_telepon') ?: null,
                        'status'      => 'Aktif',
                        'sales_id'    => $salesId,
                    ]);
                }
                $request->merge(['sekolah_id' => $sek->id]);
            } elseif ($request->type === 'Corporate' && empty($request->perusahaan_id) && $request->filled('perusahaan_manual')) {
                $per = \App\Models\Perusahaan::firstOrCreate(
                    ['nama' => trim($request->perusahaan_manual)],
                    ['status' => 'Aktif']
                );
                $request->merge(['perusahaan_id' => $per->id]);
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

            $noRegistrasi = $request->filled('no_registrasi') ? trim($request->no_registrasi) : null;
            $gelombang    = $request->filled('gelombang') ? trim($request->gelombang) : null;
            $asalKelas    = $request->filled('asal_kelas') ? trim($request->asal_kelas) : null;
            $notes        = $request->filled('notes') ? trim($request->notes) : null;
            $prodiId      = ($request->filled('prodi_id') && $request->prodi_id !== 'lainnya') ? $request->prodi_id : null;
            $prodiLainnya = ($request->prodi_id === 'lainnya' && $request->filled('prodi_lainnya')) ? trim($request->prodi_lainnya) : null;
            $prodi        = $prodiId ? \App\Models\Prodi::find($prodiId) : null;
            $category     = $request->filled('category') ? trim($request->category) : ($prodi?->nama ?: '-');
            $stageNumber  = \App\Models\Prospek::STAGES[$request->status] ?? 1;

            $prospek = Prospek::create([
                'name'               => $name,
                'no_registrasi'      => $noRegistrasi,
                'gelombang'          => $gelombang,
                'type'               => $request->type,
                'status'             => $request->status,
                'stage_number'       => $stageNumber,
                'pic'                => $request->pic,
                'pic_phone'          => \App\Support\WhatsAppHelper::formatForDatabase($request->whatsapp),
                'whatsapp'           => \App\Support\WhatsAppHelper::formatForDatabase($request->whatsapp),
                'sales_id'           => $salesId,
                'cs_id'              => $csId,
                'wilayah_id'         => $wilayahId,
                'prodi_id'           => $prodiId,
                'category'           => $category,
                'source'             => $request->source ?? 'Inbound Direct',
                'kelas'              => $request->kelas ?? 'Reguler',
                'notes'              => $notes,
                'owner_id'           => $ownerId,
                'sekolah_id'         => $request->type === 'Sekolah' ? ($request->sekolah_id ?: null) : null,
                'perusahaan_id'      => $request->type === 'Corporate' ? ($request->perusahaan_id ?: null) : null,
                'wa_ortu'            => \App\Support\WhatsAppHelper::formatForDatabase($request->wa_ortu),
                'asal_kelas'         => $asalKelas,
                'prodi_lainnya'      => $prodiLainnya,
            ]);

            if ($request->prodi_id === 'lainnya') {
                $prospek->prodi_id = null;
                $prospek->save();
            }

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
     * Tambah Presensi Siswa Manual (Presensi Langsung di Counter CS / Event).
     */
    public function prospekPresensiManual(Request $request)
    {
        if ($request->filled('no_whatsapp')) {
            $rawWa = preg_replace('/[^0-9]/', '', (string)$request->no_whatsapp);
            $cleanWa = preg_replace('/^(62|0)+/', '', $rawWa);
            if (!empty($cleanWa)) {
                $request->merge(['no_whatsapp' => '+62' . $cleanWa]);
            } else {
                $request->merge(['no_whatsapp' => \App\Support\WhatsAppHelper::prepareForValidation($request->no_whatsapp)]);
            }
        }

        if ($request->filled('wa_ortu') && trim((string)$request->wa_ortu) !== '') {
            $rawOrtu = preg_replace('/[^0-9]/', '', (string)$request->wa_ortu);
            $cleanOrtu = preg_replace('/^(62|0)+/', '', $rawOrtu);
            if (!empty($cleanOrtu)) {
                $request->merge(['wa_ortu' => '+62' . $cleanOrtu]);
            } else {
                $request->merge(['wa_ortu' => null]);
            }
        } else {
            $request->merge(['wa_ortu' => null]);
        }

        if (!$request->filled('no_registrasi') || trim((string)$request->no_registrasi) === '') {
            $request->merge(['no_registrasi' => null]);
        }
        if (!$request->filled('gelombang') || trim((string)$request->gelombang) === '') {
            $request->merge(['gelombang' => null]);
        }
        if (!$request->filled('notes') || trim((string)$request->notes) === '') {
            $request->merge(['notes' => null]);
        }

        $validated = $request->validate([
            'nama'           => 'required|string|max:255',
            'no_registrasi'  => 'nullable|string|max:100',
            'gelombang'      => 'nullable|string|max:100',
            'sekolah_id'     => 'nullable|exists:sekolahs,id',
            'sekolah_manual' => 'nullable|string|max:255',
            'asal_kelas'     => 'required|string|max:100',
            'no_whatsapp'    => ['required', 'string', 'max:25', new \App\Rules\IndonesianWhatsApp(allowEmpty: false)],
            'wa_ortu'        => ['nullable', 'string', 'max:25', new \App\Rules\IndonesianWhatsApp(allowEmpty: true)],
            'prodi_id'       => 'required',
            'prodi_lainnya'  => 'nullable|required_if:prodi_id,lainnya|string|max:255',
            'kelas'          => 'required|in:Reguler,Karyawan',
            'notes'          => 'nullable|string|max:1000',
        ]);

        if (empty($validated['sekolah_id']) && empty($request->sekolah_manual)) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['sekolah_id' => 'Nama sekolah wajib dipilih dari master data atau diisi manual jika belum terdaftar.']);
        }

        $schoolName = null;
        $sekolahId = null;
        $wilayahId = null;
        $manualNotes = null;

        if (!empty($validated['sekolah_id'])) {
            $sekolah = \App\Models\Sekolah::find($validated['sekolah_id']);
            if ($sekolah) {
                $schoolName = $sekolah->nama;
                $sekolahId = $sekolah->id;
                $wilayahId = $sekolah->wilayah_id;
            }
        } elseif (!empty($request->sekolah_manual)) {
            $manualName = trim($request->sekolah_manual);
            // Cek apakah nama manual cocok (case-insensitive) dengan data master sekolah yang ada
            $matched = \App\Models\Sekolah::whereRaw('LOWER(nama) = ?', [strtolower($manualName)])->first();
            if ($matched) {
                $schoolName = $matched->nama;
                $sekolahId = $matched->id;
                $wilayahId = $matched->wilayah_id;
            } else {
                // Buat sekolah baru di master data, lokasi NULL untuk dilengkapi Admin
                $newSchool = \App\Models\Sekolah::create([
                    'kode'   => 'SCH-' . strtoupper(\Illuminate\Support\Str::random(6)),
                    'nama'   => $manualName,
                    'status' => 'Aktif',
                ]);
                $schoolName = $newSchool->nama;
                $sekolahId = $newSchool->id;
                $wilayahId = null;
                $manualNotes = 'Sekolah baru otomatis ditambahkan ke Master Data: ' . $manualName;
            }
        }

        $name = $schoolName ?: $validated['nama'];

        $currentUser = auth()->user();
        $inputterRole = $currentUser ? ($currentUser->role ?? 'CS') : 'CS';
        $inputterName = $currentUser ? $currentUser->name : 'Staff';

        $notes = 'Presensi siswa langsung dicatat manual oleh ' . $inputterName . ' (' . $inputterRole . ')';
        if (!empty($validated['notes'])) {
            $notes .= ' | Catatan: ' . trim($validated['notes']);
        }
        if ($manualNotes) {
            $notes .= ' | ' . $manualNotes;
        }

        $prospek = Prospek::create([
            'name'             => $name,
            'no_registrasi'    => $validated['no_registrasi'] ?? null,
            'gelombang'        => $validated['gelombang'] ?? null,
            'whatsapp'         => \App\Support\WhatsAppHelper::formatForDatabase($validated['no_whatsapp']),
            'wa_ortu'          => \App\Support\WhatsAppHelper::formatForDatabase($validated['wa_ortu'] ?? null),
            'pic'              => $validated['nama'],
            'pic_phone'        => \App\Support\WhatsAppHelper::formatForDatabase($validated['no_whatsapp']),
            'type'             => 'Sekolah',
            'sekolah_id'       => $sekolahId,
            'wilayah_id'       => $wilayahId,
            'asal_kelas'       => $validated['asal_kelas'] ?? null,
            'perusahaan_id'    => null,
            'prodi_id'         => $validated['prodi_id'] === 'lainnya' ? null : $validated['prodi_id'],
            'prodi_lainnya'    => $validated['prodi_id'] === 'lainnya' ? $validated['prodi_lainnya'] : null,
            'kelas'            => $validated['kelas'],
            'status'           => 'BARU',
            'stage_number'     => 1,
            'source'           => Prospek::SOURCE_PRESENSI_SISWA,
            'notes'            => $notes,
            'owner_id'         => null, // Owner awal murni Sistem
            'sales_id'         => null, // Bebas untuk di-takeover Sales/CS
            'cs_id'            => null,
            'status_takeover'  => 'Belum Di-takeover',
            'takeover_by_id'   => null,
            'takeover_at'      => null,
        ]);

        ProspekTimeline::create([
            'prospek_id'   => $prospek->id,
            'user_id'      => auth()->id(),
            'title'        => 'Presensi Siswa Langsung Dicatat',
            'notes'        => 'Data presensi siswa dicatat langsung oleh ' . $inputterName . ' (' . $inputterRole . ') melalui form presensi manual.',
            'status_after' => $prospek->status,
            'time'         => now(),
        ]);

        $userRole = strtolower(auth()->user()->role ?? '');
        $redirectRoute = ($userRole === 'sales' && \Illuminate\Support\Facades\Route::has('sales.prospek.index'))
            ? 'sales.prospek.index'
            : (($userRole === 'spv' && \Illuminate\Support\Facades\Route::has('spv.prospek.index')) ? 'spv.prospek.index' : 'prospek.index');

        return redirect()->route($redirectRoute, ['scope' => 'presensi'])
            ->with('success', 'Presensi siswa "' . $prospek->pic . '" berhasil disimpan dan masuk ke filter Presensi Siswa.');
    }

    /**
     * Detail Prospek.
     */
    public function prospekShow(int $id): View
    {
        $prospectRaw = Prospek::with(['sales', 'cs', 'owner', 'takeoverBy', 'sekolah.wilayah', 'wilayah', 'perusahaan', 'prodi', 'latestFollowUp.user', 'followUps' => function($q) {
            $q->orderBy('tanggal', 'desc')->orderBy('id', 'desc')->with('user');
        }, 'timelines' => function($q) {
            $q->orderBy('time', 'desc')->with('user');
        }, 'transaksis' => function($q) {
            $q->orderBy('tanggal', 'desc')->orderBy('id', 'desc')->with(['user', 'verifier', 'rejecter']);
        }])->findOrFail($id);

        \Illuminate\Support\Facades\Gate::authorize('view', $prospectRaw);

        $activeTakeover = [];
        if ($prospectRaw->sales) $activeTakeover[] = 'Sales';
        if ($prospectRaw->cs) $activeTakeover[] = 'CS';

        $latestFollowUp = $prospectRaw->latestFollowUp ?? ($prospectRaw->relationLoaded('followUps') ? $prospectRaw->followUps->first() : null);

        $transaksis = $prospectRaw->transaksis;
        $pendingList         = $transaksis->where('payment_status', 'pending');
        $hasPendingTrx       = $pendingList->isNotEmpty();
        $hasVerifiedTermin1  = $transaksis->where('payment_status', 'verified')->filter(fn($t) => in_array($t->jenis, ['Pembayaran Termin 1', 'DP/Konversi', 'UKT', 'Perlengkapan']) || $t->kategori === 'Lunas')->isNotEmpty();
        $hasVerifiedFormulir = $transaksis->where('payment_status', 'verified')->filter(fn($t) => in_array($t->jenis, ['Beli Formulir', 'Pendaftaran']) || $t->kategori === 'DP Daftar')->isNotEmpty();
        $totalPaid           = (float) $transaksis->where('payment_status', 'verified')->sum('nominal');
        $totalPending        = (float) $transaksis->where('payment_status', 'pending')->sum('nominal');

        $sekolahNama = $prospectRaw->sekolah?->nama ?? ($prospectRaw->sekolah_manual ?: ($prospectRaw->type === 'Corporate' ? ($prospectRaw->perusahaan?->nama ?? '-') : ($prospectRaw->type === 'Sekolah' ? $prospectRaw->name : '-')));
        $wilayahNama = $prospectRaw->sekolah?->wilayah?->nama ?? ($prospectRaw->wilayah?->nama ?? 'Belum Diisi');

        $prospect = [
            'id' => $prospectRaw->id,
            'name' => $prospectRaw->name,
            'no_registrasi' => $prospectRaw->no_registrasi ?? '',
            'gelombang' => $prospectRaw->gelombang ?? '',
            'type' => $prospectRaw->type,
            'sales_id' => $prospectRaw->sales_id,
            'owner_id' => $prospectRaw->owner_id,
            'cs_id' => $prospectRaw->cs_id,
            'takeover_by_id' => $prospectRaw->takeover_by_id,
            'can_update_status' => (bool) (auth()->user() && auth()->user()->can('updateStatus', $prospectRaw)),
            'sekolah_id' => $prospectRaw->sekolah_id,
            'perusahaan_id' => $prospectRaw->perusahaan_id,
            'prodi_id' => $prospectRaw->prodi_id,
            'sekolah_name' => $sekolahNama,
            'wilayah_name' => $wilayahNama,
            'category' => $prospectRaw->category ?? '-',
            'kelas' => $prospectRaw->kelas ?: 'Reguler',
            'pic' => $prospectRaw->pic ?? '-',
            'pic_phone' => $prospectRaw->pic_phone ?? '-',
            'whatsapp' => $prospectRaw->whatsapp ?? '-',
            'wa_ortu' => $prospectRaw->wa_ortu ?? '',
            'asal_kelas' => $prospectRaw->asal_kelas ?? '',
            'prodi_lainnya' => $prospectRaw->prodi_lainnya ?? '',
            'status' => $prospectRaw->status,
            'stage_number' => $prospectRaw->stage_number,
            'takeover_sales' => $prospectRaw->sales ? $prospectRaw->sales->name : null,
            'takeover_cs' => $prospectRaw->cs ? $prospectRaw->cs->name : null,
            'active_takeover' => $prospectRaw->activeHandlerLabel(),
            'owner' => $prospectRaw->owner ? $prospectRaw->owner->name : 'Sistem',
            'status_takeover' => $prospectRaw->status_takeover ?? ($prospectRaw->takeover_by_id ? 'Sudah Di-takeover' : 'Belum Di-takeover'),
            'takeover_by_name' => $prospectRaw->takeoverBy?->name ?? null,
            'takeover_by_role' => $prospectRaw->takeoverBy?->role ?? null,
            'takeover_at' => $prospectRaw->takeover_at ? $prospectRaw->takeover_at->format('d M Y, H:i') : null,
            'is_presensi' => $prospectRaw->isPresensiSiswa(),
            'last_activity' => $prospectRaw->updated_at->diffForHumans(),
            'source' => $prospectRaw->source ?? '-',
            'notes' => $latestFollowUp && $latestFollowUp->catatan ? $latestFollowUp->catatan : ($prospectRaw->notes ?? ''),
            'lost_reason' => $prospectRaw->lost_reason ?? null,
            'lost_note' => $prospectRaw->lost_note ?? null,
            'created_at' => $prospectRaw->created_at ? $prospectRaw->created_at->format('d M Y') : '-',
            'takeover_time' => $prospectRaw->updated_at ? $prospectRaw->updated_at->format('d M Y, H:i') : '-',
            'last_contact' => $latestFollowUp && $latestFollowUp->tanggal ? \Carbon\Carbon::parse($latestFollowUp->tanggal)->format('d M Y, H:i') : '-',
            'next_follow_up' => $latestFollowUp && $latestFollowUp->next_follow_up ? \Carbon\Carbon::parse($latestFollowUp->next_follow_up)->format('d M Y, H:i') : '-',
            'next_follow_up_date' => $latestFollowUp && $latestFollowUp->next_follow_up ? \Carbon\Carbon::parse($latestFollowUp->next_follow_up)->toDateString() : null,
            'metode_terakhir' => $latestFollowUp ? ($latestFollowUp->metode ?? '-') : '-',
            'hasil_terakhir' => $latestFollowUp ? ($latestFollowUp->hasil ?? '-') : '-',
            'timeline' => $prospectRaw->timelines->map(function ($t) {
                return [
                    'id' => $t->id,
                    'user_id' => $t->user_id,
                    'time' => $t->time ? $t->time->format('d M, H:i') : ($t->created_at ? $t->created_at->format('d M, H:i') : '-'),
                    'title' => $t->title,
                    'notes' => $t->notes,
                    'status_before' => $t->status_before,
                    'status' => $t->status_after,
                    'user' => $t->user ? $t->user->name : 'Sistem',
                    'role' => $t->user ? $t->user->role : 'Admin',
                ];
            })->toArray(),
            'follow_ups' => $prospectRaw->followUps->map(function ($f) use ($prospectRaw) {
                $isHelper = (bool) ($f->user_id && $f->user_id != $prospectRaw->sales_id && $f->user_id != $prospectRaw->owner_id);
                return [
                    'id'             => $f->id,
                    'user_id'        => $f->user_id,
                    'user'           => $f->user ? $f->user->name : 'Sistem',
                    'role'           => $f->user ? $f->user->role : 'Sales',
                    'is_helper'      => $isHelper,
                    'tanggal'        => \Carbon\Carbon::parse($f->tanggal)->format('d M Y, H:i'),
                    'metode'         => $f->metode ?? '-',
                    'hasil'          => $f->hasil ?? '-',
                    'catatan'        => $f->catatan ?? '-',
                    'next_follow_up' => $f->next_follow_up
                        ? \Carbon\Carbon::parse($f->next_follow_up)->format('d M Y, H:i')
                        : '-',
                ];
            })->toArray(),
        ];

        $stagesMap = \App\Models\Prospek::getDynamicStagesMap();
        $allStages = array_map(function ($stageName) use ($stagesMap) {
            return ['name' => $stageName, 'number' => $stagesMap[$stageName] ?? 1];
        }, \App\Models\Prospek::getActiveStages());

        // Retrieve available Sales team for re-allocation
        $user = auth()->user();
        if ($user && strtolower($user->role) === 'spv') {
            $salesTeam = $user->teamSales()->get();
        } else {
            $salesTeam = \App\Models\User::where('role', 'Sales')->where('status', 'aktif')->get();
        }

        $sekolahsList        = \App\Models\Sekolah::getDynamicSchools();
        $perusahaansList     = \App\Models\Perusahaan::getDynamicPerusahaans();
        $prodisList          = \App\Models\Prodi::where('status', 'Aktif')->orderBy('nama')->get();
        $gelombangList       = \App\Models\MasterData::where('type', 'gelombang')->where('status', 'Aktif')->orderBy('id')->get();
        $sumberProspekList   = \App\Models\MasterData::where('type', 'sumber_prospek')->where('status', 'Aktif')->get();
        $kategoriProspekList = \App\Models\MasterData::where('type', 'kategori_prospek')->where('status', 'Aktif')->get();
        $kategoriSekolahList = \App\Models\MasterData::where('type', 'kategori_sekolah')->where('status', 'Aktif')->get();
        $wilayahKabupatenList = \App\Models\Wilayah::whereNull('parent_id')->with(['children' => function($q) {
            $q->where('status', 'Aktif')->orderBy('nama');
        }])->orderBy('nama')->get();
        $lostReasons         = \App\Models\Prospek::LOST_REASONS;
        $metodeOptions       = \App\Models\FollowUp::METODE_OPTIONS;
        $isHandler           = $prospectRaw->isHandledBySales(auth()->user()) || ($user && strtolower($user->role) === 'cs' && $prospectRaw->cs_id === $user->id);

        $prospekModel = $prospectRaw;
        $followUps = $prospectRaw->followUps;

        return view('prospek.show', compact(
            'prospect', 'allStages', 'salesTeam', 'prospectRaw', 'prospekModel', 'transaksis', 'followUps',
            'sekolahsList', 'perusahaansList', 'prodisList', 'gelombangList', 'sumberProspekList', 'kategoriProspekList',
            'kategoriSekolahList', 'wilayahKabupatenList',
            'lostReasons', 'metodeOptions', 'isHandler',
            'hasPendingTrx', 'pendingList', 'hasVerifiedTermin1', 'hasVerifiedFormulir', 'totalPaid', 'totalPending'
        ));
    }

    public function prospekUpdate(Request $request, int $id)
    {
        $prospek = Prospek::findOrFail($id);
        \Illuminate\Support\Facades\Gate::authorize('update', $prospek);

        if ($request->filled('whatsapp')) {
            $request->merge(['whatsapp' => \App\Support\WhatsAppHelper::prepareForValidation($request->whatsapp)]);
        }
        if ($request->filled('wa_ortu')) {
            $request->merge(['wa_ortu' => \App\Support\WhatsAppHelper::prepareForValidation($request->wa_ortu)]);
        }
        
        $validated = $request->validate([
            'status' => 'required|string',
            'no_registrasi' => 'nullable|string|max:100',
            'gelombang' => 'nullable|string|max:100',
            'type' => 'nullable|string',
            'name' => 'nullable|string',
            'pic' => 'nullable|string',
            'whatsapp' => ['nullable', 'string', 'max:25', new \App\Rules\IndonesianWhatsApp(allowEmpty: true)],
            'wa_ortu' => ['nullable', 'string', 'max:25', new \App\Rules\IndonesianWhatsApp(allowEmpty: true)],
            'asal_kelas' => 'nullable|string',
            'sekolah_id' => 'nullable|exists:sekolahs,id',
            'sekolah_manual' => 'nullable|string',
            'perusahaan_id' => 'nullable|exists:perusahaans,id',
            'perusahaan_manual' => 'nullable|string',
            'category' => 'nullable|string',
            'prodi_id' => 'nullable|string',
            'prodi_lainnya' => 'nullable|string',
            'source' => 'nullable|string',
            'notes' => 'nullable|string',
            'kelas' => 'nullable|in:Reguler,Karyawan',
            'sekolah_kategori_id' => 'nullable|exists:master_data,id',
            'sekolah_wilayah_id' => 'nullable|exists:wilayahs,id',
            'sekolah_kecamatan' => 'nullable|string|max:100',
            'sekolah_telepon' => 'nullable|string|max:50',
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

        if ($user && strtolower($user->role) === 'cs') {
            $allowedCsStatuses = ['FORMULIR', 'BERKAS', 'LUNAS', 'NO RESPON', 'BATAL DAFTAR', 'BATAL', 'CANCEL'];
            if (!in_array($newStatus, $allowedCsStatuses) && $oldStatus !== $newStatus) {
                return redirect()->back()->withErrors(['status' => 'CS hanya berwenang mengubah status ke FORMULIR, BERKAS, LUNAS, NO RESPON, atau BATAL DAFTAR.']);
            }
        }

        if ($newStatus === 'LUNAS' && !\App\Services\ProspekService::isClosingValid($prospek)) {
            return redirect()->back()->withErrors(['status' => 'Status LUNAS memerlukan verifikasi pembayaran (Beli Formulir & Pembayaran Termin 1) oleh CS.']);
        }

        if (!empty($validated['type'])) {
            if ($validated['type'] === 'Sekolah') {
                $validated['perusahaan_id'] = null;
                if (!empty($request->sekolah_manual)) {
                    $manualName = trim($request->sekolah_manual);
                    if ($manualName !== '') {
                        $sekolah = \App\Models\Sekolah::where('nama', $manualName)->first();
                        if (!$sekolah) {
                            $kecVal = $request->input('sekolah_kecamatan');
                            $kecName = $kecVal;
                            if (is_numeric($kecVal)) {
                                $wKec = \App\Models\Wilayah::find($kecVal);
                                $kecName = $wKec?->nama;
                            }

                            $sekolah = \App\Models\Sekolah::create([
                                'kode'        => 'SCH-' . strtoupper(\Illuminate\Support\Str::random(6)),
                                'nama'        => $manualName,
                                'kategori_id' => $request->input('sekolah_kategori_id') ?: null,
                                'wilayah_id'  => $request->input('sekolah_wilayah_id') ?: null,
                                'kecamatan'   => $kecName ?: '-',
                                'telepon'     => $request->input('sekolah_telepon') ?: null,
                                'status'      => 'Aktif',
                                'sales_id'    => $prospek->sales_id,
                            ]);
                        }
                        $validated['sekolah_id'] = $sekolah->id;
                        $validated['name'] = $sekolah->nama;
                    }
                } elseif (!empty($validated['sekolah_id'])) {
                    $sekolah = \App\Models\Sekolah::find($validated['sekolah_id']);
                    if ($sekolah) $validated['name'] = $sekolah->nama;
                }
            } elseif ($validated['type'] === 'Corporate') {
                $validated['sekolah_id'] = null;
                if (!empty($request->perusahaan_manual)) {
                    $manualName = trim($request->perusahaan_manual);
                    if ($manualName !== '') {
                        $perusahaan = \App\Models\Perusahaan::firstOrCreate(
                            ['nama' => $manualName],
                            ['kode' => 'CORP-' . strtoupper(\Illuminate\Support\Str::random(6)), 'status' => 'Aktif']
                        );
                        $validated['perusahaan_id'] = $perusahaan->id;
                        $validated['name'] = $perusahaan->nama;
                    }
                } elseif (!empty($validated['perusahaan_id'])) {
                    $perusahaan = \App\Models\Perusahaan::find($validated['perusahaan_id']);
                    if ($perusahaan) $validated['name'] = $perusahaan->nama;
                }
            } else {
                $validated['sekolah_id'] = null;
                $validated['perusahaan_id'] = null;
                if (empty($validated['name'])) {
                    $validated['name'] = $validated['pic'] ?? $prospek->name;
                }
            }
        }

        $validated['status'] = $newStatus;
        if (isset(\App\Models\Prospek::STAGES[$newStatus])) {
            $validated['stage_number'] = \App\Models\Prospek::STAGES[$newStatus];
        }

        unset($validated['sekolah_manual'], $validated['perusahaan_manual']);

        if (array_key_exists('whatsapp', $validated)) {
            $validated['whatsapp'] = \App\Support\WhatsAppHelper::formatForDatabase($validated['whatsapp']);
            $validated['pic_phone'] = $validated['whatsapp'];
        }
        if (array_key_exists('wa_ortu', $validated)) {
            $validated['wa_ortu'] = \App\Support\WhatsAppHelper::formatForDatabase($validated['wa_ortu']);
        }

        if (empty($validated['prodi_id'])) {
            $validated['prodi_id'] = null;
            $validated['prodi_lainnya'] = null;
        } elseif ($validated['prodi_id'] === 'lainnya') {
            $validated['prodi_id'] = null;
        } else {
            $validated['prodi_lainnya'] = null;
        }

        $prospek->update($validated);

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
        } else {
            ProspekTimeline::create([
                'prospek_id'  => $prospek->id,
                'user_id'     => auth()->id(),
                'title'       => 'Data Prospek Diperbarui',
                'notes'       => 'Informasi prospek diperbarui oleh ' . auth()->user()->name,
                'status_after' => $prospek->status,
                'time'         => now(),
            ]);
        }

        return redirect()->back()->with('success', 'Data prospek berhasil diperbarui!');
    }

    public function prospekDestroy(int $id)
    {
        $prospek = Prospek::findOrFail($id);
        \Illuminate\Support\Facades\Gate::authorize('delete', $prospek);
        $prospek->delete();
        
        $referer = request()->header('referer');
        if ($referer && str_contains($referer, '/prospek/' . $id)) {
            $userRole = strtolower(auth()->user()->role ?? '');
            $route = $userRole === 'sales' && \Illuminate\Support\Facades\Route::has('sales.prospek.index')
                ? 'sales.prospek.index'
                : ($userRole === 'spv' && \Illuminate\Support\Facades\Route::has('spv.prospek.index') ? 'spv.prospek.index' : 'prospek.index');
            return redirect()->route($route)->with('success', 'Prospek berhasil dihapus!');
        }

        return redirect()->back()->with('success', 'Prospek berhasil dihapus!');
    }

    public function prospekTakeover(int $id)
    {
        $user = auth()->user();
        if (!$user) {
            abort(401);
        }

        $userId = $user->id;
        $userRole = strtolower($user->role);

        $result = DB::transaction(function () use ($id, $user, $userId, $userRole) {
            // Atomic row locking to prevent race condition between concurrent requests
            $prospek = Prospek::where('id', $id)->lockForUpdate()->firstOrFail();
            \Illuminate\Support\Facades\Gate::authorize('takeover', $prospek);

            // Double takeover prevention
            if ($prospek->status_takeover === 'Sudah Di-takeover' || !is_null($prospek->takeover_by_id)) {
                $takenByName = $prospek->takeoverBy?->name ?? 'pengguna lain';
                return [
                    'success' => false,
                    'message' => 'Prospek ini sudah di-takeover sebelumnya oleh ' . $takenByName . '.',
                ];
            }

            // Presensi Siswa takeover:
            if ($prospek->isPresensiSiswa()) {
                $updates = [
                    'status_takeover' => 'Sudah Di-takeover',
                    'takeover_by_id'  => $userId,
                    'takeover_at'     => now(),
                ];
                if ($userRole === 'sales') {
                    $updates['sales_id'] = $userId;
                } elseif ($userRole === 'cs') {
                    $updates['cs_id'] = $userId;
                }

                $prospek->update($updates);

                ProspekTimeline::create([
                    'prospek_id'   => $prospek->id,
                    'user_id'      => $userId,
                    'title'        => 'Takeover Presensi Siswa',
                    'notes'        => 'Prospek Presensi Siswa berhasil di-takeover oleh ' . $user->name . ' (' . $user->role . ').',
                    'status_after' => $prospek->status,
                    'time'         => now(),
                ]);

                return [
                    'success' => true,
                    'message' => 'Prospek Presensi Siswa berhasil di-takeover oleh Anda.',
                ];
            }

            // Non-Presensi Siswa (CS proactive takeover or Sales handover):
            if ($userRole === 'cs') {
                $prospek->update([
                    'cs_id'           => $userId,
                    'status_takeover' => 'Sudah Di-takeover',
                    'takeover_by_id'  => $userId,
                    'takeover_at'     => now(),
                ]);

                ProspekTimeline::create([
                    'prospek_id'   => $prospek->id,
                    'user_id'      => $userId,
                    'title'        => 'Takeover CS',
                    'notes'        => 'Prospek di-takeover oleh CS ' . $user->name,
                    'status_after' => $prospek->status,
                    'time'         => now(),
                ]);

                return [
                    'success' => true,
                    'message' => 'Prospek berhasil di-takeover oleh CS ' . $user->name,
                ];
            }

            // Handover to CS by Sales/Admin/HM
            $cs = \App\Models\User::where('role', 'CS')->where('status', 'Aktif')->first() ?? \App\Models\User::where('role', 'CS')->first();
            if (!$cs) {
                return [
                    'success' => false,
                    'message' => 'Tidak ada user CS yang tersedia untuk menerima prospek.',
                ];
            }

            $prospek->update([
                'cs_id'           => $cs->id,
                'status_takeover' => 'Sudah Di-takeover',
                'takeover_by_id'  => $userId,
                'takeover_at'     => now(),
            ]);

            ProspekTimeline::create([
                'prospek_id'   => $prospek->id,
                'user_id'      => $userId,
                'title'        => 'Prospek Diserahkan ke CS',
                'notes'        => 'Prospek diserahkan ke CS: ' . $cs->name,
                'status_after' => $prospek->status,
                'time'         => now(),
            ]);

            return [
                'success' => true,
                'message' => 'Prospek berhasil diserahkan ke CS ' . $cs->name,
            ];
        });

        if (!$result['success']) {
            return redirect()->back()->with('error', $result['message'])->withErrors(['takeover' => $result['message']]);
        }

        return redirect()->back()->with('success', $result['message']);
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
        $request->validate([
            'prospek_id' => 'required|exists:prospeks,id',
            'status'     => 'required|string',
            'notes'      => 'nullable|string|max:1000',
        ]);

        $prospek = Prospek::findOrFail($request->prospek_id);
        \Illuminate\Support\Facades\Gate::authorize('updateStatus', $prospek);
        
        $user = auth()->user();
        $oldStatus = strtoupper($prospek->status);
        $newStatus = strtoupper(trim($request->status));

        // Status LUNAS cannot be changed to anything else
        if ($oldStatus === 'LUNAS' && $newStatus !== 'LUNAS') {
            return response()->json(['success' => false, 'message' => 'Prospek yang sudah Lunas tidak dapat diubah statusnya.'], 403);
        }

        // Sales cannot transition a prospect to LUNAS directly
        if ($user && strtolower($user->role) === 'sales' && $newStatus === 'LUNAS') {
            return response()->json(['success' => false, 'message' => 'Sales tidak dapat mengubah status menjadi Lunas.'], 403);
        }

        // CS is restricted to its authorized stages
        if ($user && strtolower($user->role) === 'cs') {
            $allowedCsStatuses = ['FORMULIR', 'BERKAS', 'LUNAS', 'NO RESPON', 'BATAL DAFTAR', 'BATAL', 'CANCEL'];
            if (!in_array($newStatus, $allowedCsStatuses)) {
                return response()->json([
                    'success' => false,
                    'message' => 'CS hanya berwenang mengubah status ke FORMULIR, BERKAS, LUNAS, NO RESPON, atau BATAL DAFTAR.'
                ], 403);
            }
        }

        // Changing status to LUNAS strictly requires verified closing payments (Formulir + Termin 1)
        if ($newStatus === 'LUNAS' && !\App\Services\ProspekService::isClosingValid($prospek)) {
            return response()->json([
                'success' => false,
                'message' => 'Status LUNAS memerlukan verifikasi pembayaran (Beli Formulir & Pembayaran Termin 1) oleh CS.'
            ], 422);
        }

        $stagesMap = \App\Models\Prospek::getDynamicStagesMap();
        $prospek->status = $newStatus;
        $prospek->stage_number = $stagesMap[$newStatus] ?? $prospek->stage_number;
        $prospek->save();

        if ($oldStatus !== $prospek->status) {
            $customNote = $request->filled('notes') ? trim($request->notes) : null;
            ProspekTimeline::create([
                'prospek_id'    => $prospek->id,
                'user_id'       => auth()->id() ?? 1,
                'title'         => 'Status Diperbarui via Pipeline Modal',
                'notes'         => $customNote ?: ('Status prospek diubah dari ' . $oldStatus . ' menjadi ' . $prospek->status),
                'status_before' => $oldStatus,
                'status_after'  => $prospek->status,
                'time'          => now(),
            ]);
        }
        
        return response()->json(['success' => true, 'message' => 'Status berhasil diperbarui.']);
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

        if (request()->filled('jenis')) {
            $jenis = ucfirst(strtolower(request('jenis')));
            if (in_array($jenis, ['Corporate', 'Perusahaan'])) {
                $query->whereIn('jenis', ['Perusahaan', 'Corporate']);
            } elseif ($jenis === 'Sekolah') {
                $query->where('jenis', 'Sekolah');
            }
        }

        $visits = $query->get()->map(fn ($k) => $this->formatKunjunganForBlade($k))->toArray();

        return view('kunjungan.index', compact('visits'));
    }

    public function kunjunganStore(Request $request)
    {
        $user = auth()->user();

        // Resolve nama_institusi from manual input, prospek, or sekolah
        if ($request->filled('sekolah_manual')) {
            $manualName = trim($request->sekolah_manual);
            if ($manualName !== '') {
                $sek = \App\Models\Sekolah::firstOrCreate(
                    ['nama' => $manualName],
                    [
                        'kode' => 'SCH-' . strtoupper(\Illuminate\Support\Str::random(6)),
                        'status' => 'Aktif',
                        'pic_name' => $request->input('pic_name'),
                        'pic_phone' => $request->input('pic_whatsapp'),
                    ]
                );
                $request->merge([
                    'sekolah_id' => $sek->id,
                    'nama_institusi' => $sek->nama,
                ]);
            }
        } elseif ($request->filled('perusahaan_manual')) {
            $manualName = trim($request->perusahaan_manual);
            if ($manualName !== '') {
                $per = \App\Models\Perusahaan::firstOrCreate(
                    ['nama' => $manualName],
                    [
                        'kode' => 'CORP-' . strtoupper(\Illuminate\Support\Str::random(6)),
                        'status' => 'Aktif',
                        'pic_name' => $request->input('pic_name'),
                        'pic_phone' => $request->input('pic_whatsapp'),
                    ]
                );
                $request->merge([
                    'perusahaan_id' => $per->id,
                    'nama_institusi' => $per->nama,
                ]);
            }
        } elseif (!$request->filled('nama_institusi')) {
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

        if ($request->filled('pic_whatsapp')) {
            $request->merge(['pic_whatsapp' => \App\Support\WhatsAppHelper::prepareForValidation($request->pic_whatsapp)]);
        }

        $request->validate([
            'nama_institusi' => 'required|string|max:255',
            'jenis'          => 'required|in:Sekolah,Perusahaan',
            'tanggal'        => 'required|date',
            'pic_name'       => 'required|string|max:255',
            'pic_whatsapp'   => ['required', 'string', 'max:25', new \App\Rules\IndonesianWhatsApp(allowEmpty: false)],
            'catatan'        => 'nullable|string|max:2000',
            'foto'           => 'nullable|image|mimes:jpeg,jpg,png,webp|max:5120',
            'dosen_pemateri' => 'nullable|string|max:255',
            'dosen_id'       => 'nullable|exists:users,id',
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
            // Lecturer
            'dosen_pemateri'        => $request->input('dosen_pemateri'),
            'dosen_id'              => $request->input('dosen_id'),
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

    public function kunjunganUpdate(Request $request, \App\Models\Kunjungan $kunjungan)
    {
        \Illuminate\Support\Facades\Gate::authorize('update', $kunjungan);

        if ($request->filled('pic_whatsapp')) {
            $request->merge(['pic_whatsapp' => \App\Support\WhatsAppHelper::prepareForValidation($request->pic_whatsapp)]);
        }

        $validated = $request->validate([
            'tanggal'                  => 'required|date',
            'waktu'                    => 'nullable|string',
            'nama_institusi'           => 'nullable|string|max:255',
            'tujuan_kunjungan'         => 'nullable|string|max:255',
            'pic_name'                 => 'nullable|string|max:255',
            'pic_whatsapp'             => ['nullable', 'string', 'max:25', new \App\Rules\IndonesianWhatsApp(allowEmpty: true)],
            'alamat'                   => 'nullable|string|max:500',
            'catatan'                  => 'nullable|string|max:2000',
            'hasil'                    => 'nullable|string|max:2000',
            'status'                   => 'nullable|string|in:Menunggu,Proses,Selesai',
            'foto'                     => 'nullable|image|mimes:jpeg,jpg,png,webp|max:5120',
            'potensi_mahasiswa'        => 'nullable|string|max:255',
            'detail_potensi_mahasiswa' => 'nullable|string|max:500',
            'kesediaan_training_ai'    => 'nullable|boolean',
            'bidang_usaha'             => 'nullable|string|max:255',
            'potensi_s1'               => 'nullable|string|max:255',
            'potensi_s2'               => 'nullable|string|max:255',
            'potensi_csr'              => 'nullable|string|max:255',
            'dosen_pemateri'           => 'nullable|string|max:255',
            'dosen_id'                 => 'nullable|exists:users,id',
        ]);

        if ($request->hasFile('foto') && $request->file('foto')->isValid()) {
            if ($kunjungan->foto_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($kunjungan->foto_path);
            }
            $validated['foto_path'] = $request->file('foto')->store('kunjungan', 'public');
        }

        if ($request->filled('nama_institusi')) {
            $validated['tujuan_kunjungan'] = $request->nama_institusi;
        } elseif ($request->filled('tujuan_kunjungan')) {
            $validated['nama_institusi'] = $request->tujuan_kunjungan;
        }

        if ($request->filled('catatan') && !$request->filled('hasil')) {
            $validated['hasil'] = $request->catatan;
        } elseif ($request->filled('hasil') && !$request->filled('catatan')) {
            $validated['catatan'] = $request->hasil;
        }

        if ($request->has('kesediaan_training_ai')) {
            $validated['kesediaan_training_ai'] = $request->boolean('kesediaan_training_ai');
        }

        if (array_key_exists('pic_whatsapp', $validated)) {
            $validated['pic_whatsapp'] = \App\Support\WhatsAppHelper::formatForDatabase($validated['pic_whatsapp']);
        }

        $kunjungan->update($validated);

        return redirect()->back()->with('success', 'Data kunjungan berhasil diperbarui!');
    }

    public function kunjunganDestroy(\App\Models\Kunjungan $kunjungan)
    {
        \Illuminate\Support\Facades\Gate::authorize('delete', $kunjungan);

        if ($kunjungan->foto_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($kunjungan->foto_path);
        }

        $kunjungan->delete();

        $referer = request()->header('referer');
        if ($referer && str_contains($referer, '/kunjungan/' . $kunjungan->id)) {
            $userRole = strtolower(auth()->user()->role ?? '');
            $route = $userRole === 'sales' && \Illuminate\Support\Facades\Route::has('sales.kunjungan.index')
                ? 'sales.kunjungan.index'
                : ($userRole === 'spv' && \Illuminate\Support\Facades\Route::has('spv.kunjungan.index') ? 'spv.kunjungan.index' : 'kunjungan.index');
            return redirect()->route($route)->with('success', 'Kunjungan berhasil dihapus.');
        }

        return redirect()->back()->with('success', 'Kunjungan berhasil dihapus.');
    }

    /**
     * Halaman Follow Up.
     */
    public function followUpIndex(): View
    {
        $allProspects = collect($this->getDbProspects(request()));
        $today = now()->toDateString();
        $user = auth()->user();
        $authRole = strtolower($user->role ?? '');
        
        $needFollowUp = [];
        $others       = [];
        $scheduled    = [];
        $done         = [];

        // Legacy / internal categories for backward compatibility
        $uncontacted  = [];
        $todayList    = [];
        $overdue      = [];
        $unscheduled  = [];
        $upcoming     = [];

        foreach ($allProspects as $p) {
            $isDone  = in_array(strtoupper($p['status'] ?? ''), ['LUNAS', 'DINGIN']);
            $isOwn   = !empty($p['is_own']);
            $isOther = !empty($p['is_other']);
            $timing  = $p['timing_category'] ?? 'uncontacted';

            if ($isDone) {
                if ($isOwn || in_array($authRole, ['spv', 'hm', 'admin'])) {
                    $done[] = $p;
                }
            } else {
                // Active prospects
                if ($isOwn) {
                    if ($timing === 'upcoming') {
                        $scheduled[] = $p;
                    } else {
                        $needFollowUp[] = $p;
                    }
                }

                if ($isOther) {
                    $others[] = $p;
                }

                // Legacy keys
                if ($timing === 'uncontacted') {
                    $uncontacted[] = $p;
                } elseif ($timing === 'today') {
                    $todayList[] = $p;
                } elseif ($timing === 'overdue') {
                    $overdue[] = $p;
                } elseif ($timing === 'unscheduled') {
                    $unscheduled[] = $p;
                } elseif ($timing === 'upcoming') {
                    $upcoming[] = $p;
                }
            }
        }

        // Sort need_follow_up
        usort($needFollowUp, function ($a, $b) {
            $prioA = $a['need_fu_priority'] ?? 99;
            $prioB = $b['need_fu_priority'] ?? 99;
            if ($prioA !== $prioB) {
                return $prioA <=> $prioB;
            }
            if ($prioA === 1 || $prioA === 2) {
                $timeA = !empty($a['next_follow_up_raw']) ? strtotime($a['next_follow_up_raw']) : (!empty($a['next_follow_up_date']) ? strtotime($a['next_follow_up_date']) : 0);
                $timeB = !empty($b['next_follow_up_raw']) ? strtotime($b['next_follow_up_raw']) : (!empty($b['next_follow_up_date']) ? strtotime($b['next_follow_up_date']) : 0);
                if ($timeA !== $timeB) {
                    return $timeA <=> $timeB;
                }
            }
            if ($prioA === 3) {
                $cA = !empty($a['created_at_raw']) ? strtotime($a['created_at_raw']) : 0;
                $cB = !empty($b['created_at_raw']) ? strtotime($b['created_at_raw']) : 0;
                if ($cA !== $cB) {
                    return $cA <=> $cB;
                }
            }
            $upA = !empty($a['updated_at_raw']) ? strtotime($a['updated_at_raw']) : 0;
            $upB = !empty($b['updated_at_raw']) ? strtotime($b['updated_at_raw']) : 0;
            return $upB <=> $upA;
        });

        // Sort others
        usort($others, function ($a, $b) {
            $prioA = $a['others_priority'] ?? 99;
            $prioB = $b['others_priority'] ?? 99;
            if ($prioA !== $prioB) {
                return $prioA <=> $prioB;
            }
            if ($prioA === 1 || $prioA === 2 || $prioA === 5) {
                $timeA = !empty($a['next_follow_up_raw']) ? strtotime($a['next_follow_up_raw']) : (!empty($a['next_follow_up_date']) ? strtotime($a['next_follow_up_date']) : 0);
                $timeB = !empty($b['next_follow_up_raw']) ? strtotime($b['next_follow_up_raw']) : (!empty($b['next_follow_up_date']) ? strtotime($b['next_follow_up_date']) : 0);
                if ($timeA !== $timeB) {
                    return $timeA <=> $timeB;
                }
            }
            if ($prioA === 3) {
                $cA = !empty($a['created_at_raw']) ? strtotime($a['created_at_raw']) : 0;
                $cB = !empty($b['created_at_raw']) ? strtotime($b['created_at_raw']) : 0;
                if ($cA !== $cB) {
                    return $cA <=> $cB;
                }
            }
            $upA = !empty($a['updated_at_raw']) ? strtotime($a['updated_at_raw']) : 0;
            $upB = !empty($b['updated_at_raw']) ? strtotime($b['updated_at_raw']) : 0;
            return $upB <=> $upA;
        });

        // Sort scheduled
        usort($scheduled, function ($a, $b) {
            $timeA = !empty($a['next_follow_up_raw']) ? strtotime($a['next_follow_up_raw']) : (!empty($a['next_follow_up_date']) ? strtotime($a['next_follow_up_date']) : PHP_INT_MAX);
            $timeB = !empty($b['next_follow_up_raw']) ? strtotime($b['next_follow_up_raw']) : (!empty($b['next_follow_up_date']) ? strtotime($b['next_follow_up_date']) : PHP_INT_MAX);
            if ($timeA !== $timeB) {
                return $timeA <=> $timeB;
            }
            $upA = !empty($a['updated_at_raw']) ? strtotime($a['updated_at_raw']) : 0;
            $upB = !empty($b['updated_at_raw']) ? strtotime($b['updated_at_raw']) : 0;
            return $upB <=> $upA;
        });

        // Sort done
        usort($done, function ($a, $b) {
            $upA = !empty($a['updated_at_raw']) ? strtotime($a['updated_at_raw']) : 0;
            $upB = !empty($b['updated_at_raw']) ? strtotime($b['updated_at_raw']) : 0;
            return $upB <=> $upA;
        });

        $prospects = [
            'need_follow_up'     => $needFollowUp,
            'others'             => $others,
            'scheduled'          => $scheduled,
            'done'               => $done,

            // Aliases
            'bantuan_sales_lain' => $others,
            'terjadwal'          => $scheduled,
            'selesai'            => $done,
            'perlu_follow_up'    => $needFollowUp,

            // Legacy keys
            'uncontacted'        => $uncontacted,
            'today'              => $todayList,
            'overdue'            => $overdue,
            'unscheduled'        => $unscheduled,
            'upcoming'           => $upcoming,
        ];

        $metodeOptions = \App\Models\FollowUp::METODE_OPTIONS;
        $statuses      = \App\Models\Prospek::getActiveStages();

        return view('follow-up.index', compact('prospects', 'metodeOptions', 'statuses'));
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
        
        $actorId = auth()->id();
        if (!$actorId) {
            abort(401, 'Silakan login terlebih dahulu.');
        }

        // Save follow up log - executor is strictly authenticated user, prospect sales_id/owner_id remain untouched
        $catatan = $request->catatan;
        \App\Models\FollowUp::create([
            'prospek_id' => $prospek->id,
            'user_id' => $actorId,
            'metode' => $request->metode ?? 'WhatsApp',
            'hasil' => $request->hasil,
            'tanggal' => now(),
            'catatan' => $catatan,
            'next_follow_up' => $request->next_follow_up,
        ]);

        $oldStatus = $prospek->status;
        $newStatus = $request->status;
        if (strtoupper(trim((string)$oldStatus)) !== strtoupper(trim((string)$newStatus))) {
            \Illuminate\Support\Facades\Gate::authorize('updateStatus', $prospek);

            // Prevent Sales from setting status directly to LUNAS (requires CS / closing payment)
            if (strtoupper($newStatus) === 'LUNAS' && strtolower(auth()->user()->role ?? '') === 'sales') {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['status' => 'Status LUNAS hanya dapat diproses oleh CS setelah pelunasan pembayaran. Sales dapat memilih status CLOSING.']);
            }

            if (strtolower(auth()->user()->role ?? '') === 'cs') {
                $allowedCsStatuses = ['FORMULIR', 'BERKAS', 'LUNAS', 'NO RESPON', 'BATAL DAFTAR', 'BATAL', 'CANCEL'];
                if (!in_array(strtoupper(trim((string)$newStatus)), $allowedCsStatuses)) {
                    return redirect()->back()
                        ->withInput()
                        ->withErrors(['status' => 'CS hanya berwenang mengubah status ke FORMULIR, BERKAS, LUNAS, NO RESPON, atau BATAL DAFTAR.']);
                }
            }

            if (strtoupper($newStatus) === 'LUNAS' && !\App\Services\ProspekService::isClosingValid($prospek)) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['status' => 'Status LUNAS memerlukan verifikasi pembayaran (Beli Formulir & Pembayaran Termin 1) oleh CS.']);
            }

            $stagesMap = \App\Models\Prospek::getDynamicStagesMap();
            $prospek->status = $newStatus;
            $prospek->stage_number = $stagesMap[$newStatus] ?? $prospek->stage_number;
            $prospek->save();

            ProspekTimeline::create([
                'prospek_id' => $prospek->id,
                'user_id' => $actorId,
                'title' => 'Follow Up & Update Status',
                'notes' => 'Follow up via ' . ($request->metode ?? 'WhatsApp') . '. Status diubah dari ' . $oldStatus . ' menjadi ' . $prospek->status . '. Hasil: ' . $request->hasil . '. Catatan: ' . $request->catatan,
                'status_before' => $oldStatus,
                'status_after' => $prospek->status,
                'time' => now(),
            ]);
        } else {
            // Log follow-up even without status change so it appears in history/timeline
            ProspekTimeline::create([
                'prospek_id' => $prospek->id,
                'user_id' => $actorId,
                'title' => 'Follow Up via ' . ($request->metode ?? 'WhatsApp'),
                'notes' => 'Hasil: ' . $request->hasil . '. Catatan: ' . $request->catatan,
                'status_before' => $oldStatus,
                'status_after' => $prospek->status,
                'time' => now(),
            ]);
            $prospek->touch();
        }

        $prospek->increment('active_follow_up_count');
        $prospek->increment('follow_up_count');

        return redirect()->back()->with('success', 'Follow-up berhasil disimpan!');
    }

    public function transaksiStore(Request $request, int $id)
    {
        $prospek = Prospek::findOrFail($id);
        $this->authorize('transaction', $prospek);

        $request->validate([
            'kategori'          => 'nullable|string|in:' . implode(',', \App\Models\Transaksi::KATEGORI_LIST),
            'jenis_items'       => 'nullable|array',
            'jenis_items.*'     => 'string|max:100',
            'nominal_items'     => 'nullable|array',
            'nominal_items.*'   => 'nullable|numeric|min:0|max:9999999999',
            'jenis'             => 'nullable|string|max:100',
            'nominal'           => 'nullable|numeric|min:0|max:9999999999',
            'tanggal'           => 'required|date',
            'notes'             => 'nullable|string|max:1000',
            'metode_pembayaran' => 'nullable|string|in:virtual_account,gopay,dana,shopeepay,tunai,bank_transfer',
            'bank_account_id'   => 'nullable|integer|exists:bank_accounts,id',
            'bank_va'           => 'nullable|string|max:100',
        ], [
            'kategori.required' => 'Kategori transaksi wajib dipilih.',
            'kategori.in'       => 'Kategori transaksi harus salah satu dari: DP Daftar, DP Perlengkapan, Lunas, Keluar.',
            'nominal.max'       => 'Nominal transaksi tidak boleh melebihi batas wajar (maksimal Rp 9.999.999.999).',
            'nominal.min'       => 'Nominal transaksi tidak boleh bernilai negatif.',
        ]);

        $bankAccount = null;
        if ($request->bank_account_id) {
            $bankAccount = \App\Models\BankAccount::find($request->bank_account_id);
        }

        $fullNotes = $request->notes ?? '';
        if ($bankAccount) {
            $bankInfo = "Transfer Bank: {$bankAccount->bank_name} - No. {$bankAccount->account_number} (A/N: {$bankAccount->account_name})";
            $fullNotes = $fullNotes ? "{$bankInfo} | Ref: {$fullNotes}" : $bankInfo;
        } elseif ($request->metode_pembayaran === 'virtual_account' && $request->bank_va) {
            $vaInfo = "Virtual Account: {$request->bank_va}";
            $fullNotes = $fullNotes ? "{$vaInfo} | Ref: {$fullNotes}" : $vaInfo;
        }

        // Periksa peran penginput transaksi
        $userRole = strtolower(auth()->user()->role ?? '');
        $isCsOrBackoffice = in_array($userRole, ['cs', 'admin', 'hm']);

        // Jika CS/Admin/HM yang input: langsung VERIFIED dan masuk riwayat pembayaran
        // Jika Sales yang input: PENDING (harus diverifikasi CS), bahkan jika kategori Lunas (Requirement L3)
        $paymentStatus = $isCsOrBackoffice
            ? \App\Models\Transaksi::STATUS_VERIFIED
            : \App\Models\Transaksi::STATUS_PENDING;

        // Tentukan komponen transaksi yang dipilih
        $itemsToCreate = [];
        if ($request->has('jenis_items') && is_array($request->jenis_items) && count($request->jenis_items) > 0) {
            foreach ($request->jenis_items as $j) {
                $nom = (float) $request->input("nominal_items.{$j}", ($j === 'Gedung' ? 0 : 0));
                $itemsToCreate[] = [
                    'jenis'   => $j,
                    'nominal' => $nom,
                ];
            }
        } elseif ($request->filled('jenis')) {
            $itemsToCreate[] = [
                'jenis'   => $request->jenis,
                'nominal' => (float) ($request->nominal ?? 0),
            ];
        } else {
            return redirect()->back()->withErrors(['jenis_items' => 'Harap pilih minimal satu jenis komponen transaksi.']);
        }

        // Validasi backend: Mencegah pengajuan ulang atas komponen transaksi yang sudah lunas atau sedang menunggu verifikasi
        $existingTransaksis = $prospek->transaksis()->get();
        $aliasesMap = [
            'Pendaftaran'  => ['Pendaftaran', 'Beli Formulir'],
            'Perlengkapan' => ['Perlengkapan'],
            'Gedung'       => ['Gedung'],
            'DP/Konversi'  => ['DP/Konversi', 'DP', 'Pembayaran Termin 1'],
            'UKT'          => ['UKT'],
        ];

        foreach ($itemsToCreate as $item) {
            $itemJenis = $item['jenis'];
            $checkAliases = [$itemJenis];
            foreach ($aliasesMap as $stdKey => $aliases) {
                if ($stdKey === $itemJenis || in_array($itemJenis, $aliases)) {
                    $checkAliases = array_unique(array_merge($checkAliases, $aliases));
                }
            }

            // 1. Cek apakah komponen ini sudah pernah lunas / diverifikasi sah
            $alreadyVerified = $existingTransaksis->first(function ($t) use ($checkAliases) {
                return in_array($t->jenis, $checkAliases) && $t->payment_status === \App\Models\Transaksi::STATUS_VERIFIED;
            });
            if ($alreadyVerified) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['jenis_items' => "Transaksi '{$itemJenis}' untuk prospek ini sudah lunas & diverifikasi sah. Tidak dapat diajukan kembali sebagai tagihan baru."]);
            }

            // 2. Cek apakah komponen ini sedang dalam antrean pending verifikasi CS
            $alreadyPending = $existingTransaksis->first(function ($t) use ($checkAliases) {
                return in_array($t->jenis, $checkAliases) && $t->payment_status === \App\Models\Transaksi::STATUS_PENDING;
            });
            if ($alreadyPending) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['jenis_items' => "Transaksi '{$itemJenis}' sedang menunggu proses verifikasi oleh CS. Harap tunggu verifikasi selesai sebelum mengajukan kembali."]);
            }
        }

        $totalNominal = 0;
        $createdJenisNames = [];
        $createdTransactions = [];

        DB::transaction(function () use (
            $prospek, $request, $fullNotes, $paymentStatus, $isCsOrBackoffice,
            $itemsToCreate, &$totalNominal, &$createdJenisNames, &$createdTransactions
        ) {
            foreach ($itemsToCreate as $item) {
                $itemJenis = $item['jenis'];
                $itemNominal = $item['nominal'];
                $totalNominal += $itemNominal;
                $createdJenisNames[] = $itemJenis;

                $itemNotes = $fullNotes;
                $isItemGedungFree = ($itemJenis === 'Gedung' && $itemNominal == 0);
                if ($isItemGedungFree) {
                    $itemNotes = $itemNotes ? "Gedung (FREE) | {$itemNotes}" : 'Gedung (FREE)';
                }

                $itemPaymentStatus = $isItemGedungFree ? \App\Models\Transaksi::STATUS_VERIFIED : $paymentStatus;

                $transaksiData = [
                    'prospek_id'        => $prospek->id,
                    'user_id'           => auth()->id(),
                    'jenis'             => $itemJenis,
                    'kategori'          => $request->kategori ?: 'Lunas',
                    'nominal'           => $itemNominal,
                    'tanggal'           => $request->tanggal,
                    'notes'             => $itemNotes,
                    'metode_pembayaran' => $request->metode_pembayaran,
                    'payment_status'    => $itemPaymentStatus,
                ];

                if ($itemPaymentStatus === \App\Models\Transaksi::STATUS_VERIFIED) {
                    $transaksiData['verified_by'] = auth()->id();
                    $transaksiData['verified_at'] = now();
                }

                $createdTransactions[] = \App\Models\Transaksi::create($transaksiData);
            }

            $oldStatus = $prospek->status;

            // Update status pipeline jika relevan
            if ($paymentStatus === \App\Models\Transaksi::STATUS_PENDING) {
                if ($prospek->stage_number < 6) {
                    $prospek->status = 'BERKAS';
                    $prospek->stage_number = 6;
                }
            } elseif (\App\Services\ProspekService::isClosingValid($prospek->fresh())) {
                $prospek->status = 'LUNAS';
                $prospek->stage_number = 7;
            } elseif (in_array('Pendaftaran', $createdJenisNames) || in_array('Beli Formulir', $createdJenisNames)) {
                if ($prospek->stage_number < 5) {
                    $prospek->status = 'FORMULIR';
                    $prospek->stage_number = 5;
                }
            }

            $prospek->save();

            $jenisSummary = implode(', ', $createdJenisNames);
            $metodeLabel = \App\Models\Transaksi::METODE_PEMBAYARAN[$request->metode_pembayaran] ?? $request->metode_pembayaran;
            $nominalText = 'Rp ' . number_format($totalNominal, 0, ',', '.');
            $timelineNotes = "Komponen: {$jenisSummary} | Kategori: {$request->kategori} | Total: {$nominalText}"
                . ($metodeLabel ? ' | Metode: ' . $metodeLabel : '')
                . ($paymentStatus === \App\Models\Transaksi::STATUS_PENDING ? ' | Status: Menunggu Verifikasi CS' : ' | Status: Terverifikasi Sah')
                . ($fullNotes ? ' | ' . $fullNotes : '');

            ProspekTimeline::create([
                'prospek_id'    => $prospek->id,
                'user_id'       => auth()->id(),
                'title'         => 'Input Transaksi (' . $request->kategori . '): ' . $jenisSummary . ($paymentStatus === \App\Models\Transaksi::STATUS_VERIFIED && $isCsOrBackoffice ? ' (Terverifikasi CS)' : ''),
                'notes'         => $timelineNotes,
                'status_before' => $oldStatus,
                'status_after'  => $prospek->status,
                'time'          => now(),
            ]);
        });

        $nominalText = 'Rp ' . number_format($totalNominal, 0, ',', '.');
        $jenisSummary = implode(', ', $createdJenisNames);

        // Jika diinput oleh CS dan terverifikasi, kirim notifikasi ke Sales pemilik prospek
        if ($isCsOrBackoffice && $paymentStatus === \App\Models\Transaksi::STATUS_VERIFIED) {
            $sales = $prospek->sales
                ?? ($prospek->sales_id ? \App\Models\User::find($prospek->sales_id) : null)
                ?? ($prospek->owner_id ? \App\Models\User::find($prospek->owner_id) : null);

            $isClosing = $prospek->status === 'LUNAS';
            $csName = auth()->user()->name;

            if ($sales && $sales->id !== auth()->id()) {
                $notifMsg = $isClosing
                    ? "Pembayaran {$nominalText} ({$jenisSummary} - {$request->kategori}) untuk '{$prospek->name}' telah diinput & diverifikasi oleh CS {$csName}. Prospek dinyatakan LUNAS (Closing)."
                    : "Pembayaran {$nominalText} ({$jenisSummary} - {$request->kategori}) untuk '{$prospek->name}' telah diinput & diverifikasi oleh CS {$csName}.";

                $sales->notify(new \App\Notifications\CrmActivityNotification(
                    title: $isClosing ? 'Closing Terverifikasi!' : 'Pembayaran Terverifikasi!',
                    message: $notifMsg,
                    type: 'success',
                    link: '/prospek',
                    icon: '',
                    senderName: $csName,
                    senderRole: auth()->user()->role,
                    action: 'pembayaran_diverifikasi'
                ));

                if ($isClosing) {
                    try {
                        $targetService = app(\App\Services\TargetAchievementService::class);
                        $targetService->checkAndNotifyTargetStatus($sales);
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning("Gagal evaluasi target setelah verifikasi CS: " . $e->getMessage());
                    }

                    $spv = $sales->supervisor ?? ($prospek->wilayah_id ? \App\Models\User::where('role', 'SPV')->where('wilayah_id', $prospek->wilayah_id)->first() : null);
                    if ($spv && $spv->id !== auth()->id()) {
                        $spv->notify(new \App\Notifications\CrmActivityNotification(
                            title: 'Closing Mahasiswa Baru!',
                            message: "Pembayaran {$nominalText} untuk '{$prospek->name}' telah diverifikasi oleh CS {$csName}. Prospek dari Sales {$sales->name} sah Closing (LUNAS).",
                            type: 'success',
                            link: '/spv/pipeline',
                            icon: '',
                            senderName: $csName,
                            senderRole: auth()->user()->role,
                            action: 'closing_verified_spv'
                        ));
                    }
                }
            }
        }

        $successMsg = $paymentStatus === \App\Models\Transaksi::STATUS_PENDING
            ? 'Transaksi (' . $request->kategori . ') berhasil disimpan! Menunggu verifikasi CS.'
            : 'Transaksi (' . $request->kategori . ') berhasil disimpan dan terverifikasi!';

        return redirect()->back()->with('success', $successMsg);
    }

    /**
     * Halaman Pipeline Board.
     */
    public function pipelineIndex(): View
    {
        $prospects = $this->getDbProspects();
        $pipelineStages = \App\Models\Prospek::getPipelineStages();

        return view('pipeline.index', compact('prospects', 'pipelineStages'));
    }

    /**
     * Halaman Target & Performa (Data Akumulasi).
     */
    public function performaIndex(): View
    {
        $user = auth()->user();
        $targetService = app(\App\Services\SalesTargetService::class);
        $targetAchievementService = app(\App\Services\TargetAchievementService::class);

        $selectedPeriode = request('periode', 'bulan-ini');
        $periodInfo = $targetAchievementService->resolvePeriod($selectedPeriode);
        $start = $periodInfo['start'];
        $end = $periodInfo['end'];
        $isSemua = $selectedPeriode === 'semua';

        if ($user && strtolower($user->role) === 'cs') {
            $salesUsers = \App\Models\User::where('id', $user->id)->get();
        } elseif ($user && strtolower($user->role) === 'spv') {
            $salesUsers = $user->teamSales()->get();
        } elseif ($user && strtolower($user->role) === 'sales') {
            $salesUsers = collect([$user]);
        } else {
            $salesUsers = \App\Models\User::where('role', 'Sales')->get();
        }

        $team = $salesUsers->map(function ($s) use ($targetService, $targetAchievementService, $periodInfo, $start, $end, $isSemua) {
            $isCs = strtolower($s->role) === 'cs';
            $activeTarget = $targetService->getActiveTarget($s);

            $rawTarget = $activeTarget ? (int)$activeTarget->target_lunas : 0;
            if ($rawTarget <= 0 && $activeTarget) {
                $rawTarget = (int)$activeTarget->target_kontak;
            }
            $targetAmount = $targetAchievementService->prorateTarget($rawTarget, $periodInfo, $activeTarget?->tipe_periode);

            $userField = $isCs ? 'cs_id' : 'sales_id';
            $prospekQuery = \App\Models\Prospek::where($userField, $s->id);

            if (!$isSemua) {
                $totalProspek = (clone $prospekQuery)->whereBetween('created_at', [$start, $end])->count();
                $closing = (clone $prospekQuery)->where('status', 'LUNAS')->whereBetween('updated_at', [$start, $end])->count();
                $lost = (clone $prospekQuery)->where('status', 'DINGIN')->whereBetween('updated_at', [$start, $end])->count();
                $followUp = \App\Models\FollowUp::where('user_id', $s->id)->whereBetween('tanggal', [$start, $end])->count();
            } else {
                $totalProspek = (clone $prospekQuery)->count();
                $closing = (clone $prospekQuery)->where('status', 'LUNAS')->count();
                $lost = (clone $prospekQuery)->where('status', 'DINGIN')->count();
                $followUp = \App\Models\FollowUp::where('user_id', $s->id)->count();
            }

            $achievement = $targetAmount > 0 ? min(100, round(($closing / $targetAmount) * 100)) : ($closing > 0 ? 100 : 0);

            return [
                'id'          => $s->id,
                'name'        => $s->name,
                'role'        => $s->role,
                'target'      => $targetAmount,
                'prospects'   => $totalProspek,
                'follow_up'   => $followUp,
                'closing'     => $closing,
                'lost'        => $lost,
                'LUNAS'       => $closing,
                'DINGIN'      => $lost,
                'achievement' => $achievement,
                'avatar'      => strtoupper(substr($s->name, 0, 2)),
                'status'      => ($targetAmount > 0 && $closing >= $targetAmount) ? 'Target Achieved' : ($achievement >= 40 ? 'On Track' : 'Progres Berjalan')
            ];
        })->toArray();

        $totalTarget = count($team) > 0 ? array_sum(array_column($team, 'target')) : 0;
        $totalRealisasi = array_sum(array_column($team, 'closing'));

        $breakdownQuery = \App\Models\Prospek::where('status', 'LUNAS');
        if (!$isSemua) {
            $breakdownQuery->whereBetween('updated_at', [$start, $end]);
        }
        $breakdown = [
            'Sekolah'   => (clone $breakdownQuery)->where('type', 'Sekolah')->count(),
            'Corporate' => (clone $breakdownQuery)->where('type', 'Corporate')->count(),
            'Individu'  => (clone $breakdownQuery)->where('type', 'Individu')->count(),
        ];

        $summary = [
            'target'        => $totalTarget,
            'realisasi'     => $totalRealisasi,
            'achievement'   => $totalTarget > 0 ? round(($totalRealisasi / $totalTarget) * 100) : ($totalRealisasi > 0 ? 100 : 0),
            'sisa_target'   => max(0, $totalTarget - $totalRealisasi),
            'breakdown'     => $breakdown,
            'periode_label' => $periodInfo['label'],
        ];

        $wilayahId = request('wilayah_id');
        $targetAchievementData = $targetAchievementService->getDashboardTargetData($user, $selectedPeriode, $wilayahId);
        $ta = $targetAchievementService->getActiveTa();

        return view('performa.index', compact('team', 'summary', 'targetAchievementData', 'selectedPeriode', 'ta'));
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
        $tkTitle = \App\Models\MasterData::where('type', 'setting')->where('kode', 'TK_TITLE')->first();
        $tkSubtitle = \App\Models\MasterData::where('type', 'setting')->where('kode', 'TK_SUBTITLE')->first();
        $tkImage = \App\Models\MasterData::where('type', 'setting')->where('kode', 'TK_IMAGE')->first();

        $tkCorpTitle = \App\Models\MasterData::where('type', 'setting')->where('kode', 'TK_CORP_TITLE')->first();
        $tkCorpSubtitle = \App\Models\MasterData::where('type', 'setting')->where('kode', 'TK_CORP_SUBTITLE')->first();
        $tkCorpImage = \App\Models\MasterData::where('type', 'setting')->where('kode', 'TK_CORP_IMAGE')->first();

        return view('profil.pengaturan', compact('tkTitle', 'tkSubtitle', 'tkImage', 'tkCorpTitle', 'tkCorpSubtitle', 'tkCorpImage'));
    }
}
