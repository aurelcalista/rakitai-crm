<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Expand role enum on users table if running MySQL
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('Admin', 'HM', 'SPV', 'Sales', 'CS', 'Telesales', 'EO', 'Data Analyst', 'Camaba') NOT NULL DEFAULT 'Sales'");
        }

        // 2. Add user_id to prospeks table (nullable, foreign key to users, nullOnDelete)
        Schema::table('prospeks', function (Blueprint $table) {
            if (!Schema::hasColumn('prospeks', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prospeks', function (Blueprint $table) {
            if (Schema::hasColumn('prospeks', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
        });

        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('Admin', 'HM', 'SPV', 'Sales', 'CS', 'Telesales', 'EO', 'Data Analyst') NOT NULL DEFAULT 'Sales'");
        }
    }
};
