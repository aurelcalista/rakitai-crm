<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$spvs = App\Models\User::where('role', 'SPV')->get(['id', 'name', 'supervisor_id', 'wilayah_id'])->toArray();
echo "SPV Data: \n" . json_encode($spvs, JSON_PRETTY_PRINT) . "\n";
