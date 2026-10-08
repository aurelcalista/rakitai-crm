<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('attendance:mark-absence')]
#[Description('Automatically mark users as Tidak Hadir if they have not checked in by the end of the day.')]
class MarkAbsence extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = \Carbon\Carbon::now()->toDateString();
        
        if (\App\Models\WorkCalendar::isHoliday($today)) {
            $this->info("Hari ini ($today) adalah hari libur. Tidak ada absensi otomatis yang ditandai.");
            return;
        }

        $users = \App\Models\User::whereIn('role', ['Sales', 'CS', 'EO', 'SPV'])
            ->where('status', 'Aktif')
            ->get();

        $count = 0;
        foreach ($users as $user) {
            $hasAttended = \App\Models\Attendance::where('user_id', $user->id)
                ->where('date', $today)
                ->exists();

            if (!$hasAttended) {
                try {
                    $wilayahId = null;
                    if (in_array($user->role, ['Sales', 'SPV'])) {
                        $wilayahId = $user->wilayah_id;
                    }

                    \App\Models\Attendance::create([
                        'user_id' => $user->id,
                        'date' => $today,
                        'time' => null,
                        'status' => 'Tidak Hadir',
                        'photo' => null,
                        'notes' => null,
                        'wilayah_id' => $wilayahId,
                    ]);
                    $count++;
                } catch (\Exception $e) {
                    // Ignore duplicate entry if occurred
                }
            }
        }

        $this->info("Successfully marked $count users as Tidak Hadir for $today.");
    }
}
