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
            if (!Schema::hasColumn('prospeks', 'prodi_id')) {
                $table->foreignId('prodi_id')->nullable()->after('category')->constrained('prodis')->nullOnDelete();
            }
            if (!Schema::hasColumn('prospeks', 'kelas')) {
                $table->enum('kelas', ['Reguler', 'Karyawan'])->default('Reguler')->after('potential');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prospeks', function (Blueprint $table) {
            if (Schema::hasColumn('prospeks', 'prodi_id')) {
                $table->dropForeign(['prodi_id']);
                $table->dropColumn('prodi_id');
            }
            if (Schema::hasColumn('prospeks', 'kelas')) {
                $table->dropColumn('kelas');
            }
        });
    }
};
