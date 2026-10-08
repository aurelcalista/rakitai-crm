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
            $table->string('wa_ortu', 20)->nullable()->after('whatsapp');
            $table->string('asal_kelas')->nullable()->after('sekolah_id');
            $table->string('prodi_lainnya')->nullable()->after('prodi_id');
            $table->unsignedBigInteger('prodi_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prospeks', function (Blueprint $table) {
            $table->dropColumn(['wa_ortu', 'asal_kelas', 'prodi_lainnya']);
            // Changing back to non-nullable might be risky if data exists, skipping it here.
        });
    }
};
