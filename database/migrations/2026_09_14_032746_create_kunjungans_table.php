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
        Schema::create('kunjungans', function (Blueprint $table) {
            $table->id();
            $table->string('nomor')->unique();
            $table->date('tanggal');
            $table->time('waktu');
            $table->foreignId('sales_id')->constrained('users')->cascadeOnDelete();
            $table->enum('jenis', ['Sekolah', 'Perusahaan']);
            $table->unsignedBigInteger('tujuan_id');
            $table->text('tujuan_kunjungan')->nullable();
            $table->text('hasil')->nullable();
            $table->text('catatan')->nullable();
            $table->enum('status', ['Menunggu', 'Proses', 'Selesai'])->default('Menunggu');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kunjungans');
    }
};
