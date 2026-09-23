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
        if (Schema::hasTable('events') && Schema::hasColumn('events', 'status')) {
            try {
                DB::statement("ALTER TABLE events MODIFY COLUMN status VARCHAR(255) DEFAULT 'Scheduled'");
            } catch (\Throwable $e) {
                // Ignore if driver doesn't support ALTER
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
