<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Kunjungan;
use App\Models\Sekolah;
use App\Models\Perusahaan;
use App\Models\Prodi;
use App\Models\Wilayah;
use App\Models\Target;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $request->session()->put('user_role', 'admin');

        $totalUsers = User::count();
        $stats = [
            'total_users'      => $totalUsers,
            'total_hm'         => User::where('role', 'HM')->count(),
            'total_spv'        => User::where('role', 'SPV')->count(),
            'total_sales'      => User::where('role', 'Sales')->count(),
            'total_cs'         => User::where('role', 'CS')->count(),
            'total_admin'      => User::where('role', 'Admin')->count(),
            'total_kunjungan'  => Kunjungan::count(),
            'total_sekolah'    => Sekolah::count(),
            'total_perusahaan' => Perusahaan::count(),
            'total_prodi'      => Prodi::count(),
            'total_wilayah'    => Wilayah::count(),
        ];

        // Slide Items for User Roles
        $userSlides = [
            [
                'title'       => 'Sales Inbound',
                'role'        => 'Sales',
                'count'       => $stats['total_sales'],
                'pct'         => $totalUsers > 0 ? round(($stats['total_sales'] / $totalUsers) * 100) : 0,
                'color'       => 'blue',
                'badge'       => 'Sales Inbound',
                'description' => 'Tim penanganan lead & audiensi prospect',
            ],
            [
                'title'       => 'Customer Service',
                'role'        => 'CS',
                'count'       => $stats['total_cs'],
                'pct'         => $totalUsers > 0 ? round(($stats['total_cs'] / $totalUsers) * 100) : 0,
                'color'       => 'teal',
                'badge'       => 'Customer Service',
                'description' => 'Verifikasi formulir & pendaftaran siswa',
            ],
            [
                'title'       => 'Supervisor Marketing',
                'role'        => 'SPV',
                'count'       => $stats['total_spv'],
                'pct'         => $totalUsers > 0 ? round(($stats['total_spv'] / $totalUsers) * 100) : 0,
                'color'       => 'indigo',
                'badge'       => 'Supervisor',
                'description' => 'Pengawasan performa & alokasi target sales',
            ],
            [
                'title'       => 'Head Marketing',
                'role'        => 'HM',
                'count'       => $stats['total_hm'],
                'pct'         => $totalUsers > 0 ? round(($stats['total_hm'] / $totalUsers) * 100) : 0,
                'color'       => 'purple',
                'badge'       => 'Head Marketing',
                'description' => 'Strategi kampanye & pencapaian target kampus',
            ],
            [
                'title'       => 'Administrator UCIC',
                'role'        => 'Admin',
                'count'       => $stats['total_admin'],
                'pct'         => $totalUsers > 0 ? round(($stats['total_admin'] / $totalUsers) * 100) : 0,
                'color'       => 'slate',
                'badge'       => 'Super Administrator',
                'description' => 'Pengelolaan sistem, master data & pengguna',
            ],
        ];

        $recentUsers = User::latest()->take(6)->get();

        $activeTargets = Target::with('sales')->where('status', 'Aktif')->latest()->limit(5)->get();

        $recentActivities = [
            ['user' => 'Administrator UCIC', 'role' => 'Admin', 'action' => 'Akses Dashboard Admin', 'target' => 'Admin Panel System', 'time' => now()->format('H:i')],
            ['user' => 'Aurel Calista', 'role' => 'Sales', 'action' => 'Update Status Prospek', 'target' => 'SMK Negeri 1 Cirebon', 'time' => '09:30'],
            ['user' => 'Dina Marlina', 'role' => 'CS', 'action' => 'Takeover Penugasan', 'target' => 'SMA Negeri 2 Majalengka', 'time' => '08:45'],
            ['user' => 'Hendra Setiawan', 'role' => 'SPV', 'action' => 'Export Laporan Rekap', 'target' => 'Periode September', 'time' => 'Kemarin'],
        ];

        return view('admin.dashboard', compact('stats', 'userSlides', 'recentUsers', 'activeTargets', 'recentActivities'));
    }
}
