<?php

namespace App\Http\Controllers\Pmb;

use App\Http\Controllers\Controller;
use App\Models\Prospek;
use App\Models\Transaksi;
use App\Services\MidtransService;
use App\Services\PaymentNotificationService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PmbPembayaranController extends Controller
{
    /**
     * Hitung rekap komponen dan status pembayaran untuk calon mahasiswa (Prospek).
     *
     * Komponen yang tercakup:
     * 1. Biaya Perlengkapan Kuliah: Rp 600.000
     * 2. Biaya Pendaftaran PMB: Rp 300.000
     * 3. DPP (Dana Pengembangan Pendidikan): Rp 500.000
     * 4. Down Payment (DP): Rp 500.000
     * 5. Pelunasan Biaya Kuliah (Sisa UKT / Semester 1)
     *
     * Total Kewajiban = Total Tagihan PMB (UKT + Pendaftaran atau Standar Rp 5.000.000)
     *
     * Aturan:
     * - Hanya transaksi berstatus 'verified' / 'paid' / 'success' / 'settlement' / 'capture' yang dihitung.
     * - Transaksi pending, menunggu pembayaran, atau gagal TIDAK mengurangi sisa tagihan.
     * - Status keseluruhan LUNAS jika seluruh kewajiban telah terbayar (sisa tagihan = Rp 0).
     *
     * @return array<string, mixed>
     */
    public static function calculateRekap(Prospek $prospek): array
    {
        $prodi = $prospek->prodi;
        $totalTagihan = MidtransService::getTotalTagihan($prospek);

        $kewajibanPendaftaran  = $prodi && isset($prodi->biaya_pendaftaran)  ? (int) $prodi->biaya_pendaftaran  : 300000;
        $kewajibanPerlengkapan = $prodi && isset($prodi->biaya_perlengkapan) ? (int) $prodi->biaya_perlengkapan : 600000;
        $kewajibanDpp          = $prodi && isset($prodi->biaya_dpp)          ? (int) $prodi->biaya_dpp          : 500000;
        $kewajibanGedung       = $prodi && isset($prodi->biaya_uang_gedung)  ? (int) $prodi->biaya_uang_gedung  : 0;

        $kewajiban = [
            'perlengkapan' => $kewajibanPerlengkapan,
            'pendaftaran'  => $kewajibanPendaftaran,
            'dpp'          => $kewajibanDpp,
            'uang_gedung'  => $kewajibanGedung,
            'dp'           => MidtransService::getBiayaDP(), // Rp 500.000
            'pelunasan'    => 0, // dihitung dinamis di bawah
        ];

        $terbayar = [
            'perlengkapan' => 0,
            'pendaftaran'  => 0,
            'dpp'          => 0,
            'uang_gedung'  => 0,
            'dp'           => 0,
            'pelunasan'    => 0,
        ];

        $transaksis = $prospek->transaksis ?? collect();

        // Hanya transaksi yang sudah terverifikasi / lunas sah
        $verifiedTransaksis = $transaksis->filter(function ($t) {
            $status = strtolower($t->payment_status ?? '');
            return in_array($status, ['verified', 'paid', 'success', 'settlement', 'capture']);
        });

        foreach ($verifiedTransaksis as $t) {
            $detail = (string) $t->detail_pembayaran;
            $jenis  = (string) $t->jenis;
            $cat    = (string) $t->kategori;
            $nom    = (float) $t->nominal;

            $isDp = (in_array($cat, Transaksi::getCategoryAliases(Transaksi::KATEGORI_DP))
                || str_contains($detail, 'Down Payment')
                || (str_contains($detail, 'DP') && !str_contains($detail, 'DPP') && !str_contains($detail, 'DP Daftar'))
                || $jenis === 'DP/Konversi')
                && $jenis !== 'Beli Formulir';

            $isPelunasan = (in_array($cat, Transaksi::getCategoryAliases(Transaksi::KATEGORI_PELUNASAN))
                || str_contains($detail, 'Pelunasan')
                || $jenis === 'Pelunasan')
                && $jenis !== 'Beli Formulir'
                && !str_contains($detail, 'Pendaftaran')
                && !str_contains($detail, 'Formulir');

            if ($isDp) {
                $terbayar['dp'] += $nom;
                continue;
            }

            if ($isPelunasan) {
                $terbayar['pelunasan'] += $nom;
                continue;
            }

            // Subkomponen Biaya 1x Bayar (Perlengkapan, Pendaftaran, DPP, Uang Gedung)
            $hasPerlengkapan = str_contains($detail, 'Perlengkapan');
            $hasPendaftaran  = str_contains($detail, 'Pendaftaran') || (empty($detail) && in_array($t->jenis, ['Beli Formulir', 'Pendaftaran']));
            $hasDpp          = str_contains($detail, 'DPP') || str_contains($detail, 'Pengembangan Pendidikan');
            $hasGedung       = str_contains($detail, 'Gedung') || str_contains($detail, 'Uang Gedung');

            $activeComponents = [];
            if ($hasPerlengkapan) $activeComponents[] = 'perlengkapan';
            if ($hasPendaftaran) $activeComponents[] = 'pendaftaran';
            if ($hasDpp) $activeComponents[] = 'dpp';
            if ($hasGedung) $activeComponents[] = 'uang_gedung';

            if (count($activeComponents) === 1) {
                $comp = $activeComponents[0];
                $terbayar[$comp] += $nom;
            } elseif (count($activeComponents) > 1) {
                // Multi komponen dalam 1 transaksi
                foreach ($activeComponents as $comp) {
                    $terbayar[$comp] += $kewajiban[$comp];
                }
            } else {
                // Fallback berdasarkan jenis atau nominal jika detail pembayaran kosong
                if ($jenis === 'Beli Formulir' || $nom == 300000 || $nom == 250000) {
                    $terbayar['pendaftaran'] += $nom;
                } elseif ($nom == 600000) {
                    $terbayar['perlengkapan'] += 600000;
                } elseif ($nom == 500000) {
                    $terbayar['dpp'] += 500000;
                } elseif ($nom == 1000000) {
                    $terbayar['uang_gedung'] += 1000000;
                } elseif ($nom == 900000) {
                    $terbayar['perlengkapan'] += 600000;
                    $terbayar['pendaftaran'] += 300000;
                } elseif ($nom == 1400000) {
                    $terbayar['perlengkapan'] += 600000;
                    $terbayar['pendaftaran'] += 300000;
                    $terbayar['dpp'] += 500000;
                } elseif ($nom == 800000) {
                    $terbayar['pendaftaran'] += 300000;
                    $terbayar['dpp'] += 500000;
                } elseif ($nom == 1100000) {
                    $terbayar['perlengkapan'] += 600000;
                    $terbayar['dpp'] += 500000;
                } elseif ($nom >= 2000000) {
                    $terbayar['pelunasan'] += $nom;
                }
            }
        }

        // Tentukan kewajiban komponen 1x bayar
        $oneTimeKewajiban = $kewajiban['perlengkapan'] + $kewajiban['pendaftaran'] + $kewajiban['dpp'] + $kewajiban['uang_gedung'];

        // Target kewajiban pelunasan = total tagihan - (kewajiban 1x bayar + DP)
        $kewajiban['pelunasan'] = max(0, $totalTagihan - ($oneTimeKewajiban + $kewajiban['dp']));
        if ($terbayar['pelunasan'] > $kewajiban['pelunasan']) {
            $kewajiban['pelunasan'] = (int) $terbayar['pelunasan'];
        }

        $rincian = [];
        foreach ($kewajiban as $key => $targetNominal) {
            $paidNominal = $terbayar[$key];
            $sisaNominal = max(0, $targetNominal - $paidNominal);
            $isLunas = ($targetNominal > 0) ? ($paidNominal >= $targetNominal) : true;

            $rincian[$key] = [
                'nama'      => match($key) {
                    'perlengkapan' => 'Biaya Perlengkapan Kuliah',
                    'pendaftaran'  => 'Biaya Pendaftaran PMB',
                    'dpp'          => 'DPP (Dana Pengembangan Pendidikan)',
                    'uang_gedung'  => 'Biaya Uang Gedung',
                    'dp'           => 'Down Payment (DP)',
                    'pelunasan'    => 'Pelunasan Biaya Kuliah',
                },
                'kewajiban' => $targetNominal,
                'terbayar'  => $paidNominal,
                'sisa'      => $sisaNominal,
                'is_lunas'  => $isLunas,
                'status'    => $isLunas ? 'Lunas' : 'Belum Lunas',
            ];
        }

        $totalKewajiban = $totalTagihan;
        $totalTerbayar = array_sum($terbayar);
        $totalSisa = max(0, $totalKewajiban - $totalTerbayar);
        $isSemuaLunas = ($totalSisa === 0 && $totalTerbayar > 0);

        $pendingTrxCount = $transaksis->filter(function ($t) {
            $status = strtolower($t->payment_status ?? '');
            return in_array($status, ['pending', 'menunggu pembayaran']);
        })->count();

        return [
            'rincian'            => $rincian,
            'total_kewajiban'    => $totalKewajiban,
            'total_terbayar'     => $totalTerbayar,
            'total_sisa'         => $totalSisa,
            'is_lunas'           => $isSemuaLunas,
            'status_keseluruhan' => $isSemuaLunas ? 'Lunas' : 'Belum Lunas',
            'pending_count'      => $pendingTrxCount,
            'total_transaksi'    => $transaksis->count(),
        ];
    }

    public function index(Request $request): View
    {
        // Auto-sync pending Midtrans transactions with Midtrans Server API
        if (!app()->runningUnitTests()) {
            $pendingSyncCandidates = Transaksi::where('payment_status', Transaksi::STATUS_PENDING)
                ->whereNotNull('order_id')
                ->latest('id')
                ->take(5)
                ->get();

            $midtransService = app(MidtransService::class);
            foreach ($pendingSyncCandidates as $cand) {
                try {
                    $midtransService->syncStatusFromMidtransApi($cand->order_id);
                } catch (\Throwable $e) {}
            }
        }

        // Query utama: 1 Camaba (Prospek) = 1 Data Rekap
        $query = Prospek::with([
            'prodi',
            'transaksis' => fn($q) => $q->orderByDesc('tanggal')->orderByDesc('id'),
            'user',
        ]);

        // Search: nama, email, no wa, no registrasi, atau order id transaksi
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('whatsapp', 'like', "%{$search}%")
                  ->orWhere('no_registrasi', 'like', "%{$search}%")
                  ->orWhereHas('transaksis', function ($tq) use ($search) {
                      $tq->where('order_id', 'like', "%{$search}%");
                  });
            });
        }

        // Filter: Metode Pembayaran (Midtrans vs Transfer Manual)
        if ($request->filled('metode') && $request->metode !== 'all') {
            $metode = strtoupper(trim($request->input('metode')));
            $query->whereHas('transaksis', function ($tq) use ($metode) {
                if ($metode === 'TRANSFER_MANUAL' || str_contains($metode, 'MANUAL')) {
                    $tq->where(function ($mq) {
                        $mq->where('metode_pembayaran', 'TRANSFER_MANUAL')
                           ->orWhere('metode_pembayaran', 'like', '%MANUAL%');
                    });
                } elseif ($metode === 'MIDTRANS' || $metode === 'ONLINE') {
                    $tq->where(function ($mq) {
                        $mq->where('metode_pembayaran', 'MIDTRANS')
                           ->orWhere('metode_pembayaran', 'like', '%VA%')
                           ->orWhere('metode_pembayaran', 'like', '%QRIS%')
                           ->orWhere('metode_pembayaran', 'like', '%GOPAY%')
                           ->orWhere('metode_pembayaran', 'like', '%BANK_TRANSFER%');
                    });
                } else {
                    $tq->where('metode_pembayaran', $metode);
                }
            });
        }

        // Filter: Rentang Tanggal Transaksi & Periode
        $periode = $request->input('periode');
        if ($periode === 'today') {
            $query->whereHas('transaksis', function ($tq) {
                $tq->whereDate('tanggal', \Carbon\Carbon::today())
                   ->orWhereDate('created_at', \Carbon\Carbon::today());
            });
        } elseif ($periode === 'this_week') {
            $query->whereHas('transaksis', function ($tq) {
                $tq->whereBetween('tanggal', [\Carbon\Carbon::now()->startOfWeek(), \Carbon\Carbon::now()->endOfWeek()])
                   ->orWhereBetween('created_at', [\Carbon\Carbon::now()->startOfWeek(), \Carbon\Carbon::now()->endOfWeek()]);
            });
        } elseif ($periode === 'this_month') {
            $query->whereHas('transaksis', function ($tq) {
                $tq->whereMonth('tanggal', \Carbon\Carbon::now()->month)
                   ->whereYear('tanggal', \Carbon\Carbon::now()->year);
            });
        } elseif ($periode === 'this_year') {
            $query->whereHas('transaksis', function ($tq) {
                $tq->whereYear('tanggal', \Carbon\Carbon::now()->year);
            });
        } elseif ($request->filled('start_date') && $request->filled('end_date')) {
            $start = $request->input('start_date') . ' 00:00:00';
            $end = $request->input('end_date') . ' 23:59:59';
            $query->whereHas('transaksis', function ($tq) use ($start, $end) {
                $tq->whereBetween('tanggal', [$start, $end])
                   ->orWhereBetween('created_at', [$start, $end]);
            });
        }

        // Filter Status / Quick Filter (all, lunas/berhasil, pending, belum_lunas, refund)
        $quickFilter = $request->input('status', $request->input('quick_filter', 'all'));
        if ($quickFilter === 'pending') {
            $query->whereHas('transaksis', function ($tq) {
                $tq->whereIn('payment_status', [
                    Transaksi::STATUS_PENDING, 'Pending', 'pending', 'menunggu pembayaran'
                ]);
            });
        } elseif ($quickFilter === 'hari_ini') {
            $query->whereHas('transaksis', function ($tq) {
                $tq->whereDate('tanggal', \Carbon\Carbon::today())
                   ->orWhereDate('created_at', \Carbon\Carbon::today());
            });
        } elseif ($quickFilter === 'lunas' || $quickFilter === 'berhasil') {
            $query->whereHas('transaksis', function ($tq) {
                $tq->whereIn('payment_status', [
                    Transaksi::STATUS_VERIFIED, 'Paid', 'paid', 'verified', 'settlement', 'capture', 'success'
                ]);
            });
        } elseif ($quickFilter === 'belum_lunas') {
            $query->where(function ($sub) {
                $sub->whereDoesntHave('transaksis', function ($tq) {
                    $tq->whereIn('payment_status', ['verified', 'paid', 'success', 'settlement', 'capture']);
                })->orWhereHas('transaksis', function ($tq) {
                    $tq->havingRaw("SUM(CASE WHEN payment_status IN ('verified', 'paid', 'success', 'settlement', 'capture') THEN nominal ELSE 0 END) < 5000000");
                });
            });
        } elseif ($quickFilter === 'refund' || $quickFilter === 'gagal') {
            $query->whereHas('transaksis', function ($tq) {
                $tq->whereIn('payment_status', [
                    Transaksi::STATUS_REJECTED, 'Failed', 'failed', 'rejected', 'deny', 'expire', 'cancel', 'batal'
                ]);
            });
        }

        // Summary Statistics (Actual DB Data)
        $countSemua = Prospek::count();

        $totalDiterima = (float) Transaksi::whereIn('payment_status', [
            Transaksi::STATUS_VERIFIED, 'Paid', 'paid', 'verified', 'settlement', 'capture', 'success'
        ])->sum('nominal');

        $countBerhasil = Transaksi::whereIn('payment_status', [
            Transaksi::STATUS_VERIFIED, 'Paid', 'paid', 'verified', 'settlement', 'capture', 'success'
        ])->count();
        $countLunas = $countBerhasil;

        $countPendingTrx = Transaksi::whereIn('payment_status', [
            Transaksi::STATUS_PENDING, 'Pending', 'pending', 'menunggu pembayaran'
        ])->count();

        $countBelumLunas = Transaksi::whereIn('payment_status', [
            Transaksi::STATUS_REJECTED, 'Failed', 'failed', 'rejected', 'deny', 'expire', 'cancel', 'batal'
        ])->count();

        $countRefund = Transaksi::whereIn('payment_status', [
            Transaksi::STATUS_REJECTED, 'Failed', 'failed', 'rejected', 'deny', 'expire', 'cancel', 'batal', 'refund'
        ])->count();

        // List metode pembayaran untuk dropdown filter
        $metodeList = collect(['MIDTRANS', 'TRANSFER_MANUAL']);

        // Pagination
        $perPage = (int) $request->input('per_page', 10);
        if (!in_array($perPage, [10, 25, 50, 100])) {
            $perPage = 10;
        }

        $rekapList = $query->orderByDesc('id')->paginate($perPage)->withQueryString();

        // Hitung rekap 5 komponen untuk masing-masing calon mahasiswa
        $rekapList->getCollection()->transform(function ($p) {
            $p->rekap = self::calculateRekap($p);
            return $p;
        });

        // Alias transaksis agar kompatibel penuh jika ada referensi lama
        $transaksis = $rekapList;

        return view('pmb.pembayaran.index', compact(
            'rekapList',
            'transaksis',
            'countSemua',
            'countBerhasil',
            'totalDiterima',
            'countLunas',
            'countBelumLunas',
            'countPendingTrx',
            'countRefund',
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

            // Cek apakah seluruh komponen prospek sekarang lunas
            $prospek = $transaksi->prospek;
            if ($prospek) {
                $prospek->load('transaksis');
                $rekap = self::calculateRekap($prospek);
                if ($rekap['is_lunas'] && $prospek->status !== 'LUNAS') {
                    $prospek->update(['status' => 'LUNAS']);
                }
            }

            PaymentNotificationService::notifyAllRoles($transaksi, 'verified');
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

            PaymentNotificationService::notifyAllRoles($transaksi, 'rejected');
        }

        return redirect()->back()->with('error', "Transaksi {$transaksi->order_id} telah ditolak/dibatalkan.");
    }

    public function sync(Request $request, $id, MidtransService $midtransService)
    {
        $transaksi = Transaksi::findOrFail($id);
        if (empty($transaksi->order_id)) {
            return redirect()->back()->with('error', "Transaksi tidak memiliki Order ID Midtrans.");
        }

        $synced = $midtransService->syncStatusFromMidtransApi($transaksi->order_id);

        if ($synced && $synced->payment_status === Transaksi::STATUS_VERIFIED) {
            $transaksi->refresh();
            $prospek = $transaksi->prospek;
            if ($prospek) {
                $prospek->load('transaksis');
                $rekap = self::calculateRekap($prospek);
                if ($rekap['is_lunas'] && $prospek->status !== 'LUNAS') {
                    $prospek->update(['status' => 'LUNAS']);
                }
            }
            return redirect()->back()->with('success', "Status transaksi {$transaksi->order_id} berhasil disinkronkan langsung dari Midtrans: LUNAS (VERIFIED)!");
        }

        $transaksi->refresh();
        return redirect()->back()->with('info', "Status transaksi {$transaksi->order_id} di Midtrans saat ini: " . strtoupper($transaksi->payment_status) . ".");
    }

    public function export(Request $request): StreamedResponse
    {
        $query = Prospek::with([
            'prodi',
            'transaksis' => fn($q) => $q->orderByDesc('tanggal')->orderByDesc('id'),
            'user',
        ]);

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('whatsapp', 'like', "%{$search}%")
                  ->orWhere('no_registrasi', 'like', "%{$search}%")
                  ->orWhereHas('transaksis', function ($tq) use ($search) {
                      $tq->where('order_id', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('metode') && $request->metode !== 'all') {
            $metode = strtoupper(trim($request->input('metode')));
            $query->whereHas('transaksis', function ($tq) use ($metode) {
                if ($metode === 'TRANSFER_MANUAL' || str_contains($metode, 'MANUAL')) {
                    $tq->where(function ($mq) {
                        $mq->where('metode_pembayaran', 'TRANSFER_MANUAL')
                           ->orWhere('metode_pembayaran', 'like', '%MANUAL%');
                    });
                } elseif ($metode === 'MIDTRANS' || $metode === 'ONLINE') {
                    $tq->where(function ($mq) {
                        $mq->where('metode_pembayaran', 'MIDTRANS')
                           ->orWhere('metode_pembayaran', 'like', '%VA%')
                           ->orWhere('metode_pembayaran', 'like', '%QRIS%')
                           ->orWhere('metode_pembayaran', 'like', '%GOPAY%')
                           ->orWhere('metode_pembayaran', 'like', '%BANK_TRANSFER%');
                    });
                } else {
                    $tq->where('metode_pembayaran', $metode);
                }
            });
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $start = $request->input('start_date') . ' 00:00:00';
            $end = $request->input('end_date') . ' 23:59:59';
            $query->whereHas('transaksis', function ($tq) use ($start, $end) {
                $tq->whereBetween('tanggal', [$start, $end])
                   ->orWhereBetween('created_at', [$start, $end]);
            });
        }

        $quickFilter = $request->input('quick_filter', 'all');
        if ($quickFilter === 'pending') {
            $query->whereHas('transaksis', function ($tq) {
                $tq->whereIn('payment_status', [
                    Transaksi::STATUS_PENDING, 'Pending', 'pending', 'menunggu pembayaran'
                ]);
            });
        } elseif ($quickFilter === 'hari_ini') {
            $query->whereHas('transaksis', function ($tq) {
                $tq->whereDate('tanggal', \Carbon\Carbon::today())
                   ->orWhereDate('created_at', \Carbon\Carbon::today());
            });
        } elseif ($quickFilter === 'lunas' || $quickFilter === 'berhasil') {
            $query->whereHas('transaksis', function ($tq) {
                $tq->whereIn('payment_status', [
                    Transaksi::STATUS_VERIFIED, 'Paid', 'paid', 'verified', 'settlement', 'capture', 'success'
                ]);
            });
        }

        $camabaList = $query->orderByDesc('id')->get();
        $fileName = 'Data_Rekap_Pembayaran_PMB_' . date('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($camabaList) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'No',
                'Order ID Terakhir',
                'No. Registrasi',
                'Nama Calon Mahasiswa',
                'Email',
                'WhatsApp',
                'Program Studi',
                'Total Kewajiban (Rp)',
                'Total Sudah Dibayar (Rp)',
                'Sisa Tagihan (Rp)',
                'Status Pembayaran',
                'Perlengkapan (Status & Terbayar)',
                'Pendaftaran (Status & Terbayar)',
                'DPP (Status & Terbayar)',
                'Down Payment (Status & Terbayar)',
                'Pelunasan (Status & Terbayar)',
                'Jumlah Transaksi Tercatat',
            ]);

            $no = 1;
            foreach ($camabaList as $p) {
                $r = self::calculateRekap($p);
                $latestTrx = $p->transaksis->first();

                fputcsv($handle, [
                    $no++,
                    $latestTrx?->order_id ?: ('TRX-' . str_pad($p->id, 5, '0', STR_PAD_LEFT)),
                    $p->no_registrasi ?? '-',
                    $p->name ?? '-',
                    $p->email ?? '-',
                    $p->whatsapp ?? '-',
                    $p->prodi?->nama_prodi ?? '-',
                    $r['total_kewajiban'],
                    $r['total_terbayar'],
                    $r['total_sisa'],
                    $r['status_keseluruhan'],
                    $r['rincian']['perlengkapan']['status'] . ' (Rp ' . number_format($r['rincian']['perlengkapan']['terbayar']) . ')',
                    $r['rincian']['pendaftaran']['status'] . ' (Rp ' . number_format($r['rincian']['pendaftaran']['terbayar']) . ')',
                    $r['rincian']['dpp']['status'] . ' (Rp ' . number_format($r['rincian']['dpp']['terbayar']) . ')',
                    $r['rincian']['dp']['status'] . ' (Rp ' . number_format($r['rincian']['dp']['terbayar']) . ')',
                    $r['rincian']['pelunasan']['status'] . ' (Rp ' . number_format($r['rincian']['pelunasan']['terbayar']) . ')',
                    $p->transaksis->count(),
                ]);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }
}
