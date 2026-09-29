<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'attendance_location_id',
        'check_in_at',
        'latitude',
        'longitude',
        'distance',
        'selfie_path',
        'status',
        'notes',
    ];

    protected $casts = [
        'check_in_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
        'distance' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function location()
    {
        return $this->belongsTo(AttendanceLocation::class, 'attendance_location_id');
    }
}
