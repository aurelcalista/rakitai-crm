<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$tables = ['kunjungans', 'sekolahs', 'perusahaans', 'targets', 'wilayahs', 'users'];
foreach ($tables as $table) {
    echo "TABLE: $table\n";
    $result = DB::select("SHOW CREATE TABLE $table");
    echo $result[0]->{'Create Table'} . "\n\n";
}
