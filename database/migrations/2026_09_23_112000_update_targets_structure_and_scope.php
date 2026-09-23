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
            if (!Schema::hasColumn('targets', 'target_type')) {
                $table->enum('target_type', ['Wilayah', 'Individual'])->default('Individual')->after('id');
            }
            if (!Schema::hasColumn('targets', 'wilayah_id')) {
                $table->foreignId('wilayah_id')->nullable()->after('target_type')->constrained('wilayahs')->nullOnDelete();
            }
            if (!Schema::hasColumn('targets', 'spv_id')) {
                $table->foreignId('spv_id')->nullable()->after('wilayah_id')->constrained('users')->nullOnDelete();
            }
        });

        Schema::table('targets', function (Blueprint $table) {
            $table->foreignId('sales_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('targets', function (Blueprint $table) {
            if (Schema::hasColumn('targets', 'spv_id')) {
                $table->dropForeign(['spv_id']);
                $table->dropColumn('spv_id');
            }
            if (Schema::hasColumn('targets', 'wilayah_id')) {
                $table->dropForeign(['wilayah_id']);
                $table->dropColumn('wilayah_id');
            }
            if (Schema::hasColumn('targets', 'target_type')) {
                $table->dropColumn('target_type');
            }
        });
    }
};
