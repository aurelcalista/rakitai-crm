<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wilayah extends Model
{
    use HasFactory;

    public function users()
    {
        return $this->hasMany(User::class, 'wilayah_id');
    }

    protected $fillable = ['kode', 'nama', 'kecamatans', 'status'];

    protected $casts = [
        'kecamatans' => 'array',
    ];

    public function sekolahs()
    {
        return $this->hasMany(Sekolah::class, 'wilayah_id');
    }

    public function perusahaans()
    {
        return $this->hasMany(Perusahaan::class, 'wilayah_id');
    }
}
