<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'nama',
        'type_id',
        'tanggal',
        'waktu_mulai',
        'waktu_selesai',
        'tanggal_mulai',
        'tanggal_selesai',
        'lokasi',
        'deskripsi',
        'eo_id',
        'status',
        'dokumentasi',
        'absen_peserta',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'waktu_mulai' => 'datetime:H:i',
        'waktu_selesai' => 'datetime:H:i',
    ];

    /**
     * Get the EO (Creator) of the event.
     */
    public function eo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'eo_id');
    }

    /**
     * Get the master data type of the event.
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(MasterData::class, 'type_id');
    }

    /**
     * Get the SPVs assigned to this event.
     */
    public function spvs(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'event_spv', 'event_id', 'spv_id')->withTimestamps();
    }

    /**
     * Get the Sales assigned to this event.
     */
    public function sales(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'event_sales', 'event_id', 'sales_id')
            ->withPivot('assigned_by_spv_id')
            ->withTimestamps();
    }
}
