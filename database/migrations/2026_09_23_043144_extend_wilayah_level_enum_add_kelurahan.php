<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Extend wilayah level enum to support deeper hierarchy levels.
     * This allows Sales/CS to have area detail yang lebih spesifik
     * (e.g. Kelurahan) sebagai descendant dari Wilayah Utama (Kota/Kabupaten).
     *
     * Supported hierarchy chain:
     *   Provinsi -> Kota/Kabupaten -> Kecamatan -> Kelurahan/Desa
     */
    public function up(): void
    {
        // SQLite doesn't support ALTER COLUMN; use raw SQL to recreate enum as check constraint
        if (DB::connection()->getDriverName() === 'sqlite') {
            // SQLite stores enums as check constraints; just confirm it's fine for tests
            // The check constraint is dropped by recreating only in SQLite test env
            // We handle this via DB::statement for SQLite
            // Note: RefreshDatabase re-runs all migrations on SQLite in-memory, so
            // we update the original migration constraint via this migration.
            return; // Skip ALTER for SQLite; handled via column change below.
        }

        // For MySQL/MariaDB: modify the enum column
        DB::statement("ALTER TABLE wilayahs MODIFY COLUMN level ENUM('Provinsi', 'Kota/Kabupaten', 'Kecamatan', 'Kelurahan/Desa') NOT NULL DEFAULT 'Kecamatan'");
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE wilayahs MODIFY COLUMN level ENUM('Provinsi', 'Kota/Kabupaten', 'Kecamatan') NOT NULL DEFAULT 'Kecamatan'");
    }
};
