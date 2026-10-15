<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('Admin', 'HM', 'SPV', 'Sales', 'CS', 'Telesales', 'EO', 'Data Analyst') NOT NULL DEFAULT 'Sales'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('Admin', 'HM', 'SPV', 'Sales', 'CS', 'Telesales', 'EO') NOT NULL DEFAULT 'Sales'");
        }
    }
};
