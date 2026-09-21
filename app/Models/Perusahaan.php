<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Perusahaan extends Model
{
    protected $fillable = [
        'kode', 'nama', 'kategori_id', 'wilayah_id', 'kecamatan', 
        'alamat', 'telepon', 'email', 'website', 'pic_name', 'pic_jabatan', 'pic_phone', 'status'
    ];

    public function kategori()
    {
        return $this->belongsTo(MasterData::class, 'kategori_id');
    }

    public function wilayah()
    {
        return $this->belongsTo(Wilayah::class, 'wilayah_id');
    }

    public function kunjungans()
    {
        return $this->hasMany(Kunjungan::class, 'tujuan_id')->where('jenis', 'Perusahaan');
    }

    public function sales()
    {
        return $this->belongsTo(User::class, 'sales_id');
    }
}
