<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds full visit detail fields for School and Corporate visits.
     * No GPS/geolocation fields as per requirements.
     */
    public function up(): void
    {
        Schema::table('kunjungans', function (Blueprint $table) {
            // Common fields
            $table->string('nama_institusi')->nullable()->after('catatan');
            $table->text('alamat')->nullable()->after('nama_institusi');
            $table->string('pic_name')->nullable()->after('alamat');
            $table->string('pic_whatsapp', 20)->nullable()->after('pic_name');
            $table->string('foto_path')->nullable()->after('pic_whatsapp');

            // School-specific fields
            $table->string('potensi_beasiswa')->nullable()->after('foto_path')
                ->comment('Sekolah: potensi jumlah beasiswa');
            $table->text('detail_beasiswa')->nullable()->after('potensi_beasiswa')
                ->comment('Sekolah: detail program beasiswa');
            $table->boolean('kesediaan_training_ai')->nullable()->after('detail_beasiswa')
                ->comment('Sekolah: kesediaan mengikuti training AI/Robotik');

            // Corporate-specific fields
            $table->string('bidang_usaha')->nullable()->after('kesediaan_training_ai')
                ->comment('Perusahaan: bidang usaha');
            $table->string('potensi_s1')->nullable()->after('bidang_usaha')
                ->comment('Perusahaan: potensi kelas karyawan S1');
            $table->string('potensi_s2')->nullable()->after('potensi_s1')
                ->comment('Perusahaan: potensi magister S2');
            $table->string('potensi_csr')->nullable()->after('potensi_s2')
                ->comment('Perusahaan: potensi CSR');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kunjungans', function (Blueprint $table) {
            $table->dropColumn([
                'nama_institusi', 'alamat', 'pic_name', 'pic_whatsapp', 'foto_path',
                'potensi_beasiswa', 'detail_beasiswa', 'kesediaan_training_ai',
                'bidang_usaha', 'potensi_s1', 'potensi_s2', 'potensi_csr',
            ]);
        });
    }
};
