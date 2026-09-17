<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kunjungan extends Model
{
    use HasFactory;

    protected $fillable = [
        'nomor', 'tanggal', 'waktu', 'sales_id', 'jenis',
        'tujuan_id', 'tujuan_kunjungan', 'hasil', 'catatan', 'status'
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function sales()
    {
        return $this->belongsTo(User::class, 'sales_id');
    }

    public function sekolah()
    {
        return $this->belongsTo(Sekolah::class, 'tujuan_id');
    }

    public function perusahaan()
    {
    public function tujuan()
    {
        if ($this->jenis === 'Sekolah') {
            return $this->belongsTo(Sekolah::class, 'tujuan_id');
        }
        return $this->belongsTo(Perusahaan::class, 'tujuan_id');
    }
}
