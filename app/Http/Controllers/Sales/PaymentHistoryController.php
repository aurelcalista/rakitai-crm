<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentHistoryController extends Controller
{
    /**
     * Tampilkan halaman Riwayat Pembayaran dinamis untuk Sales dan tim.
     */
    public function index(Request $request): View
    {
        $user = auth()->user();
        $role = $user->role ?? 'Sales';

        // Base Query Transaksi dengan eager loading relasi lengkap
        $query = Transaksi::with([
            'prospek.sekolah',
            'prospek.perusahaan',
            'prospek.prodi',
            'prospek.sales',
            'user',
            'verifier',
            'rejecter'
        ]);

        // Role Scoping Dinamis
        if ($role === 'Sales') {
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereHas('prospek', function ($qp) use ($user) {
                      $qp->where('sales_id', $user->id)
                         ->orWhere('owner_id', $user->id);
                  });
            });
        } elseif ($role === 'SPV') {
            $teamSalesIds = array_unique(array_merge([$user->id], $user->teamMemberIds()));
            $query->where(function ($q) use ($teamSalesIds) {
                $q->whereIn('user_id', $teamSalesIds)
                  ->orWhereHas('prospek', function ($qp) use ($teamSalesIds) {
                      $qp->whereIn('sales_id', $teamSalesIds)
                         ->orWhereIn('owner_id', $teamSalesIds);
                  });
            });
        }

        // Filter: Specific Sales (untuk SPV/Admin/HM)
        if ($request->filled('sales_id') && $request->sales_id !== 'all') {
            $targetSalesId = (int)$request->sales_id;
            $query->where(function ($q) use ($targetSalesId) {
                $q->where('user_id', $targetSalesId)
                  ->orWhereHas('prospek', function ($qp) use ($targetSalesId) {
                      $qp->where('sales_id', $targetSalesId)
                         ->orWhere('owner_id', $targetSalesId);
                  });
            });
        }

        // Filter: Status Pembayaran
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('payment_status', $request->status);
        }

        // Filter: Jenis Transaksi
        if ($request->filled('jenis') && $request->jenis !== 'all') {
            $query->where('jenis', $request->jenis);
        }

        // Filter: Metode Pembayaran
        if ($request->filled('metode') && $request->metode !== 'all') {
            $query->where('metode_pembayaran', $request->metode);
        }

        // Filter: Pencarian Universal (Nama Prospek, PIC, WhatsApp, Catatan, Ref ID)
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('notes', 'like', "%{$search}%")
                  ->orWhere('id', 'like', "%{$search}%")
                  ->orWhereHas('prospek', function ($qp) use ($search) {
                      $qp->where('name', 'like', "%{$search}%")
                         ->orWhere('pic', 'like', "%{$search}%")
                         ->orWhere('whatsapp', 'like', "%{$search}%");
                  })
                  ->orWhereHas('user', function ($qu) use ($search) {
                      $qu->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Filter: Rentang Tanggal Transaksi
        if ($request->filled('start_date')) {
            $query->whereDate('tanggal', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('tanggal', '<=', $request->end_date);
        }

        // Base Scope untuk Statistik Keseluruhan
        $baseStatQuery = Transaksi::query();
        if ($role === 'Sales') {
            $baseStatQuery->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereHas('prospek', function ($qp) use ($user) {
                      $qp->where('sales_id', $user->id)
                         ->orWhere('owner_id', $user->id);
                  });
            });
        } elseif ($role === 'SPV') {
            $teamSalesIds = array_unique(array_merge([$user->id], $user->teamMemberIds()));
            $baseStatQuery->where(function ($q) use ($teamSalesIds) {
                $q->whereIn('user_id', $teamSalesIds)
                  ->orWhereHas('prospek', function ($qp) use ($teamSalesIds) {
                      $qp->whereIn('sales_id', $teamSalesIds)
                         ->orWhereIn('owner_id', $teamSalesIds);
                  });
            });
        }

        // Hitung Metrik Statistik Dinamis
        $totalVerifiedAmount = (clone $baseStatQuery)->where('payment_status', Transaksi::STATUS_VERIFIED)->sum('nominal');
        $totalPendingAmount  = (clone $baseStatQuery)->where('payment_status', Transaksi::STATUS_PENDING)->sum('nominal');
        $totalRejectedAmount = (clone $baseStatQuery)->where('payment_status', Transaksi::STATUS_REJECTED)->sum('nominal');

        $countVerified = (clone $baseStatQuery)->where('payment_status', Transaksi::STATUS_VERIFIED)->count();
        $countPending  = (clone $baseStatQuery)->where('payment_status', Transaksi::STATUS_PENDING)->count();
        $countRejected = (clone $baseStatQuery)->where('payment_status', Transaksi::STATUS_REJECTED)->count();
        $countTotal    = (clone $baseStatQuery)->count();

        // Total nominal dari data hasil filter saat ini
        $filteredTotalAmount = (clone $query)->sum('nominal');
        $filteredCount       = (clone $query)->count();

        // Paginate hasil query
        $transaksis = $query->orderBy('tanggal', 'desc')
                            ->orderBy('id', 'desc')
                            ->paginate(15)
                            ->withQueryString();

        // Opsi Dinamis untuk Filter Dropdown dari Database
        $activeBankAccounts = BankAccount::where('is_active', true)->orderBy('bank_name')->get();
        
        $distinctMetode = Transaksi::distinct()
            ->whereNotNull('metode_pembayaran')
            ->where('metode_pembayaran', '!=', '')
            ->pluck('metode_pembayaran')
            ->toArray();
        $defaultMethods = ['bank_transfer', 'virtual_account', 'gopay', 'dana', 'shopeepay', 'tunai'];
        $allMetode = array_unique(array_merge($defaultMethods, $distinctMetode));

        $distinctJenis = Transaksi::distinct()
            ->whereNotNull('jenis')
            ->where('jenis', '!=', '')
            ->pluck('jenis')
            ->toArray();
        $defaultJenis = ['Beli Formulir', 'Pembayaran Termin 1', 'Pembayaran Termin 2', 'Pelunasan'];
        $allJenis = array_unique(array_merge($defaultJenis, $distinctJenis));

        // List Sales untuk filter (jika bukan Sales individual)
        $salesList = collect();
        if ($role === 'SPV') {
            $teamMemberIds = $user->teamMemberIds();
            $salesList = User::whereIn('id', $teamMemberIds)->orWhere('id', $user->id)->get(['id', 'name']);
        } elseif (in_array($role, ['Admin', 'HM', 'CS'])) {
            $salesList = User::whereIn('role', ['Sales', 'SPV'])->orderBy('name')->get(['id', 'name', 'role']);
        }

        $stats = [
            'total_verified'        => $totalVerifiedAmount,
            'total_pending'         => $totalPendingAmount,
            'total_rejected'        => $totalRejectedAmount,
            'count_verified'        => $countVerified,
            'count_pending'         => $countPending,
            'count_rejected'        => $countRejected,
            'count_total'           => $countTotal,
            'filtered_total_amount' => $filteredTotalAmount,
            'filtered_count'        => $filteredCount,
        ];

        return view('sales.pembayaran.index', compact(
            'transaksis',
            'stats',
            'allJenis',
            'allMetode',
            'activeBankAccounts',
            'salesList'
        ));
    }
}
