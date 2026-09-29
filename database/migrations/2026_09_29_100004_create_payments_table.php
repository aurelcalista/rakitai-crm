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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->string('transaction_id')->unique();
            $table->string('payment_gateway')->default('simulation');
            $table->string('payment_method')->default('qris'); // qris, bank_transfer, e_wallet
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('status')->default('pending'); // pending, paid, failed, expired
            $table->dateTime('paid_at')->nullable();
            $table->dateTime('expired_at')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['transaction_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
