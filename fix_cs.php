<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$cs = \App\Models\User::where('role', 'CS')->first();
if ($cs) {
    \App\Models\Prospek::whereNull('cs_id')->whereNull('sales_id')->where('status', 'BARU')->update(['cs_id' => $cs->id]);
    echo "Updated successfully!";
} else {
    echo "No CS found!";
}
