<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$total = App\Models\Prospek::count();
$withCs = App\Models\Prospek::whereNotNull('cs_id')->count();
$withSales = App\Models\Prospek::whereNotNull('sales_id')->count();
echo "$total total, $withCs with cs_id, $withSales with sales_id\n";
