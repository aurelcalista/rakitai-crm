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
        Schema::table('transaksis', function (Blueprint $table) {
            if (!Schema::hasColumn('transaksis', 'kategori')) {
                $table->string('kategori', 100)->nullable()->after('jenis')
                    ->comment('DP Daftar|DP Perlengkapan|Lunas|Keluar');
            }
        });

        // Ubah jenis dari enum ke string agar mendukung berbagai jenis transaksi
        // (Pendaftaran, Perlengkapan, Gedung, DP/Konversi, UKT) secara aman di MySQL
        if (config('database.default') === 'mysql') {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE `transaksis` MODIFY `jenis` VARCHAR(255) NOT NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaksis', function (Blueprint $table) {
            if (Schema::hasColumn('transaksis', 'kategori')) {
                $table->dropColumn('kategori');
            }
        });
    }
};
