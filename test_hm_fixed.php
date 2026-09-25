<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::where('role', 'HM')->first();
$hmMemberIds = $user->hmMemberIds();
$spvsInScope = \App\Models\User::whereIn('id', $hmMemberIds)->where('role', 'SPV')->pluck('id')->toArray();
$salesInScope = \App\Models\User::whereIn('supervisor_id', $spvsInScope)->where('role', 'Sales')->pluck('id')->toArray();
$directSalesInScope = \App\Models\User::whereIn('id', $hmMemberIds)->where('role', 'Sales')->pluck('id')->toArray();
$allSalesInScope = array_unique(array_merge($salesInScope, $directSalesInScope));

echo "HM ID: " . $user->id . "\n";
echo "HM Member IDs: " . json_encode($hmMemberIds) . "\n";
echo "SPVs in Scope: " . json_encode($spvsInScope) . "\n";
echo "Sales via SPV: " . json_encode($salesInScope) . "\n";
echo "Direct Sales: " . json_encode($directSalesInScope) . "\n";
echo "All Sales in Scope: " . json_encode(array_values($allSalesInScope)) . "\n";

$prospects = \App\Models\Prospek::where(function($q) use ($allSalesInScope, $user) {
    $q->whereIn('sales_id', $allSalesInScope);
    if ($user->wilayah_id) {
        $q->orWhere('wilayah_id', $user->wilayah_id);
    }
})->count();
echo "Prospects Count: " . $prospects . "\n";
