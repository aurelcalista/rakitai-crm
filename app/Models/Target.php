<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Target extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_id', 'allocated_by', 'tipe_periode', 'tahun_akademik', 'tanggal_mulai', 'tanggal_selesai',
        'target_kontak', 'target_menghubungi', 'target_followup', 'target_kunjungan',
        'target_formulir', 'target_lunas', 'status'
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
    ];

    public function sales()
    {
        return $this->belongsTo(User::class, 'sales_id');
    }

    public function allocator()
    {
        return $this->belongsTo(User::class, 'allocated_by');
    }
}
