<?php

use App\Services\SalesTargetService;
use App\Models\Target;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\User;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$service = app(SalesTargetService::class);

$now = Carbon::now();
$start = $now->copy()->startOfMonth();
$totalDays = $start->copy()->daysInMonth;

echo "Total Days in month: " . $totalDays . "\n";

$target = new Target([
    'target_kontak' => 40,
    'target_formulir' => 10,
    'tipe_periode' => 'Bulanan',
    'tanggal_mulai' => $start->toDateString(),
    'tanggal_selesai' => $start->copy()->endOfMonth()->toDateString(),
]);
$target->id = 999;
$sales = new User(['id' => 999, 'role' => 'Sales']);

$sumKontak = 0;
$sumFormulir = 0;
for ($i = 0; $i < $totalDays; $i++) {
    $currentDate = $start->copy()->addDays($i);
    // simulate calculation logic directly because getAchievementUpToDate is mocked in DB
    $elapsedDays = $i;
    $expectedKontak = (int) floor((40 * $elapsedDays) / $totalDays);
    $nextExpectedKontak = (int) floor((40 * ($elapsedDays + 1)) / $totalDays);
    $dailyKontak = max(0, $nextExpectedKontak - $expectedKontak);
    $sumKontak += $dailyKontak;
}
echo "Sum Kontak (Daily): " . $sumKontak . "\n";

$totalWeeks = ceil($totalDays / 7);
for ($w = 0; $w < $totalWeeks; $w++) {
    $expectedFormulir = (int) floor((10 * $w) / $totalWeeks);
    $nextExpectedFormulir = (int) floor((10 * ($w + 1)) / $totalWeeks);
    $weeklyFormulir = max(0, $nextExpectedFormulir - $expectedFormulir);
    $sumFormulir += $weeklyFormulir;
}
echo "Sum Formulir (Weekly): " . $sumFormulir . "\n";
