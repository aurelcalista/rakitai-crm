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
        Schema::table('prodis', function (Blueprint $table) {
            if (!Schema::hasColumn('prodis', 'biaya_pendaftaran')) {
                $table->unsignedBigInteger('biaya_pendaftaran')->default(0)->after('spp');
            }
            if (!Schema::hasColumn('prodis', 'biaya_perlengkapan')) {
                $table->unsignedBigInteger('biaya_perlengkapan')->default(0)->after('biaya_pendaftaran');
            }
            if (!Schema::hasColumn('prodis', 'biaya_dpp')) {
                $table->unsignedBigInteger('biaya_dpp')->default(0)->after('biaya_perlengkapan');
            }
            if (!Schema::hasColumn('prodis', 'biaya_uang_gedung')) {
                $table->unsignedBigInteger('biaya_uang_gedung')->default(0)->after('biaya_dpp');
            }
            if (!Schema::hasColumn('prodis', 'ukt_options')) {
                $table->json('ukt_options')->nullable()->after('biaya_uang_gedung');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prodis', function (Blueprint $table) {
            $cols = ['biaya_pendaftaran', 'biaya_perlengkapan', 'biaya_dpp', 'biaya_uang_gedung', 'ukt_options'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('prodis', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
