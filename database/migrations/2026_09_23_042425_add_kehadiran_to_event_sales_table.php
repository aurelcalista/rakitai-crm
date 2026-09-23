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
        Schema::table('event_sales', function (Blueprint $table) {
            $table->string('kehadiran')->nullable()->after('assigned_by_spv_id');
            $table->text('catatan_kehadiran')->nullable()->after('kehadiran');
            $table->string('foto_kehadiran')->nullable()->after('catatan_kehadiran');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_sales', function (Blueprint $table) {
            $table->dropColumn(['kehadiran', 'catatan_kehadiran', 'foto_kehadiran']);
        });
    }
};
