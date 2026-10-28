<?php

namespace App\Http\Controllers\Pmb;

use App\Http\Controllers\Controller;
use App\Services\MidtransService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class MidtransWebhookController extends Controller
{
    protected MidtransService $midtransService;

    public function __construct(MidtransService $midtransService)
    {
        $this->midtransService = $midtransService;
    }

    /**
     * Menerima notifikasi status transaksi HTTP POST dari server Midtrans.
     * Tidak dilindungi middleware session/login agar dapat diakses server Midtrans.
     * Menggunakan validasi signature SHA512 dan pencocokan nominal tagihan.
     */
    public function handleNotification(Request $request): JsonResponse
    {
        // Midtrans mengirimkan JSON payload di body
        $payload = $request->json()->all();
        if (empty($payload)) {
            $payload = $request->all();
        }

        $orderId = $payload['order_id'] ?? 'unknown';

        Log::info('Midtrans Webhook: Menerima notifikasi', [
            'order_id'           => $orderId,
            'status_code'        => $payload['status_code'] ?? null,
            'transaction_status' => $payload['transaction_status'] ?? null,
        ]);

        try {
            $result = $this->midtransService->processNotification($payload);

            return response()->json([
                'status'  => 'success',
                'message' => 'Notifikasi Midtrans berhasil diproses.',
                'data'    => $result,
            ], 200);
        } catch (InvalidArgumentException $e) {
            Log::warning('Midtrans Webhook Warning: ' . $e->getMessage(), [
                'order_id' => $orderId,
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 400);
        } catch (ModelNotFoundException $e) {
            Log::warning('Midtrans Webhook: ' . $e->getMessage(), [
                'order_id' => $orderId,
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 404);
        } catch (\Throwable $e) {
            Log::error('Midtrans Webhook Error: ' . $e->getMessage(), [
                'order_id' => $orderId,
                'file'     => $e->getFile(),
                'line'     => $e->getLine(),
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Terjadi kesalahan sistem saat memproses notifikasi pembayaran.',
            ], 500);
        }
    }
}
