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
            if (!Schema::hasColumn('prospeks', 'email')) {
                $table->string('email')->nullable()->after('name')->index();
            }
            if (!Schema::hasColumn('prospeks', 'status_formulir')) {
                $table->string('status_formulir', 50)->default('Belum Isi')->after('status')->index();
            }
            if (!Schema::hasColumn('prospeks', 'status_berkas')) {
                $table->string('status_berkas', 50)->default('Belum Upload')->after('status_formulir')->index();
            }
            if (!Schema::hasColumn('prospeks', 'status_ujian')) {
                $table->string('status_ujian', 50)->default('Belum Mengerjakan')->after('status_berkas')->index();
            }
            if (!Schema::hasColumn('prospeks', 'nilai_ujian')) {
                $table->decimal('nilai_ujian', 5, 2)->nullable()->after('status_ujian');
            }
            if (!Schema::hasColumn('prospeks', 'tanggal_ujian')) {
                $table->dateTime('tanggal_ujian')->nullable()->after('nilai_ujian');
            }
            if (!Schema::hasColumn('prospeks', 'minat_kuliah')) {
                $table->string('minat_kuliah', 10)->default('ya')->after('tanggal_ujian');
            }
            if (!Schema::hasColumn('prospeks', 'minat_miniclass')) {
                $table->string('minat_miniclass', 10)->default('tidak')->after('minat_kuliah');
            }
            if (!Schema::hasColumn('prospeks', 'prodi_pilihan_2_id')) {
                $table->unsignedBigInteger('prodi_pilihan_2_id')->nullable()->after('prodi_id');
                $table->foreign('prodi_pilihan_2_id')->references('id')->on('prodis')->onDelete('set null');
            }
            if (!Schema::hasColumn('prospeks', 'berkas_data')) {
                $table->json('berkas_data')->nullable()->after('notes');
            }
            if (!Schema::hasColumn('prospeks', 'form_data')) {
                $table->json('form_data')->nullable()->after('berkas_data');
            }
        });

        Schema::table('transaksis', function (Blueprint $table) {
            if (!Schema::hasColumn('transaksis', 'order_id')) {
                $table->string('order_id', 100)->nullable()->after('id')->index();
            }
            if (!Schema::hasColumn('transaksis', 'detail_pembayaran')) {
                $table->string('detail_pembayaran')->nullable()->after('nominal');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prospeks', function (Blueprint $table) {
            if (Schema::hasColumn('prospeks', 'prodi_pilihan_2_id')) {
                $table->dropForeign(['prodi_pilihan_2_id']);
            }
            $columns = [
                'email', 'status_formulir', 'status_berkas', 'status_ujian',
                'nilai_ujian', 'tanggal_ujian', 'minat_kuliah', 'minat_miniclass',
                'prodi_pilihan_2_id', 'berkas_data', 'form_data'
            ];
            foreach ($columns as $column) {
                if (Schema::hasColumn('prospeks', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('transaksis', function (Blueprint $table) {
            if (Schema::hasColumn('transaksis', 'order_id')) {
                $table->dropColumn('order_id');
            }
            if (Schema::hasColumn('transaksis', 'detail_pembayaran')) {
                $table->dropColumn('detail_pembayaran');
            }
        });
    }
};
