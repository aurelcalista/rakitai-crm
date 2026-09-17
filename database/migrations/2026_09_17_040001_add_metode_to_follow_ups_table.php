<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds communication method field to follow_ups table.
     */
    public function up(): void
    {
        Schema::table('follow_ups', function (Blueprint $table) {
            $table->enum('metode', [
                'WhatsApp',
                'Telepon',
                'Meeting',
                'Email',
            ])->default('WhatsApp')->after('user_id');

            // Also add hasil (result) field for clarity in follow-up records
            $table->string('hasil')->nullable()->after('catatan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('follow_ups', function (Blueprint $table) {
            $table->dropColumn(['metode', 'hasil']);
        });
    }
};
