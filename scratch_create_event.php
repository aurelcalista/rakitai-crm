<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::where("email", "sales@example.com")->first() ?? App\Models\User::where("role", "Sales")->first();
$now = \Carbon\Carbon::now('Asia/Jakarta');

$event = App\Models\Event::create([
    'name' => 'Demo Jadwal Aktif Sekarang',
    'nama' => 'Demo Jadwal Aktif Sekarang',
    'tanggal' => $now->format('Y-m-d'),
    'waktu_mulai' => $now->copy()->subHours(1)->format('H:i'),
    'waktu_selesai' => $now->copy()->addHours(2)->format('H:i'),
    'tanggal_mulai' => $now->copy()->subHours(1)->format('Y-m-d H:i:s'),
    'tanggal_selesai' => $now->copy()->addHours(2)->format('Y-m-d H:i:s'),
    'lokasi' => 'Universitas Catur Insan Cendekia',
    'eo_id' => $user->id,
    'status' => 'Scheduled',
]);

$event->sales()->attach($user->id, ['assigned_by_spv_id' => $user->id]);

echo "Created event: {$event->nama} (mulai: {$event->tanggal_mulai}, selesai: {$event->tanggal_selesai})\n";
