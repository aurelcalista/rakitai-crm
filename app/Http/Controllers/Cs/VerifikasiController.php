<?php

namespace App\Http\Controllers\Cs;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\ProspekTimeline;
use App\Models\Transaksi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class VerifikasiController extends Controller
{
    /**
     * Halaman daftar verifikasi & riwayat pembayaran CS.
     */
    public function index(Request $request): View
    {
        // Hanya CS, Admin, HM yang boleh mengakses
        abort_if(!in_array(auth()->user()->role, ['CS', 'Admin', 'HM']), 403);

        $activeTab = $request->query('tab', 'pending');
        if (!in_array($activeTab, ['pending', 'riwayat'])) {
            $activeTab = 'pending';
        }

        $statusFilter = $request->query('status'); // all, verified, rejected
        $search = $request->query('q');

        // Statistik ringkasan
        $totalPending = Transaksi::where('jenis', 'Pembayaran Termin 1')
            ->where('payment_status', Transaksi::STATUS_PENDING)
            ->count();
        $totalVerified = Transaksi::where('payment_status', Transaksi::STATUS_VERIFIED)
            ->where('jenis', 'Pembayaran Termin 1')
            ->count();
        $totalRejected = Transaksi::where('payment_status', Transaksi::STATUS_REJECTED)
            ->where('jenis', 'Pembayaran Termin 1')
            ->count();
        $totalRiwayat = $totalVerified + $totalRejected;
        $sumVerifiedAmount = Transaksi::where('payment_status', Transaksi::STATUS_VERIFIED)
            ->where('jenis', 'Pembayaran Termin 1')
            ->sum('nominal');

        // Base Query dengan eager loading lengkap
        $baseQuery = Transaksi::with(['prospek', 'user', 'prospek.sales', 'verifier', 'rejecter'])
            ->where('jenis', 'Pembayaran Termin 1');

        if ($activeTab === 'pending') {
            $query = (clone $baseQuery)
                ->where('payment_status', Transaksi::STATUS_PENDING)
                ->latest('tanggal');
        } else {
            // Tab Riwayat: Tampilkan transaksi yang sudah verified atau rejected
            $query = (clone $baseQuery)
                ->whereIn('payment_status', [Transaksi::STATUS_VERIFIED, Transaksi::STATUS_REJECTED])
                ->latest('updated_at');

            if ($statusFilter && in_array($statusFilter, [Transaksi::STATUS_VERIFIED, Transaksi::STATUS_REJECTED])) {
                $query->where('payment_status', $statusFilter);
            }
        }

        // Filter Pencarian
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('prospek', fn($p) => $p->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$search}%"))
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhere('rejection_reason', 'like', "%{$search}%");
            });
        }

        $transaksis = $query->paginate(20)->withQueryString();

        return view('cs.verifikasi.index', compact(
            'transaksis',
            'totalPending',
            'totalVerified',
            'totalRejected',
            'totalRiwayat',
            'sumVerifiedAmount',
            'activeTab',
            'statusFilter',
            'search'
        ));
    }

    /**
     * CS memverifikasi/menyetujui pembayaran.
     * Idempotent: jika sudah verified, tidak melakukan apapun lagi.
     */
    public function verify(Request $request, Transaksi $transaksi): RedirectResponse
    {
        // Hanya CS yang boleh memverifikasi
        abort_if(!in_array(auth()->user()->role, ['CS', 'Admin', 'HM']), 403);

        // Sales tidak boleh verifikasi transaksinya sendiri
        if (auth()->id() === $transaksi->user_id) {
            return back()->withErrors(['error' => 'Anda tidak boleh memverifikasi transaksi yang Anda input sendiri.']);
        }

        // Hanya bisa verifikasi transaksi yang pending
        if ($transaksi->payment_status !== Transaksi::STATUS_PENDING) {
            return back()->with('info', 'Transaksi ini sudah diproses sebelumnya.');
        }

        // Wrap dalam DB transaction agar idempotent & aman
        DB::transaction(function () use ($transaksi) {
            // Lock row agar tidak double-verify
            $transaksi->lockForUpdate()->first();

            // Cek ulang setelah lock
            if ($transaksi->payment_status !== Transaksi::STATUS_PENDING) {
                return; // Sudah diproses oleh request lain, skip
            }

            $transaksi->update([
                'payment_status' => Transaksi::STATUS_VERIFIED,
                'verified_by'    => auth()->id(),
                'verified_at'    => now(),
                'rejected_by'    => null,
                'rejected_at'    => null,
                'rejection_reason' => null,
            ]);

            // Update status prospek jika sekarang sudah memenuhi syarat Closing
            $prospek = $transaksi->prospek->fresh('transaksis');
            if ($prospek && \App\Services\ProspekService::isClosingValid($prospek)) {
                $oldStatus = $prospek->status;
                $prospek->status = 'LUNAS';
                $prospek->stage_number = 7;
                $prospek->save();

                // Timeline
                ProspekTimeline::create([
                    'prospek_id'    => $prospek->id,
                    'user_id'       => auth()->id(),
                    'title'         => 'Closing Terverifikasi oleh CS',
                    'notes'         => 'CS ' . auth()->user()->name . ' memverifikasi pembayaran. Transaksi ' . $transaksi->jenis . ' dinyatakan sah.',
                    'status_before' => $oldStatus,
                    'status_after'  => 'LUNAS',
                    'time'          => now(),
                ]);

                // Notifikasi ke Sales bahwa pembayaran diverifikasi
                $sales = $prospek->sales;
                if ($sales) {
                    $nominalText = 'Rp ' . number_format((float)$transaksi->nominal, 0, ',', '.');
                    $sales->notify(new \App\Notifications\CrmActivityNotification(
                        title: 'Pembayaran Diverifikasi!',
                        message: "Pembayaran {$nominalText} untuk '{$prospek->name}' telah diverifikasi oleh CS. Prospek dinyatakan LUNAS (Closing).",
                        type: 'success',
                        link: '/prospek',
                        icon: '',
                        senderName: auth()->user()->name,
                        senderRole: auth()->user()->role,
                        action: 'pembayaran_diverifikasi'
                    ));

                    // Evaluasi target Sales
                    try {
                        $targetService = app(\App\Services\TargetAchievementService::class);
                        $targetService->checkAndNotifyTargetStatus($sales);
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning("Gagal evaluasi target setelah verifikasi: " . $e->getMessage());
                    }
                }
            }
        });

        return back()->with('success', 'Pembayaran berhasil diverifikasi. Prospek dinyatakan LUNAS / Closing.');
    }

    /**
     * CS menolak/mereject pembayaran.
     */
    public function reject(Request $request, Transaksi $transaksi): RedirectResponse
    {
        // Hanya CS yang boleh
        abort_if(!in_array(auth()->user()->role, ['CS', 'Admin', 'HM']), 403);

        // Sales tidak boleh reject transaksinya sendiri
        if (auth()->id() === $transaksi->user_id) {
            return back()->withErrors(['error' => 'Anda tidak boleh menolak transaksi yang Anda input sendiri.']);
        }

        // Hanya bisa reject transaksi yang pending
        if ($transaksi->payment_status !== Transaksi::STATUS_PENDING) {
            return back()->with('info', 'Transaksi ini sudah diproses sebelumnya.');
        }

        $request->validate([
            'rejection_reason' => 'nullable|string|max:500',
        ]);

        $transaksi->update([
            'payment_status'   => Transaksi::STATUS_REJECTED,
            'rejected_by'      => auth()->id(),
            'rejected_at'      => now(),
            'rejection_reason' => $request->rejection_reason,
        ]);

        $prospek = $transaksi->prospek;
        if ($prospek) {
            ProspekTimeline::create([
                'prospek_id'    => $prospek->id,
                'user_id'       => auth()->id(),
                'title'         => 'Pembayaran Ditolak oleh CS',
                'notes'         => 'CS ' . auth()->user()->name . ' menolak pembayaran ' . $transaksi->jenis
                    . ($request->rejection_reason ? ' | Alasan: ' . $request->rejection_reason : ''),
                'status_before' => $prospek->status,
                'status_after'  => $prospek->status,
                'time'          => now(),
            ]);

            // Notifikasi ke Sales
            $sales = $prospek->sales ?? ($prospek->sales_id ? \App\Models\User::find($prospek->sales_id) : null);
            if ($sales) {
                $nominalText = 'Rp ' . number_format((float)$transaksi->nominal, 0, ',', '.');
                $sales->notify(new \App\Notifications\CrmActivityNotification(
                    title: 'Pembayaran Ditolak',
                    message: "Pembayaran {$nominalText} untuk '{$prospek->name}' ditolak oleh CS."
                        . ($request->rejection_reason ? " Alasan: " . $request->rejection_reason : ''),
                    type: 'error',
                    link: '/prospek',
                    icon: '',
                    senderName: auth()->user()->name,
                    senderRole: auth()->user()->role,
                    action: 'pembayaran_ditolak'
                ));
            }
        }

        return back()->with('success', 'Pembayaran berhasil ditolak.');
    }
}
