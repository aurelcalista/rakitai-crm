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
        Schema::table('events', function (Blueprint $table) {
            if (!Schema::hasColumn('events', 'jenis_institusi')) {
                $table->enum('jenis_institusi', ['Sekolah', 'Perusahaan'])->nullable()->after('prodi_id');
            }
            if (!Schema::hasColumn('events', 'nama_institusi')) {
                $table->string('nama_institusi')->nullable()->after('jenis_institusi');
            }
            if (!Schema::hasColumn('events', 'alamat')) {
                $table->text('alamat')->nullable()->after('nama_institusi');
            }
            if (!Schema::hasColumn('events', 'pic_name')) {
                $table->string('pic_name')->nullable()->after('alamat');
            }
            if (!Schema::hasColumn('events', 'pic_whatsapp')) {
                $table->string('pic_whatsapp', 20)->nullable()->after('pic_name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn([
                'jenis_institusi',
                'nama_institusi',
                'alamat',
                'pic_name',
                'pic_whatsapp',
            ]);
        });
    }
};
