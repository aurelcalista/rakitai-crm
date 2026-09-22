<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TahunAkademik extends Model
{
    protected $fillable = ['nama', 'status'];

    public static function getAktif()
    {
        return self::where('status', 'Aktif')->first();
    }

    protected static function booted()
    {
        static::saving(function ($model) {
            if ($model->status === 'Aktif') {
                self::where('id', '!=', $model->id)->update(['status' => 'Non-Aktif']);
            }
        });
    }
}
