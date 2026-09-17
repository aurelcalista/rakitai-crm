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
        Schema::create('prospeks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['Sekolah', 'Corporate', 'Individu']);
            $table->string('category')->nullable();
            $table->string('pic')->nullable();
            $table->string('pic_phone')->nullable();
            $table->string('whatsapp')->nullable();
            $table->enum('status', [
                'Cold Lead', 
                'Interested', 
                'Follow Up', 
                'Beli Formulir', 
                'Pembayaran Termin 1', 
                'Closing', 
                'Lost'
            ])->default('Cold Lead');
            $table->integer('stage_number')->default(1);
            $table->string('potential')->nullable();
            $table->string('ai_training')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('wilayah_id')->nullable()->constrained('wilayahs')->nullOnDelete();
            $table->foreignId('sales_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cs_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prospeks');
    }
};
