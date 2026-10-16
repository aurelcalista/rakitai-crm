<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add takeover fields to events table.
     * Supports Takeover mechanism for both Agenda Internal and EO Events.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            if (!Schema::hasColumn('events', 'takeover_by_id')) {
                $table->foreignId('takeover_by_id')->nullable()->after('eo_id')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('events', 'takeover_at')) {
                $table->timestamp('takeover_at')->nullable()->after('takeover_by_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            if (Schema::hasColumn('events', 'takeover_by_id')) {
                $table->dropForeign(['takeover_by_id']);
                $table->dropColumn('takeover_by_id');
            }
            if (Schema::hasColumn('events', 'takeover_at')) {
                $table->dropColumn('takeover_at');
            }
        });
    }
};
