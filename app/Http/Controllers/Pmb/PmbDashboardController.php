<?php

namespace App\Http\Controllers\Pmb;

use App\Http\Controllers\Controller;
use App\Models\Prodi;
use App\Models\Prospek;
use App\Models\Transaksi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PmbDashboardController extends Controller
{
    public function index(Request $request): View
    {
        // 1. Pendaftar Statistics
        $totalPendaftar = Prospek::count();
        $belumIsiForm = Prospek::where(function ($q) {
            $q->where('status_formulir', 'Belum Isi')
              ->orWhereNull('status_formulir');
        })->count();
        $sudahIsiForm = Prospek::where('status_formulir', 'Sudah Isi')->count();

        $belumUploadBerkas = Prospek::where(function ($q) {
            $q->where('status_berkas', 'Belum Upload')
              ->orWhereNull('status_berkas');
        })->count();
        $pendingBerkas = Prospek::where('status_berkas', 'Pending')->count();
        $sudahUploadBerkas = Prospek::where('status_berkas', 'Sudah Upload')->count();

        $belumUjian = Prospek::where(function ($q) {
            $q->where('status_ujian', 'Belum Mengerjakan')
              ->orWhereNull('status_ujian');
        })->count();
        $sudahUjian = Prospek::where('status_ujian', 'Sudah Mengerjakan')->count();

        // 2. Pembayaran Statistics
        $transaksiVerified = Transaksi::whereIn('payment_status', [Transaksi::STATUS_VERIFIED, 'Paid', 'paid', 'verified']);
        $totalNominalDiterima = (float) $transaksiVerified->sum('nominal');
        $countBerhasil = $transaksiVerified->count();

        $countPending = Transaksi::whereIn('payment_status', [Transaksi::STATUS_PENDING, 'Pending', 'pending'])->count();
        $countGagal = Transaksi::whereIn('payment_status', [Transaksi::STATUS_REJECTED, 'Failed', 'failed', 'rejected', 'batal'])->count();

        // 3. Distribusi Program Studi
        $prodiStats = Prodi::withCount('prospeks')
            ->orderByDesc('prospeks_count')
            ->take(6)
            ->get();

        // 4. Distribusi Gelombang
        $gelombangStats = Prospek::select('gelombang', DB::raw('count(*) as total'))
            ->groupBy('gelombang')
            ->orderByDesc('total')
            ->get();

        // 5. Data Pendaftar Terbaru
        $recentPendaftar = Prospek::with(['prodi', 'prodiPilihan2'])
            ->latest()
            ->take(6)
            ->get();

        // 6. Transaksi Terbaru
        $recentTransaksi = Transaksi::with(['prospek'])
            ->latest('tanggal')
            ->take(6)
            ->get();

        return view('pmb.dashboard', compact(
            'totalPendaftar',
            'belumIsiForm',
            'sudahIsiForm',
            'belumUploadBerkas',
            'pendingBerkas',
            'sudahUploadBerkas',
            'belumUjian',
            'sudahUjian',
            'totalNominalDiterima',
            'countBerhasil',
            'countPending',
            'countGagal',
            'prodiStats',
            'gelombangStats',
            'recentPendaftar',
            'recentTransaksi'
        ));
    }
}
