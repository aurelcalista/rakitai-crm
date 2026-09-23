<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TargetDefisit extends Model
{
    use HasFactory;

    protected $table = 'target_defisits';

    protected $fillable = [
        'spv_id',
        'sales_id',
        'tanggal',
        'defisit_kontak',
        'defisit_formulir',
        'defisit_lunas',
        'is_locked',
        'locked_at',
        'catatan',
    ];

    protected $casts = [
        'tanggal'   => 'date',
        'is_locked' => 'boolean',
        'locked_at' => 'datetime',
    ];

    public function spv()
    {
        return $this->belongsTo(User::class, 'spv_id');
    }

    public function sales()
    {
        return $this->belongsTo(User::class, 'sales_id');
    }
}
