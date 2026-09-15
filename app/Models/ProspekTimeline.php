<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProspekTimeline extends Model
{
    use HasFactory;

    protected $fillable = [
        'prospek_id', 'user_id', 'title', 'notes', 
        'status_before', 'status_after', 'time'
    ];

    protected $casts = [
        'time' => 'datetime',
    ];

    public function prospek()
    {
        return $this->belongsTo(Prospek::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
