<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Target extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_id', 'tipe_periode', 'tanggal_mulai', 'tanggal_selesai',
        'target_kontak', 'target_menghubungi', 'target_followup', 'target_kunjungan', 'status', 'academic_year_id'
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (!$model->academic_year_id) {
                $aktif = TahunAkademik::getAktif();
                if ($aktif) {
                    $model->academic_year_id = $aktif->id;
                }
            }
        });
    }

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
    ];

    public function sales()
    {
        return $this->belongsTo(User::class, 'sales_id');
    }
}
