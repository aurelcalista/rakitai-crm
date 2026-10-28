<?php

namespace App\Services;

use App\Models\Prospek;
use App\Models\ProspekTimeline;
use App\Models\TahunAkademik;
use App\Models\Transaksi;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Midtrans\Config;
use Midtrans\Snap;

class MidtransService
{
    /**
     * Konfigurasi Midtrans SDK dengan server key dan environment yang aman.
     */
    public static function configure(): void
    {
        Config::$serverKey = (string) config('services.midtrans.server_key');
        Config::$clientKey = (string) config('services.midtrans.client_key');
        Config::$isProduction = (bool) config('services.midtrans.is_production', false);
        Config::$isSanitized = true;
        Config::$is3ds = true;
    }

    /**
     * Ambil nominal resmi biaya pendaftaran PMB UCIC (default Rp 300.000).
     */
    public static function getBiayaPendaftaran(): int
    {
        return (int) config('services.midtrans.biaya_pendaftaran', 300000);
    }

    /**
     * Dapatkan tagihan pendaftaran yang sudah ada atau buat transaksi lokal baru.
     * Mencegah transaksi ganda (idempotent):
     * 1. Jika sudah lunas / verified, tolak pembuatan tagihan baru.
     * 2. Jika sudah ada tagihan pending yang belum kadaluarsa, reuse transaksi tersebut.
     * 3. Jika belum ada, buat transaksi baru dengan status pending.
     */
    public function getOrCreateRegistrationTransaksi(Prospek $prospek, ?User $user = null): Transaksi
    {
        // 1. Cek apakah sudah pernah lunas/terverifikasi
        $existingVerified = $prospek->transaksis()
            ->where('payment_status', Transaksi::STATUS_VERIFIED)
            ->where(function ($q) {
                $q->whereIn('jenis', ['Beli Formulir', 'Pendaftaran', 'Pendaftaran PMB'])
                  ->orWhere('kategori', Transaksi::KATEGORI_DP_DAFTAR);
            })
            ->first();

        if ($existingVerified) {
            throw new InvalidArgumentException("Biaya pendaftaran untuk calon mahasiswa '{$prospek->name}' sudah lunas dan terverifikasi sah.");
        }

        // 2. Cek apakah sudah ada tagihan pending Midtrans yang aktif
        $existingPending = $prospek->transaksis()
            ->where('payment_status', Transaksi::STATUS_PENDING)
            ->where('metode_pembayaran', 'MIDTRANS')
            ->where(function ($q) {
                $q->whereIn('jenis', ['Beli Formulir', 'Pendaftaran', 'Pendaftaran PMB'])
                  ->orWhere('kategori', Transaksi::KATEGORI_DP_DAFTAR);
            })
            ->latest('tanggal')
            ->first();

        if ($existingPending) {
            return $existingPending;
        }

        // 3. Buat transaksi baru
        $nominal = self::getBiayaPendaftaran();
        $aktifTa = TahunAkademik::getAktif();

        // Buat order_id yang unik dan sesuai standar Midtrans (max 50 chars, alfanumerik & strip)
        $orderId = 'PMB-UCIC-' . $prospek->id . '-' . time() . rand(10, 99);
        while (Transaksi::where('order_id', $orderId)->exists()) {
            $orderId = 'PMB-UCIC-' . $prospek->id . '-' . time() . rand(100, 999);
        }

        return Transaksi::create([
            'order_id'          => $orderId,
            'prospek_id'        => $prospek->id,
            'user_id'           => $user?->id,
            'jenis'             => 'Beli Formulir',
            'kategori'          => Transaksi::KATEGORI_DP_DAFTAR,
            'nominal'           => $nominal,
            'detail_pembayaran' => '• Biaya Pendaftaran PMB',
            'metode_pembayaran' => 'MIDTRANS',
            'payment_status'    => Transaksi::STATUS_PENDING,
            'tanggal'           => now(),
            'notes'             => 'Pembayaran pendaftaran PMB online via Midtrans Snap.',
            'academic_year_id'  => $aktifTa?->id,
        ]);
    }

    /**
     * Buat atau ambil Snap Token dari Midtrans.
     * Jika transaksi lokal sudah memiliki snap_token valid, langsung gunakan kembali tanpa memanggil API berulang.
     *
     * @return array{token: string, redirect_url: ?string}
     */
    public function createSnapToken(Transaksi $transaksi): array
    {
        if (!empty($transaksi->snap_token)) {
            return [
                'token'        => $transaksi->snap_token,
                'redirect_url' => $transaksi->snap_redirect_url,
            ];
        }

        self::configure();

        $prospek = $transaksi->prospek;
        if (!$prospek) {
            throw new ModelNotFoundException("Data calon mahasiswa untuk transaksi {$transaksi->order_id} tidak ditemukan.");
        }

        $grossAmount = (int) round($transaksi->nominal);
        $customerEmail = $prospek->email ?: ($prospek->whatsapp . '@pmb.cic.ac.id');

        $params = [
            'transaction_details' => [
                'order_id'     => $transaksi->order_id,
                'gross_amount' => $grossAmount,
            ],
            'customer_details' => [
                'first_name' => $prospek->name,
                'email'      => $customerEmail,
                'phone'      => $prospek->whatsapp,
            ],
            'item_details' => [
                [
                    'id'       => 'PMB-FORMULIR',
                    'price'    => $grossAmount,
                    'quantity' => 1,
                    'name'     => 'Biaya Pendaftaran PMB UCIC',
                ],
            ],
        ];

        try {
            $snapResponse = Snap::createTransaction($params);

            $token = is_object($snapResponse) ? ($snapResponse->token ?? '') : ($snapResponse['token'] ?? '');
            $redirectUrl = is_object($snapResponse) ? ($snapResponse->redirect_url ?? null) : ($snapResponse['redirect_url'] ?? null);

            if (empty($token)) {
                throw new \RuntimeException('Gagal memperoleh Snap Token dari Midtrans.');
            }

            $transaksi->update([
                'snap_token'        => $token,
                'snap_redirect_url' => $redirectUrl,
            ]);

            return [
                'token'        => $token,
                'redirect_url' => $redirectUrl,
            ];
        } catch (\Throwable $e) {
            Log::error('Midtrans Snap creation error', [
                'order_id' => $transaksi->order_id,
                'message'  => $e->getMessage(),
            ]);

            throw new \RuntimeException('Gagal menghubungkan ke gerbang pembayaran Midtrans: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Validasi Signature Key resmi dari Midtrans IPN/Webhook.
     * Formula: SHA512(order_id + status_code + gross_amount + ServerKey)
     */
    public function verifySignature(string $orderId, string $statusCode, string $grossAmount, string $signatureKey): bool
    {
        $serverKey = (string) config('services.midtrans.server_key');
        if (empty($serverKey)) {
            return false;
        }

        // Format gross_amount: Midtrans mengirim dengan 2 desimal atau tanpa desimal (mis. '300000.00' atau '300000')
        $expectedHashes = [
            hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey),
        ];

        if (str_contains($grossAmount, '.')) {
            $intAmount = explode('.', $grossAmount)[0];
            $expectedHashes[] = hash('sha512', $orderId . $statusCode . $intAmount . $serverKey);
        } else {
            $expectedHashes[] = hash('sha512', $orderId . $statusCode . number_format((float) $grossAmount, 2, '.', '') . $serverKey);
        }

        foreach ($expectedHashes as $expected) {
            if (hash_equals($expected, $signatureKey)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Memproses notifikasi webhook pembayaran dari server Midtrans secara idempotent.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function processNotification(array $payload): array
    {
        $orderId = (string) ($payload['order_id'] ?? '');
        $statusCode = (string) ($payload['status_code'] ?? '');
        $grossAmount = (string) ($payload['gross_amount'] ?? '');
        $signatureKey = (string) ($payload['signature_key'] ?? '');
        $transactionStatus = (string) ($payload['transaction_status'] ?? '');
        $fraudStatus = (string) ($payload['fraud_status'] ?? '');
        $paymentType = (string) ($payload['payment_type'] ?? 'midtrans');

        if (empty($orderId) || empty($statusCode) || empty($grossAmount) || empty($signatureKey) || empty($transactionStatus)) {
            Log::warning('Midtrans Notification: Payload tidak lengkap', ['order_id' => $orderId]);
            throw new InvalidArgumentException('Parameter notifikasi Midtrans tidak lengkap.');
        }

        // 1. Verifikasi Signature
        if (!$this->verifySignature($orderId, $statusCode, $grossAmount, $signatureKey)) {
            Log::warning('Midtrans Notification: Signature tidak valid', [
                'order_id'    => $orderId,
                'status_code' => $statusCode,
            ]);
            throw new InvalidArgumentException('Signature key notifikasi Midtrans tidak valid.');
        }

        // 2. Cocokkan Transaksi Lokal
        $transaksi = Transaksi::where('order_id', $orderId)->first();
        if (!$transaksi) {
            Log::error('Midtrans Notification: Transaksi lokal tidak ditemukan', ['order_id' => $orderId]);
            throw new ModelNotFoundException("Transaksi dengan Order ID {$orderId} tidak ditemukan di sistem.");
        }

        // 3. Cocokkan Nominal Transaksi
        $nominalExpected = (int) round($transaksi->nominal);
        $nominalReceived = (int) round((float) $grossAmount);
        if ($nominalExpected !== $nominalReceived) {
            Log::error('Midtrans Notification: Nominal tidak cocok', [
                'order_id' => $orderId,
                'expected' => $nominalExpected,
                'received' => $nominalReceived,
            ]);
            throw new InvalidArgumentException("Nominal pembayaran ({$nominalReceived}) tidak cocok dengan tagihan ({$nominalExpected}).");
        }

        // 4. Proses Status Pembayaran secara Idempotent
        return DB::transaction(function () use ($transaksi, $transactionStatus, $fraudStatus, $paymentType, $payload) {
            $prospek = $transaksi->prospek;
            $oldPaymentStatus = $transaksi->payment_status;

            // Kasus 1: Pembayaran BERHASIL (settlement atau capture accept)
            $isPaid = ($transactionStatus === 'settlement') ||
                      ($transactionStatus === 'capture' && $fraudStatus === 'accept');

            if ($isPaid) {
                // Jika sudah verified sebelumnya, jangan update berulang kali (idempotent)
                if ($oldPaymentStatus === Transaksi::STATUS_VERIFIED) {
                    return [
                        'status'         => 'already_verified',
                        'order_id'       => $transaksi->order_id,
                        'payment_status' => Transaksi::STATUS_VERIFIED,
                        'message'        => 'Transaksi sudah terverifikasi sebelumnya.',
                    ];
                }

                $transaksi->update([
                    'payment_status'   => Transaksi::STATUS_VERIFIED,
                    'payment_type'     => $paymentType,
                    'fraud_status'     => $fraudStatus ?: 'accept',
                    'metode_pembayaran'=> 'MIDTRANS',
                    'transaction_time' => isset($payload['transaction_time']) ? Carbon::parse($payload['transaction_time']) : now(),
                    'verified_at'      => now(),
                    'rejected_by'      => null,
                    'rejected_at'      => null,
                    'rejection_reason' => null,
                ]);

                // Update status prospek PMB: masuk ke tahap FORMULIR jika masih di tahap awal
                if ($prospek) {
                    $prospekStatusUpper = strtoupper((string) $prospek->status);
                    $initialStages = ['BARU', 'KONTAK', 'PROSPEK', 'HOT PROSPEK'];
                    if (in_array($prospekStatusUpper, $initialStages) || ((int) $prospek->stage_number < 5)) {
                        $prospek->update([
                            'status'       => 'FORMULIR',
                            'stage_number' => 5,
                        ]);
                    }

                    // Catat riwayat di ProspekTimeline
                    ProspekTimeline::create([
                        'prospek_id' => $prospek->id,
                        'user_id'    => null, // Verifikasi otomatis sistem
                        'title'      => 'Pembayaran PMB Online Sukses (Midtrans)',
                        'notes'      => "Pembayaran Order ID {$transaksi->order_id} sebesar Rp " . number_format($transaksi->nominal, 0, ',', '.') . " sukses via Midtrans ({$paymentType}) dan diverifikasi otomatis oleh sistem.",
                        'time'       => now(),
                    ]);
                }

                Log::info('Midtrans Notification: Pembayaran berhasil', [
                    'order_id'     => $transaksi->order_id,
                    'payment_type' => $paymentType,
                ]);

                return [
                    'status'         => 'success',
                    'order_id'       => $transaksi->order_id,
                    'payment_status' => Transaksi::STATUS_VERIFIED,
                    'message'        => 'Pembayaran berhasil diverifikasi secara otomatis.',
                ];
            }

            // Kasus 2: Pembayaran PENDING
            if ($transactionStatus === 'pending') {
                $transaksi->update([
                    'payment_status'   => Transaksi::STATUS_PENDING,
                    'payment_type'     => $paymentType,
                    'fraud_status'     => $fraudStatus,
                    'transaction_time' => isset($payload['transaction_time']) ? Carbon::parse($payload['transaction_time']) : now(),
                ]);

                return [
                    'status'         => 'pending',
                    'order_id'       => $transaksi->order_id,
                    'payment_status' => Transaksi::STATUS_PENDING,
                    'message'        => 'Pembayaran sedang menunggu penyelesaian oleh calon mahasiswa.',
                ];
            }

            // Kasus 3: Pembayaran GAGAL, BATAL, KADALUARSA, DITOLAK
            $failedStatuses = ['deny', 'cancel', 'expire', 'refund', 'partial_refund'];
            if (in_array($transactionStatus, $failedStatuses)) {
                // JANGAN menimpa transaksi yang sudah sah diverifikasi dengan notifikasi kadaluarsa yang telat
                if ($oldPaymentStatus === Transaksi::STATUS_VERIFIED) {
                    Log::warning('Midtrans Notification: Mengabaikan status gagal untuk transaksi yang sudah verified', [
                        'order_id'           => $transaksi->order_id,
                        'transaction_status' => $transactionStatus,
                    ]);

                    return [
                        'status'         => 'ignored',
                        'order_id'       => $transaksi->order_id,
                        'payment_status' => Transaksi::STATUS_VERIFIED,
                        'message'        => 'Status gagal diabaikan karena transaksi sudah berstatus verified.',
                    ];
                }

                $reason = match ($transactionStatus) {
                    'expire'         => 'Waktu pembayaran kadaluarsa (Expired)',
                    'cancel'         => 'Pembayaran dibatalkan oleh calon mahasiswa (Cancelled)',
                    'deny'           => 'Pembayaran ditolak oleh penyedia pembayaran (Denied)',
                    'refund'         => 'Pembayaran telah dikembalikan (Refunded)',
                    'partial_refund' => 'Pembayaran dikembalikan sebagian (Partial Refund)',
                    default          => 'Pembayaran gagal melalui Midtrans (' . $transactionStatus . ')',
                };

                $transaksi->update([
                    'payment_status'   => Transaksi::STATUS_REJECTED,
                    'payment_type'     => $paymentType,
                    'fraud_status'     => $fraudStatus,
                    'rejected_at'      => now(),
                    'rejection_reason' => $reason,
                ]);

                if ($prospek) {
                    ProspekTimeline::create([
                        'prospek_id' => $prospek->id,
                        'user_id'    => null,
                        'title'      => 'Pembayaran PMB Online Gagal / Batal',
                        'notes'      => "Pembayaran Order ID {$transaksi->order_id} via Midtrans tidak berhasil. Keterangan: {$reason}",
                        'time'       => now(),
                    ]);
                }

                return [
                    'status'         => 'failed',
                    'order_id'       => $transaksi->order_id,
                    'payment_status' => Transaksi::STATUS_REJECTED,
                    'message'        => $reason,
                ];
            }

            return [
                'status'         => 'unhandled',
                'order_id'       => $transaksi->order_id,
                'payment_status' => $transaksi->payment_status,
                'message'        => "Status transaksi Midtrans '{$transactionStatus}' dicatat.",
            ];
        });
    }
}
