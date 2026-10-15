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
            $table->string('no_registrasi', 100)->nullable()->after('name');
            $table->string('gelombang', 100)->nullable()->after('no_registrasi');

            $table->index('no_registrasi');
            $table->index('gelombang');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prospeks', function (Blueprint $table) {
            $table->dropIndex(['no_registrasi']);
            $table->dropIndex(['gelombang']);
            $table->dropColumn(['no_registrasi', 'gelombang']);
        });
    }
};
