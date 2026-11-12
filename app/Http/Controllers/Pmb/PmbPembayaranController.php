<?php

namespace App\Http\Controllers\Pmb;

use App\Http\Controllers\Controller;
use App\Models\Prospek;
use App\Models\Transaksi;
use App\Models\Voucher;
use App\Models\VoucherUsage;
use App\Services\MidtransService;
use App\Services\PaymentNotificationService;
use App\Services\VoucherDiscountService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PmbPembayaranController extends Controller
{
    public const STAGES = ['pendaftaran', 'perlengkapan', 'dpp', 'uang_gedung'];

    /**
     * Tentukan tahap pembayaran tertinggi yang sudah dibayar (verified) oleh Camaba.
     * Urutan tahap: pendaftaran -> perlengkapan -> dpp -> uang_gedung.
     *
     * @param  array<string, mixed>|null  $rekap
     */
    public static function determinePaymentStage(Prospek $prospek, ?array $rekap = null): ?string
    {
        $rekap ??= self::calculateRekap($prospek);
        $rincian = $rekap['rincian'] ?? [];

        foreach (array_reverse(self::STAGES) as $stage) {
            if (((float) ($rincian[$stage]['terbayar'] ?? 0)) > 0) {
                return $stage;
            }
            if ($stage === 'dpp' && (($rincian['dpp']['voucher']['status'] ?? null) === VoucherUsage::STATUS_USED)) {
                return 'dpp';
            }
        }

        return null;
    }

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

        $terbayarTrx = [
            'perlengkapan' => null,
            'pendaftaran'  => null,
            'dpp'          => null,
            'uang_gedung'  => null,
            'dp'           => null,
            'pelunasan'    => null,
        ];

        foreach ($verifiedTransaksis as $t) {
            $detail = (string) $t->detail_pembayaran;
            $jenis  = (string) $t->jenis;
            $cat    = (string) $t->kategori;
            $nom    = (float) $t->nominal;

            // Identifikasi komponen 1x bayar & UKT terlebih dahulu agar kategori 'Lunas' dari input manual CRM tidak menelan komponen 1x bayar
            $hasPerlengkapan = str_contains($detail, 'Perlengkapan') || $jenis === 'Perlengkapan';
            $hasPendaftaran  = str_contains($detail, 'Pendaftaran') || str_contains($detail, 'Formulir')
                || (empty($detail) && in_array($jenis, ['Beli Formulir', 'Pendaftaran', 'Biaya Pendaftaran']));
            $hasDpp          = str_contains($detail, 'DPP') || str_contains($detail, 'Pengembangan Pendidikan') || $jenis === 'DPP';
            $hasGedung       = str_contains($detail, 'Gedung') || str_contains($detail, 'Uang Gedung') || $jenis === 'Gedung';
            $hasUkt          = str_contains($detail, 'UKT') || str_contains($detail, 'Kuliah Tunggal') || $jenis === 'UKT';
            $hasOneTimeComp  = $hasPerlengkapan || $hasPendaftaran || $hasDpp || $hasGedung;

            $isDp = (in_array($cat, Transaksi::getCategoryAliases(Transaksi::KATEGORI_DP))
                || str_contains($detail, 'Down Payment')
                || (str_contains($detail, 'DP') && !str_contains($detail, 'DPP') && !str_contains($detail, 'DP Daftar'))
                || $jenis === 'DP/Konversi')
                && !$hasOneTimeComp
                && !$hasUkt
                && $jenis !== 'Beli Formulir';

            $isPelunasan = (in_array($cat, Transaksi::getCategoryAliases(Transaksi::KATEGORI_PELUNASAN))
                || str_contains($detail, 'Pelunasan')
                || $jenis === 'Pelunasan'
                || $hasUkt)
                && !$hasOneTimeComp
                && $jenis !== 'Beli Formulir';

            if ($isDp) {
                $terbayar['dp'] += $nom;
                $terbayarTrx['dp'] = $t;
                continue;
            }

            if ($isPelunasan) {
                $terbayar['pelunasan'] += $nom;
                $terbayarTrx['pelunasan'] = $t;
                continue;
            }

            $activeComponents = [];
            if ($hasPerlengkapan) $activeComponents[] = 'perlengkapan';
            if ($hasPendaftaran) $activeComponents[] = 'pendaftaran';
            if ($hasDpp) $activeComponents[] = 'dpp';
            if ($hasGedung) $activeComponents[] = 'uang_gedung';

            if (count($activeComponents) === 1) {
                $comp = $activeComponents[0];
                $terbayar[$comp] += $nom;
                $terbayarTrx[$comp] = $t;
            } elseif (count($activeComponents) > 1) {
                // Multi komponen dalam 1 transaksi
                $keywords = [
                    'perlengkapan' => 'Perlengkapan',
                    'pendaftaran'  => 'Pendaftaran|Formulir',
                    'dpp'          => 'DPP|Pengembangan Pendidikan',
                    'uang_gedung'  => 'Gedung',
                ];
                foreach ($activeComponents as $comp) {
                    $compNom = (float) ($kewajiban[$comp] ?? 0);
                    if ($compNom <= 0 && $nom > 0 && preg_match('/(?:' . $keywords[$comp] . ')[^\n]*?Rp\s*([\d\.]+)/i', $detail, $m)) {
                        $compNom = (float) str_replace('.', '', $m[1]);
                    }
                    $terbayar[$comp] += $compNom;
                    $terbayarTrx[$comp] = $t;
                }
            } else {
                // Fallback berdasarkan harga prodi aktual atau nominal standar jika detail pembayaran kosong
                $matchedByProdi = false;
                if ($prodi && $nom > 0) {
                    foreach (self::STAGES as $stageKey) {
                        if (($kewajiban[$stageKey] ?? 0) > 0 && (int) round($nom) === (int) $kewajiban[$stageKey]) {
                            $terbayar[$stageKey] += $nom;
                            $terbayarTrx[$stageKey] = $t;
                            $matchedByProdi = true;
                            break;
                        }
                    }
                }

                if ($matchedByProdi) {
                    // Sudah dicocokkan dengan nominal prodi aktual
                } elseif ($jenis === 'Beli Formulir' || $nom == 300000 || $nom == 250000) {
                    $terbayar['pendaftaran'] += $nom;
                    $terbayarTrx['pendaftaran'] = $t;
                } elseif ($nom == 600000) {
                    $terbayar['perlengkapan'] += 600000;
                    $terbayarTrx['perlengkapan'] = $t;
                } elseif ($nom == 500000) {
                    $terbayar['dpp'] += 500000;
                    $terbayarTrx['dpp'] = $t;
                } elseif ($nom == 1000000) {
                    $terbayar['uang_gedung'] += 1000000;
                    $terbayarTrx['uang_gedung'] = $t;
                } elseif ($nom == 900000) {
                    $terbayar['perlengkapan'] += 600000;
                    $terbayar['pendaftaran'] += 300000;
                    $terbayarTrx['perlengkapan'] = $t;
                    $terbayarTrx['pendaftaran'] = $t;
                } elseif ($nom == 1400000) {
                    $terbayar['perlengkapan'] += 600000;
                    $terbayar['pendaftaran'] += 300000;
                    $terbayar['dpp'] += 500000;
                    $terbayarTrx['perlengkapan'] = $t;
                    $terbayarTrx['pendaftaran'] = $t;
                    $terbayarTrx['dpp'] = $t;
                } elseif ($nom == 800000) {
                    $terbayar['pendaftaran'] += 300000;
                    $terbayar['dpp'] += 500000;
                    $terbayarTrx['pendaftaran'] = $t;
                    $terbayarTrx['dpp'] = $t;
                } elseif ($nom == 1100000) {
                    $terbayar['perlengkapan'] += 600000;
                    $terbayar['dpp'] += 500000;
                    $terbayarTrx['perlengkapan'] = $t;
                    $terbayarTrx['dpp'] = $t;
                } elseif ($nom >= 2000000) {
                    $terbayar['pelunasan'] += $nom;
                    $terbayarTrx['pelunasan'] = $t;
                }
            }
        }

        // Cari transaksi pending jika ada komponen yang belum lunas
        $pendingTransaksis = $transaksis->filter(function ($t) {
            $status = strtolower($t->payment_status ?? '');
            return in_array($status, ['pending', 'menunggu pembayaran']);
        });

        $pendingTrxByComp = [
            'perlengkapan' => null,
            'pendaftaran'  => null,
            'dpp'          => null,
            'uang_gedung'  => null,
            'dp'           => null,
            'pelunasan'    => null,
        ];

        foreach ($pendingTransaksis as $pt) {
            $pDetail = (string) $pt->detail_pembayaran;
            $pJenis  = (string) $pt->jenis;
            $pCat    = (string) $pt->kategori;
            $pNom    = (float) $pt->nominal;

            $pHasOneTime = str_contains($pDetail, 'Perlengkapan') || $pJenis === 'Perlengkapan'
                || str_contains($pDetail, 'Pendaftaran') || str_contains($pDetail, 'Formulir')
                || str_contains($pDetail, 'DPP') || $pJenis === 'DPP'
                || str_contains($pDetail, 'Gedung') || $pJenis === 'Gedung';

            $isDp = (in_array($pCat, Transaksi::getCategoryAliases(Transaksi::KATEGORI_DP))
                || str_contains($pDetail, 'Down Payment')
                || (str_contains($pDetail, 'DP') && !str_contains($pDetail, 'DPP') && !str_contains($pDetail, 'DP Daftar'))
                || $pJenis === 'DP/Konversi') && !$pHasOneTime && $pJenis !== 'Beli Formulir';

            $isPel = (in_array($pCat, Transaksi::getCategoryAliases(Transaksi::KATEGORI_PELUNASAN))
                || str_contains($pDetail, 'Pelunasan')
                || $pJenis === 'Pelunasan'
                || str_contains($pDetail, 'UKT') || $pJenis === 'UKT') && !$pHasOneTime && $pJenis !== 'Beli Formulir';

            if ($isDp) {
                $pendingTrxByComp['dp'] = $pendingTrxByComp['dp'] ?? $pt;
            } elseif ($isPel) {
                $pendingTrxByComp['pelunasan'] = $pendingTrxByComp['pelunasan'] ?? $pt;
            } else {
                if (str_contains($pDetail, 'Perlengkapan') || $pJenis === 'Perlengkapan' || in_array($pNom, [600000, 900000, 1400000, 1100000])) {
                    $pendingTrxByComp['perlengkapan'] = $pendingTrxByComp['perlengkapan'] ?? $pt;
                }
                if (str_contains($pDetail, 'Pendaftaran') || str_contains($pDetail, 'Formulir') || empty($pDetail) || in_array($pNom, [300000, 250000, 900000, 1400000, 800000])) {
                    $pendingTrxByComp['pendaftaran'] = $pendingTrxByComp['pendaftaran'] ?? $pt;
                }
                if (str_contains($pDetail, 'DPP') || $pJenis === 'DPP' || in_array($pNom, [500000, 1400000, 800000, 1100000])) {
                    $pendingTrxByComp['dpp'] = $pendingTrxByComp['dpp'] ?? $pt;
                }
                if (str_contains($pDetail, 'Gedung') || $pJenis === 'Gedung') {
                    $pendingTrxByComp['uang_gedung'] = $pendingTrxByComp['uang_gedung'] ?? $pt;
                }
            }
        }

        // Terapkan potongan voucher DPP aktif/terpakai pada kewajiban DPP (tanpa pengurangan ganda)
        $dppSummary = VoucherDiscountService::getDppSummary($prospek);
        $dppDiscount = (int) ($dppSummary['discount_amount'] ?? 0);
        $kewajibanDppAwal = (int) $kewajiban['dpp'];
        $kewajiban['dpp'] = max(0, $kewajibanDppAwal - $dppDiscount);
        $dppVoucherDisplay = VoucherDiscountService::formatUsageForDisplay($dppSummary['active_usage'] ?? null);

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

            $assignedTrx = $terbayarTrx[$key] ?? null;
            $pendingTrx = (!$assignedTrx) ? ($pendingTrxByComp[$key] ?? null) : null;
            $activeTrx = $assignedTrx ?? $pendingTrx;

            $metodeDetail = null;
            $metodeBadge = null;

            if ($assignedTrx) {
                $metodeDetail = $assignedTrx->metode_detail;
                $metodeBadge = $assignedTrx->metode_badge;
            } elseif ($pendingTrx) {
                $metodeDetail = $pendingTrx->metode_detail . ' (Pending)';
                $metodeBadge = array_merge($pendingTrx->metode_badge, [
                    'sub' => $pendingTrx->metode_badge['sub'] . ' • Pending',
                ]);
            } elseif ($targetNominal == 0 && $isLunas) {
                $metodeDetail = ($key === 'dpp' && $dppVoucherDisplay) ? ('Voucher ' . $dppVoucherDisplay['voucher_code']) : 'Bebas Biaya';
                $metodeBadge = [
                    'name'        => ($key === 'dpp' && $dppVoucherDisplay) ? 'Voucher 100%' : 'Bebas Biaya',
                    'sub'         => ($key === 'dpp' && $dppVoucherDisplay) ? $dppVoucherDisplay['voucher_code'] : 'Gratis',
                    'badge_class' => 'bg-slate-50 text-slate-500 border-slate-200',
                    'icon'        => 'none',
                ];
            } else {
                $metodeDetail = 'Belum Ada';
                $metodeBadge = null;
            }

            $rincian[$key] = [
                'nama'         => match($key) {
                    'perlengkapan' => 'Biaya Perlengkapan Kuliah',
                    'pendaftaran'  => 'Biaya Pendaftaran PMB',
                    'dpp'          => 'DPP (Dana Pengembangan Pendidikan)',
                    'uang_gedung'  => 'Biaya Uang Gedung',
                    'dp'           => 'Down Payment (DP)',
                    'pelunasan'    => 'Pelunasan Biaya Kuliah',
                },
                'kewajiban'    => $targetNominal,
                'terbayar'     => $paidNominal,
                'sisa'         => $sisaNominal,
                'is_lunas'     => $isLunas,
                'status'       => $isLunas ? 'Lunas' : 'Belum Lunas',
                'metode'       => $metodeDetail,
                'metode_badge' => $metodeBadge,
                'order_id'     => $activeTrx?->order_id,
            ];

            if ($key === 'dpp') {
                $rincian[$key]['kewajiban_awal'] = $kewajibanDppAwal;
                $rincian[$key]['diskon_voucher'] = $dppDiscount;
                $rincian[$key]['voucher']        = $dppVoucherDisplay;
                if ($dppVoucherDisplay && $dppVoucherDisplay['status'] === VoucherUsage::STATUS_APPLIED && $targetNominal === 0 && $paidNominal === 0) {
                    $rincian[$key]['is_lunas'] = false;
                    $rincian[$key]['status']   = 'Menunggu Konfirmasi';
                }
            }
        }

        $totalKewajiban = $totalTagihan;
        $totalTerbayar = array_sum($terbayar);
        $totalSisa = max(0, $totalKewajiban - $totalTerbayar);
        $isSemuaLunas = ($totalSisa === 0 && ($totalTerbayar > 0 || ($dppVoucherDisplay['status'] ?? null) === VoucherUsage::STATUS_USED));

        $pendingTrxCount = $transaksis->filter(function ($t) {
            $status = strtolower($t->payment_status ?? '');
            return in_array($status, ['pending', 'menunggu pembayaran']);
        })->count();

        return [
            'rincian'            => $rincian,
            'dpp_voucher'        => $dppVoucherDisplay,
            'total_kewajiban'    => $totalKewajiban,
            'total_terbayar'     => $totalTerbayar,
            'total_sisa'         => $totalSisa,
            'is_lunas'           => $isSemuaLunas,
            'status_keseluruhan' => $isSemuaLunas ? 'Lunas' : 'Belum Lunas',
            'pending_count'      => $pendingTrxCount,
            'total_transaksi'    => $transaksis->count(),
        ];
    }

    /**
     * Validasi nilai parameter filter tahap pembayaran dari query string.
     */
    private static function resolveActiveTahap(Request $request): string
    {
        $tahap = strtolower(trim((string) $request->input('tahap', 'all')));
        return in_array($tahap, array_merge(['all'], self::STAGES), true) ? $tahap : 'all';
    }

    /**
     * Bangun query Prospek berdasarkan filter pencarian, metode, periode, dan status.
     */
    private function buildFilteredQuery(Request $request)
    {
        $query = Prospek::with([
            'prodi',
            'transaksis' => fn($q) => $q->orderByDesc('tanggal')->orderByDesc('id'),
            'voucherUsages.voucher',
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
                } elseif ($metode === 'MIDTRANS_QRIS' || $metode === 'QRIS') {
                    $tq->where(function ($mq) {
                        $mq->where('payment_type', 'like', '%qris%')
                           ->orWhere('metode_pembayaran', 'like', '%qris%');
                    });
                } elseif ($metode === 'MIDTRANS_VA' || $metode === 'VA') {
                    $tq->where(function ($mq) {
                        $mq->where('payment_type', 'like', '%bank_transfer%')
                           ->orWhere('payment_type', 'like', '%echannel%')
                           ->orWhere('payment_type', 'like', '%va%')
                           ->orWhere('payment_type', 'like', '%bca%')
                           ->orWhere('payment_type', 'like', '%bni%')
                           ->orWhere('payment_type', 'like', '%bri%')
                           ->orWhere('payment_type', 'like', '%mandiri%')
                           ->orWhere('payment_type', 'like', '%permata%')
                           ->orWhere('metode_pembayaran', 'like', '%va%');
                    });
                } elseif ($metode === 'MIDTRANS_EWALLET' || $metode === 'EWALLET') {
                    $tq->where(function ($mq) {
                        $mq->where('payment_type', 'like', '%gopay%')
                           ->orWhere('payment_type', 'like', '%shopee%');
                    });
                } elseif ($metode === 'OFFLINE' || str_contains($metode, 'TUNAI') || str_contains($metode, 'KASIR')) {
                    $tq->where(function ($mq) {
                        $mq->where('metode_pembayaran', 'OFFLINE')
                           ->orWhere('metode_pembayaran', 'like', '%TUNAI%')
                           ->orWhere('metode_pembayaran', 'like', '%KASIR%');
                    });
                } elseif ($metode === 'MIDTRANS' || $metode === 'ONLINE') {
                    $tq->where(function ($mq) {
                        $mq->where('metode_pembayaran', 'MIDTRANS')
                           ->orWhere('metode_pembayaran', 'like', '%VA%')
                           ->orWhere('metode_pembayaran', 'like', '%QRIS%')
                           ->orWhere('metode_pembayaran', 'like', '%GOPAY%')
                           ->orWhere('metode_pembayaran', 'like', '%BANK_TRANSFER%')
                           ->orWhereNotNull('snap_token');
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

        return $query;
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
        $quickFilter = $request->input('status', $request->input('quick_filter', 'all'));
        $activeTahap = self::resolveActiveTahap($request);

        $matchedProspeks = $this->buildFilteredQuery($request)->orderByDesc('id')->get();

        // Hitung rekap & tahap tertinggi tiap Camaba untuk metric cards (Semua, Pendaftaran, Perlengkapan, DPP, Uang Gedung)
        $stageCounts = [
            'all'          => 0,
            'pendaftaran'  => 0,
            'perlengkapan' => 0,
            'dpp'          => 0,
            'uang_gedung'  => 0,
        ];

        foreach ($matchedProspeks as $p) {
            $p->rekap = self::calculateRekap($p);
            $p->payment_stage = self::determinePaymentStage($p, $p->rekap);
            $stageCounts['all']++;
            if ($p->payment_stage !== null && isset($stageCounts[$p->payment_stage])) {
                $stageCounts[$p->payment_stage]++;
            }
        }

        $countSemua             = $stageCounts['all'];
        $countStagePendaftaran  = $stageCounts['pendaftaran'];
        $countStagePerlengkapan = $stageCounts['perlengkapan'];
        $countStageDpp          = $stageCounts['dpp'];
        $countStageUangGedung   = $stageCounts['uang_gedung'];

        $filteredCollection = $activeTahap === 'all'
            ? $matchedProspeks
            : $matchedProspeks->filter(fn($p) => $p->payment_stage === $activeTahap)->values();

        // List metode pembayaran untuk dropdown filter
        $metodeList = collect(['MIDTRANS', 'TRANSFER_MANUAL']);

        // Pagination
        $perPage = (int) $request->input('per_page', 10);
        if (!in_array($perPage, [10, 25, 50, 100])) {
            $perPage = 10;
        }

        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $rekapList = new LengthAwarePaginator(
            $filteredCollection->forPage($currentPage, $perPage)->values(),
            $filteredCollection->count(),
            $perPage,
            $currentPage,
            [
                'path'  => $request->url(),
                'query' => $request->query(),
            ]
        );

        // Alias transaksis agar kompatibel penuh jika ada referensi lama
        $transaksis = $rekapList;

        // Data Master Voucher & Ringkasan Penggunaan Voucher CAMABA
        $vouchers = Voucher::with([
            'usages' => fn($q) => $q->whereIn('status', [VoucherUsage::STATUS_USED, VoucherUsage::STATUS_APPLIED])
                ->whereHas('prospek')
                ->orderByDesc('id'),
            'usages.prospek',
            'creator',
        ])->orderByDesc('id')->get();

        $voucherSummaryUsages = VoucherUsage::with(['prospek', 'voucher'])
            ->whereIn('status', [VoucherUsage::STATUS_USED, VoucherUsage::STATUS_APPLIED])
            ->whereHas('prospek')
            ->orderByDesc('id')
            ->get();

        $trashCount = strtolower((string) (auth()->user()->role ?? '')) === 'admin'
            ? Prospek::onlyTrashed()->count()
            : 0;

        return view('pmb.pembayaran.index', compact(
            'rekapList',
            'transaksis',
            'countSemua',
            'countStagePendaftaran',
            'countStagePerlengkapan',
            'countStageDpp',
            'countStageUangGedung',
            'stageCounts',
            'activeTahap',
            'metodeList',
            'quickFilter',
            'vouchers',
            'voucherSummaryUsages',
            'trashCount'
        ));
    }

    /**
     * Pastikan hanya role Admin yang diizinkan mengelola penghapusan & Trash data pembayaran.
     */
    private function authorizeAdminOnly(): \App\Models\User
    {
        $user = auth()->user();
        if (!$user || strtolower((string) $user->role) !== 'admin') {
            abort(403, 'Hanya Admin yang berwenang melakukan aksi ini.');
        }

        return $user;
    }

    /**
     * Soft delete data pembayaran CAMABA ke Trash (tanpa menghapus transaksi pembayaran atau voucher).
     */
    public function destroy(Request $request, int $id)
    {
        $admin = $this->authorizeAdminOnly();
        $prospek = Prospek::findOrFail($id);

        $nama = $prospek->name;
        $regNo = $prospek->no_registrasi ?: ('REG-' . str_pad((string) $prospek->id, 4, '0', STR_PAD_LEFT));

        DB::transaction(function () use ($prospek, $admin) {
            $prospek->deleted_by = $admin->id;
            $prospek->saveQuietly();
            $prospek->delete();
        });

        return redirect()
            ->route('pmb.pembayaran.index', $request->only(['search', 'periode', 'metode', 'status', 'tahap', 'per_page']))
            ->with('success', "Data pembayaran {$nama} ({$regNo}) berhasil dipindahkan ke Trash.");
    }

    /**
     * Menampilkan halaman Trash (Tempat Sampah) data pembayaran CAMABA yang telah di-soft-delete.
     */
    public function trash(Request $request): View
    {
        $this->authorizeAdminOnly();

        $query = Prospek::onlyTrashed()->with([
            'prodi',
            'deletedBy',
            'transaksis' => fn($q) => $q->orderByDesc('tanggal')->orderByDesc('id'),
            'voucherUsages.voucher',
        ])->withCount([
            'transaksis',
            'voucherUsages',
        ]);

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('no_registrasi', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('whatsapp', 'like', "%{$search}%")
                    ->orWhereHas('prodi', fn($pq) => $pq->where('nama', 'like', "%{$search}%"))
                    ->orWhereHas('transaksis', fn($tq) => $tq->where('order_id', 'like', "%{$search}%"));
            });
        }

        $perPage = (int) $request->input('per_page', 10);
        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $trashedList = $query->orderByDesc('deleted_at')->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        foreach ($trashedList as $item) {
            $item->rekap = self::calculateRekap($item);
        }

        $totalTrashed = Prospek::onlyTrashed()->count();

        return view('pmb.pembayaran.trash', compact('trashedList', 'totalTrashed'));
    }

    /**
     * Memulihkan data pembayaran CAMABA dari Trash kembali ke daftar utama tanpa mengubah transaksi & voucher.
     */
    public function restore(int $id)
    {
        $this->authorizeAdminOnly();
        $prospek = Prospek::onlyTrashed()->findOrFail($id);

        $nama = $prospek->name;
        $regNo = $prospek->no_registrasi ?: ('REG-' . str_pad((string) $prospek->id, 4, '0', STR_PAD_LEFT));

        DB::transaction(function () use ($prospek) {
            $prospek->deleted_by = null;
            $prospek->saveQuietly();
            $prospek->restore();
        });

        return redirect()
            ->route('pmb.pembayaran.trash')
            ->with('success', "Data pembayaran {$nama} ({$regNo}) berhasil dipulihkan ke daftar utama Data Pembayaran.");
    }

    /**
     * Hapus permanen data di Trash setelah memeriksa integritas relasi transaksi & voucher.
     */
    public function forceDelete(int $id)
    {
        $this->authorizeAdminOnly();
        $prospek = Prospek::onlyTrashed()->findOrFail($id);

        $nama = $prospek->name;
        $regNo = $prospek->no_registrasi ?: ('REG-' . str_pad((string) $prospek->id, 4, '0', STR_PAD_LEFT));

        $trxCount = $prospek->transaksis()->count();
        $voucherCount = $prospek->voucherUsages()->count();

        if ($trxCount > 0 || $voucherCount > 0) {
            return redirect()
                ->route('pmb.pembayaran.trash')
                ->with('error', "Data {$nama} ({$regNo}) tidak dapat dihapus permanen karena memiliki {$trxCount} riwayat transaksi pembayaran dan {$voucherCount} riwayat voucher yang harus tetap dijaga integritasnya.");
        }

        DB::transaction(function () use ($prospek) {
            // Jangan hapus file fisik berkas/bukti pembayaran; hanya hapus permanen prospek tanpa histori keuangan.
            $prospek->forceDelete();
        });

        return redirect()
            ->route('pmb.pembayaran.trash')
            ->with('success', "Data {$nama} ({$regNo}) berhasil dihapus secara permanen dari database.");
    }

    /**
     * Admin membuat voucher diskon DPP baru.
     */
    public function storeVoucher(Request $request)
    {
        $request->merge([
            'code' => strtoupper(trim((string) $request->input('code'))),
            'name' => $request->filled('name') ? trim((string) $request->input('name')) : null,
        ]);

        $validated = $request->validate([
            'name'                => 'nullable|string|max:150',
            'code'                => 'required|string|max:50|alpha_dash|unique:vouchers,code',
            'discount_percentage' => 'required|numeric|min:1|max:100',
            'is_active'           => 'required|boolean',
        ], [
            'name.max'                     => 'Nama voucher maksimal 150 karakter.',
            'code.required'                => 'Kode voucher wajib diisi.',
            'code.alpha_dash'              => 'Kode voucher hanya boleh berisi huruf, angka, tanda hubung (-), atau garis bawah (_).',
            'code.unique'                  => 'Kode voucher sudah terdaftar.',
            'discount_percentage.required' => 'Persentase diskon wajib diisi.',
            'discount_percentage.numeric'  => 'Persentase diskon harus berupa angka.',
            'discount_percentage.min'      => 'Persentase diskon minimal 1%.',
            'discount_percentage.max'      => 'Persentase diskon maksimal 100%.',
        ]);

        $voucher = Voucher::create([
            'code'                => $validated['code'],
            'name'                => $validated['name'] ?? null,
            'discount_percentage' => $validated['discount_percentage'],
            'is_active'           => $request->boolean('is_active'),
            'created_by'          => auth()->id(),
        ]);

        $voucherLabel = $voucher->name ? "{$voucher->name} ({$voucher->code})" : "'{$voucher->code}'";

        return redirect()->back()->with('success', "Voucher {$voucherLabel} ({$voucher->formatted_percentage}%) berhasil ditambahkan.");
    }

    /**
     * Admin memperbarui nama, persentase diskon, atau status voucher.
     * Tidak mengubah riwayat transaksi / VoucherUsage CAMABA yang sudah tersimpan.
     */
    public function updateVoucher(Request $request, Voucher $voucher)
    {
        if ($request->has('name')) {
            $request->merge([
                'name' => $request->filled('name') ? trim((string) $request->input('name')) : null,
            ]);
        }

        $validated = $request->validate([
            'name'                => 'nullable|string|max:150',
            'discount_percentage' => 'required|numeric|min:1|max:100',
            'is_active'           => 'required|boolean',
        ], [
            'name.max'                     => 'Nama voucher maksimal 150 karakter.',
            'discount_percentage.required' => 'Persentase diskon wajib diisi.',
            'discount_percentage.min'      => 'Persentase diskon minimal 1%.',
            'discount_percentage.max'      => 'Persentase diskon maksimal 100%.',
        ]);

        $updateData = [
            'discount_percentage' => $validated['discount_percentage'],
            'is_active'           => $request->boolean('is_active'),
        ];
        if (array_key_exists('name', $validated)) {
            $updateData['name'] = $validated['name'];
        }

        $voucher->update($updateData);

        $voucherLabel = $voucher->name ? "{$voucher->name} ({$voucher->code})" : "'{$voucher->code}'";

        return redirect()->back()->with('success', "Pengaturan voucher {$voucherLabel} berhasil diperbarui ({$voucher->formatted_percentage}%).");
    }

    /**
     * Admin mengaktifkan / menonaktifkan voucher dengan cepat.
     */
    public function toggleVoucher(Voucher $voucher)
    {
        $voucher->is_active = !$voucher->is_active;
        $voucher->save();

        $statusText = $voucher->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()->back()->with('success', "Voucher '{$voucher->code}' berhasil {$statusText}.");
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
        $activeTahap = self::resolveActiveTahap($request);
        $camabaList = $this->buildFilteredQuery($request)->orderByDesc('id')->get();

        if ($activeTahap !== 'all') {
            $camabaList = $camabaList->filter(function ($p) use ($activeTahap) {
                $p->rekap = self::calculateRekap($p);
                return self::determinePaymentStage($p, $p->rekap) === $activeTahap;
            })->values();
        }

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
                'Metode Pembayaran',
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
                $verifiedTrx = $p->transaksis->firstWhere('payment_status', Transaksi::STATUS_VERIFIED);
                $primaryTrx = $verifiedTrx ?? $latestTrx;
                $metodeText = $primaryTrx ? $primaryTrx->metode_detail : 'Belum Memilih';

                fputcsv($handle, [
                    $no++,
                    $latestTrx?->order_id ?: ('TRX-' . str_pad($p->id, 5, '0', STR_PAD_LEFT)),
                    $p->no_registrasi ?? '-',
                    $p->name ?? '-',
                    $p->email ?? '-',
                    $p->whatsapp ?? '-',
                    $p->prodi?->nama_prodi ?? '-',
                    $metodeText,
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
