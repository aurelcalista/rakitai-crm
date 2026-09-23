<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            if (!Schema::hasColumn('events', 'name')) {
                $table->string('name')->nullable();
            }
            if (!Schema::hasColumn('events', 'tanggal')) {
                $table->date('tanggal')->nullable();
            }
            if (!Schema::hasColumn('events', 'waktu_mulai')) {
                $table->string('waktu_mulai')->nullable();
            }
            if (!Schema::hasColumn('events', 'waktu_selesai')) {
                $table->string('waktu_selesai')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            if (Schema::hasColumn('events', 'name')) {
                $table->dropColumn('name');
            }
            if (Schema::hasColumn('events', 'tanggal')) {
                $table->dropColumn('tanggal');
            }
            if (Schema::hasColumn('events', 'waktu_mulai')) {
                $table->dropColumn('waktu_mulai');
            }
            if (Schema::hasColumn('events', 'waktu_selesai')) {
                $table->dropColumn('waktu_selesai');
            }
        });
    }
};
