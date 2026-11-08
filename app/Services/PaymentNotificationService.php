<?php

namespace App\Services;

use App\Models\Prospek;
use App\Models\Transaksi;
use App\Models\User;
use App\Notifications\CrmActivityNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PaymentNotificationService
{
    /**
     * Dispatch notifikasi pembayaran ke seluruh role yang berwenang (Admin, CS, HM, SPV, Sales).
     * Mencegah duplikasi event yang sama menggunakan idempotency key berbasis Cache.
     *
     * @param Transaksi $transaksi
     * @param string $eventType 'pending' | 'verified' | 'failed' | 'expired'
     * @return int Jumlah pengguna yang berhasil dinotifikasi
     */
    public static function notifyAllRoles(Transaksi $transaksi, string $eventType = 'pending'): int
    {
        $prospek = $transaksi->prospek;
        if (!$prospek) {
            return 0;
        }

        // Idempotency: Cegah notifikasi ganda untuk event yang sama pada order ID yang sama
        $idempotencyKey = "pmb_notif_sent_{$transaksi->order_id}_{$eventType}";
        if (Cache::has($idempotencyKey)) {
            Log::info("PaymentNotificationService: Notifikasi untuk {$idempotencyKey} sudah pernah dikirim, skip duplikat.");
            return 0;
        }

        $camabaName = $prospek->name ?: 'Calon Mahasiswa';
        $noReg = $prospek->no_registrasi ?: '-';
        $kategori = $transaksi->kategori ?: 'Biaya Pendaftaran';
        $nominalFormatted = 'Rp ' . number_format((float) $transaksi->nominal, 0, ',', '.');
        $waktuFormatted = $transaksi->tanggal ? $transaksi->tanggal : now()->format('d M Y, H:i');

        [$title, $message, $type, $action] = match ($eventType) {
            'verified' => [
                "Pembayaran Lunas: {$kategori}",
                "Pembayaran {$kategori} sebesar {$nominalFormatted} untuk '{$camabaName}' (No. Reg: {$noReg}) telah terverifikasi sah. Waktu: {$waktuFormatted}.",
                'success',
                'payment_verified',
            ],
            'failed', 'rejected' => [
                "Pembayaran Gagal: {$kategori}",
                "Pembayaran {$kategori} sebesar {$nominalFormatted} untuk '{$camabaName}' (No. Reg: {$noReg}) gagal atau ditolak. Waktu: {$waktuFormatted}.",
                'danger',
                'payment_failed',
            ],
            'expired' => [
                "Pembayaran Kadaluarsa: {$kategori}",
                "Batas waktu pembayaran {$kategori} ({$nominalFormatted}) untuk '{$camabaName}' (No. Reg: {$noReg}) telah kadaluarsa. Waktu: {$waktuFormatted}.",
                'warning',
                'payment_expired',
            ],
            default => [
                "Menunggu Pembayaran: {$kategori}",
                "Transaksi baru {$kategori} sebesar {$nominalFormatted} tercatat untuk '{$camabaName}' (No. Reg: {$noReg}). Menunggu penyelesaian pembayaran. Waktu: {$waktuFormatted}.",
                'info',
                'payment_pending',
            ],
        };

        // Kumpulkan seluruh user yang berwenang:
        // 1. Seluruh Admin aktif
        // 2. Seluruh CS aktif
        // 3. Seluruh HM aktif
        // 4. SPV (milik Sales terkait, wilayah terkait, atau seluruh SPV aktif)
        // 5. Sales pemilik prospek (jika ada)
        $recipients = collect();

        // Admin, CS, HM
        $coreStaff = User::whereIn('role', ['Admin', 'CS', 'HM'])
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', 'aktif')->orWhere('status', 'Aktif');
            })
            ->get();
        $recipients = $recipients->merge($coreStaff);

        // Sales pemilik prospek
        if ($prospek->sales_id) {
            $sales = User::find($prospek->sales_id);
            if ($sales) {
                $recipients->push($sales);
                if ($sales->supervisor_id) {
                    $spv = User::find($sales->supervisor_id);
                    if ($spv) {
                        $recipients->push($spv);
                    }
                }
            }
        }

        // SPV wilayah jika belum ada dari sales
        if ($prospek->wilayah_id) {
            $spvWilayah = User::where('role', 'SPV')
                ->where('wilayah_id', $prospek->wilayah_id)
                ->get();
            $recipients = $recipients->merge($spvWilayah);
        }

        // Jika SPV masih belum ada, sertakan seluruh SPV aktif agar tidak ada role yang tertinggal
        $allSpv = User::where('role', 'SPV')
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', 'aktif')->orWhere('status', 'Aktif');
            })
            ->get();
        $recipients = $recipients->merge($allSpv);

        // Hilangkan duplikasi berdasarkan user id
        $uniqueRecipients = $recipients->unique('id');

        $extraData = [
            'camaba_name'      => $camabaName,
            'no_registrasi'    => $noReg,
            'kategori'         => $kategori,
            'nominal'          => (float) $transaksi->nominal,
            'nominal_formatted'=> $nominalFormatted,
            'transaction_time' => (string) $waktuFormatted,
            'status'           => $eventType,
            'order_id'         => $transaksi->order_id,
            'transaksi_id'     => $transaksi->id,
            'prospek_id'       => $prospek->id,
        ];

        $sentCount = 0;
        foreach ($uniqueRecipients as $user) {
            try {
                $user->notify(new CrmActivityNotification(
                    title: $title,
                    message: $message,
                    type: $type,
                    link: '/pmb/pembayaran',
                    icon: '',
                    senderName: 'Sistem PMB UCIC',
                    senderRole: 'Sistem',
                    action: $action,
                    extraData: $extraData
                ));
                $sentCount++;
            } catch (\Throwable $e) {
                Log::warning("Gagal mengirim notifikasi pembayaran ke user #{$user->id}: " . $e->getMessage());
            }
        }

        // Tandai idempotency selama 24 jam agar webhook/event berulang tidak memicu notifikasi ganda
        Cache::put($idempotencyKey, true, now()->addHours(24));

        return $sentCount;
    }
}
