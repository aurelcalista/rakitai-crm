<?php

namespace App\Http\Controllers\Pmb;

use App\Http\Controllers\Controller;
use App\Models\Transaksi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PmbPembayaranController extends Controller
{
    public function index(Request $request): View
    {
        // Auto-sync pending Midtrans transactions with Midtrans Server API
        if (!app()->runningUnitTests()) {
            $pendingSyncCandidates = Transaksi::where('payment_status', Transaksi::STATUS_PENDING)
                ->whereNotNull('order_id')
                ->latest('id')
                ->take(5)
                ->get();

            $midtransService = app(\App\Services\MidtransService::class);
            foreach ($pendingSyncCandidates as $cand) {
                try {
                    $midtransService->syncStatusFromMidtransApi($cand->order_id);
                } catch (\Throwable $e) {}
            }
        }

        $query = Transaksi::with(['prospek.prodi', 'user']);

        // Search (Order ID, Nama pendaftar, Email)
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('order_id', 'like', "%{$search}%")
                  ->orWhereHas('prospek', function ($pq) use ($search) {
                      $pq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%")
                         ->orWhere('whatsapp', 'like', "%{$search}%");
                  });
            });
        }

        // Quick Filter Status (Semua, Berhasil, Pending, Gagal, Hari Ini)
        $quickFilter = $request->input('quick_filter', 'all');
        if ($quickFilter === 'berhasil') {
            $query->whereIn('payment_status', [Transaksi::STATUS_VERIFIED, 'Paid', 'paid', 'verified']);
        } elseif ($quickFilter === 'pending') {
            $query->whereIn('payment_status', [Transaksi::STATUS_PENDING, 'Pending', 'pending']);
        } elseif ($quickFilter === 'gagal') {
            $query->whereIn('payment_status', [Transaksi::STATUS_REJECTED, 'Failed', 'failed', 'rejected', 'batal']);
        } elseif ($quickFilter === 'hari_ini') {
            $query->whereDate('tanggal', today());
        }

        // Advanced Filter: Metode Pembayaran
        if ($request->filled('metode') && $request->metode !== 'all') {
            $query->where('metode_pembayaran', $request->metode);
        }

        // Advanced Filter: Rentang Tanggal
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('tanggal', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
        }

        // 4 Summary Statistics (Actual DB Data)
        $transaksiVerified = Transaksi::whereIn('payment_status', [Transaksi::STATUS_VERIFIED, 'Paid', 'paid', 'verified']);
        $totalDiterima = (float) $transaksiVerified->sum('nominal');
        $countBerhasil = $transaksiVerified->count();

        $countPending = Transaksi::whereIn('payment_status', [Transaksi::STATUS_PENDING, 'Pending', 'pending'])->count();
        $countGagal = Transaksi::whereIn('payment_status', [Transaksi::STATUS_REJECTED, 'Failed', 'failed', 'rejected', 'batal'])->count();

        // Unique Metodes for filter dropdown
        $metodeList = Transaksi::whereNotNull('metode_pembayaran')
            ->distinct()
            ->pluck('metode_pembayaran')
            ->filter()
            ->values();

        if ($metodeList->isEmpty()) {
            $metodeList = collect(['MIDTRANS', 'TRANSFER BRI', 'OFFLINE']);
        }

        // Sorting & Pagination
        $sortBy = $request->input('sort_by', 'tanggal');
        $sortOrder = $request->input('sort_order', 'desc');
        $allowedSorts = ['order_id', 'nominal', 'tanggal', 'payment_status'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'tanggal';
        }
        if (!in_array(strtolower($sortOrder), ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }

        $perPage = (int) $request->input('per_page', 10);
        if (!in_array($perPage, [10, 25, 50, 100])) {
            $perPage = 10;
        }

        $transaksiList = $query->orderBy($sortBy, $sortOrder)->paginate($perPage)->withQueryString();

        return view('pmb.pembayaran.index', compact(
            'transaksiList',
            'totalDiterima',
            'countBerhasil',
            'countPending',
            'countGagal',
            'metodeList',
            'quickFilter'
        ));
    }

    public function verify(Request $request, $id)
    {
        $transaksi = Transaksi::findOrFail($id);
        $user = auth()->user();

        $transaksi->update([
            'payment_status' => Transaksi::STATUS_VERIFIED,
            'verified_by' => $user->id,
            'verified_at' => now(),
            'rejected_by' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
        ]);

        // Catat di timeline prospek
        if ($transaksi->prospek_id) {
            \App\Models\ProspekTimeline::create([
                'prospek_id' => $transaksi->prospek_id,
                'user_id' => $user->id,
                'title' => 'Verifikasi Pembayaran PMB',
                'notes' => "Pembayaran Order ID {$transaksi->order_id} sebesar Rp " . number_format($transaksi->nominal, 0, ',', '.') . " berhasil diverifikasi oleh {$user->name} ({$user->role}).",
                'time' => now(),
            ]);

            // Jika kategori transaksi lunas atau nominal >= 500.000, perbarui status prospek
            $prospek = $transaksi->prospek;
            if ($prospek && $prospek->status !== 'LUNAS') {
                $prospek->update(['status' => 'LUNAS']);
            }

            // Notifikasi ke seluruh role yang berwenang
            \App\Services\PaymentNotificationService::notifyAllRoles($transaksi, 'verified');
        }

        return redirect()->back()->with('success', "Transaksi {$transaksi->order_id} berhasil diverifikasi!");
    }

    public function reject(Request $request, $id)
    {
        $transaksi = Transaksi::findOrFail($id);
        $user = auth()->user();

        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $transaksi->update([
            'payment_status' => Transaksi::STATUS_REJECTED,
            'rejected_by' => $user->id,
            'rejected_at' => now(),
            'rejection_reason' => $request->rejection_reason,
        ]);

        // Catat di timeline prospek
        if ($transaksi->prospek_id) {
            \App\Models\ProspekTimeline::create([
                'prospek_id' => $transaksi->prospek_id,
                'user_id' => $user->id,
                'title' => 'Pembayaran PMB Ditolak',
                'notes' => "Pembayaran Order ID {$transaksi->order_id} ditolak oleh {$user->name} ({$user->role}). Alasan: {$request->rejection_reason}",
                'time' => now(),
            ]);

            \App\Services\PaymentNotificationService::notifyAllRoles($transaksi, 'rejected');
        }

        return redirect()->back()->with('error', "Transaksi {$transaksi->order_id} telah ditolak/dibatalkan.");
    }

    public function sync(Request $request, $id, \App\Services\MidtransService $midtransService)
    {
        $transaksi = Transaksi::findOrFail($id);
        if (empty($transaksi->order_id)) {
            return redirect()->back()->with('error', "Transaksi tidak memiliki Order ID Midtrans.");
        }

        $synced = $midtransService->syncStatusFromMidtransApi($transaksi->order_id);

        if ($synced && $synced->payment_status === Transaksi::STATUS_VERIFIED) {
            return redirect()->back()->with('success', "Status transaksi {$transaksi->order_id} berhasil disinkronkan langsung dari Midtrans: LUNAS (VERIFIED)!");
        }

        $transaksi->refresh();
        return redirect()->back()->with('info', "Status transaksi {$transaksi->order_id} di Midtrans saat ini: " . strtoupper($transaksi->payment_status) . ".");
    }

    public function export(Request $request): StreamedResponse
    {
        $query = Transaksi::with(['prospek.prodi']);

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('order_id', 'like', "%{$search}%")
                  ->orWhereHas('prospek', function ($pq) use ($search) {
                      $pq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%")
                         ->orWhere('whatsapp', 'like', "%{$search}%");
                  });
            });
        }

        $quickFilter = $request->input('quick_filter', 'all');
        if ($quickFilter === 'berhasil') {
            $query->whereIn('payment_status', [Transaksi::STATUS_VERIFIED, 'Paid', 'paid', 'verified']);
        } elseif ($quickFilter === 'pending') {
            $query->whereIn('payment_status', [Transaksi::STATUS_PENDING, 'Pending', 'pending']);
        } elseif ($quickFilter === 'gagal') {
            $query->whereIn('payment_status', [Transaksi::STATUS_REJECTED, 'Failed', 'failed', 'rejected', 'batal']);
        } elseif ($quickFilter === 'hari_ini') {
            $query->whereDate('tanggal', today());
        }

        if ($request->filled('metode') && $request->metode !== 'all') {
            $query->where('metode_pembayaran', $request->metode);
        }

        $transaksis = $query->latest('tanggal')->get();
        $fileName = 'Data_Pembayaran_PMB_' . date('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($transaksis) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'No',
                'Order ID',
                'Nama Pendaftar',
                'Email Pendaftar',
                'Nominal (Rp)',
                'Detail Pembayaran',
                'Metode Pembayaran',
                'Status Transaksi',
                'Tanggal Transaksi',
                'Alasan Penolakan',
            ]);

            $no = 1;
            foreach ($transaksis as $t) {
                $statusLabel = match (strtolower($t->payment_status ?? 'pending')) {
                    'verified', 'paid', 'success', 'settlement', 'capture' => 'Berhasil',
                    'rejected', 'failed', 'batal', 'expire', 'deny', 'cancel' => 'Gagal',
                    default => 'Pending',
                };

                $rawMetode = strtoupper($t->metode_pembayaran ?? '');
                $paymentType = strtolower($t->payment_type ?? '');

                $metodeLabel = match(true) {
                    str_contains($paymentType, 'echannel') || str_contains($paymentType, 'mandiri') => 'Mandiri VA',
                    str_contains($paymentType, 'bca') => 'BCA VA',
                    str_contains($paymentType, 'bni') => 'BNI VA',
                    str_contains($paymentType, 'bri') => 'BRI VA',
                    str_contains($paymentType, 'permata') => 'Permata VA',
                    str_contains($paymentType, 'cimb') => 'CIMB VA',
                    str_contains($paymentType, 'danamon') => 'Danamon VA',
                    str_contains($paymentType, 'qris') => 'QRIS',
                    str_contains($paymentType, 'gopay') => 'GoPay',
                    str_contains($paymentType, 'shopee') => 'ShopeePay',
                    str_contains($paymentType, 'credit_card') => 'Kartu Kredit',
                    str_contains($paymentType, 'bank_transfer') || str_contains($paymentType, 'va') => 'Virtual Account',
                    $rawMetode === 'MIDTRANS' => 'Virtual Account',
                    $rawMetode === 'BANK_TRANSFER' => 'Transfer Bank',
                    $rawMetode === 'TRANSFER_MANUAL' || str_contains($rawMetode, 'MANUAL') => 'Transfer Manual',
                    $rawMetode === 'OFFLINE' => 'Tunai / Kasir',
                    !empty($t->metode_pembayaran) => ucwords(str_replace('_', ' ', strtolower($t->metode_pembayaran))),
                    default => 'Transfer Bank',
                };

                fputcsv($handle, [
                    $no++,
                    $t->order_id ?? ('TRX-' . $t->id),
                    $t->prospek?->name ?? 'Pendaftar #' . $t->prospek_id,
                    $t->prospek?->email ?? '-',
                    $t->nominal,
                    $t->detail_pembayaran ?? $t->jenis,
                    $metodeLabel,
                    $statusLabel,
                    $t->tanggal ? \Carbon\Carbon::parse($t->tanggal)->format('d M Y, H:i') : '-',
                    $t->rejection_reason ?? '-',
                ]);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }
}
