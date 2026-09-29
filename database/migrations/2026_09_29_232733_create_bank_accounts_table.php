<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tabel master rekening bank institusi untuk pembayaran transfer.
     */
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('bank_name');          // Nama bank (Mandiri, BCA, dsb)
            $table->string('account_number');     // Nomor rekening
            $table->string('account_name');       // Nama pemilik rekening
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();    // Catatan opsional untuk admin
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
