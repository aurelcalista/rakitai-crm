<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FollowUp extends Model
{
    use HasFactory;

    protected $fillable = [
        'prospek_id', 'user_id', 'metode', 'tanggal', 'catatan', 'hasil', 'next_follow_up',
    ];

    public const METODE_OPTIONS = [
        'WhatsApp',
        'Telepon',
        'Meeting',
        'Email',
    ];

    protected $casts = [
        'tanggal' => 'datetime',
        'next_follow_up' => 'datetime',
    ];

    public function prospek()
    {
        return $this->belongsTo(Prospek::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted()
    {
        static::created(function ($followUp) {
            try {
                $prospek = $followUp->prospek;
                if (!$prospek) return;

                $auth = auth()->user();
                $handlerId = $prospek->sales_id ?? $prospek->cs_id;

                // Jika follow-up dicatat oleh user lain (misal SPV mencatat follow-up untuk prospek milik Sales)
                if ($handlerId && (!$auth || $auth->id !== (int)$handlerId)) {
                    $handler = \App\Models\User::find($handlerId);
                    if ($handler) {
                        $actorName = $auth ? "{$auth->name} ({$auth->role})" : "Rekan Tim";
                        $handler->notify(new \App\Notifications\CrmActivityNotification(
                            title: "Aktivitas Follow-Up Baru",
                            message: "{$actorName} mencatat follow-up ({$followUp->metode}) pada prospek '{$prospek->name}'.",
                            type: 'info',
                            link: '/prospek',
                            icon: '',
                            senderName: $auth?->name,
                            senderRole: $auth?->role,
                            action: 'follow_up_created'
                        ));
                    }
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Error sending follow-up notification: ' . $e->getMessage());
            }
        });
    }
}
