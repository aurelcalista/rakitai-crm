<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttendanceLocation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'latitude',
        'longitude',
        'radius',
        'status',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'radius' => 'integer',
    ];

    /**
     * Scope for active locations only.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Relationship with attendances.
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }
}
