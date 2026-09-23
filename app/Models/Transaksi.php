<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Transaksi extends Model
{
    use HasFactory;

    protected $fillable = [
        'prospek_id',
        'user_id',
        'jenis',
        'nominal',
        'tanggal',
        'notes',
        'academic_year_id',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (!$model->academic_year_id) {
                $aktif = TahunAkademik::getAktif();
                if ($aktif) {
                    $model->academic_year_id = $aktif->id;
                }
            }
        });

        static::created(function ($transaksi) {
            try {
                $prospek = $transaksi->prospek;
                if (!$prospek) return;

                $auth = auth()->user();
                $actorName = $auth ? "{$auth->name} ({$auth->role})" : "Sistem";
                $nominalText = 'Rp ' . number_format((float)$transaksi->nominal, 0, ',', '.');

                $sales = $prospek->sales ?? ($prospek->sales_id ? \App\Models\User::find($prospek->sales_id) : null);
                $spv = $sales?->supervisor ?? ($prospek->wilayah_id ? \App\Models\User::where('role', 'SPV')->where('wilayah_id', $prospek->wilayah_id)->first() : null);
                $cs = $prospek->cs ?? ($prospek->cs_id ? \App\Models\User::find($prospek->cs_id) : null);

                // 1. Notifikasi ke Sales
                if ($sales && (!$auth || $auth->id !== $sales->id)) {
                    $sales->notify(new \App\Notifications\CrmActivityNotification(
                        title: "💳 Pembayaran Masuk",
                        message: "Pembayaran sebesar {$nominalText} ({$transaksi->jenis}) tercatat untuk prospek '{$prospek->name}' oleh {$actorName}.",
                        type: 'success',
                        link: '/prospek',
                        icon: '💳',
                        senderName: $auth?->name,
                        senderRole: $auth?->role,
                        action: 'transaksi_created_sales'
                    ));
                }

                // 2. Notifikasi ke SPV
                if ($spv && (!$auth || $auth->id !== $spv->id)) {
                    $spv->notify(new \App\Notifications\CrmActivityNotification(
                        title: "💳 Pembayaran Masuk Tim",
                        message: "Pembayaran {$nominalText} ({$transaksi->jenis}) diterima untuk '{$prospek->name}' (Sales: " . ($sales?->name ?? '-') . ").",
                        type: 'success',
                        link: '/spv/pipeline',
                        icon: '💳',
                        senderName: $auth?->name,
                        senderRole: $auth?->role,
                        action: 'transaksi_created_spv'
                    ));
                }

                // 3. Notifikasi ke CS
                if ($cs && (!$auth || $auth->id !== $cs->id)) {
                    $cs->notify(new \App\Notifications\CrmActivityNotification(
                        title: "💳 Pembayaran Siswa CS",
                        message: "Pembayaran {$nominalText} ({$transaksi->jenis}) tervalidasi untuk prospek '{$prospek->name}'.",
                        type: 'success',
                        link: '/prospek',
                        icon: '💳',
                        senderName: $auth?->name,
                        senderRole: $auth?->role,
                        action: 'transaksi_created_cs'
                    ));
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Error sending transaksi notification: ' . $e->getMessage());
            }
        });
    }

    protected $casts = [
        'tanggal' => 'datetime',
    ];

    public function prospek()
    {
        return $this->belongsTo(Prospek::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
