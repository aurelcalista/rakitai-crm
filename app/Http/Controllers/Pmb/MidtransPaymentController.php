<?php

namespace App\Http\Controllers\Pmb;

use App\Http\Controllers\Controller;
use App\Models\Prospek;
use App\Models\Transaksi;
use App\Services\MidtransService;
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
    public function checkout(Request $request, $prospekId): JsonResponse
    {
        $prospek = Prospek::findOrFail($prospekId);

        // Otorisasi: Staff CRM atau akses valid CAMABA
        $user = auth()->user();
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
            $rawItems = $request->input('items', ['pendaftaran']);
            if (!is_array($rawItems) || empty($rawItems)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Silakan pilih minimal satu komponen biaya pembayaran.',
                ], 422);
            }

            $calculated = MidtransService::calculateItems($rawItems);
            if ($calculated['total'] <= 0 || empty($calculated['item_keys'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Silakan pilih minimal satu komponen biaya pembayaran.',
                ], 422);
            }

            $forceNew = $request->boolean('fresh') || $request->boolean('new_order');
            // Dapatkan transaksi lokal yang ada atau buat baru (nominal diatur backend otoritatif)
            $transaksi = $this->midtransService->getOrCreateRegistrationTransaksi(
                $prospek,
                $user,
                $forceNew,
                $calculated['item_keys']
            );

            // Dapatkan Snap Token dari Midtrans
            $snap = $this->midtransService->createSnapToken($transaksi);

            return response()->json([
                'success'           => true,
                'order_id'          => $transaksi->order_id,
                'snap_token'        => $snap['token'],
                'snap_redirect_url' => $snap['redirect_url'],
                'client_key'        => (string) config('services.midtrans.client_key'),
                'nominal'           => (float) $transaksi->nominal,
                'nominal_formatted' => 'Rp ' . number_format($transaksi->nominal, 0, ',', '.'),
                'is_production'     => (bool) config('services.midtrans.is_production', false),
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
