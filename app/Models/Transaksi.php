<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
    ];

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
