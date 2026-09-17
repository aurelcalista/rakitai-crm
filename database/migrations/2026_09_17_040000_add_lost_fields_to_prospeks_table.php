<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds lost_reason and lost_note to prospeks for tracking Lost pipeline stage.
     */
    public function up(): void
    {
        Schema::table('prospeks', function (Blueprint $table) {
            $table->enum('lost_reason', [
                'Tidak tertarik',
                'Tidak dapat dihubungi',
                'Membatalkan',
                'Memilih kampus lain',
                'Lainnya',
            ])->nullable()->after('notes');

            $table->text('lost_note')->nullable()->after('lost_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prospeks', function (Blueprint $table) {
            $table->dropColumn(['lost_reason', 'lost_note']);
        });
    }
};
