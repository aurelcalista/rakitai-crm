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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->dateTime('tanggal_mulai');
            $table->dateTime('tanggal_selesai');
            $table->string('lokasi');
            $table->text('deskripsi')->nullable();
            $table->enum('status', ['Rencana', 'Sedang Berjalan', 'Selesai', 'Batal'])->default('Rencana');
            $table->foreignId('eo_id')->constrained('users')->cascadeOnDelete();
            $table->string('dokumentasi')->nullable();
            $table->string('absen_peserta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
