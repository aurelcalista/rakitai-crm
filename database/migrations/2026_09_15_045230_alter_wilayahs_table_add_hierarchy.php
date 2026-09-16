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
            $table->dropColumn('kecamatans');
            $table->enum('level', ['Provinsi', 'Kota/Kabupaten', 'Kecamatan'])->after('nama')->default('Kecamatan');
            $table->foreignId('parent_id')->nullable()->after('level')->constrained('wilayahs')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wilayahs', function (Blueprint $table) {
            $table->json('kecamatans')->nullable();
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['level', 'parent_id']);
        });
    }
};
