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
        Schema::create('target_defisits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spv_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('sales_id')->constrained('users')->cascadeOnDelete();
            $table->date('tanggal');
            $table->integer('defisit_kontak')->default(0);
            $table->integer('defisit_formulir')->default(0);
            $table->integer('defisit_lunas')->default(0);
            $table->boolean('is_locked')->default(true);
            $table->timestamp('locked_at')->nullable();
            $table->string('catatan')->nullable();
            $table->timestamps();

            $table->unique(['spv_id', 'sales_id', 'tanggal'], 'spv_sales_tanggal_unique');
        });

        if (!Schema::hasColumn('users', 'lokasi_penugasan')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('lokasi_penugasan')->nullable()->after('wilayah_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('target_defisits');

        if (Schema::hasColumn('users', 'lokasi_penugasan')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('lokasi_penugasan');
            });
        }
    }
};
