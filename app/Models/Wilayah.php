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

    public function children()
    {
        return $this->hasMany(Wilayah::class, 'parent_id');
    }

    public function parent()
    {
        return $this->belongsTo(Wilayah::class, 'parent_id');
    }

    protected $fillable = ['kode', 'nama', 'level', 'parent_id', 'status'];

    public function sekolahs()
    {
        return $this->hasMany(Sekolah::class, 'wilayah_id');
    }

    public function perusahaans()
    {
        return $this->hasMany(Perusahaan::class, 'wilayah_id');
    }
}
