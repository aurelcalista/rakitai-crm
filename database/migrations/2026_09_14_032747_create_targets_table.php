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
        Schema::create('targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_id')->constrained('users')->cascadeOnDelete();
            $table->enum('tipe_periode', ['Harian', 'Mingguan', 'Bulanan']);
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->integer('target_kontak')->default(0);
            $table->integer('target_followup')->default(0);
            $table->integer('target_kunjungan')->default(0);
            $table->enum('status', ['Aktif', 'Nonaktif', 'Selesai'])->default('Aktif');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('targets');
    }
};
