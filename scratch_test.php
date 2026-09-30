<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::where("email", "sales@example.com")->first() ?? App\Models\User::where("role", "Sales")->first();
echo "User ID: " . $user->id . "\n";
echo "Now (Asia/Jakarta): " . \Carbon\Carbon::now('Asia/Jakarta')->format('Y-m-d H:i:s') . "\n";
$events = App\Models\Event::with("sales")->get();
foreach($events as $e) {
    echo "Event ID: {$e->id}, name: {$e->nama}, mulai: {$e->tanggal_mulai}, selesai: {$e->tanggal_selesai}\n";
    $salesIds = $e->sales->pluck("id")->toArray();
    echo "  Sales Assigned: " . implode(", ", $salesIds) . "\n";
    if (in_array($user->id, $salesIds)) {
        echo "  --> Assigned to this user!\n";
    }
}
