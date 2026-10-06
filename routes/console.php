<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// P0 SPV: Pemeriksaan berkala SLA serah terima CS > 2 jam
Schedule::command('crm:check-cs-handover-sla')->everyFifteenMinutes();

// P0 SPV: Notifikasi otomatis evaluasi target harian di akhir jam kerja (17:30 WIB)
Schedule::command('crm:check-daily-target-spv')->dailyAt('17:30');

// Attendance: Otomatis Tidak Hadir bagi yang belum absen hari ini
Schedule::command('attendance:mark-absence')->dailyAt('23:59');
