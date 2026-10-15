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
        Schema::table('prospeks', function (Blueprint $table) {
            if (!Schema::hasColumn('prospeks', 'status_takeover')) {
                $table->string('status_takeover', 50)->nullable()->default('Belum Di-takeover')->after('notes');
            }
            if (!Schema::hasColumn('prospeks', 'takeover_by_id')) {
                $table->foreignId('takeover_by_id')->nullable()->after('status_takeover')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('prospeks', 'takeover_at')) {
                $table->timestamp('takeover_at')->nullable()->after('takeover_by_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prospeks', function (Blueprint $table) {
            if (Schema::hasColumn('prospeks', 'takeover_by_id')) {
                $table->dropForeign(['takeover_by_id']);
                $table->dropColumn('takeover_by_id');
            }
            if (Schema::hasColumn('prospeks', 'takeover_at')) {
                $table->dropColumn('takeover_at');
            }
            if (Schema::hasColumn('prospeks', 'status_takeover')) {
                $table->dropColumn('status_takeover');
            }
        });
    }
};
