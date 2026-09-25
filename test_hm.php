<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::where('role', 'HM')->first();
echo "HM ID: " . $user->id . "\n";
echo "HM Wilayah ID: " . $user->wilayah_id . "\n";
$hmMemberIds = $user->hmMemberIds();
echo "HM Member IDs: " . json_encode($hmMemberIds) . "\n";

$salesInScope = \App\Models\User::whereIn('id', $hmMemberIds)->where('role', 'Sales')->pluck('id')->toArray();
echo "Sales in Scope: " . json_encode($salesInScope) . "\n";

$prospects = \App\Models\Prospek::whereIn('sales_id', $salesInScope)->count();
echo "Prospects Count: " . $prospects . "\n";
