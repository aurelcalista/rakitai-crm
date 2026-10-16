<?php

namespace App\Http\Controllers\Cs;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Prodi;
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
        $userRole = strtoupper(auth()->user()->role ?? '');
        abort_if(!in_array($userRole, ['CS', 'ADMIN', 'HM', 'HEAD MARKETING']), 403);

        $activeTab = $request->query('tab', 'pending');
        if (!in_array($activeTab, ['pending', 'riwayat'])) {
            $activeTab = 'pending';
        }

        $statusFilter = $request->query('status'); // all, verified, rejected
        $search = $request->query('q');
        $user = auth()->user();
        $isCs = $user && strtolower($user->role) === 'cs';

        $kategoriFilter = $request->query('kategori');
        $prodiFilter = $request->query('prodi_id');
        $kelasFilter = $request->query('kelas');

        // Helper closure untuk menerapkan filter yang konsisten (kategori, jurusan/prodi, kelas, pencarian)
        $applyFilters = function ($q) use ($search, $kategoriFilter, $prodiFilter, $kelasFilter) {
            if ($kategoriFilter && $kategoriFilter !== 'all') {
                $q->where('kategori', $kategoriFilter);
            }

            if ($prodiFilter && $prodiFilter !== 'all') {
                $q->whereHas('prospek', function ($p) use ($prodiFilter) {
                    $p->where('prodi_id', $prodiFilter);
                });
            }

            if ($kelasFilter && $kelasFilter !== 'all') {
                $q->whereHas('prospek', function ($p) use ($kelasFilter) {
                    $p->where('kelas', $kelasFilter);
                });
            }

            if ($search) {
                $q->where(function ($sq) use ($search) {
                    $sq->whereHas('prospek', function ($p) use ($search) {
                        $p->where('name', 'like', "%{$search}%")
                          ->orWhere('pic', 'like', "%{$search}%")
                          ->orWhere('whatsapp', 'like', "%{$search}%")
                          ->orWhereHas('sekolah', fn($sk) => $sk->where('nama', 'like', "%{$search}%"))
                          ->orWhereHas('perusahaan', fn($pr) => $pr->where('nama', 'like', "%{$search}%"));
                    })
                    ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$search}%"))
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhere('rejection_reason', 'like', "%{$search}%");
                });
            }
        };

        // Base Query dengan eager loading lengkap (Verifikasi pembayaran dapat diakses oleh semua CS tanpa sekat wilayah)
        $baseQuery = Transaksi::with([
            'prospek.cs',
            'prospek.transaksis',
            'prospek.sekolah',
            'prospek.perusahaan',
            'prospek.prodi',
            'prospek.sales',
            'user',
            'verifier',
            'rejecter'
        ]);

        // Statistik ringkasan (mencakup seluruh antrean verifikasi untuk semua CS)
        $statsQuery = Transaksi::query();

        // Terapkan filter jurusan, kelas, kategori, dan pencarian pada statistik card
        $applyFilters($statsQuery);

        $totalPending = (clone $statsQuery)
            ->where('payment_status', Transaksi::STATUS_PENDING)
            ->distinct('prospek_id')
            ->count('prospek_id');
        $totalVerified = (clone $statsQuery)
            ->where('payment_status', Transaksi::STATUS_VERIFIED)
            ->distinct('prospek_id')
            ->count('prospek_id');
        $totalRejected = (clone $statsQuery)
            ->where('payment_status', Transaksi::STATUS_REJECTED)
            ->distinct('prospek_id')
            ->count('prospek_id');
        $totalRiwayat = (clone $statsQuery)
            ->whereIn('payment_status', [Transaksi::STATUS_VERIFIED, Transaksi::STATUS_REJECTED])
            ->distinct('prospek_id')
            ->count('prospek_id');
        $sumVerifiedAmount = (clone $statsQuery)
            ->where('payment_status', Transaksi::STATUS_VERIFIED)
            ->sum('nominal');

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

        // Terapkan filter yang sama pada query tabel transaksi
        $applyFilters($query);

        $totalCount = (clone $query)
            ->distinct('prospek_id')
            ->count('prospek_id');

        $transaksis = $query->paginate(20)->withQueryString();
        $prodis = Prodi::orderBy('nama')->get();

        return view('cs.verifikasi.index', compact(
            'transaksis',
            'prodis',
            'prodiFilter',
            'kelasFilter',
            'totalPending',
            'totalVerified',
            'totalRejected',
            'totalRiwayat',
            'sumVerifiedAmount',
            'activeTab',
            'statusFilter',
            'kategoriFilter',
            'search',
            'totalCount'
        ));
    }

    /**
     * CS memverifikasi/menyetujui pembayaran.
     * Idempotent: jika sudah verified, tidak melakukan apapun lagi.
     */
    public function verify(Request $request, Transaksi $transaksi): RedirectResponse
    {
        // Hanya CS, Admin, HM yang boleh memverifikasi
        $userRole = strtoupper(auth()->user()->role ?? '');
        abort_if(!in_array($userRole, ['CS', 'ADMIN', 'HM', 'HEAD MARKETING']), 403);

        // Sales tidak boleh verifikasi transaksinya sendiri
        if (strtolower(auth()->user()->role) === 'sales' && auth()->id() === $transaksi->user_id) {
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
            $prospek = $transaksi->prospek?->fresh('transaksis');
            $nominalText = 'Rp ' . number_format((float)$transaksi->nominal, 0, ',', '.');
            $csName = auth()->user()->name;

            // Resolve Sales penanggung jawab atau penginput transaksi
            $sales = $prospek?->sales 
                ?? ($prospek?->sales_id ? \App\Models\User::find($prospek->sales_id) : null)
                ?? ($transaksi->user_id ? \App\Models\User::find($transaksi->user_id) : null)
                ?? ($prospek?->owner_id ? \App\Models\User::find($prospek->owner_id) : null);

            $isClosing = $prospek && \App\Services\ProspekService::isClosingValid($prospek);

            if ($isClosing) {
                $oldStatus = $prospek->status;
                $prospek->status = 'LUNAS';
                $prospek->stage_number = 7;
                $prospek->save();

                // Timeline Closing
                ProspekTimeline::create([
                    'prospek_id'    => $prospek->id,
                    'user_id'       => auth()->id(),
                    'title'         => 'Closing Terverifikasi oleh CS',
                    'notes'         => 'CS ' . $csName . ' memverifikasi pembayaran. Transaksi ' . $transaksi->jenis . ' dinyatakan sah. Prospek dinyatakan LUNAS.',
                    'status_before' => $oldStatus,
                    'status_after'  => 'LUNAS',
                    'time'          => now(),
                ]);
            } else {
                // Timeline Verifikasi Transaksi biasa
                if ($prospek) {
                    ProspekTimeline::create([
                        'prospek_id'    => $prospek->id,
                        'user_id'       => auth()->id(),
                        'title'         => 'Pembayaran Diverifikasi oleh CS',
                        'notes'         => 'CS ' . $csName . ' memverifikasi transaksi ' . $transaksi->jenis . ' sebesar ' . $nominalText . '.',
                        'status_before' => $prospek->status,
                        'status_after'  => $prospek->status,
                        'time'          => now(),
                    ]);
                }
            }

            // Notifikasi ke Sales bahwa pembayaran telah diverifikasi
            if ($sales) {
                $message = $isClosing
                    ? "Pembayaran {$nominalText} ({$transaksi->jenis}) untuk '{$prospek?->name}' telah diverifikasi oleh CS {$csName}. Prospek dinyatakan LUNAS (Closing)."
                    : "Pembayaran {$nominalText} ({$transaksi->jenis}) untuk '{$prospek?->name}' telah disetujui & diverifikasi oleh CS {$csName}.";

                $sales->notify(new \App\Notifications\CrmActivityNotification(
                    title: 'Pembayaran Diverifikasi!',
                    message: $message,
                    type: 'success',
                    link: '/prospek',
                    icon: '',
                    senderName: $csName,
                    senderRole: auth()->user()->role,
                    action: 'pembayaran_diverifikasi'
                ));

                // Jika Closing, evaluasi target Sales & notifikasi SPV
                if ($isClosing) {
                    try {
                        $targetService = app(\App\Services\TargetAchievementService::class);
                        $targetService->checkAndNotifyTargetStatus($sales);
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning("Gagal evaluasi target setelah verifikasi: " . $e->getMessage());
                    }

                    $spv = $sales->supervisor ?? ($prospek?->wilayah_id ? \App\Models\User::where('role', 'SPV')->where('wilayah_id', $prospek->wilayah_id)->first() : null);
                    if ($spv && $spv->id !== auth()->id()) {
                        $spv->notify(new \App\Notifications\CrmActivityNotification(
                            title: 'Closing Mahasiswa Baru!',
                            message: "Pembayaran {$nominalText} untuk '{$prospek?->name}' telah diverifikasi oleh CS {$csName}. Prospek dari Sales {$sales->name} sah Closing (LUNAS).",
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
        });

        return back()->with('success', 'Pembayaran berhasil diverifikasi. Prospek dinyatakan LUNAS / Closing.');
    }

    /**
     * CS menolak/mereject pembayaran.
     */
    public function reject(Request $request, Transaksi $transaksi): RedirectResponse
    {
        // Hanya CS yang boleh
        $userRole = strtoupper(auth()->user()->role ?? '');
        abort_if(!in_array($userRole, ['CS', 'ADMIN', 'HM', 'HEAD MARKETING']), 403);

        // Sales tidak boleh reject transaksinya sendiri
        if (strtolower(auth()->user()->role) === 'sales' && auth()->id() === $transaksi->user_id) {
            return back()->withErrors(['error' => 'Anda tidak boleh menolak transaksi yang Anda input sendiri.']);
        }


        // Gedung (FREE) tidak boleh ditolak
        if ($transaksi->jenis === 'Gedung' && (float)$transaksi->nominal == 0) {
            return back()->withErrors(['error' => 'Transaksi komponen Gedung bersifat FREE (Rp 0) dan tidak dapat ditolak.']);
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
