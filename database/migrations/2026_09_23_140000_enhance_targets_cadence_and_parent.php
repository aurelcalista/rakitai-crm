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
        Schema::table('targets', function (Blueprint $table) {
            if (!Schema::hasColumn('targets', 'parent_id')) {
                $table->foreignId('parent_id')->nullable()->after('id')->constrained('targets')->nullOnDelete();
            }
            if (!Schema::hasColumn('targets', 'gelombang')) {
                $table->string('gelombang', 100)->nullable()->after('tipe_periode');
            }
        });

        // Ensure tipe_periode accepts 'Tahunan' across database engines
        try {
            Schema::table('targets', function (Blueprint $table) {
                $table->string('tipe_periode', 50)->default('Bulanan')->change();
            });
        } catch (\Throwable $e) {
            // Ignore if sqlite or engine does not alter enum without doctrine/dbal
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('targets', function (Blueprint $table) {
            if (Schema::hasColumn('targets', 'gelombang')) {
                $table->dropColumn('gelombang');
            }
            if (Schema::hasColumn('targets', 'parent_id')) {
                $table->dropForeign(['parent_id']);
                $table->dropColumn('parent_id');
            }
        });
    }
};
