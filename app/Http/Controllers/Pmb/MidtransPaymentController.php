<?php

namespace App\Http\Controllers\Pmb;

use App\Http\Controllers\Controller;
use App\Models\Prospek;
use App\Models\Transaksi;
use App\Services\MidtransService;
use App\Services\VoucherDiscountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MidtransPaymentController extends Controller
{
    protected MidtransService $midtransService;

    public function __construct(MidtransService $midtransService)
    {
        $this->midtransService = $midtransService;
    }

    /**
     * Memulai alur checkout Midtrans Snap untuk biaya pendaftaran CAMABA.
     * Backend memvalidasi hak akses dan menentukan nominal tagihan secara otoritatif.
     */
    public function checkout(Request $request, $prospekId = null): JsonResponse
    {
        $user = auth()->user();
        $prospek = $prospekId ? Prospek::with('prodi')->find($prospekId) : null;

        // Fallback: Jika ID prospek tidak ditemukan atau out-of-sync, gunakan prospek aktif milik Camaba yang sedang login
        if (!$prospek && $user && strtolower($user->role ?? '') === 'camaba') {
            $prospek = app(\App\Http\Controllers\Pmb\CamabaPortalController::class)->getCamabaProspek();
            $prospek?->loadMissing('prodi');
        }

        if (!$prospek) {
            return response()->json([
                'success' => false,
                'message' => 'Data calon mahasiswa tidak ditemukan atau sesi pendaftaran telah diperbarui. Silakan muat ulang (refresh) halaman.',
            ], 404);
        }

        // Camaba wajib mengisi form dan memilih prodi terlebih dahulu
        if ($user && strtolower($user->role ?? '') === 'camaba') {
            if (empty($prospek->prodi_id) || ($prospek->status_formulir ?? '') !== 'Sudah Isi') {
                return response()->json([
                    'success' => false,
                    'message' => 'Silakan lengkapi formulir pendaftaran dan pilih Program Studi terlebih dahulu sebelum melakukan pembayaran.',
                    'redirect_url' => route('pmb.akun.profil'),
                ], 422);
            }
        }

        // Otorisasi: Staff CRM atau akses valid CAMABA
        if ($user) {
            $userRole = strtolower($user->role ?? '');
            $allowedStaff = ['admin', 'hm', 'cs', 'sales', 'spv'];
            $isStaff = in_array($userRole, $allowedStaff);
            $isOwnCamaba = ($prospek->user_id == $user->id) ||
                (!empty($prospek->email) && !empty($user->email) && strtolower($prospek->email) === strtolower($user->email)) ||
                (!empty($prospek->whatsapp) && !empty($user->phone) && $prospek->whatsapp === $user->phone);

            if (!$isStaff && !$isOwnCamaba) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki hak akses untuk memproses pembayaran pendaftar ini.',
                ], 403);
            }
        } else {
            // Untuk CAMABA publik, validasi signed URL atau token akses
            if (!$request->hasValidSignatureWhileIgnoring(['token', 'snap_token'])) {
                // Periksa apakah ada order_id yang sedang berlangsung milik prospek ini
                $activeOrder = $request->input('order_id');
                $isMatched = false;
                if ($activeOrder) {
                    $isMatched = Transaksi::where('order_id', $activeOrder)
                        ->where('prospek_id', $prospek->id)
                        ->exists();
                }

                if (!$isMatched) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Sesi pembayaran tidak valid atau telah kadaluarsa.',
                    ], 403);
                }
            }
        }

        try {
            $category = $request->input('category', $request->input('kategori', Transaksi::KATEGORI_PENDAFTARAN));
            $normalizedCategory = Transaksi::normalizeCategory($category);
            $forceNew = $request->boolean('fresh') || $request->boolean('new_order');

            $selectedKeys = [];
            $uktPeriod = $request->input('ukt_period');
            $uktPeriod = is_string($uktPeriod) && trim($uktPeriod) !== '' ? trim($uktPeriod) : null;

            if ($normalizedCategory === Transaksi::KATEGORI_PENDAFTARAN && $request->has('items')) {
                $rawItems = $request->input('items');
                if (!is_array($rawItems) || empty($rawItems)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Silakan pilih minimal satu komponen biaya pembayaran.',
                    ], 422);
                }

                $activeUktOptions = MidtransService::getActiveUktCatalogOptions($prospek);
                if ($uktPeriod !== null && in_array('ukt', $rawItems, true) && !isset($activeUktOptions[$uktPeriod])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Pilihan periode UKT tidak aktif atau tidak tersedia untuk program studi ini.',
                    ], 422);
                }

                // Saring komponen yang sudah lunas atau bernilai Rp 0 (Gratis)
                if ($request->filled('voucher_code') && in_array('dpp', $rawItems, true)) {
                    $existingSummary = VoucherDiscountService::getDppSummary($prospek);
                    $reqCode = strtoupper(trim((string) $request->input('voucher_code')));
                    if (!$existingSummary['active_usage'] || $existingSummary['active_usage']->voucher_code !== $reqCode) {
                        VoucherDiscountService::applyVoucher($prospek, $reqCode);
                        $prospek->load(['prodi', 'transaksis']);
                    }
                }

                $catalog = MidtransService::getBiayaCatalog($prospek, $uktPeriod);
                $paidItems = MidtransService::resolvePaidItems($prospek);
                $unpaidItems = array_values(array_filter(
                    $rawItems,
                    fn($k) => empty($paidItems[$k]) && ((int) ($catalog[$k]['price'] ?? 0) > 0 || ($k === 'dpp' && !empty($catalog[$k]['discount_amount'])))
                ));
                if (empty($unpaidItems)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Seluruh komponen biaya yang dipilih sudah lunas terverifikasi atau bernilai Rp 0 (Gratis).',
                    ], 422);
                }

                $calculated = MidtransService::calculateItems($unpaidItems, $prospek, $uktPeriod);
                $isZeroVoucherDpp = ($calculated['total'] === 0 && in_array('dpp', $calculated['item_keys'], true) && !empty($catalog['dpp']['discount_amount']));
                if (($calculated['total'] <= 0 && !$isZeroVoucherDpp) || empty($calculated['item_keys'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Silakan pilih minimal satu komponen biaya pembayaran.',
                    ], 422);
                }
                $selectedKeys = $calculated['item_keys'];
            }

            $paymentMethod = $request->input('payment_method', 'online');
            $isManual = in_array(strtolower($paymentMethod), ['transfer_manual', 'manual', 'bank_transfer']);
            $dbPaymentMethod = $isManual ? 'TRANSFER_MANUAL' : 'MIDTRANS';

            // Dapatkan transaksi lokal yang ada atau buat baru secara aman dan atomik
            $transaksi = $this->midtransService->createCategoryTransaksi(
                $prospek,
                $normalizedCategory,
                $user,
                $forceNew,
                $selectedKeys,
                $dbPaymentMethod,
                $uktPeriod
            );

            $txPaymentType = strtolower($transaksi->payment_type ?? '');
            $txRawMetode = strtoupper($transaksi->metode_pembayaran ?? '');
            $txMetodeLabel = match(true) {
                str_contains($txPaymentType, 'echannel') || str_contains($txPaymentType, 'mandiri') => 'Mandiri VA',
                str_contains($txPaymentType, 'bca') => 'BCA VA',
                str_contains($txPaymentType, 'bni') => 'BNI VA',
                str_contains($txPaymentType, 'bri') => 'BRI VA',
                str_contains($txPaymentType, 'qris') => 'QRIS',
                str_contains($txPaymentType, 'gopay') => 'GoPay',
                $txRawMetode === 'MIDTRANS' => 'Virtual Account',
                $txRawMetode === 'BANK_TRANSFER' => 'Transfer Bank',
                $txRawMetode === 'TRANSFER_MANUAL' => 'Transfer Manual',
                $txRawMetode === 'OFFLINE' => 'Tunai / Kasir',
                !empty($transaksi->metode_pembayaran) => ucwords(str_replace('_', ' ', strtolower($transaksi->metode_pembayaran))),
                default => 'Transfer Bank',
            };

            $transaksiItem = [
                'id'                => $transaksi->id,
                'order_id'          => $transaksi->order_id ?: ('TRX-' . $transaksi->id),
                'kategori'          => $transaksi->kategori ?? 'Biaya Pendaftaran',
                'detail_pembayaran' => $transaksi->detail_pembayaran ?: ($transaksi->kategori ?: 'Biaya PMB'),
                'nominal'           => (int) $transaksi->nominal,
                'nominal_formatted' => 'Rp ' . number_format($transaksi->nominal, 0, ',', '.'),
                'tanggal'           => now()->format('d M Y, H:i') . ' WIB',
                'metode_label'      => $txMetodeLabel,
                'payment_status'    => 'pending',
                'snap_token'        => $transaksi->snap_token ?? '',
            ];

            if ($transaksi->payment_status === Transaksi::STATUS_VERIFIED && (int) $transaksi->nominal === 0) {
                $transaksiItem['payment_status'] = Transaksi::STATUS_VERIFIED;
                return response()->json([
                    'success'           => true,
                    'voucher_settled'   => true,
                    'order_id'          => $transaksi->order_id,
                    'kategori'          => $transaksi->kategori,
                    'metode_pembayaran' => $transaksi->metode_pembayaran,
                    'payment_status'    => Transaksi::STATUS_VERIFIED,
                    'nominal'           => 0,
                    'nominal_formatted' => 'Rp 0',
                    'detail'            => $transaksi->detail_pembayaran,
                    'transaksi_item'    => $transaksiItem,
                ]);
            }

            if ($isManual) {
                return response()->json([
                    'success'           => true,
                    'order_id'          => $transaksi->order_id,
                    'kategori'          => $transaksi->kategori,
                    'metode_pembayaran' => 'TRANSFER_MANUAL',
                    'nominal'           => (float) $transaksi->nominal,
                    'nominal_formatted' => 'Rp ' . number_format($transaksi->nominal, 0, ',', '.'),
                    'detail'            => $transaksi->detail_pembayaran,
                    'transaksi_item'    => $transaksiItem,
                ]);
            }

            // Dapatkan Snap Token dari Midtrans (selalu pastikan sinkron dengan metode pembayaran aktif di Admin)
            $snap = $this->midtransService->createSnapToken($transaksi, true);
            $transaksiItem['snap_token'] = $snap['token'];

            return response()->json([
                'success'           => true,
                'order_id'          => $transaksi->order_id,
                'kategori'          => $transaksi->kategori,
                'snap_token'        => $snap['token'],
                'snap_redirect_url' => $snap['redirect_url'],
                'client_key'        => (string) config('services.midtrans.client_key'),
                'nominal'           => (float) $transaksi->nominal,
                'nominal_formatted' => 'Rp ' . number_format($transaksi->nominal, 0, ',', '.'),
                'is_production'     => (bool) config('services.midtrans.is_production', false),
                'transaksi_item'    => $transaksiItem,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memproses pembayaran: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Memeriksa status aktual pembayaran suatu Order ID dari database lokal.
     */
    public function status(Request $request, $orderId): JsonResponse
    {
        $transaksi = Transaksi::where('order_id', $orderId)->with('prospek')->first();

        if (!$transaksi) {
            return response()->json([
                'success' => false,
                'message' => "Tagihan dengan Order ID {$orderId} tidak ditemukan.",
            ], 404);
        }

        // Jika belum verified, coba sinkronisasi status terbaru langsung dari Midtrans API
        // (Verifikasi keamanan dilakukan otoritatif di backend via SHA-512 signature)
        if ($transaksi->payment_status !== Transaksi::STATUS_VERIFIED) {
            $synced = $this->midtransService->syncStatusFromMidtransApi($transaksi->order_id);
            if ($synced) {
                $transaksi = $synced;
            }
        }

        $paymentStatus = strtolower($transaksi->payment_status ?? 'pending');
        $isVerified = in_array($paymentStatus, [Transaksi::STATUS_VERIFIED, 'paid']);
        $isPending = in_array($paymentStatus, [Transaksi::STATUS_PENDING, 'pending']);
        $isRejected = in_array($paymentStatus, [Transaksi::STATUS_REJECTED, 'failed', 'batal']);

        return response()->json([
            'success'           => true,
            'order_id'          => $transaksi->order_id,
            'payment_status'    => $transaksi->payment_status,
            'is_verified'       => $isVerified,
            'is_pending'        => $isPending,
            'is_rejected'       => $isRejected,
            'nominal'           => (float) $transaksi->nominal,
            'nominal_formatted' => 'Rp ' . number_format($transaksi->nominal, 0, ',', '.'),
            'rejection_reason'  => $transaksi->rejection_reason,
            'metode_pembayaran' => $transaksi->metode_pembayaran,
            'payment_type'      => $transaksi->payment_type,
            'pendaftar_name'    => $transaksi->prospek?->name,
            'tanggal'           => $transaksi->tanggal ? $transaksi->tanggal->format('d M Y, H:i') : null,
        ]);
    }

    /**
     * Halaman pembayaran khusus CAMABA (Customer-facing payment page).
     */
    public function showPaymentPage(Request $request, $orderId): View
    {
        $transaksi = Transaksi::where('order_id', $orderId)->with(['prospek.prodi', 'prospek.sekolah'])->firstOrFail();
        $pendaftar = $transaksi->prospek;

        if ($transaksi->payment_status !== Transaksi::STATUS_VERIFIED) {
            $synced = $this->midtransService->syncStatusFromMidtransApi($transaksi->order_id);
            if ($synced) {
                $transaksi = $synced;
            }
        }

        return view('pmb.pembayaran.camaba-pay', compact('transaksi', 'pendaftar'));
    }
}
