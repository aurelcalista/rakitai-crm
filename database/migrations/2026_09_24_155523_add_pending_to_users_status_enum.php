<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE users MODIFY COLUMN status ENUM('Aktif', 'Nonaktif', 'Pending') NOT NULL DEFAULT 'Aktif'");
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            // Pastikan tidak ada baris Pending sebelum rollback
            DB::table('users')->where('status', 'Pending')->update(['status' => 'Nonaktif']);
            DB::statement("ALTER TABLE users MODIFY COLUMN status ENUM('Aktif', 'Nonaktif') NOT NULL DEFAULT 'Aktif'");
        }
    }
};
