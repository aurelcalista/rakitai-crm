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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('attendance_location_id')->nullable()->constrained('attendance_locations')->nullOnDelete();
            $table->dateTime('check_in_at');
            $table->decimal('latitude', 10, 6);
            $table->decimal('longitude', 10, 6);
            $table->decimal('distance', 10, 2); // Jarak dalam meter
            $table->string('selfie_path');
            $table->string('status')->default('present'); // present, rejected
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'check_in_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
