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
}
