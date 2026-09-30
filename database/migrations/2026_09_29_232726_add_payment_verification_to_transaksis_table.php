<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Menambahkan kolom payment & verifikasi ke tabel transaksis.
     * Backward-compatible: semua kolom nullable agar data lama tidak rusak.
     */
    public function up(): void
    {
        Schema::table('transaksis', function (Blueprint $table) {
            // Metode pembayaran yang dipilih Sales
            $table->string('metode_pembayaran')->nullable()->after('notes')
                ->comment('virtual_account|gopay|dana|bank_transfer|null');

            // Status verifikasi CS
            $table->string('payment_status')->default('verified')->after('metode_pembayaran')
                ->comment('verified=lama (backward compat), pending=menunggu CS, rejected=ditolak CS');

            // Audit verifikasi
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
        });

        // Set semua transaksi lama (tanpa metode_pembayaran) ke 'verified' supaya tidak rusak
        // Default sudah 'verified' dari definisi kolom di atas, tidak perlu update manual.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaksis', function (Blueprint $table) {
            $table->dropForeign(['verified_by']);
            $table->dropForeign(['rejected_by']);
            $table->dropColumn([
                'metode_pembayaran',
                'payment_status',
                'verified_by',
                'verified_at',
                'rejected_by',
                'rejected_at',
                'rejection_reason',
            ]);
        });
    }
};
