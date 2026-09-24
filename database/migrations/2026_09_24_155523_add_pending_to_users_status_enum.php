<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN status ENUM('Aktif', 'Nonaktif', 'Pending') NOT NULL DEFAULT 'Aktif'");
    }

    public function down(): void
    {
        // Pastikan tidak ada baris Pending sebelum rollback
        DB::table('users')->where('status', 'Pending')->update(['status' => 'Nonaktif']);
        DB::statement("ALTER TABLE users MODIFY COLUMN status ENUM('Aktif', 'Nonaktif') NOT NULL DEFAULT 'Aktif'");
    }
};
