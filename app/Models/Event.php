<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama', 'tanggal_mulai', 'tanggal_selesai', 'lokasi',
        'deskripsi', 'status', 'eo_id', 'dokumentasi', 'absen_peserta'
    ];

    public function eo()
    {
        return $this->belongsTo(User::class, 'eo_id');
    }

    public function sales()
    {
        return $this->belongsToMany(User::class, 'event_sales', 'event_id', 'sales_id');
    }
}
