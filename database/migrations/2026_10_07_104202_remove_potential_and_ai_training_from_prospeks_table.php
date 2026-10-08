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
        Schema::table('prospeks', function (Blueprint $table) {
            $table->dropColumn(['potential', 'ai_training']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prospeks', function (Blueprint $table) {
            $table->string('potential', 500)->nullable();
            $table->string('ai_training', 255)->nullable();
        });
    }
};
