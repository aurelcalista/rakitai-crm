<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Target extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id', 'target_type', 'wilayah_id', 'spv_id', 'sales_id', 'allocated_by',
        'tipe_periode', 'gelombang', 'academic_year_id', 'tahun_akademik', 'tanggal_mulai', 'tanggal_selesai',
        'target_kontak', 'target_menghubungi', 'target_followup', 'target_kunjungan',
        'target_formulir', 'target_lunas', 'status',
        'is_locked', 'locked_at', 'locked_by',
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
        'is_locked' => 'boolean',
        'locked_at' => 'datetime',
    ];

    public function parentTarget(): BelongsTo
    {
        return $this->belongsTo(Target::class, 'parent_id');
    }

    public function monthlyTargets(): HasMany
    {
        return $this->hasMany(Target::class, 'parent_id');
    }

    public function isAnnual(): bool
    {
        return $this->tipe_periode === 'Tahunan';
    }

    public function wilayah(): BelongsTo
    {
        return $this->belongsTo(Wilayah::class, 'wilayah_id');
    }

    public function spv(): BelongsTo
    {
        return $this->belongsTo(User::class, 'spv_id');
    }

    public function sales(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_id');
    }

    public function allocator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'allocated_by');
    }

    public function scopeWilayahTargets($query)
    {
        return $query->where('target_type', 'Wilayah');
    }

    public function scopeIndividualTargets($query)
    {
        return $query->where('target_type', 'Individual');
    }

    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    /**
     * Check if the target is locked.
     */
    public function isLocked(): bool
    {
        return (bool) $this->is_locked;
    }

    /**
     * Lock the target by a given authorized user (HM / Admin).
     */
    public function lock(User $user): bool
    {
        $this->is_locked = true;
        $this->locked_at = now();
        $this->locked_by = $user->id;
        return $this->save();
    }

    /**
     * Unlock the target.
     */
    public function unlock(): bool
    {
        $this->is_locked = false;
        $this->locked_at = null;
        $this->locked_by = null;
        return $this->save();
    }
}
