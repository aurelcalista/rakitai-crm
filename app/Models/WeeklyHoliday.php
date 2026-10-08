<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeeklyHoliday extends Model
{
    protected $fillable = ['day_name', 'is_holiday'];
}
