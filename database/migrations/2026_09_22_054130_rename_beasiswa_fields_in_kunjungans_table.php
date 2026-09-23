<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kunjungans', function (Blueprint $table) {
            $table->renameColumn('potensi_beasiswa', 'potensi_mahasiswa');
            $table->renameColumn('detail_beasiswa', 'detail_potensi_mahasiswa');
        });
    }

    public function down(): void
    {
        Schema::table('kunjungans', function (Blueprint $table) {
            $table->renameColumn('potensi_mahasiswa', 'potensi_beasiswa');
            $table->renameColumn('detail_potensi_mahasiswa', 'detail_beasiswa');
        });
    }
};
