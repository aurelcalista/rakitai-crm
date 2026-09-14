<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Target extends Model
{
    protected $fillable = [
        'sales_id', 'tipe_periode', 'tanggal_mulai', 'tanggal_selesai',
        'target_kontak', 'target_followup', 'target_kunjungan', 'status'
    ];

    public function sales()
    {
        return $this->belongsTo(User::class, 'sales_id');
    }
}
