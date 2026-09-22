<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prospeks', function (Blueprint $table) {
            $table->foreignId('academic_year_id')->nullable()->constrained('tahun_akademiks')->nullOnDelete();
        });
        
        Schema::table('kunjungans', function (Blueprint $table) {
            $table->foreignId('academic_year_id')->nullable()->constrained('tahun_akademiks')->nullOnDelete();
        });
        
        Schema::table('targets', function (Blueprint $table) {
            $table->foreignId('academic_year_id')->nullable()->constrained('tahun_akademiks')->nullOnDelete();
        });
        
        Schema::table('transaksis', function (Blueprint $table) {
            $table->foreignId('academic_year_id')->nullable()->constrained('tahun_akademiks')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('prospeks', function (Blueprint $table) {
            $table->dropForeign(['academic_year_id']);
            $table->dropColumn('academic_year_id');
        });
        
        Schema::table('kunjungans', function (Blueprint $table) {
            $table->dropForeign(['academic_year_id']);
            $table->dropColumn('academic_year_id');
        });
        
        Schema::table('targets', function (Blueprint $table) {
            $table->dropForeign(['academic_year_id']);
            $table->dropColumn('academic_year_id');
        });
        
        Schema::table('transaksis', function (Blueprint $table) {
            $table->dropForeign(['academic_year_id']);
            $table->dropColumn('academic_year_id');
        });
    }
};
