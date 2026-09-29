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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('prospek_id')->nullable()->constrained('prospeks')->nullOnDelete();
            $table->string('invoice_number')->unique();
            $table->string('customer_name');
            $table->string('customer_phone')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->date('due_date');
            $table->string('status')->default('pending'); // pending, paid, failed, expired, cancelled
            $table->text('notes')->nullable();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['invoice_number', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
