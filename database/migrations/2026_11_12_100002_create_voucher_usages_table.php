<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voucher_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_id')->constrained('vouchers')->cascadeOnDelete();
            $table->foreignId('prospek_id')->constrained('prospeks')->cascadeOnDelete();
            $table->foreignId('transaksi_id')->nullable()->constrained('transaksis')->nullOnDelete();
            $table->string('voucher_code', 50);
            $table->decimal('discount_percentage', 5, 2);
            $table->unsignedBigInteger('dpp_before_discount');
            $table->unsignedBigInteger('discount_amount');
            $table->unsignedBigInteger('dpp_after_discount');
            $table->string('status', 30)->default('applied');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            $table->index(['prospek_id', 'voucher_id']);
            $table->index(['prospek_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_usages');
    }
};

