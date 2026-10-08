<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkCalendar extends Model
{
    protected $fillable = ['date', 'description', 'status'];

    public static function isHoliday($date)
    {
        $special = self::where('date', $date)->first();
        if ($special) {
            return $special->status === 'holiday';
        }
        
        $dayName = \Carbon\Carbon::parse($date)->format('l');
        $weekly = \App\Models\WeeklyHoliday::where('day_name', $dayName)->first();
        if ($weekly) {
            return $weekly->is_holiday;
        }
        
        return false;
    }
}
