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
        Schema::table('wilayahs', function (Blueprint $table) {
            $table->dropUnique('wilayahs_kode_unique');
            $table->unsignedBigInteger('parent_id_unique')->virtualAs('COALESCE(parent_id, 0)');
            $table->unique(['kode', 'parent_id_unique'], 'wilayahs_kode_parent_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wilayahs', function (Blueprint $table) {
            $table->dropUnique('wilayahs_kode_parent_unique');
            $table->dropColumn('parent_id_unique');
            $table->unique('kode', 'wilayahs_kode_unique');
        });
    }
};
