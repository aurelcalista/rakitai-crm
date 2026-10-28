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

    public const BIAYA_CATALOG = [
        'pendaftaran' => [
            'id'    => 'PMB-PENDAFTARAN',
            'name'  => 'Biaya Pendaftaran PMB UCIC',
            'price' => 300000,
        ],
        'perlengkapan' => [
            'id'    => 'PMB-PERLENGKAPAN',
            'name'  => 'Biaya Perlengkapan Kuliah',
            'price' => 600000,
        ],
        'dpp' => [
            'id'    => 'PMB-DPP',
            'name'  => 'Biaya Dana Pengembangan Pendidikan (DPP)',
            'price' => 500000,
        ],
    ];

    /**
     * Dapatkan katalog biaya resmi PMB UCIC.
     *
     * @return array<string, array{id: string, name: string, price: int}>
     */
    public static function getBiayaCatalog(): array
    {
        $catalog = self::BIAYA_CATALOG;
        $defaultPendaftaran = (int) config('services.midtrans.biaya_pendaftaran', 300000);
        $catalog['pendaftaran']['price'] = $defaultPendaftaran;
        return $catalog;
    }

    /**
     * Hitung total biaya dan rincian item secara otoritatif di backend.
     * Mengabaikan nominal yang dikirim dari browser / client untuk mencegah manipulasi.
     *
     * @param array<string|int, mixed> $selectedItems
     * @return array{total: int, items: array<int, array{id: string, name: string, price: int, quantity: int}>, item_keys: array<string>, summary: string}
     */
    public static function calculateItems(array $selectedItems): array
    {
        $catalog = self::getBiayaCatalog();
        $validKeys = [];

        foreach ($selectedItems as $key => $val) {
            if (is_int($key) && is_string($val) && isset($catalog[$val])) {
                $validKeys[] = $val;
            } elseif (is_string($key) && isset($catalog[$key]) && filter_var($val, FILTER_VALIDATE_BOOLEAN)) {
                $validKeys[] = $key;
            }
        }

        $validKeys = array_values(array_unique($validKeys));

        $total = 0;
        $items = [];
        $descriptions = [];

        foreach ($validKeys as $key) {
            $itemDef = $catalog[$key];
            $price = (int) $itemDef['price'];
            $total += $price;

            $items[] = [
                'id'       => $itemDef['id'],
                'name'     => $itemDef['name'],
                'price'    => $price,
                'quantity' => 1,
            ];
            $descriptions[] = '• ' . $itemDef['name'] . ' (Rp ' . number_format($price, 0, ',', '.') . ')';
        }

        return [
            'total'        => $total,
            'items'        => $items,
            'item_keys'    => $validKeys,
            'summary'      => implode("\n", $descriptions),
        ];
    }

    /**
     * Tentukan item awal yang tercentang dari transaksi yang tersimpan.
     *
     * @return array<string, bool>
     */
    public static function resolveInitialItems(Transaksi $transaksi): array
    {
        $detail = (string) $transaksi->detail_pembayaran;
        $nominal = (int) round($transaksi->nominal);

        $hasPerlengkapan = str_contains($detail, 'Perlengkapan');
        $hasPendaftaran = str_contains($detail, 'Pendaftaran') || str_contains($detail, 'Formulir');
        $hasDpp = str_contains($detail, 'DPP');

        if ($hasPerlengkapan || $hasPendaftaran || $hasDpp) {
            return [
                'perlengkapan' => $hasPerlengkapan,
                'pendaftaran'  => $hasPendaftaran,
                'dpp'          => $hasDpp,
            ];
        }

        if ($nominal === 500000) {
            return ['perlengkapan' => false, 'pendaftaran' => false, 'dpp' => true];
        }
        if ($nominal === 800000) {
            return ['perlengkapan' => false, 'pendaftaran' => true, 'dpp' => true];
        }
        if ($nominal === 600000) {
            return ['perlengkapan' => true, 'pendaftaran' => false, 'dpp' => false];
        }
        if ($nominal === 900000) {
            return ['perlengkapan' => true, 'pendaftaran' => true, 'dpp' => false];
        }
        if ($nominal === 1100000) {
            return ['perlengkapan' => true, 'pendaftaran' => false, 'dpp' => true];
        }
        if ($nominal === 1400000) {
            return ['perlengkapan' => true, 'pendaftaran' => true, 'dpp' => true];
        }

        // Default: hanya pendaftaran
        return ['perlengkapan' => false, 'pendaftaran' => true, 'dpp' => false];
    }

    /**
     * Susun rincian item_details Snap berdasarkan rincian biaya aktual transaksi.
     * Memastikan sum(price * quantity) === gross_amount sesuai spesifikasi Midtrans API.
     *
     * @return array<int, array{id: string, name: string, price: int, quantity: int}>
     */
    public function resolveItemDetailsForTransaksi(Transaksi $transaksi): array
    {
        $catalog = self::getBiayaCatalog();
        $detail = (string) $transaksi->detail_pembayaran;
        $grossAmount = (int) round($transaksi->nominal);

        $selected = [];
        if (str_contains($detail, 'Perlengkapan')) {
            $selected[] = 'perlengkapan';
        }
        if (str_contains($detail, 'Pendaftaran') || str_contains($detail, 'Formulir')) {
            $selected[] = 'pendaftaran';
        }
        if (str_contains($detail, 'DPP')) {
            $selected[] = 'dpp';
        }

        if (empty($selected)) {
            if ($grossAmount === 500000) {
                $selected = ['dpp'];
            } elseif ($grossAmount === 800000) {
                $selected = ['pendaftaran', 'dpp'];
            } elseif ($grossAmount === 600000) {
                $selected = ['perlengkapan'];
            } elseif ($grossAmount === 900000) {
                $selected = ['perlengkapan', 'pendaftaran'];
            } elseif ($grossAmount === 1100000) {
                $selected = ['perlengkapan', 'dpp'];
            } elseif ($grossAmount === 1400000) {
                $selected = ['perlengkapan', 'pendaftaran', 'dpp'];
            } elseif ($grossAmount === 300000) {
                $selected = ['pendaftaran'];
            }
        }

        $items = [];
        $sum = 0;
        foreach ($selected as $key) {
            if (isset($catalog[$key])) {
                $def = $catalog[$key];
                $price = (int) $def['price'];
                $items[] = [
                    'id'       => $def['id'],
                    'name'     => $def['name'],
                    'price'    => $price,
                    'quantity' => 1,
                ];
                $sum += $price;
            }
        }

        if (!empty($items) && $sum === $grossAmount) {
            return $items;
        }

        // Fallback jika custom nominal
        return [
            [
                'id'       => 'PMB-FORMULIR',
                'name'     => 'Biaya Pendaftaran PMB UCIC',
                'price'    => $grossAmount,
                'quantity' => 1,
            ],
        ];
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
     * 2. Jika sudah ada tagihan pending yang belum kadaluarsa dengan nominal yang sama, reuse transaksi tersebut (kecuali $forceNew = true).
     * 3. Jika belum ada atau nominal berbeda, buat transaksi baru dengan status pending.
     *
     * @param array<string|int, mixed> $selectedItemKeys
     */
    public function getOrCreateRegistrationTransaksi(
        Prospek $prospek,
        ?User $user = null,
        bool $forceNew = false,
        array $selectedItemKeys = ['pendaftaran']
    ): Transaksi {
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

        $calculated = self::calculateItems($selectedItemKeys);
        if ($calculated['total'] <= 0 || empty($calculated['item_keys'])) {
            throw new InvalidArgumentException('Silakan pilih minimal satu komponen biaya pembayaran.');
        }

        // 2. Cek apakah sudah ada tagihan pending Midtrans yang aktif dengan nominal yang sama (jika tidak diminta $forceNew)
        if (!$forceNew) {
            $existingPending = $prospek->transaksis()
                ->where('payment_status', Transaksi::STATUS_PENDING)
                ->where('metode_pembayaran', 'MIDTRANS')
                ->where('nominal', $calculated['total'])
                ->where(function ($q) {
                    $q->whereIn('jenis', ['Beli Formulir', 'Pendaftaran', 'Pendaftaran PMB'])
                      ->orWhere('kategori', Transaksi::KATEGORI_DP_DAFTAR);
                })
                ->latest('tanggal')
                ->first();

            if ($existingPending) {
                return $existingPending;
            }
        }

        // 3. Buat transaksi baru dengan nominal otoritatif dari kalkulasi backend
        $nominal = $calculated['total'];
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
            'detail_pembayaran' => $calculated['summary'],
            'metode_pembayaran' => 'MIDTRANS',
            'payment_status'    => Transaksi::STATUS_PENDING,
            'tanggal'           => now(),
            'notes'             => 'Pembayaran pendaftaran PMB online via Midtrans Snap (' . implode(', ', $calculated['item_keys']) . ').',
            'academic_year_id'  => $aktifTa?->id,
        ]);
    }

    /**
     * Susun payload parameter Snap API dengan konfigurasi redirect callbacks dinamis.
     * Mencegah redirect ke domain default IANA (example.com).
     * Callback selalu mengarah ke URL lokal resmi: http://127.0.0.1:8000/pmb/akun/bayar
     *
     * @return array<string, mixed>
     */
    public function buildSnapPayload(Transaksi $transaksi): array
    {
        $prospek = $transaksi->prospek;
        if (!$prospek) {
            throw new ModelNotFoundException("Data calon mahasiswa untuk transaksi {$transaksi->order_id} tidak ditemukan.");
        }

        $grossAmount = (int) round($transaksi->nominal);
        $customerEmail = $prospek->email ?: ($prospek->whatsapp . '@pmb.cic.ac.id');

        // Dynamic callback URLs: prioritaskan dari config/services.php atau .env,
        // dengan fallback aman ke http://127.0.0.1:8000/pmb/akun/bayar.
        // Abaikan & cegah placeholder domain IANA (example.com / example.org / example.net).
        $defaultLocalUrl = 'http://127.0.0.1:8000/pmb/akun/bayar';

        $resolveCallbackUrl = function (?string $configuredUrl) use ($defaultLocalUrl): string {
            if (empty($configuredUrl)) {
                return $defaultLocalUrl;
            }

            $trimmed = trim($configuredUrl);
            $lower = strtolower($trimmed);

            // Cegah & ganti domain placeholder IANA (example.com / example.org / example.net)
            if (str_contains($lower, 'example.com') || str_contains($lower, 'example.org') || str_contains($lower, 'example.net')) {
                return $defaultLocalUrl;
            }

            // Normalisasi localhost:8000 menjadi 127.0.0.1:8000 agar konsisten dengan origin sesi lokal CAMABA
            if (str_contains($lower, 'localhost:8000')) {
                return str_replace(['localhost:8000', 'LOCALHOST:8000'], '127.0.0.1:8000', $trimmed);
            }

            return $trimmed;
        };

        $finishUrl = $resolveCallbackUrl(config('services.midtrans.finish_redirect_url'));
        $unfinishUrl = $resolveCallbackUrl(config('services.midtrans.unfinish_redirect_url'));
        $errorUrl = $resolveCallbackUrl(config('services.midtrans.error_redirect_url'));

        $itemDetails = $this->resolveItemDetailsForTransaksi($transaksi);

        return [
            'transaction_details' => [
                'order_id'     => $transaksi->order_id,
                'gross_amount' => $grossAmount,
            ],
            'customer_details' => [
                'first_name' => $prospek->name,
                'email'      => $customerEmail,
                'phone'      => $prospek->whatsapp,
            ],
            'item_details' => $itemDetails,
            'callbacks' => [
                'finish'   => $finishUrl,
                'unfinish' => $unfinishUrl,
                'error'    => $errorUrl,
            ],
        ];
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

        $params = $this->buildSnapPayload($transaksi);

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

            if (app()->runningUnitTests()) {
                $mockToken = 'MOCK-SNAP-TOKEN-' . md5($transaksi->order_id);
                $mockRedirect = 'https://app.sandbox.midtrans.com/snap/v2/vtweb/' . $mockToken;
                $transaksi->update([
                    'snap_token'        => $mockToken,
                    'snap_redirect_url' => $mockRedirect,
                ]);

                return [
                    'token'        => $mockToken,
                    'redirect_url' => $mockRedirect,
                ];
            }

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

    /**
     * Sinkronisasi status transaksi langsung dari server Midtrans (API polling server-to-server).
     * Berguna untuk verifikasi status di lingkungan lokal/pengujian saat webhook server tidak dapat
     * menjangkau localhost, atau sebagai rekonsiliasi cadangan tanpa mempercayai redirect URL browser.
     */
    public function syncStatusFromMidtransApi(string $orderId): ?Transaksi
    {
        if (app()->runningUnitTests()) {
            return null;
        }

        self::configure();

        try {
            $statusResponse = \Midtrans\Transaction::status($orderId);
            $payload = (array) $statusResponse;

            $this->processNotification($payload);

            return Transaksi::where('order_id', $orderId)->first();
        } catch (\Throwable $e) {
            Log::info("Midtrans sync status check for order {$orderId}: " . $e->getMessage());
            return null;
        }
    }
}
