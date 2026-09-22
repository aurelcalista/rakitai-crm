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
            $table->integer('follow_up_count')->default(0)->after('stage_number');
            $table->string('tahun_akademik', 20)->default('2027/2028')->after('status');
        });

        Schema::table('targets', function (Blueprint $table) {
            $table->foreignId('allocated_by')->nullable()->after('sales_id')->constrained('users')->nullOnDelete();
            $table->string('tahun_akademik', 20)->default('2027/2028')->after('tipe_periode');
            $table->integer('target_formulir')->default(0)->after('target_kunjungan');
            $table->integer('target_lunas')->default(0)->after('target_formulir');
        });

        Schema::table('kunjungans', function (Blueprint $table) {
            $table->string('tahun_akademik', 20)->default('2027/2028')->after('tanggal');
            $table->boolean('is_outside_radius')->default(false)->after('foto_path');
            $table->string('status_verifikasi', 50)->default('Terverifikasi')->after('status');
            $table->string('lokasi_penugasan')->nullable()->after('alamat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kunjungans', function (Blueprint $table) {
            $table->dropColumn(['tahun_akademik', 'is_outside_radius', 'status_verifikasi', 'lokasi_penugasan']);
        });

        Schema::table('targets', function (Blueprint $table) {
            $table->dropForeign(['allocated_by']);
            $table->dropColumn(['allocated_by', 'tahun_akademik', 'target_formulir', 'target_lunas']);
        });

        Schema::table('prospeks', function (Blueprint $table) {
            $table->dropColumn(['follow_up_count', 'tahun_akademik']);
        });
    }
};
