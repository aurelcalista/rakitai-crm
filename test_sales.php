<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$sales = App\Models\User::where('role', 'Sales')->get(['id', 'name', 'supervisor_id', 'wilayah_id'])->toArray();
echo "Sales Data: \n" . json_encode($sales, JSON_PRETTY_PRINT) . "\n";
