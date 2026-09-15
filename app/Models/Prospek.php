<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prospek extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'type', 'category', 'pic', 'pic_phone', 'whatsapp',
        'status', 'stage_number', 'potential', 'ai_training', 'notes',
        'wilayah_id', 'sales_id', 'cs_id', 'owner_id'
    ];

    public function wilayah()
    {
        return $this->belongsTo(Wilayah::class);
    }

    public function sales()
    {
        return $this->belongsTo(User::class, 'sales_id');
    }

    public function cs()
    {
        return $this->belongsTo(User::class, 'cs_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function followUps()
    {
        return $this->hasMany(FollowUp::class);
    }

    public function timelines()
    {
        return $this->hasMany(ProspekTimeline::class);
    }
}
