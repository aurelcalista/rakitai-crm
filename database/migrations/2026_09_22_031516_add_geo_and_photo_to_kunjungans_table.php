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
        Schema::table('kunjungans', function (Blueprint $table) {
            if (!Schema::hasColumn('kunjungans', 'lat')) {
                $table->decimal('lat', 10, 8)->nullable();
            }
            if (!Schema::hasColumn('kunjungans', 'lng')) {
                $table->decimal('lng', 11, 8)->nullable();
            }
            if (!Schema::hasColumn('kunjungans', 'is_verified')) {
                $table->boolean('is_verified')->default(false);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kunjungans', function (Blueprint $table) {
            $table->dropColumn(['lat', 'lng', 'is_verified']);
        });
    }
};
